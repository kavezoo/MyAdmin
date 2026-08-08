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
     * Member-facing fields: officer edits trigger a notification email to the member.
     *
     * @var list<string>
     */
    public const PROFILE_NOTIFY_FIELDS = [
        'first_name',
        'last_name',
        'phone',
        'email',
        'club_id',
        'enabled',
        'role',
        'language_id',
        MembershipFee::FIELD_CLUB,
        MembershipFee::FIELD_NATIONAL,
    ];

    /**
     * After an officer/admin saves member profile fields that affect the member → email.
     *
     * @param list<string> $dirtyFields Field names dirty before save
     */
    public function notifyMemberProfileUpdated(EntityInterface $member, array $dirtyFields): void
    {
        $relevant = array_values(array_intersect($dirtyFields, self::PROFILE_NOTIFY_FIELDS));
        if ($relevant === []) {
            return;
        }

        $email = trim((string)($member->get('email') ?? ''));
        if ($email === '') {
            return;
        }

        $clubName = $this->clubName((int)($member->get('club_id') ?? 0));
        try {
            /** @var \App\Mailer\MembershipMailer $mailer */
            $mailer = $this->getMailer('Membership');
            $mailer->send('memberProfileUpdated', [$member, $clubName]);
        } catch (\Throwable $e) {
            Log::warning('Member profile updated email failed: ' . $e->getMessage(), ['scope' => ['membership']]);
        }
    }

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
     * Club president or country officer (president / vice president) approves applicant → member + email.
     */
    public function approve(EntityInterface $applicant, EntityInterface $approver): bool
    {
        if (strtolower((string)$applicant->get('role')) !== AppRoles::NEW) {
            return false;
        }
        if ((int)$applicant->get('club_id') < 1) {
            return false;
        }
        if (!$this->approverMayActOnApplicant($applicant, $approver)) {
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
     * Club president or country officer rejects applicant → users.enabled = false (no delete).
     */
    public function reject(EntityInterface $applicant, EntityInterface $rejecter): bool
    {
        if (strtolower((string)$applicant->get('role')) !== AppRoles::NEW) {
            return false;
        }
        if ((int)$applicant->get('club_id') < 1) {
            return false;
        }
        if (!$this->approverMayActOnApplicant($applicant, $rejecter)) {
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

        /** @var \App\Model\Table\ClubsTable $clubs */
        $clubs = $this->fetchTable('Clubs');
        $designated = $clubs->findClubPresident($clubId);

        /** @var \App\Model\Table\UsersTable $users */
        $users = $this->fetchTable('Users');
        $recipients = [];
        if ($designated !== null
            && (int)($designated->get('active') ?? 0) === 1
            && (int)($designated->get('enabled') ?? 0) === 1
        ) {
            $recipients[(string)$designated->get('id')] = $designated;
        }

        // Also notify any role=clubpresident for this club (legacy / extra).
        foreach ($users->find()
            ->where([
                'Users.role' => AppRoles::CLUBPRESIDENT,
                'Users.club_id' => $clubId,
                'Users.active' => 1,
                'Users.enabled' => 1,
            ])
            ->all() as $president
        ) {
            $recipients[(string)$president->get('id')] = $president;
        }

        $clubName = $this->clubName($clubId);
        if ($recipients === []) {
            Log::info(sprintf(
                'No clubpresident for club_id=%d (applicant %s)',
                $clubId,
                (string)$applicant->get('email')
            ), ['scope' => ['membership']]);

            return;
        }

        foreach ($recipients as $president) {
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

    /**
     * Club president: same club. President / vice president: same country.
     */
    protected function approverMayActOnApplicant(EntityInterface $applicant, EntityInterface $approver): bool
    {
        $approverRole = strtolower(trim((string)($approver->get('role') ?? '')));
        if (in_array($approverRole, [AppRoles::PRESIDENT, AppRoles::VICEPRESIDENT], true)) {
            return (int)($approver->get('country_id') ?? 0) === (int)($applicant->get('country_id') ?? 0)
                && (int)($approver->get('country_id') ?? 0) > 0;
        }

        return (int)($approver->get('club_id') ?? 0) === (int)($applicant->get('club_id') ?? 0)
            && (int)($approver->get('club_id') ?? 0) > 0;
    }
}
