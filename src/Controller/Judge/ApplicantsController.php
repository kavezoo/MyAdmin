<?php
declare(strict_types=1);

namespace App\Controller\Judge;

use App\Utility\AdminTranslate;
use App\Utility\CompetitionApplication;
use App\Utility\CompetitionResults;
use App\Utility\CompetitionResultTime;
use App\Utility\CompetitionStaff;
use App\Utility\UuidObfuscator;
use ArrayIterator;
use Cake\Datasource\Paging\PaginatedResultSet;
use Cake\Http\Response;

/**
 * Table judge: list applicants and record result times.
 */
class ApplicantsController extends AppController
{
    protected int $indexLimit = 50;

    public function index(?string $competitionId = null): ?Response
    {
        $this->set('title', __('Judge applicants'));
        $this->set('breadcrumb', __('Judge'));

        $ids = $this->assignedCompetitionIds();
        $competitionsTable = $this->fetchTable('Competitions');
        AdminTranslate::applyLocale($competitionsTable);

        $competitionOptions = [];
        $competition = null;
        $applicants = $this->emptyPaginated($this->indexLimit);
        $competitionToken = '';

        if ($ids !== []) {
            foreach ($competitionsTable->find()
                ->where(['Competitions.id IN' => $ids])
                ->orderBy(['Competitions.competition_datetime' => 'ASC'])
                ->all() as $row
            ) {
                $competitionOptions[(string)$row->id] = (string)$row->name;
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
                    CompetitionStaff::ROLE_JUDGE,
                    null,
                    $this->getRequest()
                )
            ) {
                $competition = $competitionsTable->get($competitionId, contain: ['Clubs']);
                $query = $this->fetchTable('CompetitionsUsers')->find()
                    ->contain(['Users'])
                    ->where([
                        'CompetitionsUsers.competition_id' => $competition->id,
                        'CompetitionsUsers.status IN' => CompetitionApplication::activeStatuses(),
                    ])
                    ->orderBy([
                        'Users.last_name' => 'ASC',
                        'Users.first_name' => 'ASC',
                    ]);

                $applicants = $this->paginate($query, [
                    'limit' => $this->indexLimit,
                    'maxLimit' => 200,
                    'order' => [
                        'Users.last_name' => 'ASC',
                        'Users.first_name' => 'ASC',
                    ],
                ]);
                $competitionToken = UuidObfuscator::encode((string)$competition->id);
            }
        } else {
            $competitionId = '';
        }

        $this->set(compact('competition', 'applicants', 'competitionOptions', 'competitionId', 'competitionToken'));

        return null;
    }

    public function saveResult(?string $id = null): ?Response
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
        if (!CompetitionStaff::canOperateOnCompetition(
            $competitionId,
            CompetitionStaff::ROLE_JUDGE,
            null,
            $this->getRequest()
        )) {
            $this->Flash->error(__('You are not allowed to access this competition.'));

            return $this->redirect(['action' => 'index']);
        }

        if (!CompetitionApplication::hasApplication($application)) {
            $this->Flash->error(__('This application is not active.'));

            return $this->redirect(['action' => 'index', $competitionId]);
        }

        $seconds = CompetitionResultTime::parseFromRequest($this->request->getData());
        if ($seconds === null) {
            $this->Flash->error(__('Please enter a valid result time (e.g. 12:34.567 or seconds).'));

            return $this->redirect(['action' => 'index', $competitionId]);
        }

        $result = CompetitionResults::saveTimeForApplicant(
            $competitionId,
            (string)$application->user_id,
            $seconds
        );
        if ($result['ok']) {
            if (!empty($result['competition_ended'])) {
                $this->Flash->success(__(
                    'Result time saved: {0}. All assigned competitors have times — competition ended.',
                    CompetitionResultTime::format($seconds)
                ));
            } else {
                $this->Flash->success(__('Result time saved: {0}', CompetitionResultTime::format($seconds)));
            }
        } else {
            $this->Flash->error(__('The record could not be saved. Please try again.'));
        }

        return $this->redirect(['action' => 'index', $competitionId]);
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
