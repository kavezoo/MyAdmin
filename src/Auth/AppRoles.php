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
}