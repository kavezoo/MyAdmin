<?php
declare(strict_types=1);

namespace App\Controller\President;

use App\Auth\AppRoles;
use App\Controller\Concerns\PanelMemberListTrait;
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
            $this->set(compact('membershipYear', 'countryId', 'nationalPaidOnly'));

            return;
        }

        $nationalPaidOnly = $this->resolveNationalPaidOnly();

        /** @var \App\Model\Table\UsersTable $users */
        $users = $this->fetchTable('Users');
        $where = [
            'Users.role' => AppRoles::MEMBER,
            'Users.country_id' => $countryId,
            'Users.active' => 1,
            'Users.enabled' => 1,
        ];
        if ($nationalPaidOnly) {
            $where = array_merge($where, MembershipFee::paidForYearConditions(
                'Users.' . MembershipFee::FIELD_NATIONAL,
                $membershipYear
            ));
        }

        $query = $users->find()
            ->contain(['Clubs'])
            ->where($where);

        $countryLabel = AdminCountry::label($countryId);

        $this->set('title', __('Members'));
        $this->set('breadcrumb', __('Members'));
        $this->set('members', $this->paginate(
            $query,
            $this->panelMemberPaginateOptions($this->panelMemberSortableFields(true))
        ));
        $this->set(compact('countryLabel', 'countryId', 'membershipYear', 'nationalPaidOnly'));
    }

    /**
     * Read-only member details.
     *
     * @param string|null $id User id
     * @return \Cake\Http\Response|null|void
     */
    public function view(?string $id = null)
    {
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
                'Users.role' => AppRoles::MEMBER,
                'Users.country_id' => $countryId,
                'Users.active' => 1,
                'Users.enabled' => 1,
            ])
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
                'Users.role' => AppRoles::MEMBER,
                'Users.country_id' => $countryId,
                'Users.active' => 1,
                'Users.enabled' => 1,
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
