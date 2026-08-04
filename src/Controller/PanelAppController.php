<?php
declare(strict_types=1);

namespace App\Controller;

use App\Auth\RoleHome;
use App\Utility\AdminCountry;
use App\Utility\BrowserLocale;
use Cake\Core\Configure;
use Cake\I18n\I18n;

/**
 * Shared chrome for role panels (New, Member, Clubpresident, President):
 * same layout as Admin (header / sidebar / breadcrumb / content).
 *
 * Locale: login session/cookie language, then Users.country_id fallback.
 */
abstract class PanelAppController extends AppController
{
    public function initialize(): void
    {
        parent::initialize();

        $request = $this->getRequest();
        $locale = BrowserLocale::forLoggedIn($request, $request->getAttribute('identity'));
        I18n::setLocale($locale);
        Configure::write('App.defaultLocale', $locale);
        BrowserLocale::remember($request, $locale);
        AdminCountry::applyTranslateLocale($locale);

        $prefix = (string)$request->getParam('prefix');
        $this->viewBuilder()->setLayout('admin');
        $this->set('panelPrefix', $prefix);
        $this->set('panelBrand', RoleHome::brand($prefix));
        $this->set('panelSidebar', RoleHome::sidebarElement($prefix));
        $this->set('panelHomeUrl', [
            'prefix' => $prefix,
            'controller' => 'Dashboard',
            'action' => 'index',
        ]);
        // Panels start without CRUD toolbar actions until content is defined.
        $this->set('canAdd', false);
        $this->set('canEdit', false);
        $this->set('canDelete', false);
    }
}
