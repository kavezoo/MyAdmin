<?php
declare(strict_types=1);

namespace App\Controller\Clubpresident;

use App\Auth\AppRoles;
use App\Auth\MembershipProfile;
use App\Utility\CompetitionApplication;
use App\Utility\CompetitionBrowse;

/**
 * Club president panel dashboard.
 */
class DashboardController extends AppController
{
    public function index(): void
    {
        $clubId = $this->presidentClubId();
        $pendingApplicantsCount = 0;
        $pendingCompetitionApplicantsCount = 0;

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

            $memberIds = $users->find()
                ->select(['id'])
                ->where(['Users.club_id' => $clubId])
                ->all()
                ->extract('id')
                ->toList();

            if ($memberIds !== []) {
                /** @var \App\Model\Table\CompetitionsUsersTable $competitionsUsers */
                $competitionsUsers = $this->fetchTable('CompetitionsUsers');
                $pendingCompetitionApplicantsCount = $competitionsUsers->find()
                    ->innerJoinWith('Competitions')
                    ->where([
                        'CompetitionsUsers.user_id IN' => $memberIds,
                        'CompetitionsUsers.status' => CompetitionApplication::STATUS_PENDING,
                    ])
                    ->where(CompetitionBrowse::activeConditions())
                    ->count();
            }
        }

        $this->set('title', __('Dashboard'));
        $this->set('breadcrumb', __('Dashboard'));
        $this->set(compact(
            'clubId',
            'pendingApplicantsCount',
            'pendingCompetitionApplicantsCount'
        ));
    }
}
