<?php
declare(strict_types=1);

namespace App\Controller;

use App\Auth\PanelAccess;
use App\Auth\RoleHome;
use App\Utility\AdminCountry;
use App\Utility\BrowserLocale;
use App\Utility\MembershipFee;
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
        $session = $request->getSession();
        if ($prefix !== '' && $session !== null) {
            $session->write('Panel.lastPrefix', $prefix);
        }
        $this->viewBuilder()->setLayout('admin');
        $this->set('panelPrefix', $prefix);
        $this->set('panelBrand', RoleHome::brand($prefix));
        $this->set('panelSidebar', RoleHome::sidebarElement($prefix));
        $this->set('panelHomeUrl', [
            'prefix' => $prefix,
            'controller' => 'Dashboard',
            'action' => 'index',
        ]);
        $this->set('panelSwitcherLinks', PanelAccess::switcherLinks($prefix, $request));
        // Panels start without CRUD toolbar actions until content is defined.
        $this->set('canAdd', false);
        $this->set('canEdit', false);
        $this->set('canDelete', false);

        $membershipYear = MembershipFee::currentYear();
        $clubFeeUnpaid = false;
        $identity = $request->getAttribute('identity');
        if ($identity !== null) {
            $userId = '';
            if (method_exists($identity, 'getIdentifier')) {
                $userId = (string)$identity->getIdentifier();
            } elseif (method_exists($identity, 'get')) {
                $userId = (string)($identity->get('id') ?? '');
            }
            if ($userId !== '') {
                try {
                    /** @var \App\Model\Table\UsersTable $users */
                    $users = $this->fetchTable('Users');
                    $feeUser = $users->find()
                        ->select(['id', 'club_id', MembershipFee::FIELD_CLUB])
                        ->where(['Users.id' => $userId])
                        ->first();
                    if ($feeUser !== null) {
                        $clubFeeUnpaid = MembershipFee::isClubFeeUnpaid($feeUser, $membershipYear);
                    }
                } catch (\Throwable) {
                    $clubFeeUnpaid = false;
                }
            }
        }
        $this->set(compact('membershipYear', 'clubFeeUnpaid'));
    }
}
