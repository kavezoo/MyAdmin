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
    private const APPLICANTS_ENABLED_ONLY_SESSION_KEY = 'Clubpresident.Applicants.enabled_only';

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
            $this->set('applicants', $this->emptyPaginated());
            $this->set('title', __('Applicants'));
            $this->set('breadcrumb', __('Applicants'));
            $this->set('clubName', '');

            return;
        }

        /** @var \App\Model\Table\UsersTable $users */
        $users = $this->fetchTable('Users');
        $applicantsEnabledOnly = $this->resolveApplicantsEnabledOnly();
        $where = [
            'Users.role' => AppRoles::NEW,
            'Users.club_id' => $clubId,
            'Users.membership_status' => MembershipProfile::STATUS_PENDING,
            'Users.active' => 1,
        ];
        if ($applicantsEnabledOnly) {
            $where['Users.enabled'] = 1;
        }
        $query = $users->find()
            ->where($where)
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
        $this->set(compact('clubName', 'clubId', 'applicantsEnabledOnly'));
    }

    /**
     * Index filter: only `enabled=1` applicants (default) or all pending including rejected.
     *
     * Query `enabled_only=1|0` → session; otherwise last session value; first visit → true.
     */
    protected function resolveApplicantsEnabledOnly(): bool
    {
        $session = $this->request->getSession();
        $query = $this->request->getQueryParams();

        if (array_key_exists('enabled_only', $query)) {
            $raw = $query['enabled_only'];
            if (is_array($raw)) {
                $raw = end($raw);
            }
            $enabledOnly = in_array((string)$raw, ['1', 'true', 'on'], true);
            $session->write(self::APPLICANTS_ENABLED_ONLY_SESSION_KEY, $enabledOnly);

            return $enabledOnly;
        }

        $saved = $session->read(self::APPLICANTS_ENABLED_ONLY_SESSION_KEY);
        if ($saved === null) {
            return true;
        }

        return (bool)$saved;
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
                'Users.enabled' => 1,
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

    /**
     * Reject applicant → users.enabled = false (cannot log in again).
     *
     * @param string|null $id User id
     * @return \Cake\Http\Response|null
     */
    public function reject(?string $id = null): ?Response
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
                'Users.enabled' => 1,
            ])
            ->first();
        if ($applicant === null) {
            throw new NotFoundException(__('Applicant not found.'));
        }

        $rejecterId = '';
        $identity = $this->getRequest()->getAttribute('identity');
        if ($identity !== null && method_exists($identity, 'getIdentifier')) {
            $rejecterId = (string)$identity->getIdentifier();
        } elseif ($identity !== null && method_exists($identity, 'get')) {
            $rejecterId = (string)($identity->get('id') ?? '');
        }
        if ($rejecterId === '') {
            throw new ForbiddenException(__('You must be logged in.'));
        }
        $rejecter = $users->get($rejecterId);

        $ok = (new MembershipService())->reject($applicant, $rejecter);
        if ($ok) {
            $this->Flash->success(__('The application has been rejected. The applicant can no longer log in.'));
        } else {
            $this->Flash->error(__('Could not reject this application.'));
        }

        return $this->redirect(['action' => 'index']);
    }
}
