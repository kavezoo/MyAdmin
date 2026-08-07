<?php
declare(strict_types=1);

namespace App\Mailer;

use App\Utility\EmailTemplateService;
use App\Utility\EmailTemplateSlugs;
use Cake\Datasource\EntityInterface;
use Cake\I18n\I18n;
use Cake\Mailer\Mailer;
use Cake\Mailer\Message;
use Cake\Routing\Router;

/**
 * Membership application / approval emails.
 *
 * Prefer DB `email_templates` (recipient language); fall back to Cake view templates + __().
 */
class MembershipMailer extends Mailer
{
    /**
     * Notify club president(s) about a new applicant.
     */
    public function applicationReceived(
        EntityInterface $applicant,
        EntityInterface $president,
        string $clubName,
    ): void {
        $name = $this->personName($applicant);
        $listUrl = Router::url([
            'prefix' => 'Clubpresident',
            'controller' => 'Members',
            'action' => 'index',
            '_full' => true,
        ]);
        $vars = [
            'applicantName' => $name,
            'applicantEmail' => (string)$applicant->get('email'),
            'clubName' => $clubName,
            'listUrl' => $listUrl,
            'presidentName' => trim((string)($president->get('first_name') ?? '')),
        ];

        $locale = EmailTemplateService::localeForUser($president);
        $this->deliverLocalized(
            $president,
            EmailTemplateSlugs::MEMBERSHIP_APPLICATION,
            $vars,
            $locale,
            fn () => __('New membership application: {0}', $name),
            'membership_application',
        );
    }

    /**
     * Notify the new member that the club president approved them.
     */
    public function membershipApproved(EntityInterface $member, string $clubName): void
    {
        $name = $this->personName($member);
        $loginUrl = Router::url([
            'plugin' => null,
            'prefix' => false,
            'controller' => 'Users',
            'action' => 'login',
            '_full' => true,
        ]);
        $vars = [
            'memberName' => $name,
            'clubName' => $clubName,
            'loginUrl' => $loginUrl,
        ];

        $locale = EmailTemplateService::localeForUser($member);
        $this->deliverLocalized(
            $member,
            EmailTemplateSlugs::MEMBERSHIP_APPROVED,
            $vars,
            $locale,
            fn () => __('Your membership has been approved'),
            'membership_approved',
        );
    }

    /**
     * Notify club president that the club's national annual fee was recorded.
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

        $vars = [
            'presidentName' => $presidentName,
            'clubName' => $clubName,
            'membershipYear' => (string)$membershipYear,
            'associationName' => $associationName,
            'paymentDateFormatted' => $paymentDateFormatted,
        ];

        $locale = EmailTemplateService::localeForUser($president);
        $this->deliverLocalized(
            $president,
            EmailTemplateSlugs::CLUB_NATIONAL_FEE_RECORDED,
            $vars,
            $locale,
            fn () => __('Club annual membership fee recorded for {0}', $clubName !== '' ? $clubName : __('your club')),
            'club_national_fee_recorded',
        );
    }

    /**
     * @param callable(): string $fallbackSubject
     * @param array<string, scalar|null> $vars
     */
    protected function deliverLocalized(
        EntityInterface $recipient,
        string $slug,
        array $vars,
        string $locale,
        callable $fallbackSubject,
        string $fallbackTemplate,
    ): void {
        $email = trim((string)$recipient->get('email'));
        if ($email === '') {
            return;
        }

        $previous = I18n::getLocale();
        I18n::setLocale($locale);
        try {
            $rendered = EmailTemplateService::renderForUser($recipient, $slug, $vars);
            if ($rendered !== null) {
                $this
                    ->setTo($email)
                    ->setSubject($rendered['subject'])
                    ->setEmailFormat(Message::MESSAGE_BOTH);
                $this->viewBuilder()
                    ->setTemplate('email_template_db')
                    ->setLayout('default');
                $this->setViewVars([
                    'bodyHtml' => $rendered['body_html'],
                    'bodyText' => $rendered['body_text'],
                ]);

                return;
            }

            $this
                ->setTo($email)
                ->setSubject($fallbackSubject())
                ->setEmailFormat(Message::MESSAGE_BOTH)
                ->setViewVars($vars);
            $this->viewBuilder()
                ->setTemplate($fallbackTemplate)
                ->setLayout('default');
        } finally {
            I18n::setLocale($previous);
        }
    }

    protected function personName(EntityInterface $user): string
    {
        $name = trim((string)($user->get('first_name') ?? '') . ' ' . (string)($user->get('last_name') ?? ''));
        if ($name === '') {
            return (string)($user->get('email') ?? '');
        }

        return $name;
    }
}
