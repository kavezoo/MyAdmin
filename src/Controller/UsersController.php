<?php
declare(strict_types=1);

namespace App\Controller;

use App\Auth\CurrentUser;
use App\Auth\RoleHome;
use App\Utility\AdminCountry;
use App\Utility\BrowserLocale;
use Cake\Core\Configure;
use Cake\Event\EventInterface;
use Cake\Http\Response;
use Cake\I18n\I18n;
use CakeDC\Users\Controller\UsersController as CakeDCUsersController;

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
     * Actions with country Select2 (guest language / registration).
     *
     * @var list<string>
     */
    protected const COUNTRY_SELECT_ACTIONS = [
        'login',
        'register',
    ];

    public function beforeFilter(EventInterface $event): void
    {
        parent::beforeFilter($event);

        $action = (string)$this->getRequest()->getParam('action');
        if (in_array($action, self::AUTH_LAYOUT_ACTIONS, true)) {
            $this->viewBuilder()->setLayout('login');
        } elseif ($action === 'profile') {
            $this->viewBuilder()->setLayout('admin');
            $this->applyLoggedInUiLocale();
            $role = CurrentUser::role($this->getRequest());
            $home = RoleHome::url($role);
            $prefix = (string)($home['prefix'] ?? 'Admin');
            $this->set('panelPrefix', $prefix);
            $this->set('panelBrand', RoleHome::brand($prefix));
            $this->set('panelSidebar', RoleHome::sidebarElement($prefix));
            $this->set('panelHomeUrl', $home);
            $this->set('breadcrumb', __('Profile'));
            $this->set('indexListUrl', $home);
            $this->set('breadcrumbBackLabel', __('Dashboard'));
            $this->set('canAdd', false);
            $this->set('canEdit', false);
            $this->set('canDelete', false);
        }

        if (in_array($action, self::COUNTRY_SELECT_ACTIONS, true)) {
            $this->applyGuestCountryLocale();
        }
    }

    public function beforeRender(EventInterface $event): void
    {
        parent::beforeRender($event);

        $action = (string)$this->getRequest()->getParam('action');
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
    }

    public function afterFilter(EventInterface $event): void
    {
        parent::afterFilter($event);

        $action = (string)$this->getRequest()->getParam('action');
        if (!in_array($action, self::COUNTRY_SELECT_ACTIONS, true)) {
            return;
        }

        $countryId = $this->explicitCountryId();
        if ($countryId < 1) {
            return;
        }

        $response = $this->getResponse();
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
     * After login: account country locale when set, otherwise keep the login-screen language.
     * Persist so the whole Admin UI uses the same locale.
     *
     * @param mixed $user
     */
    protected function applyStoredUserLocalePreferences(mixed $user, Response $response): Response
    {
        $locale = BrowserLocale::localeFromUser($user);
        $countryId = 0;
        if (is_object($user) && method_exists($user, 'get')) {
            $countryId = (int)($user->get('country_id') ?? 0);
        } elseif (is_array($user)) {
            $countryId = (int)($user['country_id'] ?? 0);
        }

        if ($locale === null) {
            $locale = BrowserLocale::forLoggedIn($this->getRequest(), $user);
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
        $countryLabel = '';
        $countryId = $user !== null ? (int)($user->get('country_id') ?? 0) : 0;
        if ($countryId > 0) {
            $countryLabel = AdminCountry::optionsWithLocale()[$countryId]
                ?? AdminCountry::label($countryId);
        }
        $this->set('countryLabel', $countryLabel);
    }

    /**
     * Auth screens: UI locale follows the country shown in the select (explicit or remembered).
     */
    protected function applyGuestCountryLocale(): void
    {
        $countryId = $this->resolveGuestCountryId();
        if ($countryId < 1) {
            return;
        }
        $locale = AdminCountry::localeForCountry($countryId);
        if ($locale === null) {
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
}
