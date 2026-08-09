<?php
declare(strict_types=1);

namespace App\Controller\President;

use App\Controller\Concerns\StoresCompetitionPipeImagesTrait;
use App\Model\Table\CompetitionsTable;
use App\Utility\CompetitionApplication;
use App\Utility\LocaleDateParser;
use App\Utility\LocaleNumberParser;
use App\Utility\MembershipFee;
use Cake\Http\Response;
use Cake\I18n\DateTime;

/**
 * Country competitions CRUD (president / vice president).
 *
 * @property \App\Model\Table\CompetitionsTable $Competitions
 */
class CompetitionsController extends AppController
{
    use StoresCompetitionPipeImagesTrait;

    private const SHOW_ALL_SESSION_KEY = 'President.Competitions.show_all';

    protected int $indexLimit = 50;

    protected int $indexMaxLimit = 500;

    protected CompetitionsTable $Competitions;

    /**
     * @var list<string>
     */
    protected const FORM_FIELDS = [
        'club_id',
        'city_id',
        'venue_name',
        'venue_address',
        'google_maps_url',
        'competition_text_template_id',
        'national_competition',
        'name',
        'title',
        'subtitle',
        'subtitle2',
        'first_date_of_application',
        'application_deadline',
        'competition_datetime',
        'description',
        'minimum_team_size',
        'racing_pipe_1_title',
        'racing_pipe_2_title',
        'racing_pipe_3_title',
        'pipe_type',
        'pipe_parameters',
        'tobacco_type',
        'tobacco_weight',
        'currency',
        'entry_fee_member',
        'entry_fee_non_member',
        'lunch_description',
        'lunch_price',
        'racing_pipe_1_price_member',
        'racing_pipe_1_price_non_member',
        'racing_pipe_2_price_member',
        'racing_pipe_2_price_non_member',
        'racing_pipe_3_price_member',
        'racing_pipe_3_price_non_member',
        'racing_pipe_1_image',
        'racing_pipe_2_image',
        'racing_pipe_3_image',
        'visible',
        'pos',
    ];

    public function initialize(): void
    {
        parent::initialize();
        $this->Competitions = $this->fetchTable('Competitions');
    }
    protected function setAccessFlags(): void
    {
        $this->set('canAdd', true);
        $this->set('canEdit', true);
        $this->set('canDelete', true);
    }

