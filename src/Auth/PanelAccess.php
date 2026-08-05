<?php
declare(strict_types=1);

namespace App\Auth;

use Cake\Http\ServerRequest;
use Cake\Routing\Router;

/**
 * Which role panels a user may open (member is shared; officers can step up/down).
 */
class PanelAccess
{
    /** @var list<string> Admin panel switcher targets (no `/new` — onboarding only). */
    public const ALL_ROLE_PREFIXES = [
        'Member',
        'Clubpresident',
        'President',
        'Admin',
    ];

    /**
     * Panel hierarchy — higher rank = more privileged prefix.
     */
    public static function prefixRank(string $prefix): int
    {
        $prefix = strtolower(trim($prefix));

        return match ($prefix) {
            'new' => 0,
            'member' => 10,
            'clubpresident' => 20,
            'president' => 30,
            'admin' => 40,
            default => 0,
        };
    }

    /**
     * PascalCase prefixes the user may open (unique, sorted by rank ascending).
     *
     * @return list<string>
     */
    public static function accessiblePrefixes(?ServerRequest $request = null): array
    {
        $request ??= Router::getRequest();
        $role = CurrentUser::role($request);

        if (static::canUseAdminPanel($request)) {
            return self::ALL_ROLE_PREFIXES;
        }

        $prefixes = [];

        $primary = RoleHome::prefix($role);
        if ($primary !== null && !static::isAdminPrefix($primary)) {
            $prefixes[] = $primary;
        }

        if (static::canUseMemberPanel($role)) {
            $prefixes[] = 'Member';
        }

        if (static::canUseClubPresidentPanel($request)) {
            $prefixes[] = 'Clubpresident';
        }

        $unique = [];
        foreach ($prefixes as $prefix) {
            if (!in_array($prefix, $unique, true)) {
                $unique[] = $prefix;
            }
        }

        usort($unique, static function (string $a, string $b): int {
            return static::prefixRank($a) <=> static::prefixRank($b);
        });

        return $unique;
    }

    public static function canAccessPrefix(string $prefix, ?ServerRequest $request = null): bool
    {
        if (static::isAdminPrefix($prefix) && !static::canUseAdminPanel($request)) {
            return false;
        }

        $prefix = ucfirst(strtolower(trim($prefix)));
        foreach (static::accessiblePrefixes($request) as $allowed) {
            if (strcasecmp($allowed, $prefix) === 0) {
                return true;
            }
        }

        return false;
    }

    /**
     * Admin panel: `admin` / `superuser` role or CakeDC superuser flag.
     */
    public static function canUseAdminPanel(?ServerRequest $request = null): bool
    {
        $request ??= Router::getRequest();
        $role = CurrentUser::role($request);

        return in_array($role, [AppRoles::ADMIN, AppRoles::SUPERUSER], true)
            || CurrentUser::isSuperuser($request);
    }

    /**
     * Member panel: every active role except `new` (locked to /new).
     */
    public static function canUseMemberPanel(string $role): bool
    {
        $role = strtolower(trim($role));

        return $role !== AppRoles::NEW;
    }

    /**
     * Club president panel: clubpresident / president / vicepresident with assigned club.
     * Admin / superuser: always (impersonation / support).
     */
    public static function canUseClubPresidentPanel(?ServerRequest $request = null): bool
    {
        $request ??= Router::getRequest();
        if (static::canUseAdminPanel($request)) {
            return true;
        }

        $role = CurrentUser::role($request);
        if (!in_array($role, [
            AppRoles::CLUBPRESIDENT,
            AppRoles::PRESIDENT,
            AppRoles::VICEPRESIDENT,
        ], true)) {
            return false;
        }

        return CurrentUser::clubId($request) > 0;
    }

    /**
     * Sidebar / menu links to other accessible panels.
     *
     * @return list<array{prefix: string, label: string, url: array<string, mixed>, direction: string}>
     */
    public static function switcherLinks(string $currentPrefix, ?ServerRequest $request = null): array
    {
        $request ??= Router::getRequest();
        $currentPrefix = ucfirst(strtolower(trim($currentPrefix)));
        $currentRank = static::prefixRank($currentPrefix);
        $links = [];

        foreach (static::accessiblePrefixes($request) as $prefix) {
            if (strcasecmp($prefix, $currentPrefix) === 0) {
                continue;
            }

            if (strcasecmp($prefix, 'New') === 0) {
                continue;
            }

            if (static::isAdminPrefix($prefix) && !static::canUseAdminPanel($request)) {
                continue;
            }

            if (
                strcasecmp($prefix, 'Clubpresident') === 0
                && !static::canUseClubPresidentPanel($request)
            ) {
                continue;
            }

            $rank = static::prefixRank($prefix);
            $direction = $rank > $currentRank ? 'up' : 'down';

            $links[] = [
                'prefix' => $prefix,
                'label' => RoleHome::brand($prefix),
                'url' => [
                    'prefix' => $prefix,
                    'controller' => 'Dashboard',
                    'action' => 'index',
                ],
                'direction' => $direction,
            ];
        }

        usort($links, static function (array $a, array $b): int {
            $rankA = static::prefixRank($a['prefix']);
            $rankB = static::prefixRank($b['prefix']);
            if ($rankA !== $rankB) {
                return $rankB <=> $rankA;
            }

            return strcasecmp($a['label'], $b['label']);
        });

        return $links;
    }

    protected static function isAdminPrefix(string $prefix): bool
    {
        return strcasecmp(trim($prefix), 'Admin') === 0;
    }
}
