<?php
declare(strict_types=1);

namespace App\Controller\Clubpresident;

use App\Auth\AppRoles;
use App\Auth\MembershipProfile;
use App\Controller\Concerns\PanelMemberListTrait;
use App\Service\MembershipService;
use App\Utility\MembershipFee;
use Cake\Http\Exception\ForbiddenException;
use Cake\Http\Exception\NotFoundException;
use Cake\Http\Response;

/**
 * Club members + pending applicants (cards) — club membership fee.
 */
class MembersController extends AppController
{
    use PanelMemberListTrait;

    /**
     * Active members of the president's club + pending applicant cards.
     *
     * @return \Cake\Http\Response|null|void
     */
    public function index()
    {
        $clubId = $this->presidentClubId();
        $membershipYear = MembershipFee::currentYear();
        $applicants = [];

        if ($clubId < 1) {
            $this->Flash->warning(__('Your account is not assigned to a club yet. Contact an administrator.'));
            $this->set('members', $this->emptyPaginated());
            $this->set('title', __('Members'));
            $this->set('breadcrumb', __('Members'));
            $this->set('clubName', '');
            $this->set(compact('membershipYear', 'clubId', 'applicants'));

            return;
        }

        /** @var \App\Model\Table\UsersTable $users */
        $users = $this->fetchTable('Users');
        $query = $this->scopeToPresidentClub(
            $users->find()->where([
                'Users.active' => 1,
            ] + $this->membershipRosterRoleCondition())
        );

        $applicants = $this->scopeToPresidentClub(
            $users->find()->where([
                'Users.role' => AppRoles::NEW,
                'Users.membership_status' => MembershipProfile::STATUS_PENDING,
                'Users.active' => 1,
                'Users.enabled' => 1,
            ])
        )
            ->orderBy(['Users.modified' => 'DESC', 'Users.created' => 'DESC'])
            ->limit(100)
            ->all()
            ->toList();

        $clubName = '';
        $clubCountryId = 0;
        /** @var \App\Model\Table\ClubsTable $clubs */
        $clubs = $this->fetchTable('Clubs');
        $club = $clubs->find()
            ->select(['name', 'country_id'])
            ->where(['Clubs.id' => $clubId])
            ->first();
        if ($club !== null) {
            $clubName = (string)$club->get('name');
            $clubCountryId = (int)($club->get('country_id') ?? 0);
        }

        $this->set('title', __('Members'));
        $this->set('breadcrumb', __('Members'));
        $this->set('members', $this->paginate(
            $query,
            $this->panelMemberPaginateOptions($this->panelMemberSortableFields(false))
        ));
        $this->set(compact('clubName', 'clubId', 'clubCountryId', 'membershipYear', 'applicants'));
    }

    /**
     * Edit member (contact + club fee date).
     *
     * @param string|null $id User id
     * @return \Cake\Http\Response|null|void
     */
    public function edit(?string $id = null)
    {
        $this->set('canEdit', true);
        $this->set('canAdd', false);
        $this->set('canDelete', false);

        $member = $this->findScopedMember((string)$id, containClub: true);
        /** @var \App\Model\Table\UsersTable $users */
        $users = $this->fetchTable('Users');
        $clubCountryId = (int)($member->get('country_id') ?? 0);

        if ($this->request->is(['patch', 'post', 'put'])) {
            try {
                $member = $users->patchEntity($member, $this->request->getData(), [
                    'accessibleFields' => [
                        'first_name' => true,
                        'phone' => true,
                        MembershipFee::FIELD_CLUB => true,
                    ],
                ]);
                if ($users->save($member)) {
                    $this->Flash->success(__('The member has been saved.'));

                    return $this->redirect(['action' => 'index']);
                }
            } catch (\Throwable $e) {
                // Unexpected errors → user-facing flash
            }
            $this->Flash->error(__('The record could not be saved. Please try again.'));
        }

        $this->set(compact('member'));
        $this->set('feeField', MembershipFee::FIELD_CLUB);
        $this->set('feeLabel', MembershipFee::clubFeeLabel($clubCountryId));
        $this->set('showEnabled', false);
        $this->set('title', __('Edit member'));
        $this->set('breadcrumb', __('Members'));
        $this->render('form');
    }

