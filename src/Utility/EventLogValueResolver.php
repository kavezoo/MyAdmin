<?php
declare(strict_types=1);

namespace App\Utility;

use App\Auth\AppRoles;
use App\Auth\MembershipProfile;
use App\Utility\LocaleDateParser;
use App\Utility\MembershipFee;
use Cake\ORM\Locator\LocatorAwareTrait;

/**
 * Human-friendly values for activity log diffs (FK labels, booleans, statuses).
 *
 * Raw IDs stay in request_data; resolution happens at display time (locale-aware).
 */
class EventLogValueResolver
{
    use LocatorAwareTrait;

    /** @var array<int, string> */
    protected static array $countryLabels = [];

    /** @var array<int, string> */
    protected static array $clubLabels = [];

  /**
   * Display one side of a field diff for users / officers.
   */
    public static function display(string $module, string $field, mixed $value): string
    {
        if ($value === '?') {
            return (string)__('unknown');
        }

        if (EventLogger::isSecretField($field)) {
            return static::markerLabel((string)$value, __('changed'));
        }

        if ($field === 'avatar') {
            return static::avatarLabel($value);
        }

        if ($value === null || $value === '') {
            return (string)__('empty');
        }

        if (is_string($value) && str_starts_with($value, '[') && str_ends_with($value, ']')) {
            return static::markerLabel($value, $value);
        }

        if ($field === 'country_id' || $field === 'visible_country_id') {
            return static::countryLabel((int)$value);
        }

        if ($field === 'club_id') {
            return static::clubLabel((int)$value);
        }

        if ($field === 'role') {
            return AppRoles::label((string)$value);
        }

        if ($field === 'membership_status') {
            return static::membershipStatusLabel((string)$value);
        }

        if ($field === 'club_membership_fee_date' || $field === 'national_membership_fee_date') {
            return static::membershipFeeDateLabel($module, $field, $value);
        }

        if (static::isBooleanField($field) && static::isBoolish($value)) {
            return static::boolLabel($value);
        }

        if ($module === 'Setups' && $field === 'value' && static::isBoolish($value)) {
            return static::boolLabel($value);
        }

        if (is_bool($value)) {
            return static::boolLabel($value);
        }

        return EventLogChanges::formatValue($value);
    }

    protected static function markerLabel(string $marker, string $fallback): string
    {
        return match ($marker) {
            '[empty]' => (string)__('empty'),
            '[set]' => (string)__('set'),
            '[changed]' => (string)__('changed'),
            '[redacted]' => (string)__('redacted'),
            '[complex]' => (string)__('complex value'),
            default => $fallback,
        };
    }

    protected static function avatarLabel(mixed $value): string
    {
        if ($value === null || $value === '' || $value === '[empty]') {
            return (string)__('no picture');
        }
        if ($value === '[set]' || $value === '[changed]') {
            return (string)__('picture set');
        }

        return (string)__('picture set');
    }

    protected static function membershipStatusLabel(string $status): string
    {
        $status = strtolower(trim($status));

        return match ($status) {
            MembershipProfile::STATUS_INCOMPLETE => (string)__('Incomplete'),
            MembershipProfile::STATUS_PENDING => (string)__('Pending approval'),
            MembershipProfile::STATUS_APPROVED => (string)__('Approved'),
            '' => (string)__('empty'),
            default => $status,
        };
    }

    protected static function membershipFeeDateLabel(string $module, string $field, mixed $value): string
    {
        if ($value === null || $value === '' || $value === '[empty]') {
            return (string)__('empty');
        }

        try {
            if ($value instanceof \Cake\I18n\Date) {
                return LocaleDateParser::format($value, 'date');
            }
            if (is_string($value) && preg_match('/^\d{4}-\d{2}-\d{2}/', $value)) {
                return LocaleDateParser::format(substr($value, 0, 10), 'date');
            }
        } catch (\Throwable) {
            // fall through
        }

        return EventLogChanges::formatValue($value);
    }

    protected static function boolLabel(mixed $value): string
    {
        if ($value === 1 || $value === '1' || $value === true) {
            return (string)__('Yes');
        }

        return (string)__('No');
    }

    protected static function isBoolish(mixed $value): bool
    {
        return $value === 0
            || $value === 1
            || $value === '0'
            || $value === '1'
            || is_bool($value);
    }

    protected static function isBooleanField(string $field): bool
    {
        return in_array($field, [
            'enabled',
            'active',
            'visible',
            'master',
            'obsolete',
            'is_superuser',
        ], true) || str_starts_with($field, 'is_');
    }

    protected static function countryLabel(int $countryId): string
    {
        if ($countryId < 1) {
            return (string)__('empty');
        }
        if (isset(static::$countryLabels[$countryId])) {
            return static::$countryLabels[$countryId];
        }

        $label = AdminCountry::label($countryId);
        if ($label === '') {
            $label = '#' . $countryId;
        }
        static::$countryLabels[$countryId] = $label;

        return $label;
    }

    protected static function clubLabel(int $clubId): string
    {
        if ($clubId < 1) {
            return (string)__('empty');
        }
        if (isset(static::$clubLabels[$clubId])) {
            return static::$clubLabels[$clubId];
        }

        try {
            /** @var \App\Model\Table\ClubsTable $clubs */
            $clubs = (new self())->fetchTable('Clubs');
            $club = $clubs->find()
                ->select(['name'])
                ->where(['Clubs.id' => $clubId])
                ->first();
            $label = $club !== null ? trim((string)$club->get('name')) : '';
            if ($label === '') {
                $label = '#' . $clubId;
            }
        } catch (\Throwable) {
            $label = '#' . $clubId;
        }

        static::$clubLabels[$clubId] = $label;

        return $label;
    }
}
