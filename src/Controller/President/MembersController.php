<?php
declare(strict_types=1);

namespace App\Controller\President;

use App\Auth\AppRoles;
use App\Auth\CurrentUser;
use App\Auth\MembershipProfile;
use App\Controller\Concerns\PanelMemberListTrait;
use App\Service\MembershipService;
use App\Utility\AdminCountry;
use App\Utility\MembershipFee;
use Cake\Http\Exception\ForbiddenException;
use Cake\Http\Exception\NotFoundException;
use Cake\Http\Response;

/**
 * Country members — national membership fee (president / vice president).
 */
class MembersController extends AppController
{
    use PanelMemberListTrait;

    private const NATIONAL_PAID_ONLY_SESSION_KEY = 'President.Members.national_paid_only';

    private const SHOW_APPLICANTS_SESSION_KEY = 'President.Members.show_applicants';

    /**
     * Active members in the officer's country.
     *
     * @return \Cake\Http\Response|null|void
     */
    public function index()
    {
        $countryId = $this->officerCountryId();
        $membershipYear = MembershipFee::currentYear();

        if ($countryId < 1) {
            $this->Flash->warning(__('Your account is not assigned to a country yet. Contact an administrator.'));
            $this->set('members', $this->emptyPaginated());
            $this->set('title', __('Members'));
            $this->set('breadcrumb', __('Members'));
            $this->set('countryLabel', '');
            $nationalPaidOnly = false;
            $showApplicants = false;
            $applicants = [];
            $this->set(compact('membershipYear', 'countryId', 'nationalPaidOnly', 'showApplicants', 'applicants'));

            return;
        }

        $nationalPaidOnly = $this->resolveNationalPaidOnly();
        $showApplicants = $this->resolveShowApplicants();
        $applicants = [];

        /** @var \App\Model\Table\UsersTable $users */
        $users = $this->fetchTable('Users');
        $where = [
            'Users.country_id' => $countryId,
            'Users.active' => 1,
        ] + $this->membershipRosterRoleCondition();
        if ($nationalPaidOnly) {
            $where = array_merge($where, MembershipFee::paidForYearConditions(
                'Users.' . MembershipFee::FIELD_NATIONAL,
                $membershipYear
            ));
        }

        $query = $users->find()
            ->contain(['Clubs'])
            ->where($where);

        if ($showApplicants) {
            $applicants = $users->find()
                ->contain(['Clubs'])
                ->where([
                    'Users.country_id' => $countryId,
                    'Users.role' => AppRoles::NEW,
                    'Users.membership_status' => MembershipProfile::STATUS_PENDING,
                    'Users.active' => 1,
                    'Users.enabled' => 1,
                ])
                ->orderBy(['Users.modified' => 'DESC', 'Users.created' => 'DESC'])
                ->limit(200)
                ->all()
                ->toList();
        }

        $countryLabel = AdminCountry::label($countryId);

        $this->set('title', __('Members'));
        $this->set('breadcrumb', __('Members'));
        $this->set('members', $this->paginate(
            $query,
            $this->panelMemberPaginateOptions($this->panelMemberSortableFields(true))
        ));
        $this->set(compact('countryLabel', 'countryId', 'membershipYear', 'nationalPaidOnly', 'showApplicants', 'applicants'));
    }

