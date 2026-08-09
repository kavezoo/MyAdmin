<?php
declare(strict_types=1);

namespace App\Controller\Checkin;

use App\Utility\AdminTranslate;
use App\Utility\CompetitionApplication;
use App\Utility\CompetitionFees;
use App\Utility\CompetitionStaff;
use App\Utility\LocaleDateParser;
use ArrayIterator;
use Cake\Datasource\Paging\PaginatedResultSet;
use Cake\Http\Response;
use Cake\I18n\DateTime;

/**
 * On-site applicants: pipes to hand out + mark entry fee paid.
 * Home screen of the Check-in prefix (list opens immediately).
 */
class ApplicantsController extends AppController
{
    protected int $indexLimit = 50;

    public function index(?string $competitionId = null): ?Response
    {
        $this->set('title', __('Check-in applicants'));
        $this->set('breadcrumb', __('Check-in'));

        $ids = $this->assignedCompetitionIds();
        $competitionsTable = $this->fetchTable('Competitions');
        AdminTranslate::applyLocale($competitionsTable);
        $appsTable = $this->fetchTable('CompetitionsUsers');

        $competitionOptions = [];
        $competition = null;
        $applicants = $this->emptyPaginated($this->indexLimit);
        $unapprovedOnly = $this->request->getQuery('unapproved_only') === '1';
        $unpaidOnly = $this->request->getQuery('unpaid_only') === '1';
        $allFeesPaid = false;

        if ($ids !== []) {
            foreach ($competitionsTable->find()
                ->where(['Competitions.id IN' => $ids])
                ->orderBy(['Competitions.competition_datetime' => 'ASC'])
                ->all() as $row
            ) {
                $when = $row->competition_datetime
                    ? LocaleDateParser::format($row->competition_datetime, 'datetime_short')
                    : '';
                $label = (string)$row->name;
                if ($when !== '') {
                    $label .= ' — ' . $when;
                }
                $competitionOptions[(string)$row->id] = $label;
            }

            $competitionId = $competitionId !== null && $competitionId !== ''
                ? $competitionId
                : (string)$this->request->getQuery('competition_id');
            if ($competitionId === '' || !isset($competitionOptions[$competitionId])) {
                $competitionId = (string)array_key_first($competitionOptions);
            }

            if (
                $competitionId !== ''
                && CompetitionStaff::canOperateOnCompetition(
                    $competitionId,
                    CompetitionStaff::ROLE_CHECKIN,
                    null,
                    $this->getRequest()
                )
            ) {
                $competition = $competitionsTable->get($competitionId, contain: ['Clubs']);

                $activeBase = [
                    'CompetitionsUsers.competition_id' => $competition->id,
                    'CompetitionsUsers.status IN' => CompetitionApplication::activeStatuses(),
                ];
                $hasActiveApplicants = $appsTable->exists($activeBase);
                $allFeesPaid = $hasActiveApplicants && !$appsTable->exists($activeBase + [
                    'CompetitionsUsers.fee_paid_at IS' => null,
                ]);

                $query = $appsTable->find()
                    ->contain(['Users', 'FeeCollectors'])
                    ->where($activeBase)
                    ->orderBy([
                        'Users.last_name' => 'ASC',
                        'Users.first_name' => 'ASC',
                    ]);

                if ($unapprovedOnly) {
                    $query->where(['CompetitionsUsers.status' => CompetitionApplication::STATUS_PENDING]);
                }
                if ($unpaidOnly) {
                    $query->where(['CompetitionsUsers.fee_paid_at IS' => null]);
                }

                $applicants = $this->paginate($query, [
                    'limit' => $this->indexLimit,
                    'maxLimit' => 200,
                    'order' => [
                        'Users.last_name' => 'ASC',
                        'Users.first_name' => 'ASC',
                    ],
                ]);

                // Persist fee snapshot for unpaid rows on this page (entry + pipes + total).
                foreach ($applicants as $app) {
                    if ($app->user === null) {
                        continue;
                    }
                    CompetitionFees::syncDueAmounts($appsTable, $competition, $app, $app->user);
                }
            }
        } else {
            $competitionId = '';
        }

        $this->set(compact(
            'competition',
            'applicants',
            'competitionOptions',
            'competitionId',
            'unapprovedOnly',
            'unpaidOnly',
            'allFeesPaid',
        ));

        return null;
    }

