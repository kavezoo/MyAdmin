<?php
declare(strict_types=1);

namespace App\Controller\Clubpresident;

use App\Auth\AppRoles;
use App\Auth\MembershipProfile;

/**
 * Club president panel dashboard.
 */
class DashboardController extends AppController
{
    public function index(): void
    {
        $clubId = $this->presidentClubId();
        $pendingApplicantsCount = 0;

        if ($clubId > 0) {
            /** @var \App\Model\Table\UsersTable $users */
            $users = $this->fetchTable('Users');
            $pendingApplicantsCount = $this->scopeToPresidentClub(
                $users->find()->where([
                    'Users.role' => AppRoles::NEW,
                    'Users.membership_status' => MembershipProfile::STATUS_PENDING,
                    'Users.active' => 1,
                    'Users.enabled' => 1,
                ])
            )->count();
        }

        $this->set('title', __('Dashboard'));
        $this->set('breadcrumb', __('Dashboard'));
        $this->set(compact('clubId', 'pendingApplicantsCount'));
    }
}