    /**
     * Edit member (contact + national fee + enabled).
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
        $countryId = (int)($member->get('country_id') ?? 0);
        $actorRole = CurrentUser::role($this->getRequest());
        $targetRole = strtolower(trim((string)($member->get('role') ?? '')));
        $canEditRole = AppRoles::canEditTargetRole($actorRole, $targetRole);
        $roleOptions = AppRoles::assignableOptionsForActor($actorRole);

        if ($this->request->is(['patch', 'post', 'put'])) {
            try {
                $data = $this->request->getData();
                $requestedRole = strtolower(trim((string)($data['role'] ?? '')));
                $accessible = [
                    'first_name' => true,
                    'phone' => true,
                    'enabled' => true,
                    MembershipFee::FIELD_NATIONAL => true,
                ];
                if (
                    $canEditRole
                    && $requestedRole !== ''
                    && AppRoles::canAssignRole($actorRole, $requestedRole)
                ) {
                    $accessible['role'] = true;
                } else {
                    unset($data['role']);
                }

                $member = $users->patchEntity($member, $data, [
                    'accessibleFields' => $accessible,
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
        $this->set('feeField', MembershipFee::FIELD_NATIONAL);
        $this->set('feeLabel', MembershipFee::nationalFeeLabel($countryId));
        $this->set('showEnabled', true);
        $this->set('showRole', true);
        $this->set('roleOptions', $roleOptions);
        $this->set('roleSelectDisabled', !$canEditRole);
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
     * JSON: club (parent) row for linked modal.
     *
     * @param string|null $id Club id
     * @return \Cake\Http\Response
     */
    public function clubRecordGet(?string $id = null): Response
    {
        $this->request->allowMethod(['get']);
        $countryId = $this->officerCountryId();
        if ($countryId < 1) {
            return $this->jsonRecordNotFound();
        }

        /** @var \App\Model\Table\ClubsTable $clubs */
        $clubs = $this->fetchTable('Clubs');
        $club = $clubs->find()
            ->where([
                'Clubs.id' => (int)$id,
                'Clubs.country_id' => $countryId,
            ])
            ->first();
        if ($club === null) {
            return $this->jsonRecordNotFound();
        }

        return $this->jsonRecordResponse($this->clubRecordPayload($club));
    }

    /**
     * Record national membership fee payment (today).
     *
     * @param string|null $id User id
     * @return \Cake\Http\Response|null
     */
    public function updateNationalFee(?string $id = null): ?Response
    {
        $this->request->allowMethod(['post', 'put', 'patch']);
        $countryId = $this->officerCountryId();
        if ($countryId < 1) {
            throw new ForbiddenException(__('Your account is not assigned to a country yet.'));
        }

        /** @var \App\Model\Table\UsersTable $users */
        $users = $this->fetchTable('Users');
        $member = $users->find()
            ->where([
                'Users.id' => (string)$id,
                'Users.country_id' => $countryId,
                'Users.active' => 1,
                'Users.enabled' => 1,
            ] + $this->membershipRosterRoleCondition())
            ->first();
        if ($member === null) {
            throw new NotFoundException(__('Member not found.'));
        }

        $membershipYear = MembershipFee::currentYear();
        $existingDate = $member->get(MembershipFee::FIELD_NATIONAL);
        if (MembershipFee::isPaidForYear($existingDate, $membershipYear)) {
            $this->Flash->info(__('This member has already paid the national membership fee for {0}.', $membershipYear));

            return $this->redirect(['action' => 'index']);
        }

        $member = $users->patchEntity($member, [
            MembershipFee::FIELD_NATIONAL => MembershipFee::today(),
        ], [
            'accessibleFields' => [
                MembershipFee::FIELD_NATIONAL => true,
            ],
        ]);

        if (!$users->save($member)) {
            $this->Flash->error(__('Could not record the national membership fee payment.'));

            return $this->redirect(['action' => 'index']);
        }

        $this->Flash->success(__('National membership fee payment recorded for {0}.', $membershipYear));

        return $this->redirect(['action' => 'index']);
    }

