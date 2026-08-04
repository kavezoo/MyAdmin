<?php
declare(strict_types=1);

namespace App\Controller;

use App\Auth\AppRoles;
use App\Auth\CurrentUser;
use App\Auth\EventLogAccess;
use App\Auth\MembershipProfile;
use App\Auth\RoleHome;
use App\Service\MembershipService;
use App\Utility\AdminCountry;
use App\Utility\AdminLanguage;
use App\Utility\BrowserLocale;
use Cake\Core\Configure;
use Cake\Event\EventInterface;
use Cake\Http\Exception\ForbiddenException;
use Cake\Http\Exception\NotFoundException;
use Cake\Http\Response;
use Cake\I18n\I18n;
use Cake\Routing\Router;
use CakeDC\Users\Controller\UsersController as CakeDCUsersController;
use CakeDC\Users\Utility\UsersUrl;

/**
 * App Users controller — login layout + country / locale on auth screens.
 */
class UsersController extends CakeDCUsersController
{
    /**
     * Auth screens that use templates/layout/login.php
     *
     * @var list<string>
     */
    protected const AUTH_LAYOUT_ACTIONS = [
        'login',
        'register',
        'requestResetPassword',
        'requestLoginLink',
        'changePassword',
        'resendTokenValidation',
        'socialEmail',
        'singleTokenLogin',
        'verify',
    ];

    /**
     * Actions with country Select2 (registration — account country).
     *
     * @var list<string>
     */
    protected const COUNTRY_SELECT_ACTIONS = [
        'register',
    ];

    /**
     * Actions with language Select2 (login UI language).
     *
     * @var list<string>
     */
    protected const LANGUAGE_SELECT_ACTIONS = [
        'login',
    ];

    public function beforeFilter(EventInterface $event): void
    {
        parent::beforeFilter($event);

        $action = (string)$this->getRequest()->getParam('action');
        if (in_array($action, self::AUTH_LAYOUT_ACTIONS, true)) {
            $this->viewBuilder()->setLayout('login');
        } elseif (in_array($action, ['profile', 'completeProfile', 'eventLog', 'eventLogView'], true)) {
            $this->viewBuilder()->setLayout('admin');
            $this->applyLoggedInUiLocale();
            $role = CurrentUser::role($this->getRequest());
            $home = RoleHome::url($role);
            $prefix = (string)($home['prefix'] ?? 'Admin');
            $this->set('panelPrefix', $prefix);
            $this->set('panelBrand', RoleHome::brand($prefix));
            $this->set('panelSidebar', RoleHome::sidebarElement($prefix));
            $this->set('panelHomeUrl', $home);
            $this->set('indexListUrl', $home);
            $this->set('breadcrumbBackLabel', __('Dashboard'));
            $this->set('canAdd', false);
            $this->set('canEdit', false);
            $this->set('canDelete', false);
            if ($action === 'profile') {
                $this->set('breadcrumb', __('Profile'));
            } elseif ($action === 'completeProfile') {
                $this->set('breadcrumb', __('Complete your profile'));
            } else {
                $this->set('breadcrumb', __('My event log'));
                $this->set('indexListUrl', UsersUrl::actionUrl('eventLog'));
            }
        }

        if (in_array($action, self::LANGUAGE_SELECT_ACTIONS, true)) {
            $this->applyGuestLanguageLocale();
        } elseif (in_array($action, self::COUNTRY_SELECT_ACTIONS, true)) {
            $this->applyGuestCountryLocale();
        }
    }

    public function beforeRender(EventInterface $event): void
    {
        parent::beforeRender($event);

        $action = (string)$this->getRequest()->getParam('action');
        if (in_array($action, self::LANGUAGE_SELECT_ACTIONS, true)) {
            $uiLocale = I18n::getLocale();
            AdminCountry::applyTranslateLocale($uiLocale);
            $this->set('languageOptions', AdminLanguage::options($uiLocale));
            $this->set('selectedLocale', BrowserLocale::canonicalize($uiLocale) ?? $uiLocale);
            $this->set('selectedLanguageLabel', AdminLanguage::displayName($uiLocale, $uiLocale));
        }
        if (in_array($action, self::COUNTRY_SELECT_ACTIONS, true)) {
            $uiLocale = I18n::getLocale();
            AdminCountry::applyTranslateLocale($uiLocale);
            $this->set('countryOptions', AdminCountry::optionsWithLocale($uiLocale));
            $this->set('countryLocales', AdminCountry::localeMap());
            $this->set('selectedCountryId', $this->resolveGuestCountryId());
        }
        if ($action === 'profile') {
            $this->prepareProfileViewVars();
        }
        if ($action === 'completeProfile') {
            $this->prepareCompleteProfileViewVars();
        }
    }

