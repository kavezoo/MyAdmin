<?php
declare(strict_types=1);

namespace App\Controller\New;

use App\Auth\MembershipProfile;
use App\Service\MembershipService;

/**
 * New panel dashboard — complete profile CTA, or waiting after profile submitted.
 */
class DashboardController extends AppController
{
    /**
     * @return \Cake\Http\Response|null|void
     */
    public function index()
    {
        $needsCompletion = false;
        $waiting = false;
        $missingFields = [];
        $identity = $this->getRequest()->getAttribute('identity');
        $userId = '';
        if ($identity !== null) {
            if (method_exists($identity, 'getIdentifier')) {
                $userId = (string)$identity->getIdentifier();
            } elseif (method_exists($identity, 'get')) {
                $userId = (string)($identity->get('id') ?? '');
            }
        }
        if ($userId !== '') {
            /** @var \App\Model\Table\UsersTable $users */
            $users = $this->fetchTable('Users');
            $user = $users->find()
                ->select([
                    'id',
                    'role',
                    'membership_status',
                    'first_name',
                    'country_id',
                    'club_id',
                    'application_notified',
                    'email',
                ])
                ->where(['Users.id' => $userId])
                ->first();
            if ($user !== null) {
                $needsCompletion = MembershipProfile::needsProfileCompletion($user);
                if ($needsCompletion) {
                    $missingFields = MembershipProfile::missingFieldLabels($user);
                } elseif (MembershipProfile::isWaitingForApproval($user)) {
                    // Name + club filled → waiting for club president (heal status if stuck on incomplete)
                    if (!MembershipProfile::isPending($user)) {
                        (new MembershipService())->onProfileCompleted($user);
                    }
                    $waiting = true;
                }
            }
        }

        $this->set('title', __('Dashboard'));
        $this->set(compact('needsCompletion', 'waiting', 'missingFields'));
    }
}
