<?php
declare(strict_types=1);

namespace App\Utility;

use Cake\I18n\Date;
use Cake\I18n\DateTime;

/**
 * Competition application window + applicant status helpers.
 */
final class CompetitionApplication
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_ASSIGNED = 'assigned';

    public const STATUS_WITHDRAWN = 'withdrawn';

    public const STATUS_INVALID = 'invalid';

    /**
     * Statuses that still count as an open application (row exists and is actionable).
     *
     * @return list<string>
     */
    public static function activeStatuses(): array
    {
        return [
            self::STATUS_PENDING,
            self::STATUS_ASSIGNED,
        ];
    }

    /**
     * Member has a live application row (not missing, not withdrawn/invalid).
     */
    public static function hasApplication(mixed $application): bool
    {
        if ($application === null || !is_object($application)) {
            return false;
        }
        $id = $application->id ?? null;
        if ($id === null || $id === '') {
            return false;
        }
        $status = strtolower(trim((string)($application->status ?? '')));
        if ($status === self::STATUS_WITHDRAWN || $status === self::STATUS_INVALID) {
            return false;
        }

        return $status === '' || in_array($status, self::activeStatuses(), true);
    }

    /**
     * @return array<string, string>
     */
    public static function statusOptions(): array
    {
        return [
            self::STATUS_PENDING => __('Awaiting team assignment'),
            self::STATUS_ASSIGNED => __('Registered'),
            self::STATUS_WITHDRAWN => __('Withdrawn'),
            self::STATUS_INVALID => __('Invalid'),
        ];
    }

    public static function statusLabel(string $status): string
    {
        $options = static::statusOptions();

        return $options[$status] ?? $status;
    }

    /**
     * Official applicant = club president assigned a sub-team (competitions_clubs).
     */
    public static function isRegistered(?string $status, int|string|null $competitionClubId): bool
    {
        if ($competitionClubId === null || $competitionClubId === '' || (int)$competitionClubId < 1) {
            return false;
        }

        return ($status ?? '') === self::STATUS_ASSIGNED;
    }

    /**
     * Lunch / pipe / comment fields from request (member or club president edit).
     *
     * @param array<string, mixed> $data Request data
     * @return array<string, mixed>
     */
    public static function detailFieldsFromData(array $data): array
    {
        return [
            'lunch_for_the_attendant' => (int)($data['lunch_for_the_attendant'] ?? 0),
            'companion_count' => (int)($data['companion_count'] ?? 0),
            'special_lunch' => trim((string)($data['special_lunch'] ?? '')),
            'racing_pipe_1_qty' => (int)($data['racing_pipe_1_qty'] ?? 0),
            'racing_pipe_2_qty' => (int)($data['racing_pipe_2_qty'] ?? 0),
            'racing_pipe_3_qty' => (int)($data['racing_pipe_3_qty'] ?? 0),
            'comment' => trim((string)($data['comment'] ?? '')),
        ];
    }

    /**
     * Member may apply only with a club and club membership fee paid for the current year.
     */
    public static function memberMayApply(mixed $user): bool
    {
        $clubId = 0;
        if (is_object($user) && method_exists($user, 'get')) {
            $clubId = (int)($user->get('club_id') ?? 0);
        } elseif (is_array($user)) {
            $clubId = (int)($user['club_id'] ?? 0);
        }

        if ($clubId < 1) {
            return false;
        }

        return !MembershipFee::isClubFeeUnpaid($user);
    }

    /**
     * Today is within [first_date_of_application, application_deadline] (inclusive).
     */
    public static function isApplicationOpen(
        Date|DateTime|string|null $first,
        Date|DateTime|string|null $deadline,
        Date|DateTime|string|null $today = null,
    ): bool {
        $todayStr = static::toDateString($today ?? Date::today());
        $firstStr = static::toDateString($first);
        $deadlineStr = static::toDateString($deadline);
        if ($todayStr === '' || $firstStr === '' || $deadlineStr === '') {
            return false;
        }

        return $todayStr >= $firstStr && $todayStr <= $deadlineStr;
    }

    /**
     * Competition still shown on member dashboard (not ended).
     */
    public static function isUpcomingOrOngoing(
        DateTime|string|null $endDatetime,
        DateTime|string|null $now = null,
    ): bool {
        if ($endDatetime === null || $endDatetime === '') {
            return true;
        }
        $end = static::toDateTimeString($endDatetime);
        $nowStr = static::toDateTimeString($now ?? DateTime::now());
        if ($end === '' || $nowStr === '') {
            return true;
        }

        return $end >= $nowStr;
    }

    public static function isPastDeadline(
        Date|DateTime|string|null $deadline,
        Date|DateTime|string|null $today = null,
    ): bool {
        $todayStr = static::toDateString($today ?? Date::today());
        $deadlineStr = static::toDateString($deadline);
        if ($todayStr === '' || $deadlineStr === '') {
            return false;
        }

        return $todayStr > $deadlineStr;
    }

    /**
     * Competition announcement / CRUD content is read-only after application_deadline
     * (the day after the deadline; deadline day itself stays editable).
     */
    public static function isContentLocked(
        Date|DateTime|string|null $deadline,
        Date|DateTime|string|null $today = null,
    ): bool {
        return static::isPastDeadline($deadline, $today);
    }

    public static function toDateString(Date|DateTime|string|null $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }
        if ($value instanceof Date || $value instanceof DateTime) {
            return $value->format('Y-m-d');
        }
        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d');
        }

        return substr(trim((string)$value), 0, 10);
    }

    public static function toDateTimeString(DateTime|string|null $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }
        if ($value instanceof DateTime) {
            return $value->format('Y-m-d H:i:s');
        }
        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d H:i:s');
        }

        return trim((string)$value);
    }
}