    /**
     * AJAX: enable / disable member account (`users.enabled`).
     *
     * Event log is written by EventLogBehavior when Activity logging is on for the country.
     *
     * @param string|null $id User id
     * @return \Cake\Http\Response
     */
    public function toggleEnabled(?string $id = null): Response
    {
        $this->request->allowMethod(['post', 'put', 'patch']);
        $countryId = $this->officerCountryId();
        if ($countryId < 1) {
            return $this->response
                ->withType('application/json')
                ->withStatus(403)
                ->withStringBody(json_encode([
                    'ok' => false,
                    'message' => (string)__('Your account is not assigned to a country yet.'),
                ]));
        }

        /** @var \App\Model\Table\UsersTable $users */
        $users = $this->fetchTable('Users');
        $member = $users->find()
            ->where([
                'Users.id' => (string)$id,
                'Users.country_id' => $countryId,
                'Users.active' => 1,
            ] + $this->membershipRosterRoleCondition())
            ->first();
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
     * Approve pending applicant in the officer's country → member + joined date.
     *
     * @param string|null $id User id
     * @return \Cake\Http\Response|null
     */
    public function approve(?string $id = null): ?Response
    {
        $this->request->allowMethod(['post', 'put', 'patch']);
        $countryId = $this->officerCountryId();
        if ($countryId < 1) {
            throw new ForbiddenException(__('Your account is not assigned to a country yet.'));
        }

        /** @var \App\Model\Table\UsersTable $users */
        $users = $this->fetchTable('Users');
        $applicant = $users->find()
            ->where([
                'Users.id' => (string)$id,
                'Users.country_id' => $countryId,
                'Users.role' => AppRoles::NEW,
                'Users.membership_status' => MembershipProfile::STATUS_PENDING,
                'Users.enabled' => 1,
            ])
            ->first();
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
     * Reject pending applicant in the officer's country → users.enabled = false.
     *
     * @param string|null $id User id
     * @return \Cake\Http\Response|null
     */
    public function reject(?string $id = null): ?Response
    {
        $this->request->allowMethod(['post', 'put', 'patch']);
        $countryId = $this->officerCountryId();
        if ($countryId < 1) {
            throw new ForbiddenException(__('Your account is not assigned to a country yet.'));
        }

        /** @var \App\Model\Table\UsersTable $users */
        $users = $this->fetchTable('Users');
        $applicant = $users->find()
            ->where([
                'Users.id' => (string)$id,
                'Users.country_id' => $countryId,
                'Users.role' => AppRoles::NEW,
                'Users.membership_status' => MembershipProfile::STATUS_PENDING,
                'Users.enabled' => 1,
            ])
            ->first();
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
     * Index filter: only members with national fee paid for current year (default off = all).
     */
    protected function resolveNationalPaidOnly(): bool
    {
        $session = $this->request->getSession();
        $query = $this->request->getQueryParams();

        if (array_key_exists('national_paid_only', $query)) {
            $raw = $query['national_paid_only'];
            if (is_array($raw)) {
                $raw = end($raw);
            }
            $paidOnly = in_array((string)$raw, ['1', 'true', 'on'], true);
            $session->write(self::NATIONAL_PAID_ONLY_SESSION_KEY, $paidOnly);

            return $paidOnly;
        }

        $saved = $session->read(self::NATIONAL_PAID_ONLY_SESSION_KEY);
        if ($saved === null) {
            return false;
        }

        return (bool)$saved;
    }

    /**
     * Index filter: show pending applicants (role `new`) as cards above the list.
     */
    protected function resolveShowApplicants(): bool
    {
        $session = $this->request->getSession();
        $query = $this->request->getQueryParams();

        if (array_key_exists('show_applicants', $query)) {
            $raw = $query['show_applicants'];
            if (is_array($raw)) {
                $raw = end($raw);
            }
            $show = in_array((string)$raw, ['1', 'true', 'on'], true);
            $session->write(self::SHOW_APPLICANTS_SESSION_KEY, $show);

            return $show;
        }

        $saved = $session->read(self::SHOW_APPLICANTS_SESSION_KEY);
        if ($saved === null) {
            return false;
        }

        return (bool)$saved;
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
        $countryId = $this->officerCountryId();
        if ($countryId < 1) {
            throw new ForbiddenException(__('Your account is not assigned to a country yet.'));
        }

        /** @var \App\Model\Table\UsersTable $users */
        $users = $this->fetchTable('Users');
        $query = $users->find()
            ->where([
                'Users.id' => $id,
                'Users.country_id' => $countryId,
            ]);
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
