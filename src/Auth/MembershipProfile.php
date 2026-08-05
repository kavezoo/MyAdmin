<?php
declare(strict_types=1);

namespace App\Auth;

/**
 * Membership onboarding for role `new` → profile complete → pending → `member`.
 *
 * Accepts entity, identity (get()), or array.
 */
class MembershipProfile
{
    public const STATUS_INCOMPLETE = 'incomplete';

    public const STATUS_PENDING = 'pending';

    public const STATUS_APPROVED = 'approved';

    /**
     * Member since date — non-null means the application was accepted.
     */
    public const FIELD_JOINED = 'membership_joined_date';

    /**
     * Required profile fields after registration.
     *
     * @return list<string>
     */
    public static function requiredFields(): array
    {
        return ['first_name', 'country_id', 'club_id'];
    }

    /**
     * True when the applicant was accepted (joined date set).
     */
    public static function isJoined(mixed $user): bool
    {
        $date = static::value($user, self::FIELD_JOINED);

        return $date !== null && $date !== '';
    }

    /**
     * Human description for event_logs (membership join / enable).
     *
     * @param array<string, array{from: mixed, to: mixed}> $changes
     */
    public static function activityDescriptions(
        \Cake\Datasource\EntityInterface $user,
        array $changes,
        bool $created
    ): string {
        $name = static::displayName($user);
        if ($name === '') {
            $name = trim((string)($user->get('email') ?? ''));
        }
        if ($name === '') {
            $name = (string)__('user');
        }

        $parts = [];

        if (isset($changes[self::FIELD_JOINED])) {
            $to = $changes[self::FIELD_JOINED]['to'] ?? null;
            $from = $changes[self::FIELD_JOINED]['from'] ?? null;
            $toEmpty = $to === null || $to === '' || $to === '[empty]';
            $fromEmpty = $from === null || $from === '' || $from === '[empty]';
            if ($fromEmpty && !$toEmpty) {
                $formatted = is_object($to) && method_exists($to, 'format')
                    ? \App\Utility\LocaleDateParser::format($to, 'date')
                    : (string)$to;
                $parts[] = (string)__('Membership approved — member since {0}', $formatted);
            } elseif (!$fromEmpty && $toEmpty) {
                $parts[] = (string)__('Membership join date cleared');
            } elseif (!$fromEmpty && !$toEmpty) {
                $parts[] = (string)__('Membership join date changed');
            }
        }

        if (isset($changes['role']) || isset($changes['membership_status'])) {
            $roleTo = (string)($changes['role']['to'] ?? $user->get('role') ?? '');
            $statusTo = (string)($changes['membership_status']['to'] ?? $user->get('membership_status') ?? '');
            if ($roleTo === AppRoles::MEMBER || $statusTo === self::STATUS_APPROVED) {
                if ($parts === []) {
                    $parts[] = (string)__('Approved as club member');
                }
            }
        }

        if (isset($changes['enabled'])) {
            $toEnabled = filter_var($changes['enabled']['to'] ?? null, FILTER_VALIDATE_BOOLEAN)
                || $changes['enabled']['to'] === 1
                || $changes['enabled']['to'] === '1';
            $fromRaw = $changes['enabled']['from'] ?? null;
            $fromEnabled = filter_var($fromRaw, FILTER_VALIDATE_BOOLEAN)
                || $fromRaw === 1
                || $fromRaw === '1';
            // Cake may store 0/1 ints
            if (is_numeric($changes['enabled']['to'] ?? null)) {
                $toEnabled = (int)$changes['enabled']['to'] === 1;
            }
            if (is_numeric($fromRaw)) {
                $fromEnabled = (int)$fromRaw === 1;
            }
            if ($toEnabled && !$fromEnabled) {
                $parts[] = (string)__('Account enabled');
            } elseif (!$toEnabled && $fromEnabled) {
                $parts[] = (string)__('Account disabled');
            }
        }

        if ($parts === []) {
            return '';
        }

        $prefix = $created
            ? (string)__('Membership record created for {0}', $name)
            : (string)__('Membership updated for {0}', $name);

        return $prefix . ': ' . implode('; ', $parts);
    }

    public static function statusOf(mixed $user): string
    {
        $status = strtolower(trim((string)(static::value($user, 'membership_status') ?? '')));

        return in_array($status, [self::STATUS_INCOMPLETE, self::STATUS_PENDING, self::STATUS_APPROVED], true)
            ? $status
            : self::STATUS_INCOMPLETE;
    }

    public static function isComplete(mixed $user): bool
    {
        foreach (static::requiredFields() as $field) {
            $value = static::value($user, $field);
            if ($field === 'country_id' || $field === 'club_id') {
                if ((int)$value < 1) {
                    return false;
                }
                continue;
            }
            if (trim((string)$value) === '') {
                return false;
            }
        }

        return true;
    }

    public static function isPending(mixed $user): bool
    {
        return static::statusOf($user) === self::STATUS_PENDING;
    }

    public static function needsProfileCompletion(mixed $user): bool
    {
        $role = strtolower(trim((string)(static::value($user, 'role') ?? '')));

        return $role === AppRoles::NEW && !static::isComplete($user);
    }

    /**
     * Own profile edit + avatar upload (all valid roles, including `new`).
     */
    public static function canEditOwnProfile(mixed $user): bool
    {
        $role = strtolower(trim((string)(static::value($user, 'role') ?? '')));
        if ($role === '') {
            return false;
        }

        return AppRoles::isValid($role);
    }

    /**
     * Display name: registration uses a single `first_name` field.
     */
    public static function displayName(mixed $user): string
    {
        $first = trim((string)(static::value($user, 'first_name') ?? ''));
        $last = trim((string)(static::value($user, 'last_name') ?? ''));
        if ($first !== '' && $last !== '') {
            return $first . ' ' . $last;
        }

        return $first !== '' ? $first : $last;
    }

    /**
     * True when user switches from one club to another (not first assignment).
     */
    public static function isClubSwitch(int $previousClubId, int $newClubId): bool
    {
        return $previousClubId > 0 && $newClubId > 0 && $previousClubId !== $newClubId;
    }

    public static function wasNotified(mixed $user): bool
    {
        return !empty(static::value($user, 'application_notified'));
    }

    protected static function value(mixed $user, string $field): mixed
    {
        if (is_array($user)) {
            return $user[$field] ?? null;
        }
        if (is_object($user) && method_exists($user, 'get')) {
            return $user->get($field);
        }
        if (is_object($user) && isset($user->{$field})) {
            return $user->{$field};
        }

        return null;
    }
}
