<?php
declare(strict_types=1);

namespace App\Controller\Clubpresident;

use App\Auth\CurrentUser;
use App\Controller\Concerns\ManagesCompetitionStaffTrait;
use App\Utility\AdminTranslate;
use App\Utility\CompetitionStaff;
use Cake\Http\Response;

/**
 * Clubpresident: assign check-in / judge for competitions hosted by own club.
 */
class CompetitionStaffController extends AppController
{
    use ManagesCompetitionStaffTrait;

    protected const LAST_VISITED_SESSION_KEY = 'Clubpresident.lastVisited';

    protected const INDEX_STATE_SESSION_KEY = 'Clubpresident.indexState';

    protected int $indexLimit = 50;

    protected int $indexMaxLimit = 500;

    protected function indexStateSessionKey(): string
    {
        return self::INDEX_STATE_SESSION_KEY;
    }

    protected function lastVisitedSessionKey(): string
    {
        return self::LAST_VISITED_SESSION_KEY;
    }

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

        $clubId = $this->presidentClubId();
        $competitionsTable = $this->fetchTable('Competitions');
        AdminTranslate::applyLocale($competitionsTable);

        $paginateOptions = $this->indexPaginateOptionsFor($competitionsTable, [
            'sortableFields' => [
                'name',
                'competition_datetime',
            ],
            'order' => [
                'Competitions.competition_datetime' => 'DESC',
            ],
        ]);

        if ($clubId < 1) {
            $this->set('competitions', $this->emptyPaginated((int)($paginateOptions['limit'] ?? $this->indexLimit)));

            return;
        }

        $query = $competitionsTable->find()
            ->where(['Competitions.club_id' => $clubId]);
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
        $clubId = $this->presidentClubId();
        if ($clubId < 1 || $competitionId === null || $competitionId === '') {
            $this->Flash->error(__('Record not found.'));

            return null;
        }
        try {
            $competition = $this->fetchTable('Competitions')->get($competitionId);
        } catch (\Throwable) {
            $this->Flash->error(__('Record not found.'));

            return null;
        }
        if ((int)$competition->club_id !== $clubId) {
            $this->Flash->error(__('You can only manage staff for competitions hosted by your club.'));

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
        if ($countryId > 0) {
            return $countryId;
        }

        return CurrentUser::countryId($this->getRequest());
    }
}
