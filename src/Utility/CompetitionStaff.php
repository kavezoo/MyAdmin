<?php
declare(strict_types=1);

namespace App\Utility;

use App\Auth\CurrentUser;
use App\Auth\PanelAccess;
use Cake\Http\ServerRequest;
use Cake\ORM\Locator\LocatorAwareTrait;
use Cake\Routing\Router;
use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;

/**
 * Competition-day staff assignments (check-in desk / table judge).
 *
 * Does not change Users.role — capability is per competition + user.
 * Panel / API access is limited to the full calendar day of competition_datetime
 * (date part in the competition country's timezone).
 */
class CompetitionStaff
{
    use LocatorAwareTrait;

    public const ROLE_CHECKIN = 'checkin';

    public const ROLE_JUDGE = 'judge';

    /** @var list<string> */
    public const ROLES = [
        self::ROLE_CHECKIN,
        self::ROLE_JUDGE,
    ];

    /**
     * staff_role → CakePHP panel prefix (PascalCase).
     */
    public static function prefixForStaffRole(string $staffRole): ?string
    {
        return match (strtolower(trim($staffRole))) {
            self::ROLE_CHECKIN => 'Checkin',
            self::ROLE_JUDGE => 'Judge',
            default => null,
        };
    }

    public static function staffRoleForPrefix(string $prefix): ?string
    {
        return match (strtolower(trim($prefix))) {
            'checkin' => self::ROLE_CHECKIN,
            'judge' => self::ROLE_JUDGE,
            default => null,
        };
    }