    public function afterFilter(EventInterface $event): void
    {
        parent::afterFilter($event);

        $action = (string)$this->getRequest()->getParam('action');
        $response = $this->getResponse();

        if (in_array($action, self::LANGUAGE_SELECT_ACTIONS, true)) {
            $locale = $this->explicitLocale();
            if ($locale !== null) {
                $response = BrowserLocale::persist($this->getRequest(), $response, $locale);
                $this->setResponse($response);
            }

            return;
        }

        if (!in_array($action, self::COUNTRY_SELECT_ACTIONS, true)) {
            return;
        }

        $countryId = $this->explicitCountryId();
        if ($countryId < 1) {
            return;
        }

        $response = AdminCountry::set($countryId, $this->getRequest(), $response);
        $locale = AdminCountry::localeForCountry($countryId);
        if ($locale !== null) {
            $response = BrowserLocale::persist($this->getRequest(), $response, $locale);
        }
        $this->setResponse($response);
    }

    /**
     * @return \Cake\Http\Response|null
     */
    public function login()
    {
        $result = parent::login();

        if (!$result instanceof Response) {
            return $result;
        }

        $identity = $this->getRequest()->getAttribute('identity');
        if ($identity === null) {
            return $result;
        }

        return $this->applyStoredUserLocalePreferences($identity, $result);
    }

    /**
     * @return \Cake\Http\Response|null
     */
    public function register()
    {
        $rememberCountryId = $this->explicitCountryId();

        if ($this->getRequest()->is('post')) {
            $data = $this->getUsersTable()->normalizeRegistrationData(
                (array)$this->getRequest()->getData()
            );
            $this->setRequest($this->getRequest()->withParsedBody($data));
            if (!empty($data['country_id'])) {
                $rememberCountryId = (int)$data['country_id'];
            }
        }

        $result = parent::register();

        if ($rememberCountryId > 0) {
            $response = $result instanceof Response
                ? $result
                : $this->getResponse();
            $response = AdminCountry::set($rememberCountryId, $this->getRequest(), $response);
            $locale = AdminCountry::localeForCountry($rememberCountryId);
            if ($locale !== null) {
                $this->applyUiLocale($locale);
                $response = BrowserLocale::persist($this->getRequest(), $response, $locale);
            }
            if ($result instanceof Response) {
                return $response;
            }
            $this->setResponse($response);
        }

        return $result;
    }

    /**
     * Mandatory profile form for role `new` after registration.
     *
     * @return \Cake\Http\Response|null|void
     */
    public function completeProfile()
    {
        $identity = $this->getRequest()->getAttribute('identity');
        if ($identity === null) {
            throw new ForbiddenException(__('You must be logged in.'));
        }

        $userId = '';
        if (method_exists($identity, 'getIdentifier')) {
            $userId = (string)$identity->getIdentifier();
        } elseif (method_exists($identity, 'get')) {
            $userId = (string)($identity->get('id') ?? '');
        }
        if ($userId === '') {
            throw new ForbiddenException(__('You must be logged in.'));
        }

        /** @var \App\Model\Table\UsersTable $users */
        $users = $this->getUsersTable();
        $user = $users->get($userId);
        $role = strtolower(trim((string)$user->get('role')));
        if ($role !== AppRoles::NEW) {
            return $this->redirect(RoleHome::url($role));
        }
        if (MembershipProfile::isComplete($user)) {
            return $this->redirect(RoleHome::url(AppRoles::NEW));
        }

        if ($this->getRequest()->is(['post', 'put', 'patch'])) {
            $data = (array)$this->getRequest()->getData();
            $allowed = ['first_name', 'last_name', 'phone', 'country_id', 'club_id'];
            $patch = [];
            foreach ($allowed as $field) {
                if (array_key_exists($field, $data)) {
                    $patch[$field] = $data[$field];
                }
            }
            if (isset($patch['country_id'])) {
                $patch['country_id'] = (int)$patch['country_id'];
            }
            if (isset($patch['club_id'])) {
                $patch['club_id'] = (int)$patch['club_id'];
            }

            $user = $users->patchEntity($user, $patch, [
                'validate' => 'profileComplete',
                'fields' => $allowed,
            ]);

            if (!$user->hasErrors() && $users->save($user)) {
                (new MembershipService())->onProfileCompleted($user);
                $fresh = $users->get($userId);
                if ($this->components()->has('Authentication')) {
                    $this->Authentication->setIdentity($fresh);
                }
                $this->Flash->success(__('Your profile has been saved. The club president will review your application.'));

                return $this->redirect(RoleHome::url(AppRoles::NEW));
            }
            $this->Flash->error(__('Please correct the errors below.'));
        }

        $this->set('title', __('Complete your profile'));
        $this->set(compact('user'));
        $this->set('canEdit', false);
        $this->set('canAdd', false);
        $this->set('canDelete', false);
    }

