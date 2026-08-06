<?php
declare(strict_types=1);

namespace App\Auth;

/**
 * Canonical application roles (keys stored on Users after CakeDC install).
 *
 * Display: role key + translated label, e.g. "admin — Admin".
 * Msgids are English; translations live in resources/locales/*.po.
 */
class AppRoles
{
    public const SUPERUSER = 'superuser';

    public const ADMIN = 'admin';

    public const PRESIDENT = 'president';

    public const VICEPRESIDENT = 'vicepresident';

    public const CLUBPRESIDENT = 'clubpresident';

    public const EDITOR = 'editor';

    public const MEMBER = 'member';

    public const NEW = 'new';

    /**
     * @return list<string>
     */
    public static function list(): array
    {
        return [
            self::SUPERUSER,
            self::ADMIN,
            self::PRESIDENT,
            self::VICEPRESIDENT,
            self::CLUBPRESIDENT,
            self::EDITOR,
            self::MEMBER,
            self::NEW,
        ];
    }

    public static function isValid(string $role): bool
    {
        return in_array(strtolower(trim($role)), static::list(), true);
    }

    /**
     * Translated display name only (msgid English).
     */
    public static function label(string $role): string
    {
        $role = strtolower(trim($role));

        return match ($role) {
            self::SUPERUSER => __('Superuser'),
            self::ADMIN => __('Admin'),
            self::PRESIDENT => __('President'),
            self::VICEPRESIDENT => __('Vice president'),
            self::CLUBPRESIDENT => __('Club president'),
            self::EDITOR => __('Editor'),
            self::MEMBER => __('Member'),
            self::NEW => __('New member'),
            default => $role,
        };
    }

    /**
     * "role — Translated label" for selects / lists.
     */
    public static function labeled(string $role): string
    {
        $role = strtolower(trim($role));

        return $role . ' — ' . static::label($role);
    }

    /**
     * All roles: key => "key — Label".
     *
     * @return array<string, string>
     */
    public static function options(): array
    {
        $out = [];
        foreach (static::list() as $role) {
            $out[$role] = static::labeled($role);
        }

        return $out;
    }

    /**
     * Roles that may open the Setups module (menu + URL).
     * Only superuser — see {@see \App\Auth\SetupAccess::canAccessModule()}.
     *
     * @return list<string>
     */
    public static function setupsModuleRoles(): array
    {
        return [
            self::SUPERUSER,
        ];
    }

    /**
     * Roles shown on President / Clubpresident Members lists (fee roster).
     *
     * Includes officers who are also club/country members (e.g. clubpresident
     * must see themselves). Excludes `new` (applicant cards) and admin/superuser.
     *
     * @return list<string>
     */
    public static function membershipRosterRoles(): array
    {
        return [
            self::MEMBER,
            self::EDITOR,
            self::CLUBPRESIDENT,
            self::PRESIDENT,
            self::VICEPRESIDENT,
        ];
    }

    /**
     * Roles that may use the global header search (Admin Search).
     * President / vicepresident and above; not clubpresident or below.
     *
     * @return list<string>
     */
    public static function globalSearchRoles(): array
    {
        return [
            self::SUPERUSER,
            self::ADMIN,
            self::PRESIDENT,
            self::VICEPRESIDENT,
        ];
    }

    public static function canUseGlobalSearch(string $role): bool
    {
        return in_array(strtolower(trim($role)), static::globalSearchRoles(), true);
    }

    /**
     * Higher number = higher privilege.
     */
    public static function rank(string $role): int
    {
        return match (strtolower(trim($role))) {
            self::SUPERUSER => 80,
            self::ADMIN => 70,
            self::PRESIDENT => 60,
            self::VICEPRESIDENT => 50,
            self::CLUBPRESIDENT => 40,
            self::EDITOR => 30,
            self::MEMBER => 20,
            self::NEW => 10,
            default => 0,
        };
    }

