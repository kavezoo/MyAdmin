<?php
declare(strict_types=1);

namespace App\Controller\Clubpresident;

use App\Model\Table\CompetitionsClubsTable;
use App\Utility\AdminTranslate;
use App\Utility\CompetitionApplication;
use App\Utility\CompetitionBrowse;
use App\Utility\LocaleDateParser;
use App\Utility\LocaleNumberParser;
use Cake\Http\Exception\NotFoundException;
use Cake\Http\Response;
use Cake\I18n\DateTime;

/**
 * Club teams (alcsapatok) + applicant assignment for competitions.
 *
 * @property \App\Model\Table\CompetitionsClubsTable $CompetitionsClubs
 */
class CompetitionTeamsController extends AppController
{
    protected const LAST_VISITED_SESSION_KEY = 'Clubpresident.lastVisited';

    protected const INDEX_STATE_SESSION_KEY = 'Clubpresident.indexState';

    protected int $indexLimit = 50;

    protected int $indexMaxLimit = 200;

    protected CompetitionsClubsTable $CompetitionsClubs;

    public function initialize(): void
    {
        parent::initialize();
        $this->CompetitionsClubs = $this->fetchTable('CompetitionsClubs');
    }

    protected function indexStateSessionKey(): string
    {
        return self::INDEX_STATE_SESSION_KEY;
    }

    protected function lastVisitedSessionKey(): string
    {
        return self::LAST_VISITED_SESSION_KEY;
    }

    /**
     * Teams for this club across open/upcoming competitions.
     *
     * @return \Cake\Http\Response|null|void
     */
    public function index()
    {
        $clubId = $this->presidentClubId();
        $this->set('title', __('Sub-teams'));
        $this->viewBuilder()->setVar('breadcrumb', __('Sub-teams'));
        $this->set('canAdd', true);
        $this->set('canEdit', true);
        $this->set('canDelete', true);

        $club = $this->fetchTable('Clubs')->get($clubId);
        $homeCountryId = (int)($club->country_id ?? 0);
        $browseCountryId = CompetitionBrowse::resolveCountryId(
            $this->request,
            $homeCountryId,
            CompetitionBrowse::SESSION_CLUBPRESIDENT_TEAMS
        );
        $browseCountryOptions = CompetitionBrowse::countryOptions();

        $redirect = $this->applyIndexListState('CompetitionTeams');
        if ($redirect !== null) {
            return $redirect;
        }

        $paginateOptions = $this->indexPaginateOptionsFor($this->CompetitionsClubs, [
            'sortableFields' => [
                'id',
                'Subclubs.name',
                'user_count',
                'visible',
                'created',
                'Competitions.name',
            ],
            'order' => [
                'CompetitionsClubs.created' => 'DESC',
            ],
        ], [
            'Competitions' => $this->CompetitionsClubs->Competitions->getTarget(),
            'Subclubs' => $this->CompetitionsClubs->Subclubs->getTarget(),
        ]);

        AdminTranslate::applyLocale($this->CompetitionsClubs->Competitions->getTarget());

        $query = $this->CompetitionsClubs->find()
            ->contain(['Competitions', 'Subclubs'])
            ->innerJoinWith('Subclubs')
            ->where(['CompetitionsClubs.club_id' => $clubId]);
        if ($browseCountryId > 0) {
            $query->where(['Competitions.country_id' => $browseCountryId])
                ->where(CompetitionBrowse::activeConditions());
        } else {
            $query->where(['1 = 0']);
        }
        $query = $this->applyIndexSearch($query, $this->CompetitionsClubs);

        $redirect = $this->resolveIndexPageForLastVisited('CompetitionTeams', $query, $paginateOptions);
        if ($redirect !== null) {
            return $redirect;
        }

        $teams = $this->paginate($query, $paginateOptions);
        $this->setLastVisitedForIndex('CompetitionTeams');
        $this->set(compact(
            'teams',
            'clubId',
            'browseCountryId',
            'browseCountryOptions',
            'homeCountryId'
        ));
    }

