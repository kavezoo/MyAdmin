<?php
declare(strict_types=1);

namespace App\Controller\Judge;

use App\Controller\AppController as BaseAppController;
use App\Utility\CompetitionResultTime;
use App\Utility\CompetitionResults;
use App\Utility\UuidObfuscator;
use Cake\Event\EventInterface;
use Cake\Http\Response;

/**
 * Public-ish close API for table judge devices:
 * POST /judge/close/{128-char pair token}
 * Body: email + time (time_seconds | time_ms | time).
 *
 * No session required — the obfuscated token identifies competition + competitor.
 * Does not use Judge\AppController (that requires staff panel login).
 */
class CloseController extends BaseAppController
{
    public function initialize(): void
    {
        parent::initialize();
        $this->viewBuilder()->disableAutoLayout();
    }

    public function beforeFilter(EventInterface $event)
    {
        parent::beforeFilter($event);
		
        $this->response = $this->response->withType('application/json');
    }

    /**
     * Close competitor participation with result time.
     */
    public function index(?string $token = null): Response
    {
		//dd('competition_staff');
/*		
		competition_id, user_id, staff_role: judge
		
		competition_id-vel megkeresni a versenyt, és ha még tart, akkor
		megkeresni az email alapján az usert és ha megvan, akkor megnézni, hogy a megtalált user->id megegyezik-e ezzel az id-vel, ha igen, akkor lezárhatja a rekordot param:user_id
		ha a verseny nem tart már, akkor false értékkel tér vissza a json
*/		
		
        $this->request->allowMethod(['post']);

        $pair = UuidObfuscator::decodePair((string)$token);
        if ($pair === null) {
            return $this->json([
                'success' => false,
                'message' => 'Invalid close token. Expect a 128-character combined competition+competitor token.',
            ], 400);
        }
        [$competitionId, $userId] = $pair;

        $data = $this->request->getData();
        if (!is_array($data)) {
            $data = [];
        }

        $email = trim((string)($data['email'] ?? $data['recorded_by_email'] ?? ''));
        if ($email === '' || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            return $this->json([
                'success' => false,
                'message' => 'Valid email is required (who recorded the result).',
                'competition_id' => $competitionId,
                'user_id' => $userId,
            ], 422);
        }

        $seconds = CompetitionResultTime::parseFromRequest($data);
        if ($seconds === null) {
            return $this->json([
                'success' => false,
                'message' => 'Missing or invalid result time. Send time_seconds, time_ms, or time (mm:ss.SSS).',
                'competition_id' => $competitionId,
                'user_id' => $userId,
            ], 422);
        }

        $result = CompetitionResults::saveTimeForApplicant(
            $competitionId,
            $userId,
            $seconds,
            $email
        );

        if (!$result['ok']) {
            return $this->json([
                'success' => false,
                'message' => $result['error'] ?? 'Save failed.',
                'competition_id' => $competitionId,
                'user_id' => $userId,
            ], $result['code']);
        }

        $application = $result['application'];
        $saved = $application !== null ? (float)$application->get('result_time') : $seconds;

        return $this->json([
            'success' => true,
            'message' => !empty($result['competition_ended'])
                ? 'Result time saved. All assigned competitors have times — competition ended.'
                : 'Result time saved. Competitor participation closed.',
            'competition_id' => $competitionId,
            'user_id' => $userId,
            'application_id' => $application !== null ? (int)$application->id : null,
            'result_time' => $saved,
            'result_time_formatted' => CompetitionResultTime::format($saved),
            'recorded_by_email' => $email,
            'competition_ended' => !empty($result['competition_ended']),
        ], 200);
    }

    /**
     * @param array<string, mixed> $payload
     */
    protected function json(array $payload, int $status = 200): Response
    {
        return $this->response
            ->withStatus($status)
            ->withType('application/json')
            ->withStringBody((string)json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }
}
