<?php
declare(strict_types=1);

namespace App\Utility;

use App\Auth\CountryAccess;
use App\Auth\EventLogAccess;
use App\Auth\LanguageAccess;
use App\Auth\MembershipProfile;
use App\Auth\SetupAccess;
use Cake\Http\ServerRequest;
use CakeDC\Users\Utility\UsersUrl;

/**
 * Single source of truth for panel dashboard cards ↔ sidebar menu items.
 *
 * Every destination card must appear in the sidebar (and vice versa), per prefix.
 *
 * @phpstan-type NavItem array{
 *   title: string,
 *   text: string,
 *   url: array<string, mixed>|string,
 *   button: string,
 *   btnClass?: string,
 *   icon: string,
 *   matchControllers?: list<string>,
 *   matchActions?: list<string>|null,
 *   navGroup?: 'main'|'settings'
 * }
 */
final class PanelNav
{
    /** Top-level sidebar + dashboard (domain CRUD). */
    public const NAV_GROUP_MAIN = 'main';

    /** Admin Settings submenu (ref / geo / setups / logs). */
    public const NAV_GROUP_SETTINGS = 'settings';

    /**
     * Navigation items for a panel prefix (dashboard cards + sidebar links).
     *
     * @param array<string, mixed> $context Optional: clubId (Clubpresident), …
     * @return list<NavItem>
     */
    public static function forPrefix(string $prefix, ServerRequest $request, array $context = []): array
    {
        return match (strtolower(trim($prefix))) {
            'admin' => self::admin($request),
            'president' => self::president(),
            'clubpresident' => self::clubpresident(),
            'member' => self::member(),
            'new' => self::newPanel($request),
            default => [],
        };
    }

    /**
     * Whether the current request matches this nav item (sidebar active state).
     *
     * @param NavItem $item
     */
    public static function isActive(array $item, ServerRequest $request): bool
    {
        $controller = (string)$request->getParam('controller');
        $action = (string)$request->getParam('action');
        $controllers = $item['matchControllers'] ?? self::controllersFromUrl($item['url'] ?? null);
        if ($controllers === [] || !in_array($controller, $controllers, true)) {
            // Profile / complete-profile live outside prefixed controllers.
            $url = $item['url'] ?? null;
            if (is_string($url)) {
                $path = '/' . trim((string)$request->getPath(), '/');
                $target = '/' . trim($url, '/');
                if ($target !== '/' && ($path === $target || str_starts_with($path, $target . '/'))) {
                    return true;
                }
                if ($target === '/profile' && $controller === 'Users' && $action === 'profile') {
                    return true;
                }
                if ($target === '/complete-profile' && $controller === 'Users' && $action === 'completeProfile') {
                    return true;
                }
            }

            return false;
        }

        $actions = $item['matchActions'] ?? null;
        if ($actions === null) {
            return true;
        }

        return in_array($action, $actions, true);
    }

    /**
     * Filter nav items by group (`main` | `settings`). Items without `navGroup` count as `main`.
     *
     * @param list<NavItem> $items
     * @return list<NavItem>
     */
    public static function itemsInGroup(array $items, string $group): array
    {
        $group = $group === self::NAV_GROUP_SETTINGS ? self::NAV_GROUP_SETTINGS : self::NAV_GROUP_MAIN;
        $out = [];
        foreach ($items as $item) {
            $itemGroup = (string)($item['navGroup'] ?? self::NAV_GROUP_MAIN);
            if ($itemGroup === $group) {
                $out[] = $item;
            }
        }

        return $out;
    }