    /**
     * @param string|null $id
     * @return \Cake\Http\Response|null|void
     */
    public function view(?string $id = null)
    {
        $clubId = $this->presidentClubId();
        $team = $this->getScopedTeam($id, $clubId, ['Competitions', 'Subclubs']);
        $this->rememberLastVisited('CompetitionTeams', $team->id);

        $members = $this->fetchTable('CompetitionsUsers')->find()
            ->contain(['Users'])
            ->where(['CompetitionsUsers.competition_club_id' => (int)$team->id])
            ->orderBy(['CompetitionsUsers.created' => 'ASC'])
            ->all();

        $minimum = (int)($team->competition->minimum_team_size ?? 3);
        $meetsMinimum = $this->CompetitionsClubs->meetsMinimumTeamSize($team, $minimum);

        $this->set(compact('team', 'members', 'minimum', 'meetsMinimum'));
        $this->set('teamName', (string)($team->subclub->name ?? ''));
        $this->set('canAdd', true);
        $this->set('canEdit', true);
        $this->set('canDelete', (int)$team->user_count === 0);
        $this->set('title', __('Sub-team details'));
        $this->viewBuilder()->setVar('breadcrumb', __('Sub-teams'));
    }

    /**
     * JSON modal payload for index double-click.
     *
     * @param string|null $id
     * @return \Cake\Http\Response
     */
    public function recordGet(?string $id = null): Response
    {
        $this->request->allowMethod(['get']);
        $clubId = $this->presidentClubId();

        try {
            $team = $this->getScopedTeam($id, $clubId, ['Competitions', 'Subclubs']);
        } catch (\Throwable $e) {
            return $this->response
                ->withStatus(404)
                ->withType('application/json')
                ->withStringBody(json_encode([
                    'success' => false,
                    'message' => __('Record not found.'),
                ], JSON_UNESCAPED_UNICODE));
        }

        $this->rememberLastVisited('CompetitionTeams', $team->id);
        $minimum = (int)($team->competition->minimum_team_size ?? 3);

        return $this->response
            ->withType('application/json')
            ->withStringBody(json_encode([
                'success' => true,
                'record' => [
                    'id' => $team->id,
                    'competition' => (string)($team->competition->name ?? ''),
                    'name' => (string)($team->subclub->name ?? ''),
                    'user_count' => LocaleNumberParser::format($team->user_count, decimals: 0),
                    'minimum_team_size' => LocaleNumberParser::format($minimum, decimals: 0),
                    'application_datetime' => $team->application_datetime
                        ? LocaleDateParser::format($team->application_datetime, 'datetime_short')
                        : '',
                    'visible' => (bool)$team->visible,
                    'pos' => LocaleNumberParser::format($team->pos, decimals: 0),
                    'created' => $team->created
                        ? LocaleDateParser::format($team->created, 'datetime_short')
                        : '',
                    'modified' => $team->modified
                        ? LocaleDateParser::format($team->modified, 'datetime_short')
                        : '',
                ],
                'canDelete' => (int)$team->user_count === 0,
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    /**
     * JSON modal payload for linked competition name on the teams index (read-only).
     *
     * @param string|null $id Competition UUID
     * @return \Cake\Http\Response
     */
    public function competitionRecordGet(?string $id = null): Response
    {
        $this->request->allowMethod(['get']);
        $clubId = $this->presidentClubId();

        try {
            $competitions = $this->fetchTable('Competitions');
            AdminTranslate::applyLocale($competitions);
            /** @var \App\Model\Entity\Competition $competition */
            $competition = $competitions->get($id, contain: ['Clubs']);
            if (!(bool)$competition->visible) {
                throw new NotFoundException(__('Record not found.'));
            }
        } catch (\Throwable $e) {
            return $this->response
                ->withStatus(404)
                ->withType('application/json')
                ->withStringBody(json_encode([
                    'success' => false,
                    'message' => __('Record not found.'),
                ], JSON_UNESCAPED_UNICODE));
        }

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
                    'user_count' => LocaleNumberParser::format($competition->user_count, decimals: 0),
                    'visible' => (bool)$competition->visible,
                    'can_delete' => false,
                ],
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    /**
     * Remove a member from this team (back to pending assignment).
     *
     * @param string|null $id Team id
     * @return \Cake\Http\Response|null
     */
    public function unassign(?string $id = null): ?Response
    {
        $this->request->allowMethod(['post', 'delete']);
        $clubId = $this->presidentClubId();
        $team = $this->getScopedTeam($id, $clubId);
        $userId = trim((string)$this->request->getData('user_id'));

        if ($userId === '') {
            $this->Flash->error(__('Member not found.'));

            return $this->redirect(['action' => 'view', $team->id]);
        }

        /** @var \App\Model\Table\CompetitionsUsersTable $applicantsTable */
        $applicantsTable = $this->fetchTable('CompetitionsUsers');
        $row = $applicantsTable->find()
            ->where([
                'CompetitionsUsers.competition_id' => (string)$team->competition_id,
                'CompetitionsUsers.competition_club_id' => (int)$team->id,
                'CompetitionsUsers.user_id' => $userId,
            ])
            ->first();

        if ($row === null) {
            $this->Flash->error(__('Member not found on this team.'));

            return $this->redirect(['action' => 'view', $team->id]);
        }

        $row->competition_club_id = null;
        $row->status = CompetitionApplication::STATUS_PENDING;
        if ($applicantsTable->save($row)) {
            $this->Flash->success(__('Member removed from the team.'));
        } else {
            $this->Flash->error(__('Could not remove member.'));
        }

        return $this->redirect(['action' => 'view', $team->id]);
    }

    /**
     * JSON: next suggested team name for club + competition (serial restarts per competition).
     *
     * Query: competition_id
     *
     * @return \Cake\Http\Response
     */
    public function suggestedName(): Response
    {
        $this->request->allowMethod(['get']);
        $clubId = $this->presidentClubId();
        $competitionId = trim((string)$this->request->getQuery('competition_id'));

        if ($clubId < 1 || $competitionId === '') {
            return $this->response
                ->withType('application/json')
                ->withStringBody((string)json_encode([
                    'success' => false,
                    'name' => '',
                ], JSON_UNESCAPED_UNICODE));
        }

        if (
            !$this->competitionVisibleForClub($competitionId, $clubId)
            && !$this->competitionBelongsToClubCountry($competitionId, $clubId)
        ) {
            return $this->response
                ->withType('application/json')
                ->withStatus(404)
                ->withStringBody((string)json_encode([
                    'success' => false,
                    'name' => '',
                ], JSON_UNESCAPED_UNICODE));
        }

        $name = $this->fetchTable('Subclubs')->suggestNextName($clubId, $competitionId);

        return $this->response
            ->withType('application/json')
            ->withStringBody((string)json_encode([
                'success' => true,
                'name' => $name,
                'competition_id' => $competitionId,
            ], JSON_UNESCAPED_UNICODE));
    }

    /**
     * @return \Cake\Http\Response|null|void
     */
    public function add()
    {
        $clubId = $this->presidentClubId();
        $team = $this->newEntityWithSchemaDefaults($this->CompetitionsClubs);
        $team->club_id = $clubId;
        $team->visible = true;
        $team->application_datetime = DateTime::now();

        $competitionOptions = $this->openCompetitionOptionsForClub($clubId);
        $defaultCompetitionId = $competitionOptions !== [] ? (string)array_key_first($competitionOptions) : '';
        if (!$this->request->is('post') && $defaultCompetitionId !== '') {
            $team->competition_id = $defaultCompetitionId;
        }
        $teamName = $this->fetchTable('Subclubs')->suggestNextName(
            $clubId,
            (string)($team->competition_id ?? $defaultCompetitionId)
        );
        if ($this->request->is('post')) {
            $teamName = trim((string)$this->request->getData('name'));
        }

        if ($this->request->is('post')) {
            if ($this->saveTeam($team, $clubId)) {
                $this->rememberLastVisited('CompetitionTeams', $team->id);
                $this->Flash->success(__('The team has been saved.'));

                return $this->redirectToIndexList('CompetitionTeams');
            }
            $teamName = trim((string)$this->request->getData('name'));
        }

        $this->set('competitionOptions', $competitionOptions);
        $this->set(compact('team', 'teamName'));
        $this->set('title', __('New sub-team'));
        $this->viewBuilder()->setVar('breadcrumb', __('Sub-teams'));
        $this->render('form');
    }

    /**
     * @param string|null $id
     * @return \Cake\Http\Response|null|void
     */
    public function edit(?string $id = null)
    {
        $clubId = $this->presidentClubId();
        $team = $this->getScopedTeam($id, $clubId, ['Competitions', 'Subclubs']);
        $this->rememberLastVisited('CompetitionTeams', $team->id);
        $teamName = (string)($team->subclub->name ?? '');

        if ($this->request->is(['patch', 'post', 'put'])) {
            if ($this->saveTeam($team, $clubId)) {
                $this->Flash->success(__('The team has been saved.'));

                return $this->redirectToIndexList('CompetitionTeams');
            }
            $teamName = trim((string)$this->request->getData('name'));
        }

        $this->setCompetitionOptions($clubId, (string)$team->competition_id);
        $this->set(compact('team', 'teamName'));
        $this->set('title', __('Edit sub-team'));
        $this->viewBuilder()->setVar('breadcrumb', __('Sub-teams'));
        $this->render('form');
    }

    /**
     * Assign club applicants to this team / move between teams.
     *
     * @param string|null $id Team id
     * @return \Cake\Http\Response|null|void
     */
    public function applicants(?string $id = null)
    {
        $clubId = $this->presidentClubId();
        $team = $this->getScopedTeam($id, $clubId, ['Competitions', 'Subclubs']);
        $competitionId = (string)$team->competition_id;
        $minimum = (int)($team->competition->minimum_team_size ?? 3);

        /** @var \App\Model\Table\CompetitionsUsersTable $applicantsTable */
        $applicantsTable = $this->fetchTable('CompetitionsUsers');

        if ($this->request->is(['post', 'put', 'patch'])) {
            $assignUserId = trim((string)$this->request->getData('user_id'));
            $targetTeamId = (int)$this->request->getData('competition_club_id');
            if ($assignUserId !== '') {
                $row = $applicantsTable->find()
                    ->where([
                        'CompetitionsUsers.competition_id' => $competitionId,
                        'CompetitionsUsers.user_id' => $assignUserId,
                    ])
                    ->first();
                if ($row === null) {
                    $this->Flash->error(__('Applicant not found.'));
                } else {
                    // Ensure target team belongs to this club + competition
                    $targetId = $targetTeamId > 0 ? $targetTeamId : (int)$team->id;
                    $target = $this->CompetitionsClubs->find()
                        ->where([
                            'CompetitionsClubs.id' => $targetId,
                            'CompetitionsClubs.club_id' => $clubId,
                            'CompetitionsClubs.competition_id' => $competitionId,
                        ])
                        ->first();
                    if ($target === null) {
                        $this->Flash->error(__('Invalid team.'));
                    } else {
                        $row->competition_club_id = (int)$target->id;
                        $row->status = CompetitionApplication::STATUS_ASSIGNED;
                        if ($applicantsTable->save($row)) {
                            $this->Flash->success(__('Member assigned to the team.'));
                        } else {
                            $this->Flash->error(__('Could not assign member.'));
                        }
                    }
                }
            }

            return $this->redirect(['action' => 'applicants', $team->id]);
        }

        $clubMemberIds = $this->fetchTable('Users')->find()
            ->select(['id'])
            ->where(['Users.club_id' => $clubId])
            ->all()
            ->extract('id')
            ->toList();

        $applicants = $applicantsTable->find()
            ->contain(['Users', 'CompetitionsClubs' => ['Subclubs']])
            ->where([
                'CompetitionsUsers.competition_id' => $competitionId,
                'CompetitionsUsers.user_id IN' => $clubMemberIds !== [] ? $clubMemberIds : ['0'],
            ])
            ->orderBy(['CompetitionsUsers.created' => 'ASC'])
            ->all();

        $teamRows = $this->CompetitionsClubs->find()
            ->contain(['Subclubs'])
            ->where([
                'CompetitionsClubs.club_id' => $clubId,
                'CompetitionsClubs.competition_id' => $competitionId,
            ])
            ->orderBy(['Subclubs.name' => 'ASC'])
            ->all();
        $teamOptions = [];
        foreach ($teamRows as $row) {
            $teamOptions[(int)$row->id] = (string)($row->subclub->name ?? ('#' . $row->id));
        }

        $meetsMinimum = $this->CompetitionsClubs->meetsMinimumTeamSize($team, $minimum);
        $this->set(compact('team', 'applicants', 'teamOptions', 'minimum', 'meetsMinimum'));
        $this->set('title', __('Assign applicants'));
        $this->viewBuilder()->setVar('breadcrumb', __('Sub-teams'));
    }

    /**
     * @param string|null $id
     * @return \Cake\Http\Response|null
     */
    public function delete(?string $id = null): ?Response
    {
        $this->request->allowMethod(['post', 'delete']);
        $team = $this->getScopedTeam($id, $this->presidentClubId());

        if (!$this->CompetitionsClubs->canDelete($team)) {
            $this->Flash->error(__('Cannot delete this sub-team because it already has assigned members.'));

            return $this->redirectToIndexList('CompetitionTeams');
        }

        $subclubId = (int)$team->subclub_id;

        $deleted = false;
        try {
            $deleted = (bool)$this->CompetitionsClubs->getConnection()->transactional(function () use ($team, $subclubId) {
                if (!$this->CompetitionsClubs->delete($team)) {
                    return false;
                }
                // Orphan name row: remove if no other team entry uses it.
                if ($subclubId > 0) {
                    $stillUsed = $this->CompetitionsClubs->exists(['subclub_id' => $subclubId]);
                    if (!$stillUsed) {
                        $subclubs = $this->fetchTable('Subclubs');
                        $subclub = $subclubs->find()->where(['Subclubs.id' => $subclubId])->first();
                        if ($subclub !== null) {
                            $subclubs->delete($subclub);
                        }
                    }
                }

                return true;
            });
        } catch (\Throwable) {
            $deleted = false;
        }

        if ($deleted) {
            $deletedId = $team->id;
            if ((string)$this->getLastVisitedId('CompetitionTeams') === (string)$deletedId) {
                $this->clearLastVisited('CompetitionTeams');
            }
            $this->Flash->success(__('The record has been deleted.'));
        } else {
            $errors = $team->getError('_delete');
            $message = (is_array($errors) && $errors !== [])
                ? (string)reset($errors)
                : __('The record could not be deleted. Please try again.');
            $this->Flash->error($message);
        }

        return $this->redirectToIndexList('CompetitionTeams');
    }

    protected function saveTeam(\App\Model\Entity\CompetitionsClub $team, int $clubId): bool
    {
        $data = $this->request->getData();
        $isNew = $team->isNew();
        // club_id is set by the controller (not a form field) — must be in patch data
        // or requirePresence('club_id', 'create') leaves a sticky validation error.
        $data['club_id'] = $clubId;
        // Name is stored only on subclubs — never patch competitions_clubs.name.
        $fields = $isNew
            ? ['competition_id', 'club_id', 'visible', 'pos']
            : ['visible', 'pos'];
        $team = $this->CompetitionsClubs->patchEntity($team, $data, [
            'fields' => $fields,
        ]);
        $team->club_id = $clubId;
        $team->visible = !empty($data['visible']);
        if ($team->application_datetime === null) {
            $team->application_datetime = DateTime::now();
        }

        $competitionId = (string)$team->competition_id;
        if ($competitionId === '') {
            $team->setError('competition_id', __('Select a competition.'));
            $this->flashEntityErrors($team);

            return false;
        }
        if ($isNew && !$this->competitionVisibleForClub($competitionId, $clubId)) {
            $team->setError('competition_id', __('Select a competition that is currently open for applications.'));
            $this->flashEntityErrors($team);

            return false;
        }
        if (!$isNew && !$this->competitionBelongsToClubCountry($competitionId, $clubId)) {
            $team->setError('competition_id', __('Invalid competition.'));
            $this->flashEntityErrors($team);

            return false;
        }

        $name = trim((string)($data['name'] ?? ''));
        if ($name === '') {
            $name = $this->fetchTable('Subclubs')->suggestNextName($clubId, $competitionId);
        }

        $userId = $this->currentUserId();
        if ($userId === '') {
            $this->Flash->error(__('You must be logged in to save a sub-team.'));

            return false;
        }

        /** @var \App\Model\Table\SubclubsTable $subclubs */
        $subclubs = $this->fetchTable('Subclubs');

        try {
            $ok = (bool)$this->CompetitionsClubs->getConnection()->transactional(function () use (
                $team,
                $clubId,
                $competitionId,
                $userId,
                $name,
                $isNew,
                $subclubs,
            ) {
                if ($isNew || (int)$team->subclub_id < 1) {
                    $subclub = $subclubs->createNamed(
                        $clubId,
                        $competitionId,
                        $userId,
                        $name,
                        (bool)$team->visible
                    );
                    if ($subclub === null) {
                        $team->setError('subclub_id', __('The sub-club record could not be saved.'));

                        return false;
                    }
                    $team->subclub_id = (int)$subclub->id;
                } else {
                    $subclub = $subclubs->get((int)$team->subclub_id);
                    if ((int)$subclub->club_id !== $clubId) {
                        $team->setError('subclub_id', __('Invalid sub-club.'));

                        return false;
                    }
                    $subclub->name = $name;
                    $subclub->visible = (bool)$team->visible;
                    if (!$subclubs->save($subclub)) {
                        $team->setErrors($subclub->getErrors());

                        return false;
                    }
                }

                return (bool)$this->CompetitionsClubs->save($team);
            });
        } catch (\Throwable $e) {
            $this->Flash->error(__('The record could not be saved. Please try again.'));

            return false;
        }

        if ($ok) {
            return true;
        }

        $this->flashEntityErrors($team);

        return false;
    }

    /**
     * Logged-in user id (UUID string).
     */
    protected function currentUserId(): string
    {
        $identity = $this->getRequest()->getAttribute('identity');
        if ($identity === null) {
            return '';
        }
        if (method_exists($identity, 'getIdentifier')) {
            return (string)$identity->getIdentifier();
        }
        if (method_exists($identity, 'get')) {
            return (string)($identity->get('id') ?? '');
        }

        return '';
    }

    /**
     * Competitions in the browse country with an open application window
     * (first_date_of_application ≤ today ≤ application_deadline) and not ended.
     *
     * @param string|null $keepCompetitionId Always include this id on edit (even if window closed).
     */
    protected function setCompetitionOptions(int $clubId, ?string $keepCompetitionId = null): void
    {
        $options = $this->openCompetitionOptionsForClub($clubId);
        if ($keepCompetitionId !== null && $keepCompetitionId !== '' && !isset($options[$keepCompetitionId])) {
            $competitions = $this->fetchTable('Competitions');
            AdminTranslate::applyLocale($competitions);
            $row = $competitions->find()
                ->select(['id', 'name'])
                ->where(['Competitions.id' => $keepCompetitionId])
                ->first();
            if ($row !== null) {
                $options[(string)$row->id] = (string)$row->name . ' (' . __('applications closed') . ')';
            }
        }
        $this->set('competitionOptions', $options);
    }

    /**
     * @return array<string, string>
     */
    protected function openCompetitionOptionsForClub(int $clubId): array
    {
        $club = $this->fetchTable('Clubs')->get($clubId);
        $homeCountryId = (int)$club->country_id;
        $countryId = CompetitionBrowse::resolveCountryId(
            $this->request,
            $homeCountryId,
            CompetitionBrowse::SESSION_CLUBPRESIDENT_TEAMS
        );
        $today = \Cake\I18n\Date::today()->format('Y-m-d');

        if ($countryId < 1) {
            return [];
        }

        $competitions = $this->fetchTable('Competitions');
        AdminTranslate::applyLocale($competitions);

        return $competitions->find('list', keyField: 'id', valueField: 'name')
            ->where([
                'Competitions.country_id' => $countryId,
                'Competitions.first_date_of_application <=' => $today,
                'Competitions.application_deadline >=' => $today,
            ])
            ->where(CompetitionBrowse::activeConditions())
            ->orderBy(['Competitions.application_deadline' => 'ASC', 'Competitions.name' => 'ASC'])
            ->toArray();
    }

    protected function competitionVisibleForClub(string $competitionId, int $clubId): bool
    {
        return array_key_exists($competitionId, $this->openCompetitionOptionsForClub($clubId));
    }

    protected function competitionBelongsToClubCountry(string $competitionId, int $clubId): bool
    {
        // Sub-teams may be created for abroad competitions (browse country).
        return $this->fetchTable('Competitions')->exists([
            'Competitions.id' => $competitionId,
        ]);
    }

    /**
     * @param list<string> $contain
     */
    protected function getScopedTeam(?string $id, int $clubId, array $contain = []): \App\Model\Entity\CompetitionsClub
    {
        if (in_array('Competitions', $contain, true)) {
            AdminTranslate::applyLocale($this->CompetitionsClubs->Competitions->getTarget());
        }

        /** @var \App\Model\Entity\CompetitionsClub $team */
        $team = $this->CompetitionsClubs->get($id, contain: $contain);
        if ((int)$team->club_id !== $clubId) {
            throw new NotFoundException(__('Record not found.'));
        }

        return $team;
    }
}
