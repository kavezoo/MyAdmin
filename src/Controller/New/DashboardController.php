<?php
declare(strict_types=1);

namespace App\Controller\New;

use App\Auth\MembershipProfile;

/**
 * New panel dashboard — waiting state after profile submitted.
 */
class DashboardController extends AppController
{
    /**
     * @return \Cake\Http\Response|null|void
     */
    public function index()
    {
        $pending = false;
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
                ->select(['id', 'membership_status', 'role'])
                ->where(['Users.id' => $userId])
                ->first();
            if ($user !== null) {
                $pending = MembershipProfile::isPending($user);
            }
        }

        $this->set('title', __('Dashboard'));
        $this->set(compact('pending'));
    }
}