    /**
     * Roles a president / vicepresident may assign on the Members edit form.
     * Max = president; no admin / superuser / new.
     *
     * @return list<string>
     */
    public static function presidentAssignableRoles(): array
    {
        return [
            self::MEMBER,
            self::EDITOR,
            self::CLUBPRESIDENT,
            self::VICEPRESIDENT,
            self::PRESIDENT,
        ];
    }

    /**
     * Select options: key => "key — Label" for {@see presidentAssignableRoles()}.
     *
     * @return array<string, string>
     */
    public static function presidentAssignableOptions(): array
    {
        $out = [];
        foreach (static::presidentAssignableRoles() as $role) {
            $out[$role] = static::labeled($role);
        }

        return $out;
    }

    /**
     * Actor may set this target role on the President Members edit form.
     * Vicepresident cannot assign `president`; only president / admin / superuser can.
     */
    public static function canAssignRole(string $actorRole, string $newRole): bool
    {
        $actorRole = strtolower(trim($actorRole));
        $newRole = strtolower(trim($newRole));
        if (!static::isPresidentAssignable($newRole)) {
            return false;
        }
        if (!in_array($actorRole, [
            self::PRESIDENT,
            self::VICEPRESIDENT,
            self::ADMIN,
            self::SUPERUSER,
        ], true)) {
            return false;
        }
        if ($newRole === self::PRESIDENT) {
            return in_array($actorRole, [self::PRESIDENT, self::ADMIN, self::SUPERUSER], true);
        }

        return true;
    }

    /**
     * Actor may change this member's current role (e.g. VP cannot edit a president).
     */
    public static function canEditTargetRole(string $actorRole, string $targetRole): bool
    {
        $actorRole = strtolower(trim($actorRole));
        $targetRole = strtolower(trim($targetRole));
        if (in_array($targetRole, [self::ADMIN, self::SUPERUSER], true)) {
            return false;
        }
        if ($targetRole === self::PRESIDENT) {
            return in_array($actorRole, [self::PRESIDENT, self::ADMIN, self::SUPERUSER], true);
        }
        if (!static::isPresidentAssignable($targetRole) && $targetRole !== '') {
            return false;
        }

        return in_array($actorRole, [
            self::PRESIDENT,
            self::VICEPRESIDENT,
            self::ADMIN,
            self::SUPERUSER,
        ], true);
    }

    /**
     * Role select options filtered by the logged-in officer.
     *
     * @return array<string, string>
     */
    public static function assignableOptionsForActor(string $actorRole): array
    {
        $out = [];
        foreach (static::presidentAssignableRoles() as $role) {
            if (static::canAssignRole($actorRole, $role)) {
                $out[$role] = static::labeled($role);
            }
        }

        return $out;
    }

    /**
     * Club president assignment: promote to `clubpresident` only from member / editor.
     * President / vicepresident (and above) keep their current role.
     */
    public static function shouldPromoteToClubPresident(string $role): bool
    {
        $role = strtolower(trim($role));

        return in_array($role, [self::MEMBER, self::EDITOR], true);
    }

    /**
     * Profile club switch: these officers keep their role (no re-application as `new`).
     * Clubpresident / president / vicepresident simply join the other club.
     */
    public static function keepsRoleOnClubSwitch(string $role): bool
    {
        return in_array(strtolower(trim($role)), [
            self::CLUBPRESIDENT,
            self::PRESIDENT,
            self::VICEPRESIDENT,
        ], true);
    }

    /**
     * When someone else becomes club elnök: demote only pure `clubpresident` → member.
     * President / vicepresident keep their role.
     */
    public static function shouldDemoteFromClubPresident(string $role): bool
    {
        return strtolower(trim($role)) === self::CLUBPRESIDENT;
    }

    public static function isPresidentAssignable(string $role): bool
    {
        return in_array(strtolower(trim($role)), static::presidentAssignableRoles(), true);
    }
}