    /**
     * @return \Cake\Http\Response|null|void
     */
    public function index()
    {
        $this->set('title', __('Competitions'));
        $this->viewBuilder()->setVar('breadcrumb', __('Competitions'));
        $this->setAccessFlags();

        $countryId = $this->officerCountryId();
        $competitionYear = MembershipFee::currentYear();
        // Read `show_all` before list-state rewrite (that query key is not persisted in index URL).
        $showAllCompetitions = $this->resolveShowAllCompetitions();

        if ($countryId < 1) {
            $this->Flash->warning(__('Your account is not assigned to a country yet. Contact an administrator.'));
            $this->set('competitions', $this->emptyPaginated($this->indexLimit));
            $this->set(compact('countryId', 'competitionYear', 'showAllCompetitions'));

            return;
        }

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
                'Clubs.name',
            ],
            'order' => [
                'Competitions.first_date_of_application' => 'DESC',
                'Competitions.name' => 'ASC',
            ],
        ], [
            'Clubs' => $this->Competitions->Clubs->getTarget(),
        ]);

        $where = ['Competitions.country_id' => $countryId];
        if (!$showAllCompetitions) {
            $where['Competitions.competition_datetime >='] = sprintf('%d-01-01 00:00:00', $competitionYear);
            $where['Competitions.competition_datetime <'] = sprintf('%d-01-01 00:00:00', $competitionYear + 1);
        }

        $query = $this->Competitions->find()
            ->contain([
                'Clubs',
                'Users',
                'Modifiers',
                'CompetitionsClubs' => function ($q) {
                    return $q
                        ->contain(['Subclubs', 'Clubs'])
                        ->orderBy([
                            'Clubs.name' => 'ASC',
                            'Subclubs.name' => 'ASC',
                        ]);
                },
            ])
            ->where($where);
        $query = $this->applyIndexSearch($query, $this->Competitions);

        $redirect = $this->resolveIndexPageForLastVisited('Competitions', $query, $paginateOptions);
        if ($redirect !== null) {
            return $redirect;
        }

        $competitions = $this->paginate($query, $paginateOptions);
        $this->setLastVisitedForIndex('Competitions');
        $this->set(compact('competitions', 'countryId', 'competitionYear', 'showAllCompetitions'));
    }

    /**
     * Index filter: show every year (default off = current calendar year by competition_datetime).
     */
    protected function resolveShowAllCompetitions(): bool
    {
        $session = $this->request->getSession();
        $query = $this->request->getQueryParams();

        if (array_key_exists('show_all', $query)) {
            $raw = $query['show_all'];
            if (is_array($raw)) {
                $raw = end($raw);
            }
            $showAll = in_array((string)$raw, ['1', 'true', 'on'], true);
            $session->write(self::SHOW_ALL_SESSION_KEY, $showAll);

            return $showAll;
        }

        $saved = $session->read(self::SHOW_ALL_SESSION_KEY);
        if ($saved === null) {
            return false;
        }

        return (bool)$saved;
    }

    /**
     * @return \Cake\Http\Response|null|void
     */
    public function add()
    {
        $countryId = $this->officerCountryId();
        $competition = $this->newEntityWithSchemaDefaults($this->Competitions);
        $competition->country_id = $countryId;
        $competition->national_competition = false;
        $competition->visible = true;
        $competition->minimum_team_size = 3;
        $competition->competition_datetime = DateTime::now()->setTime(14, 0, 0);
        $identity = $this->getRequest()->getAttribute('identity');
        if ($identity !== null && method_exists($identity, 'getIdentifier')) {
            $competition->user_id = (string)$identity->getIdentifier();
        }

        if ($this->request->is('post')) {
            if ($this->saveCompetition($competition, $countryId)) {
                $this->rememberLastVisited('Competitions', $competition->id);
                $this->Flash->success(__('The competition has been saved.'));

                return $this->redirectToIndexList('Competitions');
            }
            $this->flashEntityErrors($competition->getErrors());
        }

        $this->setFormLanguageTabs($countryId > 0 ? $countryId : null);
        $this->setFormOptions($competition, $countryId);
        $this->set(compact('competition'));
        $this->setAccessFlags();
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
        $countryId = $this->officerCountryId();
        $competition = $this->getScopedCompetition($id, $countryId, withTranslations: true);
        $this->rememberLastVisited('Competitions', $competition->id);
        $contentLocked = CompetitionApplication::isContentLocked($competition->application_deadline);

        if ($this->request->is(['patch', 'post', 'put'])) {
            if ($contentLocked) {
                $this->Flash->error(__(
                    'The application deadline has passed. Competition details can no longer be edited.'
                ));
            } elseif ($this->saveCompetition($competition, $countryId)) {
                $this->rememberLastVisited('Competitions', $competition->id);
                $this->Flash->success(__('The competition has been saved.'));

                return $this->redirectToIndexList('Competitions');
            } else {
                $this->flashEntityErrors($competition->getErrors());
            }
        }

        $this->setFormLanguageTabs($countryId > 0 ? $countryId : null);
        $this->setFormOptions($competition, $countryId);
        $this->set(compact('competition', 'contentLocked'));
        $this->setAccessFlags();
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
        $countryId = $this->officerCountryId();
        $competition = $this->getScopedCompetition($id, $countryId, ['Clubs', 'Cities', 'Countries']);
        $this->rememberLastVisited('Competitions', $competition->id);
        $this->getRequest()->getSession()->write(
            'President.CompetitionCash.competitionId',
            (string)$competition->id
        );
        $this->set(
            'competitionStaffGroups',
            \App\Utility\CompetitionStaff::groupedDisplayPeople((string)$competition->id)
        );

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
        $this->setAccessFlags();
        $this->set('title', __('Competition details'));
        $this->viewBuilder()->setVar('breadcrumb', __('Competitions'));
    }

    /**
     * @param string|null $id
     * @return \Cake\Http\Response|null
     */
    public function delete(?string $id = null): ?Response
    {
        $this->request->allowMethod(['post', 'delete']);
        $countryId = $this->officerCountryId();
        $competition = $this->getScopedCompetition($id, $countryId);

        return $this->deleteEntityOrFail($this->Competitions, $competition);
    }

    /**
     * @param string|null $id
     * @return \Cake\Http\Response
     */
    public function recordGet(?string $id = null): Response
    {
        $this->request->allowMethod(['get']);
        $countryId = $this->officerCountryId();

        try {
            $competition = $this->getScopedCompetition($id, $countryId, ['Clubs']);
        } catch (\Throwable $e) {
            return $this->response
                ->withStatus(404)
                ->withType('application/json')
                ->withStringBody(json_encode([
                    'success' => false,
                    'message' => __('Record not found.'),
                ], JSON_UNESCAPED_UNICODE));
        }

        $this->rememberLastVisited('Competitions', $competition->id);

        return $this->response
            ->withType('application/json')
            ->withStringBody(json_encode([
                'success' => true,
                'record' => [
                    'id' => $competition->id,
                    'name' => $competition->name,
                    'title' => $competition->title,
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
                    'pipe_type' => (string)($competition->pipe_type ?? ''),
                    'pipe_parameters' => (string)($competition->pipe_parameters ?? ''),
                    'tobacco_type' => (string)($competition->tobacco_type ?? ''),
                    'tobacco_weight' => \App\Utility\CompetitionTextRender::vars($competition)['tobacco_weight'] ?? '',
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

    protected function saveCompetition(\App\Model\Entity\Competition $competition, int $countryId): bool
    {
        if (
            !$competition->isNew()
            && CompetitionApplication::isContentLocked($competition->get('application_deadline'))
        ) {
            $competition->setError(
                'application_deadline',
                __('The application deadline has passed. Competition details can no longer be edited.')
            );

            return false;
        }

        if ($countryId < 1) {
            $competition->setError('country_id', __('Your account is not assigned to a country yet. Contact an administrator.'));

            return false;
        }

        $data = $this->request->getData();
        if (!is_array($data)) {
            $data = [];
        }

        // Empty optional text → ''; start/end datetime are not form fields (set elsewhere).
        foreach (['subtitle', 'subtitle2', 'description', 'venue_name', 'venue_address', 'google_maps_url', 'lunch_description'] as $optional) {
            if (!array_key_exists($optional, $data)) {
                continue;
            }
            if (is_string($data[$optional]) && trim($data[$optional]) === '') {
                $data[$optional] = '';
            }
        }
        if (array_key_exists('city_id', $data)) {
            $data['city_id'] = (int)($data['city_id'] ?? 0);
        }
        if (array_key_exists('competition_text_template_id', $data)) {
            $tid = (int)($data['competition_text_template_id'] ?? 0);
            $data['competition_text_template_id'] = $tid > 0 ? $tid : null;
        }
        foreach (['racing_pipe_1_title', 'racing_pipe_2_title', 'racing_pipe_3_title', 'pipe_type', 'pipe_parameters', 'tobacco_type'] as $pipeTitle) {
            if (array_key_exists($pipeTitle, $data) && is_string($data[$pipeTitle])) {
                $data[$pipeTitle] = trim($data[$pipeTitle]);
            }
        }

        $identity = $this->getRequest()->getAttribute('identity');
        $actorId = '';
        if ($identity !== null && method_exists($identity, 'getIdentifier')) {
            $actorId = (string)$identity->getIdentifier();
        }
        $userId = (string)$competition->user_id;
        if ($userId === '' && $actorId !== '') {
            $userId = $actorId;
        }
        $modifiedBy = $actorId !== '' ? $actorId : $userId;
        // Must be in patch data: validation requirePresence runs on create from request fields.
        $data['country_id'] = $countryId;
        $data['currency'] = \App\Utility\CountryCurrency::normalize(
            $data['currency'] ?? $competition->get('currency') ?? '',
            $countryId
        );
        $data['user_id'] = $userId;
        $data['modified_by'] = $modifiedBy;
        $data['national_competition'] = !empty($data['national_competition']);
        $data['visible'] = !empty($data['visible']);
        $data = $this->scrubEmptyTranslations($data);

        $previousClubId = $competition->isNew() ? 0 : (int)$competition->get('club_id');

        $this->setFormTranslateLocale($this->Competitions, $countryId);

        $competition = $this->Competitions->patchEntity($competition, $data, [
            'fields' => array_merge(self::FORM_FIELDS, ['country_id', 'user_id', 'modified_by', '_translations']),
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

        $this->storeCompetitionPipeImages($competition);

        return true;
    }

    /**
     * @param list<string> $contain
     */
    protected function getScopedCompetition(
        ?string $id,
        int $countryId,
        array $contain = [],
        bool $withTranslations = false
    ): \App\Model\Entity\Competition {
        if ($withTranslations) {
            /** @var \App\Model\Entity\Competition $competition */
            $competition = $this->getWithTranslations($this->Competitions, $id, $contain, $countryId);
        } else {
            \App\Utility\AdminTranslate::applyLocale($this->Competitions);
            /** @var \App\Model\Entity\Competition $competition */
            $competition = $this->Competitions->get($id, contain: $contain);
        }
        if ($countryId < 1 || (int)$competition->country_id !== $countryId) {
            throw new \Cake\Http\Exception\NotFoundException(__('Record not found.'));
        }

        return $competition;
    }

    protected function setFormOptions(\App\Model\Entity\Competition $competition, int $countryId): void
    {
        $includeClubId = $competition->isNew() ? 0 : (int)$competition->get('club_id');
        /** @var \App\Model\Table\ClubsTable $clubs */
        $clubs = $this->fetchTable('Clubs');
        $clubOptions = $countryId > 0
            ? $clubs->optionsForCompetitionOrganizer($countryId, $includeClubId)
            : [];
        $this->set(compact('clubOptions', 'countryId'));

        /** @var \App\Model\Table\CompetitionTextTemplatesTable $templates */
        $templates = $this->fetchTable('CompetitionTextTemplates');
        $this->set('templateOptions', $countryId > 0 ? $templates->optionsForCountry($countryId) : []);
        $this->set('placeholderHelp', \App\Utility\CompetitionTextRender::placeholderHelp());

        $cityOptions = [];
        $cityId = (int)($competition->get('city_id') ?? 0);
        if ($cityId > 0) {
            /** @var \App\Model\Table\CitiesTable $cities */
            $cities = $this->fetchTable('Cities');
            $city = $cities->find()->where(['Cities.id' => $cityId])->first();
            if ($city !== null) {
                $cityOptions[(string)$city->get('id')] = $cities->optionLabel($city);
            }
        }
        $this->set(compact('cityOptions'));
        $this->set('formCountryId', $countryId);
        $defaultCurrency = \App\Utility\CountryCurrency::forCountryId($countryId);
        $this->set('defaultCurrency', $defaultCurrency);
        $this->set('currencyOptions', \App\Utility\CountryCurrency::options());
        if ($competition->isNew() && trim((string)($competition->get('currency') ?? '')) === '') {
            $competition->set('currency', $defaultCurrency);
        }
    }

    /**
     * Select2 AJAX: cities by name/ZIP for competition venue.
     */
    public function cityOptions(): Response
    {
        $this->request->allowMethod(['get']);
        $officerCountryId = $this->officerCountryId();
        if ($officerCountryId < 1) {
            return $this->response
                ->withType('application/json')
                ->withStringBody((string)json_encode([
                    'results' => [],
                    'pagination' => ['more' => false],
                ], JSON_UNESCAPED_UNICODE));
        }

        $countryId = (int)$this->request->getQuery('country_id');
        if ($countryId < 1) {
            $countryId = $officerCountryId;
        }
        if ($countryId !== $officerCountryId) {
            return $this->response
                ->withStatus(403)
                ->withType('application/json')
                ->withStringBody((string)json_encode([
                    'results' => [],
                    'pagination' => ['more' => false],
                ], JSON_UNESCAPED_UNICODE));
        }

        $term = trim((string)$this->request->getQuery('q'));
        $page = max(1, (int)$this->request->getQuery('page'));
        $limit = 30;
        if (mb_strlen($term) < 2) {
            return $this->response
                ->withType('application/json')
                ->withStringBody((string)json_encode([
                    'results' => [],
                    'pagination' => ['more' => false],
                ], JSON_UNESCAPED_UNICODE));
        }

        /** @var \App\Model\Table\CitiesTable $cities */
        $cities = $this->fetchTable('Cities');
        $like = '%' . str_replace(['%', '_'], ['\\%', '\\_'], $term) . '%';
        $query = $cities->find()
            ->select(['Cities.id', 'Cities.name', 'Cities.zip'])
            ->where([
                'Cities.country_id' => $countryId,
                'OR' => [
                    'Cities.name LIKE' => $like,
                    'Cities.zip LIKE' => $like,
                ],
            ])
            ->orderBy(['Cities.name' => 'ASC', 'Cities.zip' => 'ASC', 'Cities.id' => 'ASC']);

        $total = (clone $query)->count();
        $rows = $query->limit($limit)->offset(($page - 1) * $limit)->all();
        $results = [];
        foreach ($rows as $city) {
            $results[] = [
                'id' => (string)$city->get('id'),
                'text' => $cities->optionLabel($city),
            ];
        }

        return $this->response
            ->withType('application/json')
            ->withStringBody((string)json_encode([
                'results' => $results,
                'pagination' => ['more' => ($page * $limit) < $total],
            ], JSON_UNESCAPED_UNICODE));
    }
}
