<?php
declare(strict_types=1);

namespace App\Controller\Clubpresident;

use App\Auth\AppRoles;
use App\Auth\MembershipProfile;
use App\Service\MembershipService;
use Cake\Http\Exception\ForbiddenException;
use Cake\Http\Exception\NotFoundException;
use Cake\Http\Response;

/**
 * Membership applicants for the club president's club.
 */
class ApplicantsController extends AppController
{
    /**
     * Pending applicants for this club.
     *
     * @return \Cake\Http\Response|null|void
     */
    public function index()
    {
        $clubId = $this->presidentClubId();
        if ($clubId < 1) {
            $this->Flash->warning(__('Your account is not assigned to a club yet. Contact an administrator.'));
            $this->set('applicants', []);
            $this->set('title', __('Applicants'));
            $this->set('breadcrumb', __('Applicants'));
            $this->set('clubName', '');

            return;
        }

        /** @var \App\Model\Table\UsersTable $users */
        $users = $this->fetchTable('Users');
        $query = $users->find()
            ->where([
                'Users.role' => AppRoles::NEW,
                'Users.club_id' => $clubId,
                'Users.membership_status' => MembershipProfile::STATUS_PENDING,
                'Users.active' => 1,
            ])
            ->orderBy(['Users.modified' => 'DESC', 'Users.created' => 'DESC']);

        $clubName = '';
        /** @var \App\Model\Table\ClubsTable $clubs */
        $clubs = $this->fetchTable('Clubs');
        $club = $clubs->find()->select(['name'])->where(['Clubs.id' => $clubId])->first();
        if ($club !== null) {
            $clubName = (string)$club->get('name');
        }

        $this->set('title', __('Applicants'));
        $this->set('breadcrumb', __('Applicants'));
        $this->set('applicants', $this->paginate($query, [
            'limit' => 50,
            'maxLimit' => 200,
        ]));
        $this->set(compact('clubName', 'clubId'));
    }

    /**
     * Approve applicant → member role + email.
     *
     * @param string|null $id User id
     * @return \Cake\Http\Response|null
     */
    public function approve(?string $id = null): ?Response
    {
        $this->request->allowMethod(['post', 'put', 'patch']);
        $clubId = $this->presidentClubId();
        if ($clubId < 1) {
            throw new ForbiddenException(__('Your account is not assigned to a club yet.'));
        }

        /** @var \App\Model\Table\UsersTable $users */
        $users = $this->fetchTable('Users');
        $applicant = $users->find()
            ->where([
                'Users.id' => (string)$id,
                'Users.role' => AppRoles::NEW,
                'Users.club_id' => $clubId,
                'Users.membership_status' => MembershipProfile::STATUS_PENDING,
            ])
            ->first();
        if ($applicant === null) {
            throw new NotFoundException(__('Applicant not found.'));
        }

        $approverId = '';
        $identity = $this->getRequest()->getAttribute('identity');
        if ($identity !== null && method_exists($identity, 'getIdentifier')) {
            $approverId = (string)$identity->getIdentifier();
        } elseif ($identity !== null && method_exists($identity, 'get')) {
            $approverId = (string)($identity->get('id') ?? '');
        }
        if ($approverId === '') {
            throw new ForbiddenException(__('You must be logged in.'));
        }
        $approver = $users->get($approverId);

        $ok = (new MembershipService())->approve($applicant, $approver);
        if ($ok) {
            $this->Flash->success(__('Membership approved. The new member has been notified by email.'));
        } else {
            $this->Flash->error(__('Could not approve this application.'));
        }

        return $this->redirect(['action' => 'index']);
    }

    protected function presidentClubId(): int
    {
        $identity = $this->getRequest()->getAttribute('identity');
        if ($identity === null) {
            return 0;
        }
        if (method_exists($identity, 'get')) {
            return (int)($identity->get('club_id') ?? 0);
        }

        return 0;
    }
}