    /**
     * Current user's own event log (all roles).
     *
     * @return \Cake\Http\Response|null|void
     */
    public function eventLog()
    {
        if (!EventLogAccess::canViewOwn($this->getRequest())) {
            throw new ForbiddenException(__('You must be logged in to view your event log.'));
        }

        $identity = $this->getRequest()->getAttribute('identity');
        $userId = '';
        if (is_object($identity) && method_exists($identity, 'getIdentifier')) {
            $userId = (string)$identity->getIdentifier();
        } elseif (is_object($identity) && method_exists($identity, 'get')) {
            $userId = (string)($identity->get('id') ?? '');
        }
        if ($userId === '') {
            throw new ForbiddenException(__('You must be logged in to view your event log.'));
        }

        $this->loadComponent('Paginator');
        /** @var \App\Model\Table\EventLogsTable $eventLogs */
        $eventLogs = $this->fetchTable('EventLogs');
        $query = $eventLogs->find()
            ->contain(['Countries'])
            ->where(['EventLogs.user_id' => $userId])
            ->orderBy(['EventLogs.created' => 'DESC', 'EventLogs.id' => 'DESC']);

        $q = trim((string)$this->getRequest()->getQuery('q'));
        if ($q !== '') {
            $like = '%' . str_replace(['%', '_'], ['\\%', '\\_'], $q) . '%';
            $query->where([
                'OR' => [
                    'EventLogs.description LIKE' => $like,
                    'EventLogs.module LIKE' => $like,
                    'EventLogs.action LIKE' => $like,
                    'EventLogs.url LIKE' => $like,
                    'EventLogs.ip LIKE' => $like,
                ],
            ]);
        }

        $this->set('title', __('My event log'));
        $this->set('eventLogs', $this->paginate($query, [
            'limit' => 50,
            'maxLimit' => 200,
        ]));
        $this->set('searchQ', $q);
        $this->set('canSearchAll', EventLogAccess::canSearch($this->getRequest()));
    }

    /**
     * Own event detail.
     *
     * @param string|null $id
     * @return \Cake\Http\Response|null|void
     */
    public function eventLogView(?string $id = null)
    {
        /** @var \App\Model\Table\EventLogsTable $eventLogs */
        $eventLogs = $this->fetchTable('EventLogs');
        $eventLog = $eventLogs->find()
            ->contain(['Countries', 'Users'])
            ->where(['EventLogs.id' => (int)$id])
            ->first();
        if ($eventLog === null) {
            throw new NotFoundException(__('Record not found.'));
        }
        if (!EventLogAccess::canView($eventLog, $this->getRequest())) {
            throw new ForbiddenException(__('You are not allowed to view this event.'));
        }

        $this->set('title', __('Event log'));
        $this->set(compact('eventLog'));
        $this->set('isOwnEventLog', true);
        $this->render('/Admin/EventLogs/view');
    }

    /**
     * After login: keep the login-screen language (session/cookie); country_id only for AdminCountry scope.
     *
     * @param mixed $user
     */
    protected function applyStoredUserLocalePreferences(mixed $user, Response $response): Response
    {
        $locale = BrowserLocale::forLoggedIn($this->getRequest(), $user);

        $countryId = 0;
        if (is_object($user) && method_exists($user, 'get')) {
            $countryId = (int)($user->get('country_id') ?? 0);
        } elseif (is_array($user)) {
            $countryId = (int)($user['country_id'] ?? 0);
        }

        $this->applyUiLocale($locale);
        $response = BrowserLocale::persist($this->getRequest(), $response, $locale);

        if ($countryId > 0) {
            $response = AdminCountry::set($countryId, $this->getRequest(), $response);
        }

        return $response;
    }

