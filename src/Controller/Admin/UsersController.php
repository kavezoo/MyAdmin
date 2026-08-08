<?php
declare(strict_types=1);

namespace App\Controller\Admin;

use App\Auth\AppRoles;
use App\Auth\MembershipProfile;
use App\Service\MembershipService;
use App\Utility\AdminCountry;
use App\Utility\CompetitionApplication;
use App\Utility\LocaleDateParser;
use App\Utility\MembershipFee;
use Cake\Http\Response;

/**
 * Global Admin CRUD for users (CakeDC Users + membership fields).
 *
 * @property \App\Model\Table\UsersTable $Users
 */
class UsersController extends AppController
{
    protected int $indexLimit = 50;

    protected int $indexMaxLimit = 500;

    /**
     * @return \Cake\Http\Response|null|void
     */
    public function index()
    {
        $this->set('title', __('Users'));
        $this->viewBuilder()->setVar('breadcrumb', __('Users'));

        $scoped = $this->beginAdminCountryScopedIndex($this->Users);
        if ($scoped instanceof Response) {
            return $scoped;
        }
        $filterCountryId = $scoped['countryId'];

        $redirect = $this->applyIndexListState('Users');
        if ($redirect !== null) {
            return $redirect;
        }

        $paginateOptions = $this->indexPaginateOptionsFor($this->Users, [
            'sortableFields' => [
                'Users.id',
                'Users.first_name',
                'Users.last_name',
                'Users.email',
                'Users.role',
                'Users.country_id',
                'Users.active',
                'Users.enabled',
                'Users.created',
                'Users.modified',
                'Countries.name',
                'Clubs.name',
            ],
            'order' => [
                'Users.last_name' => 'ASC',
                'Users.first_name' => 'ASC',
                'Users.email' => 'ASC',
            ],
        ], [
            'Countries' => $this->Users->Countries->getTarget(),
            'Clubs' => $this->Users->Clubs->getTarget(),
        ]);

        $query = $this->applyAdminCountryWhere(
            $this->Users->find()->contain(['Countries', 'Clubs']),
            $this->Users,
            $filterCountryId
        );
        $query = $this->applyIndexSearch($query, $this->Users);

        $redirect = $this->resolveIndexPageForLastVisited('Users', $query, $paginateOptions);
        if ($redirect !== null) {
            return $redirect;
        }

        $users = $this->paginate($query, $paginateOptions);
        $this->setLastVisitedForIndex('Users');
        $deletableUserIds = [];
        foreach ($users as $row) {
            if ($this->Users->canDelete($row)) {
                $deletableUserIds[(string)$row->id] = true;
            }
        }
        $this->set(compact('users', 'deletableUserIds'));
    }

    /**
     * @return \Cake\Http\Response|null|void
     */
    public function add()
    {
        $user = $this->newEntityWithSchemaDefaults($this->Users);
        if ($this->request->is('post')) {
            $data = $this->prepareSaveData($this->request->getData(), isNew: true);
            $user = $this->Users->patchEntity($user, $data, [
                'fields' => $this->formFields(isNew: true),
            ]);
            if ($this->Users->save($user)) {
                $this->rememberLastVisited('Users', $user->id);
                $this->Flash->success(__('The user has been saved.'));

                return $this->redirectToIndexList('Users');
            }
            $this->flashEntityErrors($user, null, $this->fieldLabels());
        }

        $scope = \App\Utility\AdminCountryScope::scopeForTable($this->request, $this->Users);
        if ($scope['countryId'] > 0 && !(int)$user->get('country_id')) {
            $user->set('country_id', $scope['countryId']);
        }

        $this->setFormVars($user);
        $this->set('title', __('New user'));
        $this->viewBuilder()->setVar('breadcrumb', __('Users'));
        $this->render('form');
    }

