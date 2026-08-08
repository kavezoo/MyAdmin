<?php
declare(strict_types=1);

namespace App\Controller\Admin;

use App\Model\Entity\Competition;
use App\Model\Table\CompetitionsTable;
use App\Utility\AdminCountry;
use App\Utility\CompetitionApplication;
use App\Utility\LocaleDateParser;
use App\Utility\LocaleNumberParser;
use Cake\Http\Response;

/**
 * Global Admin CRUD for competitions (optional country filter).
 *
 * @property \App\Model\Table\CompetitionsTable $Competitions
 */
class CompetitionsController extends AppController
{
    protected int $indexLimit = 50;

    protected int $indexMaxLimit = 500;

    protected CompetitionsTable $Competitions;

    /**
     * @var list<string>
     */
    protected const FORM_FIELDS = [
        'country_id',
        'club_id',
        'national_competition',
        'name',
        'title',
        'subtitle',
        'subtitle2',
        'first_date_of_application',
        'application_deadline',
        'competition_datetime',
        'start_datetime',
        'end_datetime',
        'description',
        'minimum_team_size',
        'racing_pipe_1_title',
        'racing_pipe_2_title',
        'racing_pipe_3_title',
        'visible',
        'pos',
    ];

    public function initialize(): void
    {
        parent::initialize();
        $this->Competitions = $this->fetchTable('Competitions');
    }

    /**
     * @return \Cake\Http\Response|null|void
     */
    public function index()
    {
        $this->set('title', __('Competitions'));
        $this->viewBuilder()->setVar('breadcrumb', __('Competitions'));

        $scoped = $this->beginAdminCountryScopedIndex($this->Competitions);
        if ($scoped instanceof Response) {
            return $scoped;
        }
        $filterCountryId = $scoped['countryId'];

        $redirect = $this->applyIndexListState('Competitions');
        if ($redirect !== null) {
            return $redirect;
        }

        $paginateOptions = $this->indexPaginateOptionsFor($this->Competitions, [
            'sortableFields' => [
                'id',
                'name',
                'title',
                'first_date_of_application',
                'application_deadline',
                'competition_datetime',
                'end_datetime',
                'minimum_team_size',
                'user_count',
                'national_competition',
                'visible',
                'pos',
                'created',
                'modified',
                'Countries.name',
                'Clubs.name',
            ],
            'order' => [
                'Competitions.first_date_of_application' => 'DESC',
                'Competitions.name' => 'ASC',
            ],
        ], [
            'Countries' => $this->Competitions->Countries->getTarget(),
            'Clubs' => $this->Competitions->Clubs->getTarget(),
        ]);

        $query = $this->applyAdminCountryWhere(
            $this->Competitions->find()->contain(['Countries', 'Clubs']),
            $this->Competitions,
            $filterCountryId
        );
        $query = $this->applyIndexSearch($query, $this->Competitions);

        $redirect = $this->resolveIndexPageForLastVisited('Competitions', $query, $paginateOptions);
        if ($redirect !== null) {
            return $redirect;
        }

        $competitions = $this->paginate($query, $paginateOptions);
        $this->setLastVisitedForIndex('Competitions');
        $this->set(compact('competitions'));
    }

    /**
     * @return \Cake\Http\Response|null|void
     */
    public function add()
    {
        $scoped = \App\Utility\AdminCountryScope::scopeForTable($this->request, $this->Competitions);
        $filterCountryId = $scoped['countryId'];
        $competition = $this->newEntityWithSchemaDefaults($this->Competitions);
        if ($filterCountryId > 0) {
            $competition->country_id = $filterCountryId;
        }
        $competition->national_competition = false;
        $competition->visible = true;
        $competition->minimum_team_size = 3;
        $identity = $this->getRequest()->getAttribute('identity');
        if ($identity !== null && method_exists($identity, 'getIdentifier')) {
            $competition->user_id = (string)$identity->getIdentifier();
        }

        if ($this->request->is('post')) {
            if ($this->saveCompetition($competition)) {
                $this->rememberLastVisited('Competitions', $competition->id);
                $this->Flash->success(__('The competition has been saved.'));

                return $this->redirectToIndexList('Competitions');
            }
            $this->flashEntityErrors($competition);
        }

        $this->setFormOptions($competition);
        $this->set(compact('competition', 'filterCountryId'));
        $this->set('title', __('New competition'));
        $this->viewBuilder()->setVar('breadcrumb', __('Competitions'));
        $this->render('form');
    }