    /**
     * Read-only member details.
     *
     * @param string|null $id User id
     * @return \Cake\Http\Response|null|void
     */
    public function view(?string $id = null)
    {
        $this->set('canEdit', true);
        $this->set('canAdd', false);
        $this->set('canDelete', false);
        $member = $this->findScopedMember((string)$id, containClub: true);
        $this->set(compact('member'));
        $this->set('title', __('Member details'));
        $this->set('breadcrumb', __('Members'));
    }

    /**
     * JSON: member row for index modal.
     *
     * @param string|null $id User id
     * @return \Cake\Http\Response
     */
    public function recordGet(?string $id = null): Response
    {
        $this->request->allowMethod(['get']);

        try {
            $member = $this->findScopedMember((string)$id, containClub: true);
        } catch (NotFoundException) {
            return $this->jsonRecordNotFound();
        }

        return $this->jsonRecordResponse($this->memberRecordPayload($member));
    }

    /**
     * AJAX: enable / disable member account (`users.enabled`).
     *
     * Event log via EventLogBehavior when activity logging is on for the country.
     *
     * @param string|null $id User id
     * @return \Cake\Http\Response
     */
    public function toggleEnabled(?string $id = null): Response
    {
        $this->request->allowMethod(['post', 'put', 'patch']);
        $clubId = $this->presidentClubId();
        if ($clubId < 1) {
            return $this->response
                ->withType('application/json')
                ->withStatus(403)
                ->withStringBody(json_encode([
                    'ok' => false,
                    'message' => (string)__('Your account is not assigned to a club yet.'),
                ]));
        }

        /** @var \App\Model\Table\UsersTable $users */
        $users = $this->fetchTable('Users');
        $member = $this->scopeToPresidentClub(
            $users->find()->where([
                'Users.id' => (string)$id,
                'Users.active' => 1,
            ] + $this->membershipRosterRoleCondition())
        )->first();
        if ($member === null) {
            return $this->response
                ->withType('application/json')
                ->withStatus(404)
                ->withStringBody(json_encode([
                    'ok' => false,
                    'message' => (string)__('Member not found.'),
                ]));
        }

        $raw = $this->request->getData('enabled');
        if ($raw === null) {
            $raw = $this->request->getQuery('enabled');
        }
        if ($raw === null || $raw === '') {
            $enabled = !((int)($member->get('enabled') ?? 0) === 1);
        } else {
            $enabled = in_array((string)$raw, ['1', 'true', 'on', 'yes'], true);
        }

        $member->set('enabled', $enabled);
        if (!$users->save($member, [
            'checkRules' => false,
            'accessibleFields' => [
                'enabled' => true,
                'modified' => true,
            ],
        ])) {
            return $this->response
                ->withType('application/json')
                ->withStatus(422)
                ->withStringBody(json_encode([
                    'ok' => false,
                    'message' => (string)__('Could not update the member account.'),
                ]));
        }

        return $this->response
            ->withType('application/json')
            ->withStringBody(json_encode([
                'ok' => true,
                'enabled' => $enabled,
                'message' => $enabled
                    ? (string)__('Member account enabled.')
                    : (string)__('Member account disabled.'),
            ]));
    }