    /**
     * @param string|null $id
     * @return \Cake\Http\Response|null|void
     */
    public function edit(?string $id = null)
    {
        $user = $this->Users->get($id);
        $denied = $this->denyIfOutsideAdminCountryScope($user);
        if ($denied !== null) {
            return $denied;
        }
        $this->rememberLastVisited('Users', $user->id);
        if ($this->request->is(['patch', 'post', 'put'])) {
            $data = $this->prepareSaveData($this->request->getData(), isNew: false);
            $user = $this->Users->patchEntity($user, $data, [
                'fields' => $this->formFields(isNew: false),
            ]);
            $dirty = $user->getDirty();
            if ($this->Users->save($user)) {
                (new MembershipService())->notifyMemberProfileUpdated($user, $dirty);
                $this->rememberLastVisited('Users', $user->id);
                $this->Flash->success(__('The user has been saved.'));

                return $this->redirectToIndexList('Users');
            }
            $this->flashEntityErrors($user, null, $this->fieldLabels());
        }

        $this->setFormVars($user);
        $this->setCanDeleteFlag($this->Users, $user);
        $this->set('title', __('Edit user'));
        $this->viewBuilder()->setVar('breadcrumb', __('Users'));
        $this->render('form');
    }

    /**
     * @param string|null $id
     * @return \Cake\Http\Response|null|void
     */
    public function view(?string $id = null)
    {
        $user = $this->Users->get($id, contain: [
            'Countries',
            'Clubs',
            'CompetitionsUsers' => fn ($query) => $query
                ->contain([
                    'Competitions',
                    'CompetitionsClubs' => ['Subclubs', 'Clubs'],
                ])
                ->orderBy([
                    'Competitions.application_deadline' => 'DESC',
                    'Competitions.name' => 'ASC',
                    'CompetitionsUsers.created' => 'ASC',
                ]),
        ]);
        $denied = $this->denyIfOutsideAdminCountryScope($user);
        if ($denied !== null) {
            return $denied;
        }
        $this->rememberLastVisited('Users', $user->id);
        $this->set(compact('user'));
        $this->set('countryLabel', AdminCountry::label((int)$user->country_id));
        $this->setCanDeleteFlag($this->Users, $user);
        $this->set('title', __('User details'));
        $this->viewBuilder()->setVar('breadcrumb', __('Users'));
    }

    /**
     * @param string|null $id
     */
    public function delete(?string $id = null): ?Response
    {
        $this->request->allowMethod(['post', 'delete']);
        $user = $this->Users->get($id);
        $denied = $this->denyIfOutsideAdminCountryScope($user);
        if ($denied !== null) {
            return $denied;
        }

        return $this->deleteEntityOrFail($this->Users, $user);
    }

    /**
     * @param string|null $id User id
     */
    public function recordGet(?string $id = null): Response
    {
        $this->request->allowMethod(['get']);
        try {
            $user = $this->Users->get($id, contain: ['Countries', 'Clubs']);
        } catch (\Throwable) {
            return $this->jsonRecordNotFound();
        }

        if (!\App\Utility\AdminCountryScope::entityAllowed($user, $this->request)) {
            return $this->response->withStatus(403)->withType('application/json')->withStringBody((string)json_encode([
                'success' => false, 'message' => __('You are not allowed to access records from another country.'),
            ], JSON_UNESCAPED_UNICODE));
        }

        $this->rememberLastVisited('Users', $user->id);

        return $this->jsonRecordResponse($this->userRecordPayload($user));
    }

    /**
     * JSON: club row for linked modal on index.
     *
     * @param string|null $id Club id
     */
    public function clubRecordGet(?string $id = null): Response
    {
        $this->request->allowMethod(['get']);
        try {
            /** @var \App\Model\Table\ClubsTable $clubs */
            $clubs = $this->fetchTable('Clubs');
            $club = $clubs->get($id, contain: ['Countries', 'Cities']);
        } catch (\Throwable) {
            return $this->jsonRecordNotFound();
        }

        return $this->jsonRecordResponse($this->clubRecordPayload($club));
    }