    /**
     * Admin: domain CRUD = top-level (`main`); ref/geo/setups/logs = Settings submenu.
     *
     * @return list<NavItem>
     */
    private static function admin(ServerRequest $request): array
    {
        $items = [];

        // Domain (top-level sidebar) — not under Settings
        if (CountryAccess::canAccessModule($request)) {
            $items[] = [
                'title' => __('Users'),
                'text' => __('Manage user accounts, roles, membership fields and country/club assignment.'),
                'url' => ['prefix' => 'Admin', 'controller' => 'Users', 'action' => 'index'],
                'button' => __('Go to Users'),
                'btnClass' => 'btn-outline-primary',
                'icon' => 'fa-users',
                'matchControllers' => ['Users'],
                'navGroup' => self::NAV_GROUP_MAIN,
            ];
            $items[] = [
                'title' => __('Clubs'),
                'text' => __('Manage clubs across all countries, including their contact details and visibility.'),
                'url' => ['prefix' => 'Admin', 'controller' => 'Clubs', 'action' => 'index'],
                'button' => __('Go to Clubs'),
                'btnClass' => 'btn-outline-primary',
                'icon' => 'fa-sitemap',
                'matchControllers' => ['Clubs'],
                'navGroup' => self::NAV_GROUP_MAIN,
            ];
            $items[] = [
                'title' => __('Competitions'),
                'text' => __('Manage competitions across all countries: dates, minimum team size, and visibility.'),
                'url' => ['prefix' => 'Admin', 'controller' => 'Competitions', 'action' => 'index'],
                'button' => __('Go to Competitions'),
                'btnClass' => 'btn-outline-primary',
                'icon' => 'fa-trophy',
                'matchControllers' => ['Competitions', 'CompetitionTeams', 'CompetitionApplicants'],
                'navGroup' => self::NAV_GROUP_MAIN,
            ];
            $items[] = [
                'title' => __('Email templates'),
                'text' => __('Edit system email templates by language (content used when the application sends mail).'),
                'url' => ['prefix' => 'Admin', 'controller' => 'EmailTemplates', 'action' => 'index'],
                'button' => __('Go to Email templates'),
                'btnClass' => 'btn-outline-primary',
                'icon' => 'fa-envelope',
                'matchControllers' => ['EmailTemplates'],
                'navGroup' => self::NAV_GROUP_MAIN,
            ];
        }

        // Configuration / reference (Settings submenu)
        if (SetupAccess::canAccessModule($request)) {
            $items[] = [
                'title' => __('Setups'),
                'text' => __('Country settings and EAV setup values for the working country.'),
                'url' => ['prefix' => 'Admin', 'controller' => 'Setups', 'action' => 'index'],
                'button' => __('Go to Setups'),
                'btnClass' => 'btn-outline-primary',
                'icon' => 'fa-cogs',
                'matchControllers' => ['Setups'],
                'navGroup' => self::NAV_GROUP_SETTINGS,
            ];
        }
        if (LanguageAccess::canAccessModule($request)) {
            $items[] = [
                'title' => __('Languages'),
                'text' => __('Manage UI languages for the login selector: code, name, endonym and visibility.'),
                'url' => ['prefix' => 'Admin', 'controller' => 'Languages', 'action' => 'index'],
                'button' => __('Go to Languages'),
                'btnClass' => 'btn-outline-primary',
                'icon' => 'fa-language',
                'matchControllers' => ['Languages'],
                'navGroup' => self::NAV_GROUP_SETTINGS,
            ];
        }
        if (CountryAccess::canAccessModule($request)) {
            $items[] = [
                'title' => __('Countries'),
                'text' => __('View and edit countries, visibility and related settings.'),
                'url' => ['prefix' => 'Admin', 'controller' => 'Countries', 'action' => 'index'],
                'button' => __('Go to Countries'),
                'btnClass' => 'btn-outline-primary',
                'icon' => 'fa-globe',
                'matchControllers' => ['Countries'],
                'navGroup' => self::NAV_GROUP_SETTINGS,
            ];
            $items[] = [
                'title' => __('Counties'),
                'text' => __('Manage counties (regions) linked to countries.'),
                'url' => ['prefix' => 'Admin', 'controller' => 'Counties', 'action' => 'index'],
                'button' => __('Go to Counties'),
                'btnClass' => 'btn-outline-primary',
                'icon' => 'fa-map',
                'matchControllers' => ['Counties'],
                'navGroup' => self::NAV_GROUP_SETTINGS,
            ];
            $items[] = [
                'title' => __('Cities'),
                'text' => __('Manage cities linked to counties and countries.'),
                'url' => ['prefix' => 'Admin', 'controller' => 'Cities', 'action' => 'index'],
                'button' => __('Go to Cities'),
                'btnClass' => 'btn-outline-primary',
                'icon' => 'fa-building',
                'matchControllers' => ['Cities'],
                'navGroup' => self::NAV_GROUP_SETTINGS,
            ];
        }
        if (EventLogAccess::canSearch($request)) {
            $items[] = [
                'title' => __('Event logs'),
                'text' => __('Search the activity / event log for this country.'),
                'url' => ['prefix' => 'Admin', 'controller' => 'EventLogs', 'action' => 'index'],
                'button' => __('Go to Event logs'),
                'btnClass' => 'btn-outline-primary',
                'icon' => 'fa-list-alt',
                'matchControllers' => ['EventLogs'],
                'navGroup' => self::NAV_GROUP_SETTINGS,
            ];
        }

        return $items;
    }

