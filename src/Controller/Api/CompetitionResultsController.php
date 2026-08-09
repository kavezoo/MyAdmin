<?php
declare(strict_types=1);

namespace App\Controller\Api;

use App\Auth\CurrentUser;
use App\Utility\CompetitionResultTime;
use App\Utility\CompetitionResults;
use App\Utility\CompetitionStaff;
use App\Utility\UuidObfuscator;
use Cake\Http\Response;

/**
 * POST competition result times from Flutter judge app (QR + obfuscated UUID tokens).
 *
 * URL: /api/competitions/results/{competitionToken}/{userToken}
 */
class CompetitionResultsController extends AppController
{
    /**
     * Submit / update result_time for an applicant.
     */
    public function submit(?string $competitionToken = null, ?string $userToken = null): Response
    {
        $this->request->allowMethod(['post']);

        $judgeId = CurrentUser::id($this->getRequest());
        if ($judgeId === null || $judgeId === '') {
            return $this->jsonResponse([
                'success' => false,
                'message' => 'Authentication required.',
            ], 401);
        }

        $competitionId = UuidObfuscator::decode((string)$competitionToken);
        $userId = UuidObfuscator::decode((string)$userToken);
        if ($competitionId === null || $userId === null) {
            return $this->jsonResponse([
                'success' => false,
                'message' => 'Invalid competition or competitor token.',
            ], 400);
        }

        if (!CompetitionStaff::userAssignedToCompetition(
            $competitionId,
            CompetitionStaff::ROLE_JUDGE,
            $judgeId,
            $this->getRequest(),
            true
        )) {
            return $this->jsonResponse([
                'success' => false,
                'message' => 'Not allowed: judge assignment required on competition day.',
            ], 403);
        }

        $data = $this->request->getData();
        if (!is_array($data)) {
            $data = [];
        }

        $email = trim((string)($data['email'] ?? $data['recorded_by_email'] ?? ''));
        $seconds = CompetitionResultTime::parseFromRequest($data);
        if ($seconds === null) {
            return $this->jsonResponse([
                'success' => false,
                'message' => 'Missing or invalid result time. Send time_seconds, time_ms, or time (mm:ss.SSS).',
            ], 422);
        }

        $result = CompetitionResults::saveTimeForApplicant(
            $competitionId,
            $userId,
            $seconds,
            $email !== '' ? $email : null
        );
        if (!$result['ok']) {
            return $this->jsonResponse([
                'success' => false,
                'message' => $result['error'] ?? 'Save failed.',
                'competition_id' => $competitionId,
                'user_id' => $userId,
            ], $result['code']);
        }

        $application = $result['application'];
        $saved = $application !== null ? (float)$application->get('result_time') : $seconds;

        return $this->jsonResponse([
            'success' => true,
            'message' => !empty($result['competition_ended'])
                ? 'Result time saved. All assigned competitors have times — competition ended.'
                : 'Result time saved.',
            'competition_id' => $competitionId,
            'user_id' => $userId,
            'application_id' => $application !== null ? (int)$application->id : null,
            'result_time' => $saved,
            'result_time_formatted' => CompetitionResultTime::format($saved),
            'competition_ended' => !empty($result['competition_ended']),
        ], 200);
    }
}
