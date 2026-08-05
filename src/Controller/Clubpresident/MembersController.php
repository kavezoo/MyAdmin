<?php
declare(strict_types=1);

namespace App\Controller\Clubpresident;

use App\Auth\AppRoles;
use App\Controller\Concerns\PanelMemberListTrait;
use App\Utility\MembershipFee;
use Cake\Http\Exception\ForbiddenException;
use Cake\Http\Exception\NotFoundException;
use Cake\Http\Response;

/**
 * Active club members — club membership fee date (club president only).
 */
class MembersController extends AppController
{
    use PanelMemberListTrait;

    /**
     * Active members of the president's club.
     *
     * @return \Cake\Http\Response|null|void
     */
    public function index()
    {
        $clubId = $this->presidentClubId();
        $membershipYear = MembershipFee::currentYear();

        if ($clubId < 1) {
            $this->Flash->warning(__('Your account is not assigned to a club yet. Contact an administrator.'));
            $this->set('members', $this->emptyPaginated());
            $this->set('title', __('Active members'));
            $this->set('breadcrumb', __('Active members'));
            $this->set('clubName', '');
            $this->set(compact('membershipYear', 'clubId'));

            return;
        }

        /** @var \App\Model\Table\UsersTable $users */
        $users = $this->fetchTable('Users');
        $query = $users->find()
            ->where([
                'Users.role' => AppRoles::MEMBER,
                'Users.club_id' => $clubId,
                'Users.active' => 1,
                'Users.enabled' => 1,
            ]);

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

        $this->set('title', __('Active members'));
        $this->set('breadcrumb', __('Active members'));
        $this->set('members', $this->paginate(
            $query,
            $this->panelMemberPaginateOptions($this->panelMemberSortableFields(false))
        ));
        $this->set(compact('clubName', 'clubId', 'clubCountryId', 'membershipYear'));
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
        $this->set('breadcrumb', __('Active members'));
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
        $member = $users->find()
            ->where([
                'Users.id' => (string)$id,
                'Users.role' => AppRoles::MEMBER,
                'Users.club_id' => $clubId,
                'Users.active' => 1,
                'Users.enabled' => 1,
            ])
            ->first();
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
        $query = $users->find()
            ->where([
                'Users.id' => $id,
                'Users.role' => AppRoles::MEMBER,
                'Users.club_id' => $clubId,
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