    /**
     * Calendar date (Y-m-d) of competition_datetime in the competition country timezone.
     *
     * @param \Cake\I18n\DateTime|\DateTimeInterface|string|null $competitionDatetime
     */
    public static function competitionCalendarDate(
        mixed $competitionDatetime,
        ?int $countryId = null,
    ): ?string {
        if ($competitionDatetime === null || $competitionDatetime === '') {
            return null;
        }

        $tzName = ($countryId !== null && $countryId > 0)
            ? (AdminTimezone::forCountry($countryId) ?: AdminTimezone::appDefault())
            : AdminTimezone::appDefault();
        $tz = new DateTimeZone($tzName);

        try {
            if ($competitionDatetime instanceof DateTimeInterface) {
                // DB stores UTC; wall-clock day = country TZ date of competition_datetime.
                return DateTimeImmutable::createFromInterface($competitionDatetime)
                    ->setTimezone($tz)
                    ->format('Y-m-d');
            }

            $raw = trim((string)$competitionDatetime);
            // Prefer explicit date prefix if present (Y-m-d …).
            if (preg_match('/^(\d{4}-\d{2}-\d{2})/', $raw, $m)) {
                $utc = new DateTimeZone(AdminTimezone::appDefault());
                $parsed = new DateTimeImmutable($raw, $utc);

                return $parsed->setTimezone($tz)->format('Y-m-d');
            }

            $utc = new DateTimeZone(AdminTimezone::appDefault());
            $parsed = new DateTimeImmutable($raw, $utc);

            return $parsed->setTimezone($tz)->format('Y-m-d');
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * True when "now" falls on the competition's calendar day (country TZ), full day.
     *
     * @param \Cake\I18n\DateTime|\DateTimeInterface|string|null $competitionDatetime
     */
    public static function isCompetitionStaffDay(
        mixed $competitionDatetime,
        ?int $countryId = null,
        ?DateTimeInterface $now = null,
    ): bool {
        $compDate = static::competitionCalendarDate($competitionDatetime, $countryId);
        if ($compDate === null) {
            return false;
        }

        $tzName = ($countryId !== null && $countryId > 0)
            ? (AdminTimezone::forCountry($countryId) ?: AdminTimezone::appDefault())
            : AdminTimezone::appDefault();
        $tz = new DateTimeZone($tzName);

        $nowLocal = $now !== null
            ? DateTimeImmutable::createFromInterface($now)->setTimezone($tz)
            : new DateTimeImmutable('now', $tz);

        return $nowLocal->format('Y-m-d') === $compDate;
    }

    /**
     * @param object|array<string, mixed> $competition Entity or row with competition_datetime + country_id
     */
    public static function isCompetitionStaffDayFor(mixed $competition, ?DateTimeInterface $now = null): bool
    {
        if (is_array($competition)) {
            $dt = $competition['competition_datetime'] ?? null;
            $countryId = (int)($competition['country_id'] ?? 0);
        } elseif (is_object($competition)) {
            $dt = method_exists($competition, 'get')
                ? $competition->get('competition_datetime')
                : ($competition->competition_datetime ?? null);
            $countryId = (int)(method_exists($competition, 'get')
                ? ($competition->get('country_id') ?? 0)
                : ($competition->country_id ?? 0));
        } else {
            return false;
        }

        return static::isCompetitionStaffDay($dt, $countryId > 0 ? $countryId : null, $now);
    }

    /**
     * Staff rows for a user (full Competitions contain — no field-select; Translate-safe).
     *
     * @return list<\App\Model\Entity\CompetitionStaff>
     */
    protected static function assignmentsForUser(string $userId, ?string $staffRole = null): array
    {
        try {
            $query = (new static())->getTableLocator()->get('CompetitionStaff')->find()
                ->contain(['Competitions'])
                ->where([
                    'CompetitionStaff.user_id' => $userId,
                    'CompetitionStaff.visible' => true,
                ])
                ->orderBy(['CompetitionStaff.id' => 'ASC']);
            if ($staffRole !== null && $staffRole !== '') {
                $query->where(['CompetitionStaff.staff_role' => $staffRole]);
            } else {
                $query->where(['CompetitionStaff.staff_role IN' => self::ROLES]);
            }

            return $query->all()->toList();
        } catch (\Throwable) {
            return [];
        }
    }

    /**
     * @return list<string> PascalCase prefixes with at least one assignment active today.
     */
    public static function assignedPrefixes(?string $userId = null, ?ServerRequest $request = null): array
    {
        $userId = $userId ?: CurrentUser::id($request ?? Router::getRequest());
        if ($userId === null || $userId === '') {
            return [];
        }

        $prefixes = [];
        foreach (static::assignmentsForUser($userId) as $row) {
            $competition = $row->get('competition');
            if ($competition === null || !static::isCompetitionStaffDayFor($competition)) {
                continue;
            }
            $prefix = static::prefixForStaffRole((string)$row->get('staff_role'));
            if ($prefix !== null && !in_array($prefix, $prefixes, true)) {
                $prefixes[] = $prefix;
            }
        }

        usort($prefixes, static function (string $a, string $b): int {
            return PanelAccess::prefixRank($a) <=> PanelAccess::prefixRank($b);
        });

        return $prefixes;
    }

    public static function userHasStaffRole(string $staffRole, ?string $userId = null, ?ServerRequest $request = null): bool
    {
        $prefix = static::prefixForStaffRole($staffRole);
        if ($prefix === null) {
            return false;
        }

        if (in_array($prefix, static::assignedPrefixes($userId, $request), true)) {
            return true;
        }

        return false;
    }

    /**
     * Assignment exists and competition day is today (country TZ).
     */
    public static function userAssignedToCompetition(
        string $competitionId,
        string $staffRole,
        ?string $userId = null,
        ?ServerRequest $request = null,
        bool $requireStaffDay = true,
    ): bool {
        $userId = $userId ?: CurrentUser::id($request ?? Router::getRequest());
        if ($userId === '' || $competitionId === '') {
            return false;
        }
        $staffRole = strtolower(trim($staffRole));
        if (!in_array($staffRole, self::ROLES, true)) {
            return false;
        }

        foreach (static::assignmentsForUser($userId, $staffRole) as $row) {
            if ((string)$row->get('competition_id') !== $competitionId) {
                continue;
            }
            if (!$requireStaffDay) {
                return true;
            }

            return static::isCompetitionStaffDayFor($row->get('competition'));
        }

        return false;
    }

    /**
     * May operate check-in / judge on a competition today:
     * only with an explicit competition_staff assignment for that role.
     */
    public static function canOperateOnCompetition(
        string $competitionId,
        string $staffRole,
        ?string $userId = null,
        ?ServerRequest $request = null,
    ): bool {
        return static::userAssignedToCompetition($competitionId, $staffRole, $userId, $request, true);
    }

    /**
     * Competition ids for the Check-in / Judge desk UI (today only).
     * Explicit competition_staff assignments only (no officer auto-access).
     *
     * @return list<string>
     */
    public static function deskCompetitionIds(
        string $staffRole,
        ?string $userId = null,
        ?ServerRequest $request = null,
    ): array {
        $request ??= Router::getRequest();
        $dated = [];
        foreach (static::competitionIdsForUser($staffRole, $userId, $request, true) as $id) {
            $dated[$id] = $id;
        }

        return array_values(array_keys($dated));
    }

    /**
     * Officers must be assigned via competition_staff like everyone else.
     */
    public static function officerMayUseStaffDesks(?ServerRequest $request = null): bool
    {
        return false;
    }

    /**
     * Competition ids whose competition_datetime calendar date is today (officer country scope).
     *
     * @return list<string>
     */
    public static function todayCompetitionIdsForOfficer(?ServerRequest $request = null): array
    {
        $request ??= Router::getRequest();
        try {
            $query = (new static())->getTableLocator()->get('Competitions')->find()
                ->select(['Competitions.id', 'Competitions.competition_datetime', 'Competitions.country_id']);
            if (!PanelAccess::canUseAdminPanel($request) || !CurrentUser::isSuperuser($request)) {
                $countryId = CurrentUser::countryId($request);
                if ($countryId < 1) {
                    return [];
                }
                // Admin (non-superuser) and president/VP: own country only.
                $query->where(['Competitions.country_id' => $countryId]);
            }
            $rows = $query->all();
        } catch (\Throwable) {
            return [];
        }

        $dated = [];
        foreach ($rows as $competition) {
            if (!static::isCompetitionStaffDayFor($competition)) {
                continue;
            }
            $id = (string)$competition->get('id');
            $dt = $competition->get('competition_datetime');
            $sort = $dt instanceof DateTimeInterface ? $dt->format('Y-m-d H:i:s') : (string)$dt;
            $dated[$id] = $sort;
        }
        asort($dated, SORT_STRING);

        return array_keys($dated);
    }

    /**
     * Competition ids where the user holds a staff role and the competition day is today.
     * Ordered by competition_datetime ASC.
     *
     * @return list<string>
     */
    public static function competitionIdsForUser(
        string $staffRole,
        ?string $userId = null,
        ?ServerRequest $request = null,
        bool $requireStaffDay = true,
    ): array {
        $userId = $userId ?: CurrentUser::id($request ?? Router::getRequest());
        $staffRole = strtolower(trim($staffRole));
        if ($userId === '' || !in_array($staffRole, self::ROLES, true)) {
            return [];
        }

        $dated = [];
        foreach (static::assignmentsForUser($userId, $staffRole) as $row) {
            $competition = $row->get('competition');
            if ($requireStaffDay && !static::isCompetitionStaffDayFor($competition)) {
                continue;
            }
            $id = (string)$row->get('competition_id');
            $sort = '';
            if ($competition !== null && method_exists($competition, 'get')) {
                $dt = $competition->get('competition_datetime');
                if ($dt instanceof DateTimeInterface) {
                    $sort = $dt->format('Y-m-d H:i:s');
                } elseif ($dt !== null) {
                    $sort = (string)$dt;
                }
            }
            $dated[$id] = $sort;
        }

        asort($dated, SORT_STRING);

        return array_keys($dated);
    }

    public static function roleLabel(string $staffRole): string
    {
        return match (strtolower(trim($staffRole))) {
            self::ROLE_CHECKIN => __('Check-in'),
            self::ROLE_JUDGE => __('Table judge'),
            default => $staffRole,
        };
    }

    /**
     * Staff people for competition view (check-in first, then table judges).
     *
     * @return array{
     *     checkin: list<array{id: string, name: string}>,
     *     judge: list<array{id: string, name: string}>
     * }
     */
    public static function groupedDisplayPeople(string $competitionId): array
    {
        $groups = [
            self::ROLE_CHECKIN => [],
            self::ROLE_JUDGE => [],
        ];
        $competitionId = trim($competitionId);
        if ($competitionId === '') {
            return $groups;
        }

        try {
            /** @var \App\Model\Table\CompetitionStaffTable $table */
            $table = (new static())->getTableLocator()->get('CompetitionStaff');
            $rows = $table->listForCompetition($competitionId);
        } catch (\Throwable) {
            return $groups;
        }

        foreach ($rows as $row) {
            $role = strtolower(trim((string)$row->get('staff_role')));
            if (!isset($groups[$role])) {
                continue;
            }
            $user = $row->get('user');
            $name = static::userDisplayName($user);
            if ($name === '') {
                continue;
            }
            $userId = '';
            if (is_object($user) && method_exists($user, 'get')) {
                $userId = trim((string)($user->get('id') ?? ''));
            } elseif (is_array($user)) {
                $userId = trim((string)($user['id'] ?? ''));
            }
            if ($userId === '') {
                $userId = trim((string)($row->get('user_id') ?? ''));
            }
            $groups[$role][] = [
                'id' => $userId,
                'name' => $name,
            ];
        }

        foreach ($groups as $role => $people) {
            usort($people, static function (array $a, array $b): int {
                return strnatcasecmp($a['name'], $b['name']);
            });
            $groups[$role] = array_values($people);
        }

        return $groups;
    }

    /**
     * Display names grouped for competition view (check-in first, then table judges).
     *
     * @return array{checkin: list<string>, judge: list<string>}
     */
    public static function groupedDisplayNames(string $competitionId): array
    {
        $people = static::groupedDisplayPeople($competitionId);
        $names = [
            self::ROLE_CHECKIN => [],
            self::ROLE_JUDGE => [],
        ];
        foreach ($people as $role => $list) {
            foreach ($list as $person) {
                $names[$role][] = (string)($person['name'] ?? '');
            }
        }

        return $names;
    }

    public static function userDisplayName(mixed $user): string
    {
        if ($user === null) {
            return '';
        }
        $last = '';
        $first = '';
        $email = '';
        if (is_object($user) && method_exists($user, 'get')) {
            $last = trim((string)($user->get('last_name') ?? ''));
            $first = trim((string)($user->get('first_name') ?? ''));
            $email = trim((string)($user->get('email') ?? ''));
        } elseif (is_array($user)) {
            $last = trim((string)($user['last_name'] ?? ''));
            $first = trim((string)($user['first_name'] ?? ''));
            $email = trim((string)($user['email'] ?? ''));
        }
        $label = trim($last . ' ' . $first);
        if ($label === '') {
            $label = $email;
        }

        return $label;
    }
}