    /**
     * @param string|null $id
     * @return \Cake\Http\Response|null|void
     */
    public function edit(?string $id = null)
    {
        $competition = $this->getCompetition($id);
        $denied = $this->denyIfOutsideAdminCountryScope($competition);
        if ($denied !== null) {
            return $denied;
        }
        $this->rememberLastVisited('Competitions', $competition->id);

        if ($this->request->is(['patch', 'post', 'put'])) {
            if ($this->saveCompetition($competition)) {
                $this->rememberLastVisited('Competitions', $competition->id);
                $this->Flash->success(__('The competition has been saved.'));

                return $this->redirectToIndexList('Competitions');
            }
            $this->flashEntityErrors($competition);
        }

        $this->setFormOptions($competition);
        $this->set(compact('competition'));
        $this->setCanDeleteFlag($this->Competitions, $competition);
        $this->set('title', __('Edit competition'));
        $this->viewBuilder()->setVar('breadcrumb', __('Competitions'));
        $this->render('form');
    }

    /**
     * @param string|null $id
     * @return \Cake\Http\Response|null|void
     */
    public function view(?string $id = null)
    {
        $competition = $this->getCompetition($id, ['Countries', 'Clubs']);
        $denied = $this->denyIfOutsideAdminCountryScope($competition);
        if ($denied !== null) {
            return $denied;
        }
        $this->rememberLastVisited('Competitions', $competition->id);

        $minimum = (int)($competition->minimum_team_size ?? 0);
        $competitionsClubs = $this->fetchTable('CompetitionsClubs');
        $allTeams = $competitionsClubs->find()
            ->contain(['Subclubs', 'Clubs'])
            ->where(['CompetitionsClubs.competition_id' => $competition->id])
            ->orderBy(['Clubs.name' => 'ASC', 'Subclubs.name' => 'ASC'])
            ->all();
        $qualifyingTeams = $competitionsClubs->filterMeetingMinimum($allTeams, $minimum);
        $qualifiedTeamIds = array_map(
            static fn ($team): int => (int)$team->id,
            $qualifyingTeams
        );

        $qualifyingApplicants = [];
        if ($qualifiedTeamIds !== []) {
            $qualifyingApplicants = $this->fetchTable('CompetitionsUsers')->find()
                ->contain([
                    'Users',
                    'CompetitionsClubs' => ['Subclubs', 'Clubs'],
                ])
                ->where([
                    'CompetitionsUsers.competition_id' => $competition->id,
                    'CompetitionsUsers.competition_club_id IN' => $qualifiedTeamIds,
                    'CompetitionsUsers.status' => CompetitionApplication::STATUS_ASSIGNED,
                ])
                ->orderBy([
                    'Users.last_name' => 'ASC',
                    'Users.first_name' => 'ASC',
                    'Users.email' => 'ASC',
                ])
                ->all()
                ->toList();
        }

        $this->set(compact(
            'competition',
            'minimum',
            'qualifyingTeams',
            'qualifyingApplicants'
        ));
        $this->set('countryLabel', AdminCountry::label((int)$competition->country_id));
        $this->setCanDeleteFlag($this->Competitions, $competition);
        $this->set('title', __('Competition details'));
        $this->viewBuilder()->setVar('breadcrumb', __('Competitions'));
    }

    /**
     * @param string|null $id
     */
    public function delete(?string $id = null): ?Response
    {
        $this->request->allowMethod(['post', 'delete']);
        $competition = $this->getCompetition($id);
        $denied = $this->denyIfOutsideAdminCountryScope($competition);
        if ($denied !== null) {
            return $denied;
        }

        return $this->deleteEntityOrFail($this->Competitions, $competition);
    }

    /**
     * @param string|null $id
     */
    public function recordGet(?string $id = null): Response
    {
        $this->request->allowMethod(['get']);

        try {
            $competition = $this->getCompetition($id, ['Countries', 'Clubs']);
        } catch (\Throwable) {
            return $this->response
                ->withStatus(404)
                ->withType('application/json')
                ->withStringBody((string)json_encode([
                    'success' => false,
                    'message' => __('Record not found.'),
                ], JSON_UNESCAPED_UNICODE));
        }

        if (!\App\Utility\AdminCountryScope::entityAllowed($competition, $this->request)) {
            return $this->response
                ->withStatus(403)
                ->withType('application/json')
                ->withStringBody((string)json_encode([
                    'success' => false,
                    'message' => __('You are not allowed to access records from another country.'),
                ], JSON_UNESCAPED_UNICODE));
        }

        $this->rememberLastVisited('Competitions', $competition->id);

        return $this->response
            ->withType('application/json')
            ->withStringBody((string)json_encode([
                'success' => true,
                'record' => [
                    'id' => $competition->id,
                    'name' => $competition->name,
                    'title' => $competition->title,
                    'country' => AdminCountry::label((int)$competition->country_id),
                    'club' => $competition->club->name ?? '',
                    'national_competition' => (bool)$competition->national_competition,
                    'first_date_of_application' => $competition->first_date_of_application
                        ? LocaleDateParser::format($competition->first_date_of_application, 'date')
                        : '',
                    'application_deadline' => $competition->application_deadline
                        ? LocaleDateParser::format($competition->application_deadline, 'date')
                        : '',
                    'competition_datetime' => $competition->competition_datetime
                        ? LocaleDateParser::format($competition->competition_datetime, 'datetime_short')
                        : '',
                    'end_datetime' => $competition->end_datetime
                        ? LocaleDateParser::format($competition->end_datetime, 'datetime_short')
                        : '',
                    'minimum_team_size' => LocaleNumberParser::format($competition->minimum_team_size, decimals: 0),
                    'user_count' => LocaleNumberParser::format($competition->user_count, decimals: 0),
                    'visible' => (bool)$competition->visible,
                    'pos' => LocaleNumberParser::format($competition->pos, decimals: 0),
                    'created' => $competition->created
                        ? LocaleDateParser::format($competition->created, 'datetime_short')
                        : '',
                    'modified' => $competition->modified
                        ? LocaleDateParser::format($competition->modified, 'datetime_short')
                        : '',
                    'can_delete' => $this->Competitions->canDelete($competition),
                ],
            ], JSON_UNESCAPED_UNICODE));
    }

