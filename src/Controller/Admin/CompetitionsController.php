<?php
declare(strict_types=1);

namespace App\Controller\Admin;

use App\Controller\Concerns\StoresCompetitionPipeImagesTrait;
use App\Model\Entity\Competition;
use App\Model\Table\CompetitionsTable;
use App\Utility\AdminCountry;
use App\Utility\CompetitionApplication;
use App\Utility\LocaleDateParser;
use App\Utility\LocaleNumberParser;
use Cake\Http\Response;
use Cake\I18n\DateTime;

/**
 * Global Admin CRUD for competitions (optional country filter).
 *
 * @property \App\Model\Table\CompetitionsTable $Competitions
 */
class CompetitionsController extends AppController
{
    use StoresCompetitionPipeImagesTrait;

    protected int $indexLimit = 50;

    protected int $indexMaxLimit = 500;

    protected CompetitionsTable $Competitions;

    /**
     * @var list<string>
     */
    protected const FORM_FIELDS = [
        'country_id',
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
            $this->Competitions->find()->contain(['Countries', 'Clubs', 'Users', 'Modifiers']),
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
        $competition->competition_datetime = DateTime::now()->setTime(14, 0, 0);
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

        $tabsCountryId = (int)($competition->country_id ?? $filterCountryId);
        $this->setFormLanguageTabs($tabsCountryId > 0 ? $tabsCountryId : null);
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
        $competition = $this->getCompetition($id, withTranslations: true);
        $denied = $this->denyIfOutsideAdminCountryScope($competition);
        if ($denied !== null) {
            return $denied;
        }
        $this->rememberLastVisited('Competitions', $competition->id);
        $contentLocked = CompetitionApplication::isContentLocked($competition->application_deadline);

        if ($this->request->is(['patch', 'post', 'put'])) {
            if ($contentLocked) {
                $this->Flash->error(__(
                    'The application deadline has passed. Competition details can no longer be edited.'
                ));
            } elseif ($this->saveCompetition($competition)) {
                $this->rememberLastVisited('Competitions', $competition->id);
                $this->Flash->success(__('The competition has been saved.'));

                return $this->redirectToIndexList('Competitions');
            } else {
                $this->flashEntityErrors($competition);
            }
        }

        $tabsCountryId = (int)($competition->country_id ?? 0);
        $this->setFormLanguageTabs($tabsCountryId > 0 ? $tabsCountryId : null);
        $this->setFormOptions($competition);
        $this->set(compact('competition', 'contentLocked'));
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
        $competition = $this->getCompetition($id, ['Countries', 'Clubs', 'Cities']);
        $denied = $this->denyIfOutsideAdminCountryScope($competition);
        if ($denied !== null) {
            return $denied;
        }
        $this->rememberLastVisited('Competitions', $competition->id);
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

    protected function saveCompetition(Competition $competition): bool
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

        $data = $this->constrainAdminCountryData($data);

        $countryId = (int)($data['country_id'] ?? $competition->country_id ?? 0);
        if ($countryId < 1 || !AdminCountry::isValidCountryId($countryId)) {
            $competition->setError('country_id', __('Select a valid country.'));

            return false;
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
            'fields' => array_merge(self::FORM_FIELDS, ['user_id', 'modified_by', '_translations']),
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
    protected function getCompetition(
        ?string $id,
        array $contain = [],
        bool $withTranslations = false
    ): Competition {
        if ($withTranslations) {
            $probe = $this->Competitions->find()
                ->select(['Competitions.id', 'Competitions.country_id'])
                ->where(['Competitions.id' => $id])
                ->firstOrFail();
            $countryId = (int)$probe->country_id;

            /** @var \App\Model\Entity\Competition $competition */
            $competition = $this->getWithTranslations(
                $this->Competitions,
                $id,
                $contain,
                $countryId > 0 ? $countryId : null
            );

            return $competition;
        }

        \App\Utility\AdminTranslate::applyLocale($this->Competitions);
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
        $this->set('countryCurrencyMap', $this->countryCurrencyMap());
        if ($competition->isNew() && trim((string)($competition->get('currency') ?? '')) === '') {
            $competition->set('currency', $defaultCurrency);
        }
    }

    /**
     * @return array<string, string>
     */
    protected function countryCurrencyMap(): array
    {
        $map = [];
        foreach ($this->fetchTable('Countries')->find()->select(['id', 'iso2', 'currency'])->all() as $country) {
            $code = strtoupper(trim((string)($country->get('currency') ?? '')));
            if (preg_match('/^[A-Z]{3}$/', $code) !== 1) {
                $code = \App\Utility\CountryCurrency::fromIso2((string)$country->get('iso2'));
            }
            $map[(string)(int)$country->get('id')] = $code;
        }

        return $map;
    }

    /**
     * Select2 AJAX: cities by name/ZIP for competition venue.
     */
    public function cityOptions(): Response
    {
        $this->request->allowMethod(['get']);
        $countryId = (int)$this->request->getQuery('country_id');
        $term = trim((string)$this->request->getQuery('q'));
        $page = max(1, (int)$this->request->getQuery('page'));
        $limit = 30;

        if ($countryId < 1 || !AdminCountry::isValidCountryId($countryId) || mb_strlen($term) < 2) {
            return $this->response
                ->withType('application/json')
                ->withStringBody((string)json_encode([
                    'results' => [],
                    'pagination' => ['more' => false],
                ], JSON_UNESCAPED_UNICODE));
        }

        if (!\App\Utility\AdminCountryScope::canChangeCountry($this->request)) {
            $scoped = \App\Utility\AdminCountryScope::scopeForTable($this->request, $this->Competitions);
            $allowed = (int)($scoped['countryId'] ?? 0);
            if ($allowed > 0 && $countryId !== $allowed) {
                return $this->response
                    ->withStatus(403)
                    ->withType('application/json')
                    ->withStringBody((string)json_encode([
                        'results' => [],
                        'pagination' => ['more' => false],
                    ], JSON_UNESCAPED_UNICODE));
            }
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

    protected function validCountryFilter(): int
    {
        $raw = $this->request->getQuery('country_id');
        $id = is_array($raw) ? (int)end($raw) : (int)$raw;

        return $id > 0 && AdminCountry::isValidCountryId($id) ? $id : 0;
    }
}