    /**
     * JSON: competition application row for related tab modal.
     *
     * @param string|null $id competitions_users.id
     */
    public function applicationRecordGet(?string $id = null): Response
    {
        $this->request->allowMethod(['get']);
        try {
            /** @var \App\Model\Table\CompetitionsUsersTable $applications */
            $applications = $this->fetchTable('CompetitionsUsers');
            $application = $applications->get($id, contain: [
                'Competitions',
                'CompetitionsClubs' => ['Subclubs', 'Clubs'],
                'Users',
            ]);
        } catch (\Throwable) {
            return $this->jsonRecordNotFound();
        }

        return $this->jsonRecordResponse($this->applicationRecordPayload($application));
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    protected function prepareSaveData(array $data, bool $isNew): array
    {
        if ($isNew) {
            $data = $this->Users->normalizeRegistrationData($data);
        }
        $email = trim((string)($data['email'] ?? ''));
        if ($email !== '' && trim((string)($data['username'] ?? '')) === '') {
            $data['username'] = $email;
        }
        foreach (['country_id', 'club_id', 'language_id'] as $intField) {
            if (array_key_exists($intField, $data) && $data[$intField] !== '' && $data[$intField] !== null) {
                $data[$intField] = (int)$data[$intField];
            }
        }
        $data = $this->constrainAdminCountryData($data);
        if (!$isNew) {
            unset($data['password'], $data['password_confirm']);
        }

        return $data;
    }

    /** @return list<string> */
    protected function formFields(bool $isNew): array
    {
        $fields = [
            'first_name',
            'last_name',
            'phone',
            'role',
            'country_id',
            'club_id',
            'language_id',
            'active',
            'enabled',
            'membership_status',
            MembershipProfile::FIELD_JOINED,
            MembershipFee::FIELD_CLUB,
            MembershipFee::FIELD_NATIONAL,
        ];
        if ($isNew) {
            $fields = array_merge([
                'username',
                'email',
                'password',
                'password_confirm',
            ], $fields);
        }

        return $fields;
    }

    /** @return array<string, string> */
    protected function fieldLabels(): array
    {
        return [
            'email' => __('Email'),
            'username' => __('Username'),
            'first_name' => __('Name'),
            'last_name' => __('Last name'),
            'phone' => __('Phone'),
            'role' => __('Role'),
            'country_id' => __('Country'),
            'club_id' => __('Club'),
            'language_id' => __('Language'),
            'active' => __('Active'),
            'enabled' => __('Enabled'),
            'password' => __('Password'),
            'password_confirm' => __('Confirm password'),
            'membership_status' => __('Membership status'),
            MembershipProfile::FIELD_JOINED => __('Member since'),
            MembershipFee::FIELD_CLUB => MembershipFee::clubFeeLabel(0),
            MembershipFee::FIELD_NATIONAL => MembershipFee::nationalFeeLabel(0),
        ];
    }

    protected function setFormVars(object $user): void
    {
        $clubOptions = $this->clubOptions();
        $languageOptions = [];
        foreach ($this->fetchTable('Languages')->find()->orderBy(['Languages.name' => 'ASC'])->all() as $language) {
            $languageOptions[(int)$language->get('id')] = (string)$language->get('name');
        }

        $this->set('user', $user);
        $canChange = \App\Utility\AdminCountryScope::canChangeCountry($this->request);
        $countryId = (int)($user->get('country_id') ?? 0);
        if ($countryId < 1) {
            $countryId = \App\Utility\AdminCountryScope::ownCountryId($this->request);
        }
        $this->set('countryOptions', $canChange
            ? AdminCountry::options()
            : ($countryId > 0 ? [$countryId => AdminCountry::label($countryId)] : []));
        $this->set('canChangeCountry', $canChange);
        $this->set('clubOptions', $clubOptions);
        $this->set('languageOptions', $languageOptions);
        $this->set('roleOptions', AppRoles::options());
        $this->set('membershipStatusOptions', [
            MembershipProfile::STATUS_INCOMPLETE => __('Incomplete'),
            MembershipProfile::STATUS_PENDING => __('Pending approval'),
            MembershipProfile::STATUS_APPROVED => __('Approved'),
        ]);
    }

    /** @return array<int, string> */
    protected function clubOptions(): array
    {
        $options = [0 => __('— No club —')];
        $clubsQuery = $this->fetchTable('Clubs')->find()
            ->contain(['Countries'])
            ->orderBy(['Countries.name' => 'ASC', 'Clubs.name' => 'ASC']);
        if (!\App\Utility\AdminCountryScope::canChangeCountry($this->request)) {
            $own = \App\Utility\AdminCountryScope::ownCountryId($this->request);
            if ($own > 0) {
                $clubsQuery->where(['Clubs.country_id' => $own]);
            }
        }
        foreach ($clubsQuery->all() as $club) {
            $country = AdminCountry::label((int)$club->get('country_id'));
            $options[(int)$club->get('id')] = trim($country . ' — ' . (string)$club->get('name'));
        }

        return $options;
    }

    protected function validCountryFilter(): int
    {
        $raw = $this->request->getQuery('country_id');
        $id = is_array($raw) ? (int)end($raw) : (int)$raw;

        return $id > 0 && AdminCountry::isValidCountryId($id) ? $id : 0;
    }

    /**
     * @param \Cake\Datasource\EntityInterface $user
     * @return array<string, mixed>
     */
    protected function userRecordPayload(object $user): array
    {
        $clubName = '';
        if ($user->club !== null) {
            $clubName = (string)$user->club->name;
        }
        $roleKey = strtolower(trim((string)($user->role ?? '')));

        return [
            'id' => $user->id,
            'first_name' => MembershipProfile::displayName($user),
            'email' => (string)($user->email ?? ''),
            'phone' => (string)($user->phone ?? ''),
            'role' => $roleKey !== '' ? AppRoles::label($roleKey) : '',
            'country' => AdminCountry::label((int)($user->country_id ?? 0)),
            'club' => $clubName,
            'active' => (bool)$user->active,
            'enabled' => (int)($user->enabled ?? 0) === 1,
            MembershipProfile::FIELD_JOINED => $user->get(MembershipProfile::FIELD_JOINED)
                ? LocaleDateParser::format($user->get(MembershipProfile::FIELD_JOINED), 'date')
                : '',
            MembershipFee::FIELD_CLUB => $user->get(MembershipFee::FIELD_CLUB)
                ? LocaleDateParser::format($user->get(MembershipFee::FIELD_CLUB), 'date')
                : '',
            MembershipFee::FIELD_NATIONAL => $user->get(MembershipFee::FIELD_NATIONAL)
                ? LocaleDateParser::format($user->get(MembershipFee::FIELD_NATIONAL), 'date')
                : '',
            'created' => $user->created ? LocaleDateParser::format($user->created, 'datetime_short') : '',
            'modified' => $user->modified ? LocaleDateParser::format($user->modified, 'datetime_short') : '',
            'can_delete' => $this->Users->canDelete($user),
        ];
    }

    /**
     * @param \Cake\Datasource\EntityInterface $club
     * @return array<string, mixed>
     */
    protected function clubRecordPayload(object $club): array
    {
        /** @var \App\Model\Table\ClubsTable $clubsTable */
        $clubsTable = $this->fetchTable('Clubs');

        return [
            'id' => $club->id,
            'name' => (string)($club->name ?? ''),
            'short_name' => (string)($club->short_name ?? ''),
            'country' => AdminCountry::label((int)($club->country_id ?? 0)),
            'city' => $club->city !== null ? (string)$club->city->name : '',
            'enabled' => (bool)$club->enabled,
            'visible' => (bool)$club->visible,
            'user_count' => (int)($club->user_count ?? 0),
            'created' => $club->created ? LocaleDateParser::format($club->created, 'datetime_short') : '',
            'modified' => $club->modified ? LocaleDateParser::format($club->modified, 'datetime_short') : '',
            'can_delete' => $clubsTable->canDelete($club),
        ];
    }

    /**
     * @param \Cake\Datasource\EntityInterface $application
     * @return array<string, mixed>
     */
    protected function applicationRecordPayload(object $application): array
    {
        $competitionName = (string)($application->competition->name ?? '');
        $teamName = '';
        if ($application->competitions_club !== null && $application->competitions_club->subclub !== null) {
            $teamName = (string)$application->competitions_club->subclub->name;
        }
        $clubName = '';
        if ($application->competitions_club !== null && $application->competitions_club->club !== null) {
            $clubName = (string)$application->competitions_club->club->name;
        }

        return [
            'id' => $application->id,
            'competition' => $competitionName,
            'club' => $clubName,
            'team' => $teamName !== '' ? $teamName : '—',
            'status' => CompetitionApplication::statusLabel((string)($application->status ?? '')),
            'created' => $application->created
                ? LocaleDateParser::format($application->created, 'datetime_short')
                : '',
            'modified' => $application->modified
                ? LocaleDateParser::format($application->modified, 'datetime_short')
                : '',
            'can_delete' => false,
        ];
    }

    protected function jsonRecordResponse(array $record): Response
    {
        return $this->response
            ->withType('application/json')
            ->withStringBody((string)json_encode([
                'success' => true,
                'record' => $record,
            ], JSON_UNESCAPED_UNICODE));
    }

    protected function jsonRecordNotFound(): Response
    {
        return $this->response
            ->withStatus(404)
            ->withType('application/json')
            ->withStringBody((string)json_encode([
                'success' => false,
                'message' => __('Record not found.'),
            ], JSON_UNESCAPED_UNICODE));
    }
}
