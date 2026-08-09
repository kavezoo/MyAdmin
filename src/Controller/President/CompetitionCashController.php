<?php
declare(strict_types=1);

namespace App\Controller\President;

use App\Utility\AdminTranslate;
use App\Utility\CompetitionCashDesk;
use Cake\Http\Response;

/**
 * Presidency cash desk overview — same collector till table as check-in (read-only).
 */
class CompetitionCashController extends AppController
{
    protected const SELECTED_COMPETITION_SESSION = 'President.CompetitionCash.competitionId';

    protected int $indexLimit = 50;

    protected int $indexMaxLimit = 500;

    /**
     * Competition picker list.
     *
     * @return \Cake\Http\Response|null|void
     */
    public function index()
    {
        $this->set('title', __('Cash desk'));
        $this->set('breadcrumb', __('Cash desk'));

        $redirect = $this->applyIndexListState('CompetitionCash');
        if ($redirect !== null) {
            return $redirect;
        }

        $countryId = $this->officerCountryId();
        $competitionsTable = $this->fetchTable('Competitions');
        AdminTranslate::applyLocale($competitionsTable);

        $paginateOptions = $this->indexPaginateOptionsFor($competitionsTable, [
            'sortableFields' => [
                'name',
                'competition_datetime',
                'Clubs.name',
            ],
            'order' => [
                'Competitions.competition_datetime' => 'DESC',
            ],
        ], [
            'Clubs' => $competitionsTable->Clubs->getTarget(),
        ]);

        if ($countryId < 1) {
            $this->set('competitions', $this->emptyPaginated((int)($paginateOptions['limit'] ?? $this->indexLimit)));

            return;
        }

        $query = $competitionsTable->find()
            ->contain(['Clubs'])
            ->where(['Competitions.country_id' => $countryId]);
        $query = $this->applyIndexSearch($query, $competitionsTable);

        $redirect = $this->resolveIndexPageForLastVisited('CompetitionCash', $query, $paginateOptions);
        if ($redirect !== null) {
            return $redirect;
        }

        $competitions = $this->paginate($query, $paginateOptions);
        $this->setLastVisitedForIndex('CompetitionCash');
        $this->set(compact('competitions'));
    }

    /**
     * Cash desk for one competition (presidency read-only).
     */
    public function view(?string $competitionId = null): ?Response
    {
        $this->set('title', __('Cash desk'));
        $this->set('breadcrumb', __('Cash desk'));

        $countryId = $this->officerCountryId();
        $competitionsTable = $this->fetchTable('Competitions');
        AdminTranslate::applyLocale($competitionsTable);

        $competitionOptions = [];
        if ($countryId > 0) {
            foreach ($competitionsTable->find()
                ->where(['Competitions.country_id' => $countryId])
                ->orderBy(['Competitions.competition_datetime' => 'DESC'])
                ->all() as $row
            ) {
                $competitionOptions[(string)$row->id] = CompetitionCashDesk::competitionOptionLabel($row);
            }
        }

        $session = $this->request->getSession();
        $fromQuery = (string)$this->request->getQuery('competition_id');
        if ($competitionId === null || $competitionId === '') {
            $competitionId = $fromQuery !== '' ? $fromQuery : (string)$session->read(self::SELECTED_COMPETITION_SESSION);
        }
        if ($competitionId === '' || !isset($competitionOptions[$competitionId])) {
            $competitionId = $competitionOptions !== []
                ? (string)array_key_first($competitionOptions)
                : '';
        }

        $competition = null;
        $paidCashGroups = [];
        $paidCashTotal = 0.0;

        if ($competitionId !== '' && isset($competitionOptions[$competitionId])) {
            try {
                $competition = $competitionsTable->get($competitionId, contain: ['Clubs']);
                if ((int)$competition->country_id !== $countryId) {
                    $this->Flash->error(__('Record not found.'));

                    return $this->redirect(['action' => 'index']);
                }
                $session->write(self::SELECTED_COMPETITION_SESSION, $competitionId);
                $this->rememberLastVisited('CompetitionCash', $competition->id);
                $desk = CompetitionCashDesk::paidGroupsForCompetition($competition);
                $paidCashGroups = $desk['groups'];
                $paidCashTotal = $desk['total'];
            } catch (\Throwable) {
                $this->Flash->error(__('Record not found.'));

                return $this->redirect(['action' => 'index']);
            }
        }

        $this->set(compact(
            'competition',
            'competitionOptions',
            'competitionId',
            'paidCashGroups',
            'paidCashTotal',
        ));

        return null;
    }
}
