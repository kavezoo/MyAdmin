<?php
declare(strict_types=1);

namespace App\Auth;

use App\Utility\CompetitionStaff;

/**
 * Role → panel prefix home (URL / Cake route array).
 *
 * Registration.defaultRole `new` → only `/new` (unless competition staff).
 */
class RoleHome
{
    /**
     * CakePHP prefix name (PascalCase) for a Users.role key, or null if unknown.
     */
    public static function prefix(string $role): ?string
    {
        $role = strtolower(trim($role));

        return match ($role) {
            AppRoles::NEW => 'New',
            AppRoles::MEMBER, AppRoles::EDITOR => 'Member',
            AppRoles::CLUBPRESIDENT => 'Clubpresident',
            AppRoles::PRESIDENT, AppRoles::VICEPRESIDENT => 'President',
            AppRoles::ADMIN, AppRoles::SUPERUSER => 'Admin',
            default => null,
        };
    }

    /**
     * Absolute path after login, e.g. `/new`.
     */
    public static function path(string $role): string
    {
        $prefix = static::prefix($role);
        if ($prefix === null) {
            return '/login';
        }

        return '/' . strtolower($prefix);
    }

    /**
     * Cake redirect URL array for Dashboard of the role's panel.
     *
     * Guests with staff assignment land on their staff panel (first by rank).
     *
     * @return array<string, mixed>
     */
    public static function url(string $role, ?string $userId = null): array
    {
        $role = strtolower(trim($role));
        if ($role === AppRoles::NEW && $userId !== null && $userId !== '') {
            $staff = CompetitionStaff::assignedPrefixes($userId);
            if ($staff !== []) {
                $prefix = $staff[0];
                if (strcasecmp($prefix, 'Checkin') === 0) {
                    return [
                        'prefix' => 'Checkin',
                        'controller' => 'Applicants',
                        'action' => 'index',
                    ];
                }

                return [
                    'prefix' => $prefix,
                    'controller' => 'Dashboard',
                    'action' => 'index',
                ];
            }
        }

        $prefix = static::prefix($role);
        if ($prefix === null) {
            return ['plugin' => null, 'controller' => 'Users', 'action' => 'login'];
        }

        return [
            'prefix' => $prefix,
            'controller' => 'Dashboard',
            'action' => 'index',
        ];
    }

    /**
     * Brand label for the header logo (translated).
     */
    public static function brand(string $prefix): string
    {
        $prefix = strtolower(trim($prefix));

        return match ($prefix) {
            'new' => AppRoles::label(AppRoles::NEW),
            'member' => AppRoles::label(AppRoles::MEMBER),
            'clubpresident' => AppRoles::label(AppRoles::CLUBPRESIDENT),
            'president' => AppRoles::label(AppRoles::PRESIDENT),
            'admin' => __('Admin'),
            'checkin' => __('Check-in'),
            'judge' => __('Judge'),
            default => __('Admin'),
        };
    }

    /**
     * Sidebar element path relative to templates/element/.
     */
    public static function sidebarElement(string $prefix): string
    {
        $prefix = strtolower(trim($prefix));

        return match ($prefix) {
            'new' => 'new/sidebar',
            'member' => 'member/sidebar',
            'clubpresident' => 'clubpresident/sidebar',
            'president' => 'president/sidebar',
            'checkin' => 'checkin/sidebar',
            'judge' => 'judge/sidebar',
            default => 'admin/sidebar',
        };
    }
}
