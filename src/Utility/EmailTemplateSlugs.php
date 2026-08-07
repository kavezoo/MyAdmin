<?php
declare(strict_types=1);

namespace App\Utility;

/**
 * Known email template slugs (DB `email_templates.slug`).
 */
final class EmailTemplateSlugs
{
    public const MEMBERSHIP_APPLICATION = 'membership_application';

    public const MEMBERSHIP_APPROVED = 'membership_approved';

    public const CLUB_NATIONAL_FEE_RECORDED = 'club_national_fee_recorded';

    /**
     * @return array<string, string> slug => admin label msgid
     */
    public static function options(): array
    {
        return [
            self::MEMBERSHIP_APPLICATION => __('Membership application (to club president)'),
            self::MEMBERSHIP_APPROVED => __('Membership approved (to member)'),
            self::CLUB_NATIONAL_FEE_RECORDED => __('Club national fee recorded (to club president)'),
        ];
    }
}
