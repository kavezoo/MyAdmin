<?php
declare(strict_types=1);

namespace App\Utility;

use App\Auth\AppRoles;

/**
 * Setup `edit_by` levels — minimum role that may change the value.
 *
 * - superuser: system-critical — only Superuser
 * - admin: Admin and Superuser
 * - president: President, Vice president, Admin, Superuser
 *
 * Creating new setup rows: Superuser only (see SetupAccess).
 */
class SetupEditBy
{
    public const SUPERUSER = 'superuser';

    public const ADMIN = 'admin';

    public const PRESIDENT = 'president';

    /**
     * Roles allowed to change a value at each edit_by level.
     *
     * @var array<string, list<string>>
     */
    protected const ROLE_MAP = [
        self::SUPERUSER => [AppRoles::SUPERUSER],
        self::ADMIN => [AppRoles::SUPERUSER, AppRoles::ADMIN],
        self::PRESIDENT => [
            AppRoles::SUPERUSER,
            AppRoles::ADMIN,
            AppRoles::PRESIDENT,
            AppRoles::VICEPRESIDENT,
        ],
    ];

    /**
     * @return list<string>
     */
    public static function list(): array
    {
        return [self::SUPERUSER, self::ADMIN, self::PRESIDENT];
    }

    public static function isValid(string $editBy): bool
    {
        return in_array(strtolower(trim($editBy)), static::list(), true);
    }

    /**
     * Select options: edit_by key => "key — Translated role label".
     *
     * @return array<string, string>
     */
    public static function options(): array
    {
        $out = [];
        foreach (static::list() as $level) {
            $out[$level] = AppRoles::labeled($level);
        }

        return $out;
    }

    /**
     * Short translated explanation for forms.
     */
    public static function helpText(): string
    {
        return __('Who may change this value: Superuser (system-critical), Admin and above, or President / Vice president level.');
    }

    public static function label(string $editBy): string
    {
        $editBy = strtolower(trim($editBy));
        if (!static::isValid($editBy)) {
            return $editBy;
        }

        return AppRoles::labeled($editBy);
    }

    /**
     * Whether $role may edit a setup value with the given edit_by level.
     */
    public static function allows(string $editBy, string $role): bool
    {
        $editBy = strtolower(trim($editBy));
        $role = strtolower(trim($role));
        if (!static::isValid($editBy)) {
            $editBy = self::ADMIN;
        }
        $allowed = self::ROLE_MAP[$editBy] ?? self::ROLE_MAP[self::ADMIN];

        return in_array($role, $allowed, true);
    }

    /**
     * Map legacy DB values to current levels.
     */
    public static function normalizeStored(string $editBy): string
    {
        $editBy = strtolower(trim($editBy));
        if ($editBy === 'officers') {
            return self::PRESIDENT;
        }
        if (static::isValid($editBy)) {
            return $editBy;
        }

        return self::ADMIN;
    }
}