    public function markPaid(?string $id = null): ?Response
    {
        $this->request->allowMethod(['post']);
        $apps = $this->fetchTable('CompetitionsUsers');
        try {
            $application = $apps->get($id, contain: ['Users', 'Competitions']);
        } catch (\Throwable) {
            $this->Flash->error(__('Record not found.'));

            return $this->redirect(['action' => 'index']);
        }

        $competitionId = (string)$application->competition_id;
        $assignedAnyDay = CompetitionStaff::userAssignedToCompetition(
            $competitionId,
            CompetitionStaff::ROLE_CHECKIN,
            null,
            $this->getRequest(),
            requireStaffDay: false
        );
        if (!$assignedAnyDay) {
            $this->Flash->error(__('You are not allowed to access this competition.'));

            return $this->redirect(['action' => 'index']);
        }
        if (!CompetitionStaff::canOperateOnCompetition(
            $competitionId,
            CompetitionStaff::ROLE_CHECKIN,
            null,
            $this->getRequest()
        )) {
            $this->Flash->error(__(
                'Check-in payments can only be recorded on the competition day.'
            ));

            return $this->redirect(['action' => 'index']);
        }

        if (!CompetitionApplication::hasApplication($application)) {
            $this->Flash->error(__('This application is not active.'));

            return $this->redirect(['action' => 'index', $competitionId]);
        }

        $competition = $application->competition;
        $user = $application->user;
        if ($competition !== null && $user !== null) {
            CompetitionFees::applyDueToEntity($application, $competition, $user, force: true);
        }
        $collectorId = trim((string)(\App\Auth\CurrentUser::id($this->getRequest()) ?? ''));
        $application->set('fee_paid_at', DateTime::now());
        $application->set('fee_paid_by', $collectorId !== '' ? $collectorId : null);

        $fields = array_merge(CompetitionFees::dueAmountFields(), ['fee_paid_at', 'fee_paid_by']);
        if ($apps->save($application, [
            'fields' => $fields,
            'accessibleFields' => array_fill_keys($fields, true),
        ])) {
            $total = CompetitionFees::format((float)$application->fee_total, $competition ?? 'HUF');
            $this->Flash->success(__('Marked as paid at {0}. Total: {1}.', LocaleDateParser::format(
                $application->fee_paid_at,
                'datetime_short'
            ), $total));
        } else {
            $this->Flash->error(__('The record could not be saved. Please try again.'));
        }

        return $this->redirect($this->checkinIndexUrl($competitionId));
    }

    /**
     * Check-in list URL with optional filter query (unapproved / unpaid).
     *
     * @return array<string, mixed>
     */
    protected function checkinIndexUrl(string $competitionId): array
    {
        $url = ['action' => 'index', $competitionId];
        $query = [];
        if ($this->request->getQuery('unapproved_only') === '1') {
            $query['unapproved_only'] = '1';
        }
        if ($this->request->getQuery('unpaid_only') === '1') {
            $query['unpaid_only'] = '1';
        }
        if ($query !== []) {
            $url['?'] = $query;
        }

        return $url;
    }

    protected function emptyPaginated(int $limit = 50): PaginatedResultSet
    {
        return new PaginatedResultSet(new ArrayIterator([]), [
            'count' => 0,
            'totalCount' => 0,
            'perPage' => $limit,
            'currentPage' => 1,
            'pageCount' => 1,
            'start' => 0,
            'end' => 0,
            'hasPrevPage' => false,
            'hasNextPage' => false,
            'requestedPage' => 1,
        ]);
    }
}