    /**
     * @return list<NavItem>
     */
    private static function president(): array
    {
        return [
            [
                'title' => __('Members'),
                'text' => __('Open the list of members in your country: national membership fees, enable/disable accounts, and member details.'),
                'url' => ['prefix' => 'President', 'controller' => 'Members', 'action' => 'index'],
                'button' => __('Go to Members'),
                'btnClass' => 'btn-primary',
                'icon' => 'fa-users',
                'matchControllers' => ['Members'],
            ],
            [
                'title' => __('Clubs'),
                'text' => __('Manage clubs in your country: create and edit clubs, assign club presidents, visibility and position.'),
                'url' => ['prefix' => 'President', 'controller' => 'Clubs', 'action' => 'index'],
                'button' => __('Go to Clubs'),
                'btnClass' => 'btn-primary',
                'icon' => 'fa-sitemap',
                'matchControllers' => ['Clubs'],
            ],
            [
                'title' => __('Competitions'),
                'text' => __('Announce and manage competitions for your country: dates, minimum team size, and visibility.'),
                'url' => ['prefix' => 'President', 'controller' => 'Competitions', 'action' => 'index'],
                'button' => __('Go to Competitions'),
                'btnClass' => 'btn-primary',
                'icon' => 'fa-trophy',
                'matchControllers' => ['Competitions'],
            ],
            [
                'title' => __('Email templates'),
                'text' => __('Edit system email templates by language (content used when the application sends mail).'),
                'url' => ['prefix' => 'President', 'controller' => 'EmailTemplates', 'action' => 'index'],
                'button' => __('Go to Email templates'),
                'btnClass' => 'btn-primary',
                'icon' => 'fa-envelope',
                'matchControllers' => ['EmailTemplates'],
            ],
        ];
    }

    /**
     * @return list<NavItem>
     */
    private static function clubpresident(): array
    {
        return [
            [
                'title' => __('Members'),
                'text' => __('Open your club members list: review pending applicants, approve or reject applications, and record club membership fee payments.'),
                'url' => ['prefix' => 'Clubpresident', 'controller' => 'Members', 'action' => 'index'],
                'button' => __('Go to Members'),
                'btnClass' => 'btn-primary',
                'icon' => 'fa-users',
                'matchControllers' => ['Members', 'Applicants'],
            ],
            [
                'title' => __('Clubs'),
                'text' => __('View your club details and browse other clubs in countries that have clubs.'),
                'url' => ['prefix' => 'Clubpresident', 'controller' => 'Clubs', 'action' => 'index'],
                'button' => __('Go to Clubs'),
                'btnClass' => 'btn-primary',
                'icon' => 'fa-sitemap',
                'matchControllers' => ['Clubs'],
            ],
            [
                'title' => __('Sub-teams'),
                'text' => __('Create and manage club sub-teams (alcsapatok) for open competitions.'),
                'url' => ['prefix' => 'Clubpresident', 'controller' => 'CompetitionTeams', 'action' => 'index'],
                'button' => __('Go to Sub-teams'),
                'btnClass' => 'btn-primary',
                'icon' => 'fa-users',
                'matchControllers' => ['CompetitionTeams'],
            ],
            [
                'title' => __('Competition applicants'),
                'text' => __('Assign members who applied to a competition into a sub-team, or delete applications that should not compete.'),
                'url' => ['prefix' => 'Clubpresident', 'controller' => 'CompetitionApplicants', 'action' => 'index'],
                'button' => __('Go to Competition applicants'),
                'btnClass' => 'btn-primary',
                'icon' => 'fa-user-plus',
                'matchControllers' => ['CompetitionApplicants'],
            ],
        ];
    }

