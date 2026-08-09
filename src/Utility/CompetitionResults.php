<?php
declare(strict_types=1);

namespace App\Utility;

use App\Model\Entity\CompetitionsUser;
use Cake\I18n\DateTime;
use Cake\ORM\Locator\LocatorAwareTrait;

/**
 * Persist competitor result_time (seconds) on competitions_users; end competition when all assigned have times.
 */
final class CompetitionResults
{
    use LocatorAwareTrait;

    /**
     * Find active application and save result time (+ optional recorder email).
     *
     * @return array{ok: bool, application: ?CompetitionsUser, error: ?string, code: int, competition_ended?: bool}
     */
    public static function saveTimeForApplicant(
        string $competitionId,
        string $userId,
        float $seconds,
        ?string $recordedByEmail = null,
    ): array {
        if ($seconds < 0) {
            return [
                'ok' => false,
                'application' => null,
                'error' => 'Invalid result time.',
                'code' => 422,
            ];
        }

        $seconds = round($seconds, 3);
        $table = (new static())->getTableLocator()->get('CompetitionsUsers');

        try {
            /** @var CompetitionsUser|null $application */
            $application = $table->find()
                ->where([
                    'CompetitionsUsers.competition_id' => $competitionId,
                    'CompetitionsUsers.user_id' => $userId,
                    'CompetitionsUsers.status IN' => CompetitionApplication::activeStatuses(),
                ])
                ->first();
        } catch (\Throwable) {
            return [
                'ok' => false,
                'application' => null,
                'error' => 'Lookup failed.',
                'code' => 500,
            ];
        }

        if ($application === null) {
            return [
                'ok' => false,
                'application' => null,
                'error' => 'Applicant not found for this competition.',
                'code' => 404,
            ];
        }

        $fields = ['result_time'];
        $application->set('result_time', $seconds);
        $email = $recordedByEmail !== null ? trim($recordedByEmail) : '';
        if ($email !== '') {
            $application->set('result_recorded_by_email', mb_substr($email, 0, 255));
            $fields[] = 'result_recorded_by_email';
        }

        if (!$table->save($application, [
            'fields' => $fields,
            'accessibleFields' => array_fill_keys($fields, true),
        ])) {
            return [
                'ok' => false,
                'application' => $application,
                'error' => 'Could not save result time.',
                'code' => 500,
            ];
        }

        $ended = static::maybeEndCompetitionIfAllResultsIn($competitionId);

        return [
            'ok' => true,
            'application' => $application,
            'error' => null,
            'code' => 200,
            'competition_ended' => $ended,
        ];
    }

    /**
     * When every assigned competitor has result_time, set competitions.end_datetime (if still empty).
     */
    public static function maybeEndCompetitionIfAllResultsIn(string $competitionId): bool
    {
        $competitionId = trim($competitionId);
        if ($competitionId === '') {
            return false;
        }

        try {
            $apps = (new static())->getTableLocator()->get('CompetitionsUsers');
            $assigned = $apps->find()
                ->where([
                    'CompetitionsUsers.competition_id' => $competitionId,
                    'CompetitionsUsers.status' => CompetitionApplication::STATUS_ASSIGNED,
                ]);
            $total = (clone $assigned)->count();
            if ($total < 1) {
                return false;
            }
            $missing = (clone $assigned)
                ->where(['CompetitionsUsers.result_time IS' => null])
                ->count();
            if ($missing > 0) {
                return false;
            }

            $competitions = (new static())->getTableLocator()->get('Competitions');
            $competition = $competitions->get($competitionId);
            if ($competition->get('end_datetime')) {
                return false;
            }
            $competition->set('end_datetime', DateTime::now());
            if (!$competitions->save($competition, [
                'fields' => ['end_datetime'],
                'accessibleFields' => ['end_datetime' => true],
                'checkRules' => false,
            ])) {
                return false;
            }

            return true;
        } catch (\Throwable) {
            return false;
        }
    }
}
