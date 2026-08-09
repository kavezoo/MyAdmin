<?php
declare(strict_types=1);

namespace App\Auth;

use App\Utility\CompetitionStaff;
use Cake\Http\ServerRequest;
use Cake\Routing\Router;

/**
 * Which role panels a user may open (member is shared; officers can step up/down).
 * Competition staff prefixes (Checkin / Judge) come from competition_staff assignments.
 */
class PanelAccess
{
    /**
     * Membership hierarchy targets for admin switcher (staff prefixes added dynamically).
     *
     * @var list<string>
     */
    public const ALL_ROLE_PREFIXES = [
        'Member',
        'Clubpresident',
        'President',
        'Admin',
    ];

    /** @var list<string> */
    public const STAFF_PREFIXES = [
        'Checkin',
        'Judge',
    ];

    /**
     * Panel hierarchy — higher rank = more privileged prefix.
     */
    public static function prefixRank(string $prefix): int
    {
        $prefix = strtolower(trim($prefix));

        return match ($prefix) {
            'new' => 0,
            'checkin' => 8,
            'judge' => 9,
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
     * Guest (`new`): only assigned staff prefixes (no Member / New in switcher list for panels).
     * Member+: normal hierarchy + assigned staff prefixes.
     * Admin: all membership prefixes + staff prefixes **only when assigned for today**.
     *
     * @return list<string>
     */
    public static function accessiblePrefixes(?ServerRequest $request = null): array
    {
        $request ??= Router::getRequest();
        $role = CurrentUser::role($request);
        $staffPrefixes = CompetitionStaff::assignedPrefixes(CurrentUser::id($request), $request);

        if (static::canUseAdminPanel($request)) {
            // Check-in / Judge: same rule as everyone — competition_staff + competition day only.
            $prefixes = array_merge(self::ALL_ROLE_PREFIXES, $staffPrefixes);

            return static::uniqueSorted($prefixes);
        }

        // Guests: only staff panels they were assigned to (not /new as a switch target).
        if ($role === AppRoles::NEW) {
            return static::uniqueSorted($staffPrefixes);
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

        foreach ($staffPrefixes as $staffPrefix) {
            $prefixes[] = $staffPrefix;
        }

        return static::uniqueSorted($prefixes);
    }

    public static function canAccessPrefix(string $prefix, ?ServerRequest $request = null): bool
    {
        $request ??= Router::getRequest();
        $prefixNorm = ucfirst(strtolower(trim($prefix)));

        if (static::isStaffPrefix($prefixNorm)) {
            $staffRole = CompetitionStaff::staffRoleForPrefix($prefixNorm);
            if ($staffRole === null) {
                return false;
            }

            // No admin/officer bypass — desk only with assignment on competition day.
            return CompetitionStaff::userHasStaffRole($staffRole, CurrentUser::id($request), $request);
        }

        if (static::isAdminPrefix($prefixNorm) && !static::canUseAdminPanel($request)) {
            return false;
        }

        foreach (static::accessiblePrefixes($request) as $allowed) {
            if (strcasecmp($allowed, $prefixNorm) === 0) {
                return true;
            }
        }

        // Guests keep /new for onboarding even when staff-assigned.
        if ($prefixNorm === 'New' && CurrentUser::role($request) === AppRoles::NEW) {
            return true;
        }

        return false;
    }

    public static function canUseAdminPanel(?ServerRequest $request = null): bool
    {
        $request ??= Router::getRequest();
        $role = CurrentUser::role($request);

        return in_array($role, [AppRoles::ADMIN, AppRoles::SUPERUSER], true)
            || CurrentUser::isSuperuser($request);
    }

    public static function canUseMemberPanel(string $role): bool
    {
        $role = strtolower(trim($role));

        return $role !== AppRoles::NEW;
    }

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

            if (static::isStaffPrefix($prefix) && !static::canAccessPrefix($prefix, $request)) {
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

    public static function isStaffPrefix(string $prefix): bool
    {
        return in_array(ucfirst(strtolower(trim($prefix))), self::STAFF_PREFIXES, true);
    }

    protected static function isAdminPrefix(string $prefix): bool
    {
        return strcasecmp(trim($prefix), 'Admin') === 0;
    }

    /**
     * @param list<string> $prefixes
     * @return list<string>
     */
    protected static function uniqueSorted(array $prefixes): array
    {
        $unique = [];
        foreach ($prefixes as $prefix) {
            $prefix = ucfirst(strtolower(trim($prefix)));
            if ($prefix !== '' && !in_array($prefix, $unique, true)) {
                $unique[] = $prefix;
            }
        }
        usort($unique, static function (string $a, string $b): int {
            return static::prefixRank($a) <=> static::prefixRank($b);
        });

        return $unique;
    }
}
