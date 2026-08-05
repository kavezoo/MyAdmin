<?php
declare(strict_types=1);

namespace App\Utility;

use Cake\Datasource\EntityInterface;
use Cake\I18n\Date;
use Cake\I18n\I18n;

/**
 * Club + national membership fee payment dates on users.
 *
 * A non-null date whose calendar year matches the target year means membership is paid for that year.
 */
class MembershipFee
{
    public const FIELD_CLUB = 'club_membership_fee_date';

    public const FIELD_NATIONAL = 'national_membership_fee_date';

    /** @var list<string> */
    public const DATE_FIELDS = [
        self::FIELD_CLUB,
        self::FIELD_NATIONAL,
    ];

    public static function currentYear(): int
    {
        return (int)date('Y');
    }

    public static function clubFeeLabel(?int $countryId = null): string
    {
        return (string)__('Club membership fee');
    }

    public static function nationalFeeLabel(?int $countryId = null): string
    {
        $countryId = (int)($countryId ?? 0);
        if ($countryId > 0) {
            $iso2 = strtoupper(trim((string)(AdminCountry::iso2Map([$countryId])[$countryId] ?? '')));
            if ($iso2 === 'HU') {
                return (string)__('MPE membership fee');
            }
        }

        return (string)__('National association membership fee');
    }

    public static function fieldLabel(string $field, ?int $countryId = null): string
    {
        return match ($field) {
            self::FIELD_CLUB => static::clubFeeLabel($countryId),
            self::FIELD_NATIONAL => static::nationalFeeLabel($countryId),
            default => $field,
        };
    }

    public static function isPaidForYear(mixed $date, int $year): bool
    {
        $date = static::toDate($date);
        if ($date === null) {
            return false;
        }

        return (int)$date->format('Y') === $year;
    }

    public static function paymentDisplay(mixed $date, int $year): string
    {
        $date = static::toDate($date);
        if (!static::isPaidForYear($date, $year)) {
            return (string)__('Not paid for {0}', $year);
        }

        return LocaleDateParser::format($date, 'date', I18n::getLocale());
    }

    /**
     * Localized date string when paid for year; empty otherwise.
     */
    public static function paidDateFormatted(mixed $date, int $year): string
    {
        $date = static::toDate($date);
        if (!static::isPaidForYear($date, $year)) {
            return '';
        }

        return LocaleDateParser::format($date, 'date', I18n::getLocale());
    }

    /**
     * Localized stored payment date (any year); empty when the field is null.
     */
    public static function lastPaymentFormatted(mixed $date): string
    {
        $date = static::toDate($date);
        if ($date === null) {
            return '';
        }

        return LocaleDateParser::format($date, 'date', I18n::getLocale());
    }

    public static function today(): Date
    {
        return Date::today();
    }

    /**
     * WHERE fragments for “paid for calendar year” on a date column.
     *
     * @return array<string, string>
     */
    public static function paidForYearConditions(string $field, int $year): array
    {
        return [
            $field . ' >=' => sprintf('%04d-01-01', $year),
            $field . ' <=' => sprintf('%04d-12-31', $year),
        ];
    }

    /**
     * Human description for event_logs (call inside ActivityLogLocale::runForCountry).
     *
     * @param array<string, array{from: mixed, to: mixed}> $changes
     */
    public static function activityDescriptions(
        EntityInterface $user,
        array $changes,
        bool $created
    ): string {
        $countryId = (int)($user->get('country_id') ?? 0);
        $name = trim((string)($user->get('first_name') ?? ''));
        if ($name === '') {
            $name = trim((string)($user->get('email') ?? ''));
        }

        $parts = [];
        foreach (self::DATE_FIELDS as $field) {
            if (!isset($changes[$field])) {
                continue;
            }
            $pair = $changes[$field];
            $from = static::toDate($pair['from'] ?? null);
            $to = static::toDate($pair['to'] ?? null);
            $label = static::fieldLabel($field, $countryId);
            $parts[] = static::describeChange($label, $from, $to);
        }

        if ($parts === []) {
            return '';
        }

        $prefix = $created
            ? (string)__('Membership fee record created for {0}', $name !== '' ? $name : __('user'))
            : (string)__('Membership fee updated for {0}', $name !== '' ? $name : __('user'));

        return $prefix . ': ' . implode('; ', $parts);
    }

    protected static function describeChange(string $label, ?Date $from, ?Date $to): string
    {
        $fromEmpty = $from === null;
        $toEmpty = $to === null;

        if ($fromEmpty && !$toEmpty) {
            return (string)__(
                '{0} payment recorded for {1}: {2}',
                $label,
                $to->format('Y'),
                static::formatDateForLog($to)
            );
        }

        if (!$fromEmpty && $toEmpty) {
            return (string)__(
                '{0} payment removed (was {1} for {2})',
                $label,
                static::formatDateForLog($from),
                $from->format('Y')
            );
        }

        if (!$fromEmpty && !$toEmpty) {
            return (string)__(
                '{0} payment date changed for {1}: {2} → {3}',
                $label,
                $to->format('Y'),
                static::formatDateForLog($from),
                static::formatDateForLog($to)
            );
        }

        return (string)__('{0} payment cleared', $label);
    }

    protected static function formatDateForLog(?Date $date): string
    {
        if ($date === null) {
            return (string)__('empty');
        }

        return LocaleDateParser::format($date, 'date', I18n::getLocale());
    }

    protected static function toDate(mixed $value): ?Date
    {
        if ($value === null || $value === '' || $value === '[empty]') {
            return null;
        }
        if ($value instanceof Date) {
            return $value;
        }
        if ($value instanceof \DateTimeInterface) {
            return new Date($value->format('Y-m-d'));
        }
        if (is_string($value)) {
            $trim = trim($value);
            if ($trim === '' || str_starts_with($trim, '[')) {
                return null;
            }
            try {
                return new Date(substr($trim, 0, 10));
            } catch (\Throwable) {
                return null;
            }
        }

        return null;
    }
}
