<?php
declare(strict_types=1);

namespace App\Controller\President;

use App\Controller\Concerns\ManagesCompetitionStaffTrait;
use App\Utility\AdminTranslate;
use App\Utility\CompetitionStaff;
use Cake\Http\Response;

/**
 * President / VP: assign check-in / judge for competitions in own country.
 */
class CompetitionStaffController extends AppController
{
    use ManagesCompetitionStaffTrait;

    protected int $indexLimit = 50;

    protected int $indexMaxLimit = 500;

    /**
     * @return \Cake\Http\Response|null|void
     */
    public function index()
    {
        $this->set('title', __('Competition staff'));
        $this->set('breadcrumb', __('Competition staff'));

        $redirect = $this->applyIndexListState('CompetitionStaff');
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

        $redirect = $this->resolveIndexPageForLastVisited('CompetitionStaff', $query, $paginateOptions);
        if ($redirect !== null) {
            return $redirect;
        }

        $competitions = $this->paginate($query, $paginateOptions);
        $this->setLastVisitedForIndex('CompetitionStaff');
        $this->set(compact('competitions'));
    }

    public function manage(?string $competitionId = null): ?Response
    {
        $competition = $this->requireCompetitionForStaff($competitionId);
        if ($competition === null) {
            return $this->redirect(['action' => 'index']);
        }
        $this->rememberLastVisited('CompetitionStaff', $competition->id);
        $staffTable = $this->fetchTable('CompetitionStaff');
        $competitionStaff = $staffTable->listForCompetition((string)$competition->id);
        $this->set(compact('competition', 'competitionStaff'));
        $this->set('staffRoles', [
            CompetitionStaff::ROLE_CHECKIN => CompetitionStaff::roleLabel(CompetitionStaff::ROLE_CHECKIN),
            CompetitionStaff::ROLE_JUDGE => CompetitionStaff::roleLabel(CompetitionStaff::ROLE_JUDGE),
        ]);
        $this->set('title', __('Competition staff'));
        $this->set('breadcrumb', __('Competition staff'));

        return null;
    }

    protected function requireCompetitionForStaff(?string $competitionId)
    {
        $countryId = $this->officerCountryId();
        if ($countryId < 1 || $competitionId === null || $competitionId === '') {
            $this->Flash->error(__('Record not found.'));

            return null;
        }
        try {
            $competition = $this->fetchTable('Competitions')->get($competitionId, contain: ['Clubs']);
        } catch (\Throwable) {
            $this->Flash->error(__('Record not found.'));

            return null;
        }
        if ((int)$competition->country_id !== $countryId) {
            $this->Flash->error(__('Record not found.'));

            return null;
        }

        return $competition;
    }

    protected function staffReturnUrl(string $competitionId): array
    {
        return ['action' => 'manage', $competitionId];
    }

    protected function staffFallbackUrl(): array
    {
        return ['action' => 'index'];
    }

    protected function staffSearchCountryId(): int
    {
        $countryId = (int)$this->request->getQuery('country_id');
        $officerCountryId = $this->officerCountryId();
        if ($countryId > 0 && $countryId === $officerCountryId) {
            return $countryId;
        }

        return $officerCountryId;
    }
}
