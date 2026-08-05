<?php
declare(strict_types=1);

namespace App\Service;

use App\Auth\AppRoles;
use App\Auth\MembershipProfile;
use App\Mailer\MembershipMailer;
use App\Utility\MembershipFee;
use Cake\Datasource\EntityInterface;
use Cake\Log\Log;
use Cake\Mailer\MailerAwareTrait;
use Cake\ORM\Locator\LocatorAwareTrait;

/**
 * Profile completion → notify clubpresident; approve → member + notify user.
 */
class MembershipService
{
    use LocatorAwareTrait;
    use MailerAwareTrait;

    /**
     * After profile save: mark pending and email club presidents once.
     */
    public function onProfileCompleted(EntityInterface $user): void
    {
        if (!MembershipProfile::isComplete($user)) {
            return;
        }

        /** @var \App\Model\Table\UsersTable $users */
        $users = $this->fetchTable('Users');
        $user->set('membership_status', MembershipProfile::STATUS_PENDING);
        $users->save($user, [
            'checkRules' => false,
            'skipEventLog' => false,
        ]);

        if (MembershipProfile::wasNotified($user)) {
            return;
        }

        $this->notifyClubPresidents($user);
        $user->set('application_notified', true);
        $users->save($user, [
            'checkRules' => false,
            'fields' => ['application_notified', 'modified'],
        ]);
    }

    /**
     * Club president approves applicant → role member + email.
     */
    public function approve(EntityInterface $applicant, EntityInterface $approver): bool
    {
        if (strtolower((string)$applicant->get('role')) !== AppRoles::NEW) {
            return false;
        }
        if ((int)$applicant->get('club_id') < 1) {
            return false;
        }
        if ((int)$approver->get('club_id') !== (int)$applicant->get('club_id')) {
            return false;
        }

        /** @var \App\Model\Table\UsersTable $users */
        $users = $this->fetchTable('Users');
        $applicant->set('role', AppRoles::MEMBER);
        $applicant->set('membership_status', MembershipProfile::STATUS_APPROVED);
        $applicant->set(MembershipProfile::FIELD_JOINED, MembershipFee::today());
        if (!$users->save($applicant, [
            'checkRules' => false,
            'accessibleFields' => [
                'role' => true,
                'membership_status' => true,
                MembershipProfile::FIELD_JOINED => true,
                'modified' => true,
            ],
        ])) {
            return false;
        }

        $clubName = $this->clubName((int)$applicant->get('club_id'));
        try {
            /** @var \App\Mailer\MembershipMailer $mailer */
            $mailer = $this->getMailer('Membership');
            $mailer->send('membershipApproved', [$applicant, $clubName]);
        } catch (\Throwable $e) {
            Log::warning('Membership approved email failed: ' . $e->getMessage(), ['scope' => ['membership']]);
        }

        return true;
    }

    /**
     * Club president rejects applicant → users.enabled = false (no delete).
     */
    public function reject(EntityInterface $applicant, EntityInterface $rejecter): bool
    {
        if (strtolower((string)$applicant->get('role')) !== AppRoles::NEW) {
            return false;
        }
        if ((int)$applicant->get('club_id') < 1) {
            return false;
        }
        if ((int)$rejecter->get('club_id') !== (int)$applicant->get('club_id')) {
            return false;
        }

        /** @var \App\Model\Table\UsersTable $users */
        $users = $this->fetchTable('Users');
        $applicant->set('enabled', false);

        return $users->save($applicant, [
            'checkRules' => false,
            'accessibleFields' => [
                'enabled' => true,
                'modified' => true,
            ],
        ]);
    }

    /**
     * After profile club switch: notify presidents of the new club (always re-notify).
     */
    public function onClubChanged(EntityInterface $user): void
    {
        $clubId = (int)$user->get('club_id');
        if ($clubId < 1) {
            return;
        }

        $this->notifyClubPresidents($user);

        /** @var \App\Model\Table\UsersTable $users */
        $users = $this->fetchTable('Users');
        $user->set('application_notified', true);
        $users->save($user, [
            'checkRules' => false,
            'accessibleFields' => [
                'application_notified' => true,
                'modified' => true,
            ],
        ]);
    }

    protected function notifyClubPresidents(EntityInterface $applicant): void
    {
        $clubId = (int)$applicant->get('club_id');
        if ($clubId < 1) {
            return;
        }

        /** @var \App\Model\Table\UsersTable $users */
        $users = $this->fetchTable('Users');
        $presidents = $users->find()
            ->where([
                'Users.role' => AppRoles::CLUBPRESIDENT,
                'Users.club_id' => $clubId,
                'Users.active' => 1,
                'Users.enabled' => 1,
            ])
            ->all();

        $clubName = $this->clubName($clubId);
        if ($presidents->count() === 0) {
            Log::info(sprintf(
                'No clubpresident for club_id=%d (applicant %s)',
                $clubId,
                (string)$applicant->get('email')
            ), ['scope' => ['membership']]);

            return;
        }

        foreach ($presidents as $president) {
            try {
                /** @var \App\Mailer\MembershipMailer $mailer */
                $mailer = $this->getMailer('Membership');
                $mailer->send('applicationReceived', [$applicant, $president, $clubName]);
            } catch (\Throwable $e) {
                Log::warning('Application email failed: ' . $e->getMessage(), ['scope' => ['membership']]);
            }
        }
    }

    protected function clubName(int $clubId): string
    {
        /** @var \App\Model\Table\ClubsTable $clubs */
        $clubs = $this->fetchTable('Clubs');
        $club = $clubs->find()->select(['name'])->where(['Clubs.id' => $clubId])->first();

        return $club !== null ? (string)$club->get('name') : ('#' . $clubId);
    }
}