    /**
     * @return list<NavItem>
     */
    private static function member(): array
    {
        return [
            [
                'title' => __('Profile'),
                'text' => __('View your membership profile: personal data, club, and membership fee status. Edit is available from the profile page.'),
                'url' => UsersUrl::actionUrl('profile'),
                'button' => __('Go to Profile'),
                'btnClass' => 'btn-primary',
                'icon' => 'fa-user',
                'matchControllers' => ['Users'],
                'matchActions' => ['profile', 'edit'],
            ],
            [
                'title' => __('Competitions'),
                'text' => __('Browse open competitions in your country, apply, and withdraw until your club president assigns a sub-team.'),
                'url' => ['prefix' => 'Member', 'controller' => 'Competitions', 'action' => 'index'],
                'button' => __('Go to Competitions'),
                'btnClass' => 'btn-primary',
                'icon' => 'fa-trophy',
                'matchControllers' => ['Competitions'],
                'matchActions' => ['index', 'view', 'apply', 'updateApplication', 'withdraw'],
            ],
            [
                'title' => __('Competition archive'),
                'text' => __('Past competitions you took part in, with results when available.'),
                'url' => ['prefix' => 'Member', 'controller' => 'Competitions', 'action' => 'archive'],
                'button' => __('Go to Archive'),
                'btnClass' => 'btn-outline-primary',
                'icon' => 'fa-archive',
                'matchControllers' => ['Competitions'],
                'matchActions' => ['archive'],
            ],
            [
                'title' => __('Clubs'),
                'text' => __('View your club and browse other clubs in countries that have clubs.'),
                'url' => ['prefix' => 'Member', 'controller' => 'Clubs', 'action' => 'index'],
                'button' => __('Go to Clubs'),
                'btnClass' => 'btn-primary',
                'icon' => 'fa-sitemap',
                'matchControllers' => ['Clubs'],
            ],
        ];
    }

    /**
     * @return list<NavItem>
     */
    private static function newPanel(ServerRequest $request): array
    {
        $identity = $request->getAttribute('identity');
        $data = null;
        if ($identity !== null) {
            $data = method_exists($identity, 'getOriginalData')
                ? $identity->getOriginalData()
                : $identity;
        }
        if ($data !== null && MembershipProfile::needsProfileCompletion($data)) {
            return [
                [
                    'title' => __('Complete your profile'),
                    'text' => __('Enter the missing details so you can submit your membership application.'),
                    'url' => '/complete-profile',
                    'button' => __('Go to Complete profile'),
                    'btnClass' => 'btn-primary',
                    'icon' => 'fa-id-card',
                    'matchControllers' => ['Users'],
                    'matchActions' => ['completeProfile'],
                ],
            ];
        }

        return [
            [
                'title' => __('Profile'),
                'text' => __('View the profile you submitted. Your club president will review your application.'),
                'url' => UsersUrl::actionUrl('profile'),
                'button' => __('Go to Profile'),
                'btnClass' => 'btn-primary',
                'icon' => 'fa-user',
                'matchControllers' => ['Users'],
                'matchActions' => ['profile', 'edit'],
            ],
        ];
    }

    /**
     * @param array<string, mixed>|string|null $url
     * @return list<string>
     */
    private static function controllersFromUrl(array|string|null $url): array
    {
        if (!is_array($url) || !isset($url['controller'])) {
            return [];
        }

        return [(string)$url['controller']];
    }
}