    /**
     * Approve pending applicant → member + joined date.
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
        $applicant = $this->scopeToPresidentClub(
            $users->find()->where([
                'Users.id' => (string)$id,
                'Users.role' => AppRoles::NEW,
                'Users.membership_status' => MembershipProfile::STATUS_PENDING,
                'Users.enabled' => 1,
            ])
        )->first();
        if ($applicant === null) {
            throw new NotFoundException(__('Applicant not found.'));
        }

        $approver = $users->get($this->requireIdentityUserId());
        $ok = (new MembershipService())->approve($applicant, $approver);
        if ($ok) {
            $this->Flash->success(__('Membership approved. The new member has been notified by email.'));
        } else {
            $this->Flash->error(__('Could not approve this application.'));
        }

        return $this->redirect(['action' => 'index']);
    }

    /**
     * Reject pending applicant → users.enabled = false.
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
        $applicant = $this->scopeToPresidentClub(
            $users->find()->where([
                'Users.id' => (string)$id,
                'Users.role' => AppRoles::NEW,
                'Users.membership_status' => MembershipProfile::STATUS_PENDING,
                'Users.enabled' => 1,
            ])
        )->first();
        if ($applicant === null) {
            throw new NotFoundException(__('Applicant not found.'));
        }

        $rejecter = $users->get($this->requireIdentityUserId());
        $ok = (new MembershipService())->reject($applicant, $rejecter);
        if ($ok) {
            $this->Flash->success(__('The application has been rejected. The applicant can no longer log in.'));
        } else {
            $this->Flash->error(__('Could not reject this application.'));
        }

        return $this->redirect(['action' => 'index']);
    }

    /**
     * Update club membership fee payment date for one member.
     *
     * @param string|null $id User id
     * @return \Cake\Http\Response|null
     */
    public function updateClubFee(?string $id = null): ?Response
    {
        $this->request->allowMethod(['post', 'put', 'patch']);
        $clubId = $this->presidentClubId();
        if ($clubId < 1) {
            throw new ForbiddenException(__('Your account is not assigned to a club yet.'));
        }

        /** @var \App\Model\Table\UsersTable $users */
        $users = $this->fetchTable('Users');
        $member = $this->scopeToPresidentClub(
            $users->find()->where([
                'Users.id' => (string)$id,
                'Users.active' => 1,
                'Users.enabled' => 1,
            ] + $this->membershipRosterRoleCondition())
        )->first();
        if ($member === null) {
            throw new NotFoundException(__('Member not found.'));
        }

        $membershipYear = MembershipFee::currentYear();
        $existingDate = $member->get(MembershipFee::FIELD_CLUB);
        if (MembershipFee::isPaidForYear($existingDate, $membershipYear)) {
            $this->Flash->info(__('This member has already paid the club membership fee for {0}.', $membershipYear));

            return $this->redirect(['action' => 'index']);
        }

        $member = $users->patchEntity($member, [
            MembershipFee::FIELD_CLUB => MembershipFee::today(),
        ], [
            'accessibleFields' => [
                MembershipFee::FIELD_CLUB => true,
            ],
        ]);

        if (!$users->save($member)) {
            $this->Flash->error(__('Could not record the club membership fee payment.'));

            return $this->redirect(['action' => 'index']);
        }

        $this->Flash->success(__('Club membership fee payment recorded for {0}.', $membershipYear));

        return $this->redirect(['action' => 'index']);
    }

    protected function requireIdentityUserId(): string
    {
        $identity = $this->getRequest()->getAttribute('identity');
        $userId = '';
        if ($identity !== null && method_exists($identity, 'getIdentifier')) {
            $userId = (string)$identity->getIdentifier();
        } elseif ($identity !== null && method_exists($identity, 'get')) {
            $userId = (string)($identity->get('id') ?? '');
        }
        if ($userId === '') {
            throw new ForbiddenException(__('You must be logged in.'));
        }

        return $userId;
    }

    /**
     * @param bool $containClub
     * @return \Cake\Datasource\EntityInterface
     */
    protected function findScopedMember(string $id, bool $containClub = false): \Cake\Datasource\EntityInterface
    {
        $clubId = $this->presidentClubId();
        if ($clubId < 1) {
            throw new ForbiddenException(__('Your account is not assigned to a club yet.'));
        }

        /** @var \App\Model\Table\UsersTable $users */
        $users = $this->fetchTable('Users');
        $query = $this->scopeToPresidentClub(
            $users->find()->where([
                'Users.id' => $id,
                'Users.active' => 1,
            ] + $this->membershipRosterRoleCondition())
        );
        if ($containClub) {
            $query->contain(['Clubs']);
        }

        $member = $query->first();
        if ($member === null) {
            throw new NotFoundException(__('Member not found.'));
        }

        return $member;
    }
}