    protected function saveCompetition(Competition $competition): bool
    {
        $data = $this->request->getData();
        if (!is_array($data)) {
            $data = [];
        }

        foreach (['start_datetime', 'end_datetime', 'subtitle', 'subtitle2', 'description'] as $optional) {
            if (!array_key_exists($optional, $data)) {
                continue;
            }
            if (is_string($data[$optional]) && trim($data[$optional]) === '') {
                $data[$optional] = $optional === 'description' || str_starts_with($optional, 'subtitle')
                    ? ''
                    : null;
            }
        }
        foreach (['racing_pipe_1_title', 'racing_pipe_2_title', 'racing_pipe_3_title'] as $pipeTitle) {
            if (array_key_exists($pipeTitle, $data) && is_string($data[$pipeTitle])) {
                $data[$pipeTitle] = trim($data[$pipeTitle]);
            }
        }

        $data = $this->constrainAdminCountryData($data);

        $countryId = (int)($data['country_id'] ?? $competition->country_id ?? 0);
        if ($countryId < 1 || !AdminCountry::isValidCountryId($countryId)) {
            $competition->setError('country_id', __('Select a valid country.'));

            return false;
        }

        $identity = $this->getRequest()->getAttribute('identity');
        $userId = (string)$competition->user_id;
        if ($userId === '' && $identity !== null && method_exists($identity, 'getIdentifier')) {
            $userId = (string)$identity->getIdentifier();
        }
        $data['country_id'] = $countryId;
        $data['user_id'] = $userId;
        $data['national_competition'] = !empty($data['national_competition']);
        $data['visible'] = !empty($data['visible']);

        $previousClubId = $competition->isNew() ? 0 : (int)$competition->get('club_id');

        $competition = $this->Competitions->patchEntity($competition, $data, [
            'fields' => array_merge(self::FORM_FIELDS, ['user_id']),
        ]);

        $clubId = (int)$competition->club_id;
        /** @var \App\Model\Table\ClubsTable $clubs */
        $clubs = $this->fetchTable('Clubs');
        if (
            $clubId < 1
            || !$clubs->isAllowedCompetitionOrganizer(
                $clubId,
                $countryId,
                allowExistingClubId: $previousClubId
            )
        ) {
            $competition->setError(
                'club_id',
                __('Only clubs with national membership fee paid for this year can organize a competition.')
            );

            return false;
        }

        if ($competition->getErrors()) {
            return false;
        }

        if (!$this->Competitions->save($competition)) {
            if ($competition->getErrors() === []) {
                $competition->setError('_save', __('The database could not save this record. Please try again.'));
            }

            return false;
        }

        return true;
    }

    /**
     * @param list<string> $contain
     */
    protected function getCompetition(?string $id, array $contain = []): Competition
    {
        /** @var \App\Model\Entity\Competition $competition */
        $competition = $this->Competitions->get($id, contain: $contain);

        return $competition;
    }

    protected function setFormOptions(Competition $competition): void
    {
        $countryId = (int)($competition->country_id ?? 0);
        $includeClubId = $competition->isNew() ? 0 : (int)$competition->get('club_id');
        /** @var \App\Model\Table\ClubsTable $clubs */
        $clubs = $this->fetchTable('Clubs');
        $clubOptions = $countryId > 0
            ? $clubs->optionsForCompetitionOrganizer($countryId, $includeClubId)
            : [];
        $canChange = \App\Utility\AdminCountryScope::canChangeCountry($this->request);
        $this->set('countryOptions', $canChange
            ? AdminCountry::options()
            : ($countryId > 0 ? [$countryId => AdminCountry::label($countryId)] : []));
        $this->set('canChangeCountry', $canChange);
        $this->set(compact('clubOptions'));
    }

    protected function validCountryFilter(): int
    {
        $raw = $this->request->getQuery('country_id');
        $id = is_array($raw) ? (int)end($raw) : (int)$raw;

        return $id > 0 && AdminCountry::isValidCountryId($id) ? $id : 0;
    }
}
