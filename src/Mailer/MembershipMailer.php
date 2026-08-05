<?php
declare(strict_types=1);

namespace App\Mailer;

use Cake\Datasource\EntityInterface;
use Cake\Mailer\Mailer;
use Cake\Mailer\Message;
use Cake\Routing\Router;

/**
 * Membership application / approval emails.
 */
class MembershipMailer extends Mailer
{
    /**
     * Notify club president(s) about a new applicant.
     *
     * @param \Cake\Datasource\EntityInterface $applicant
     * @param \Cake\Datasource\EntityInterface $president
     * @param string $clubName
     */
    public function applicationReceived(
        EntityInterface $applicant,
        EntityInterface $president,
        string $clubName,
    ): void {
        $name = trim((string)($applicant->get('first_name') ?? '') . ' ' . (string)($applicant->get('last_name') ?? ''));
        if ($name === '') {
            $name = (string)($applicant->get('email') ?? '');
        }
        $listUrl = Router::url([
            'prefix' => 'Clubpresident',
            'controller' => 'Members',
            'action' => 'index',
            '_full' => true,
        ]);

        $this
            ->setTo((string)$president->get('email'))
            ->setSubject(__('New membership application: {0}', $name))
            ->setEmailFormat(Message::MESSAGE_BOTH)
            ->setViewVars([
                'applicantName' => $name,
                'applicantEmail' => (string)$applicant->get('email'),
                'clubName' => $clubName,
                'listUrl' => $listUrl,
                'presidentName' => trim((string)($president->get('first_name') ?? '')),
            ]);

        $this->viewBuilder()
            ->setTemplate('membership_application')
            ->setLayout('default');
    }

    /**
     * Notify the new member that the club president approved them.
     *
     * @param \Cake\Datasource\EntityInterface $member
     * @param string $clubName
     */
    public function membershipApproved(EntityInterface $member, string $clubName): void
    {
        $name = trim((string)($member->get('first_name') ?? '') . ' ' . (string)($member->get('last_name') ?? ''));
        if ($name === '') {
            $name = (string)($member->get('email') ?? '');
        }
        $loginUrl = Router::url([
            'plugin' => null,
            'prefix' => false,
            'controller' => 'Users',
            'action' => 'login',
            '_full' => true,
        ]);

        $this
            ->setTo((string)$member->get('email'))
            ->setSubject(__('Your membership has been approved'))
            ->setEmailFormat(Message::MESSAGE_BOTH)
            ->setViewVars([
                'memberName' => $name,
                'clubName' => $clubName,
                'loginUrl' => $loginUrl,
            ]);

        $this->viewBuilder()
            ->setTemplate('membership_approved')
            ->setLayout('default');
    }

    /**
     * Notify club president that the club's national annual fee was recorded.
     *
     * @param \Cake\Datasource\EntityInterface $president
     * @param \Cake\Datasource\EntityInterface $club
     * @param int $membershipYear
     * @param string $associationName Localized national association name
     * @param string $paymentDateFormatted Localized payment date
     */
    public function clubNationalFeeRecorded(
        EntityInterface $president,
        EntityInterface $club,
        int $membershipYear,
        string $associationName,
        string $paymentDateFormatted,
    ): void {
        $presidentName = trim((string)($president->get('first_name') ?? ''));
        $clubName = trim((string)($club->get('name') ?? ''));
        $email = trim((string)($president->get('email') ?? ''));
        if ($email === '') {
            return;
        }

        $this
            ->setTo($email)
            ->setSubject(__('Club annual membership fee recorded for {0}', $clubName !== '' ? $clubName : __('your club')))
            ->setEmailFormat(Message::MESSAGE_BOTH)
            ->setViewVars([
                'presidentName' => $presidentName,
                'clubName' => $clubName,
                'membershipYear' => $membershipYear,
                'associationName' => $associationName,
                'paymentDateFormatted' => $paymentDateFormatted,
            ]);

        $this->viewBuilder()
            ->setTemplate('club_national_fee_recorded')
            ->setLayout('default');
    }
}
