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
     * Required profile fields after registration.
     *
     * @return list<string>
     */
    public static function requiredFields(): array
    {
        return ['first_name', 'last_name', 'phone', 'country_id', 'club_id'];
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