    protected function prepareProfileViewVars(): void
    {
        /** @var \CakeDC\Users\Model\Entity\User|null $user */
        $user = $this->viewBuilder()->getVar('user');
        // Always re-read is_superuser from DB (session/entity cast must not invent a badge).
        if ($user !== null && $user->get('id')) {
            try {
                $row = $this->getUsersTable()->find()
                    ->select(['is_superuser'])
                    ->where(['Users.id' => $user->get('id')])
                    ->disableHydration()
                    ->first();
                if (is_array($row) && array_key_exists('is_superuser', $row)) {
                    $user->set('is_superuser', $row['is_superuser']);
                    $user->setDirty('is_superuser', false);
                }
            } catch (\Throwable $e) {
                // keep loaded entity
            }
        }
        $countryLabel = '';
        $countryId = $user !== null ? (int)($user->get('country_id') ?? 0) : 0;
        if ($countryId > 0) {
            $countryLabel = AdminCountry::optionsWithLocale()[$countryId]
                ?? AdminCountry::label($countryId);
        }
        $this->set('countryLabel', $countryLabel);
    }

    protected function prepareCompleteProfileViewVars(): void
    {
        /** @var \CakeDC\Users\Model\Entity\User|null $user */
        $user = $this->viewBuilder()->getVar('user');
        $countryId = 0;
        if ($user !== null) {
            $countryId = (int)($user->get('country_id') ?? 0);
        }
        $explicit = $this->getRequest()->getQuery('country_id');
        if (is_numeric($explicit) && (int)$explicit > 0) {
            $countryId = (int)$explicit;
            if ($user !== null) {
                $user->set('country_id', $countryId);
                $user->set('club_id', 0);
            }
        }

        $uiLocale = I18n::getLocale();
        AdminCountry::applyTranslateLocale($uiLocale);
        /** @var \App\Model\Table\ClubsTable $clubs */
        $clubs = $this->fetchTable('Clubs');

        $this->set('countryOptions', AdminCountry::optionsWithLocale($uiLocale));
        $this->set('clubOptions', $clubs->optionsForCountry($countryId));
        $this->set('selectedCountryId', $countryId);
        $this->set('completeProfileUrl', Router::url(UsersUrl::actionUrl('completeProfile')));
    }

    /**
     * Login: UI locale from ?locale= → session/cookie → browser Accept-Language.
     */
    protected function applyGuestLanguageLocale(): void
    {
        $explicit = $this->explicitLocale();
        if ($explicit !== null) {
            $this->applyUiLocale($explicit);

            return;
        }

        $this->applyUiLocale(BrowserLocale::resolve($this->getRequest()));
    }

    /**
     * Register: UI locale follows the country shown in the select (explicit or remembered).
     */
    protected function applyGuestCountryLocale(): void
    {
        $countryId = $this->resolveGuestCountryId();
        if ($countryId < 1) {
            $this->applyUiLocale(BrowserLocale::resolve($this->getRequest()));

            return;
        }
        $locale = AdminCountry::localeForCountry($countryId);
        if ($locale === null) {
            $this->applyUiLocale(BrowserLocale::resolve($this->getRequest()));

            return;
        }
        $this->applyUiLocale($locale);
    }

    /**
     * Profile / logged-in Users actions: same locale rules as Admin.
     */
    protected function applyLoggedInUiLocale(): void
    {
        $request = $this->getRequest();
        $locale = BrowserLocale::forLoggedIn($request, $request->getAttribute('identity'));
        $this->applyUiLocale($locale);
    }

    protected function applyUiLocale(string $locale): void
    {
        I18n::setLocale($locale);
        Configure::write('App.defaultLocale', $locale);
        BrowserLocale::remember($this->getRequest(), $locale);
        AdminCountry::applyTranslateLocale($locale);
    }

    /**
     * Country for the select default: explicit → cookie/session → HU.
     */
    protected function resolveGuestCountryId(): int
    {
        $explicit = $this->explicitCountryId();
        if ($explicit > 0) {
            return $explicit;
        }

        return AdminCountry::id($this->getRequest());
    }

    /**
     * Country only if present in POST or ?country_id=.
     */
    protected function explicitCountryId(): int
    {
        $fromData = $this->getRequest()->getData('country_id');
        if (is_numeric($fromData) && (int)$fromData > 0) {
            return (int)$fromData;
        }
        $fromQuery = $this->getRequest()->getQuery('country_id');
        if (is_numeric($fromQuery) && (int)$fromQuery > 0) {
            return (int)$fromQuery;
        }

        return 0;
    }

    /**
     * Locale from ?locale= / POST locale when valid against available languages.
     */
    protected function explicitLocale(): ?string
    {
        $raw = $this->getRequest()->getQuery('locale');
        if (!is_string($raw) || trim($raw) === '') {
            $raw = $this->getRequest()->getData('locale');
        }
        if (!is_string($raw) || trim($raw) === '') {
            return null;
        }

        return BrowserLocale::canonicalize(trim($raw));
    }
}
