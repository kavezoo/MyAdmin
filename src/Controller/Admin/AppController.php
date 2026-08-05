<?php
declare(strict_types=1);

namespace App\Controller\Admin;

use App\Auth\PanelAccess;
use App\Controller\AppController as BaseController;
use App\Controller\Concerns\IndexListCrudTrait;
use App\Utility\AdminCountry;
use App\Utility\BrowserLocale;
use Cake\Core\Configure;
use Cake\Datasource\EntityInterface;
use Cake\Event\EventInterface;
use Cake\Http\Response;
use Cake\I18n\I18n;
use Cake\ORM\Table;

/**
 * Admin Application Controller
 *
 * Shared base for controllers under the Admin prefix.
 * Locale: login-screen language (session/cookie), then Users.country_id → Countries.locale,
 * else App.adminLocale. No language switcher in the UI.
 */
class AppController extends BaseController
{
    use IndexListCrudTrait;

    /**
     * Session key: last worked-on Admin records (per Table alias + global `_last`).
     * Structure: ['Samples' => 12, 'Cities' => 3, '_last' => ['model' => 'Samples', 'id' => 12]]
     */
    protected const LAST_VISITED_SESSION_KEY = 'Admin.lastVisited';

    /**
     * Session: per-model index list state (sort, direction, page, q, limit).
     * Structure: Admin.indexState[Samples] = ['sort' => …, 'direction' => …, 'page' => 2, 'q' => '…']
     */
    protected const INDEX_STATE_SESSION_KEY = 'Admin.indexState';

    /**
     * @return void
     */
    public function initialize(): void
    {
        parent::initialize();

        $request = $this->getRequest();
        $locale = BrowserLocale::forLoggedIn($request, $request->getAttribute('identity'));
        I18n::setLocale($locale);
        Configure::write('App.defaultLocale', $locale);
        BrowserLocale::remember($request, $locale);
        AdminCountry::applyTranslateLocale($locale);

        $this->viewBuilder()->setLayout('admin');
        $this->set('panelPrefix', 'Admin');
        $this->set('panelBrand', __('Admin'));
        $this->set('panelSidebar', 'admin/sidebar');
        $this->set('panelHomeUrl', [
            'prefix' => 'Admin',
            'controller' => 'Dashboard',
            'action' => 'index',
        ]);
        $session = $request->getSession();
        if ($session !== null) {
            $session->write('Panel.lastPrefix', 'Admin');
        }
        $this->set('panelSwitcherLinks', PanelAccess::switcherLinks('Admin', $request));
        $this->set('registeredCountryExamples', AdminCountry::registeredCountryExamples($request));
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
     * Soft deny: toast warning + redirect (no CakePHP ForbiddenException error page).
     *
     * @param array<string, mixed> $redirect Default Admin dashboard
     */
    protected function denyWithFlashWarning(
        string $message,
        array $redirect = ['prefix' => 'Admin', 'controller' => 'Dashboard', 'action' => 'index']
    ): Response {
        $this->Flash->warning($message);

        return $this->redirect($redirect);
    }

    /**
     * Expose Back-to-list URL with restored index query (sort / page / search).
     *
     * @param \Cake\Event\EventInterface $event
     * @return void
     */
    public function beforeRender(EventInterface $event): void
    {
        parent::beforeRender($event);

        $controller = (string)$this->request->getParam('controller');
        if ($controller === '' || in_array($controller, ['Dashboard', 'Search'], true)) {
            return;
        }
        $this->set('indexListUrl', $this->indexListUrl($controller));
    }

    /**
     * Flash üzenet SweetAlert2 modallal (egyszerre egy; több sorban).
     *
     * Alapértelmezett Flash → Simple Notify toast (több is lehet).
     * SWAL: $this->flashSwal('success', __('…'));
     *
     * @param string $type success|error|warning|info|default
     * @param string $message
     * @param array<string, mixed> $options Flash options (key, params, …)
     * @return void
     */
    protected function flashSwal(string $type, string $message, array $options = []): void
    {
        $map = [
            'success' => 'flash/success_swal',
            'error' => 'flash/error_swal',
            'warning' => 'flash/warning_swal',
            'info' => 'flash/info_swal',
            'default' => 'flash/default_swal',
        ];
        $options['element'] = $map[$type] ?? $map['default'];
        if (in_array($type, ['success', 'error', 'warning', 'info'], true)) {
            $this->Flash->{$type}($message, $options);

            return;
        }
        $this->Flash->set($message, $options);
    }

    /**
     * Language tabs for translatable Admin forms.
     * Source: active country’s `country_visibilities` (own + additional languages).
     *
     * @return void
     */
    protected function setFormLanguageTabs(): void
    {
        $tabs = \App\Utility\FormLanguages::tabs();
        $this->set('formLanguageTabs', $tabs);
        $this->set('formDefaultLocale', \App\Utility\FormLanguages::defaultLocaleForForm());
    }

    /**
     * Load entity with all Translate EAV rows (edit form).
     * Root fields use the form default locale (own language, or en_GB when that tab exists).
     *
     * @param \Cake\ORM\Table $table
     * @param mixed $id
     * @param array<string, mixed>|list<string> $contain
     * @return \Cake\Datasource\EntityInterface
     */
    protected function getWithTranslations(Table $table, mixed $id, array $contain = []): EntityInterface
    {
        if (!$table->hasBehavior('Translate')) {
            return $table->get($id, contain: $contain);
        }

        $defaultLocale = \App\Utility\FormLanguages::defaultLocaleForForm();
        $table->getBehavior('Translate')->setLocale($defaultLocale);

        $pk = $table->aliasField($table->getPrimaryKey());
        $query = $table->find('translations')->where([$pk => $id]);
        if ($contain !== []) {
            $query->contain($contain);
        }

        /** @var \Cake\Datasource\EntityInterface $entity */
        $entity = $query->firstOrFail();

        return $entity;
    }
}
