<?php
declare(strict_types=1);

namespace App\Controller\Api;

/**
 * Result listings — /api/v1/results/...
 */
class ResultsController extends ApiController
{
    public function myResults()
    {
        $identity = $this->Authentication->getIdentity();
        if ($identity === null) {
            return $this->jsonResponse(['success' => false, 'message' => 'Nincs identity.'], 401);
        }
        $user = $identity->getOriginalData();
        $userId = is_object($user) ? $user->id : ($user['id'] ?? null);
        $competitionsUsersTable = $this->fetchTable('CompetitionsUsers');

        // Qualify columns: Competitions also has user_id; Translate joins share field names.
        $results = $competitionsUsersTable->find()
            ->where([
                'CompetitionsUsers.user_id' => $userId,
                'CompetitionsUsers.result_time IS NOT' => null,
            ])
            ->contain(['Competitions'])
            ->all();

        return $this->jsonResponse(['success' => true, 'data' => $results]);
    }

    public function allResults()
    {
        $identity = $this->Authentication->getIdentity();
        if ($identity === null) {
            return $this->jsonResponse(['success' => false, 'message' => 'Nincs identity.'], 401);
        }
        $user = $identity->getOriginalData();
        $role = is_object($user) ? (string)($user->role ?? '') : (string)($user['role'] ?? '');
        if (!$this->isApiOpenAccess() && $role === 'new') {
            return $this->jsonResponse(['success' => false, 'message' => 'Nincs jogosultságod.'], 403);
        }

        $competitionsUsersTable = $this->fetchTable('CompetitionsUsers');

        $results = $competitionsUsersTable->find()
            ->where(['CompetitionsUsers.result_time IS NOT' => null])
            ->contain([
                'Users' => ['fields' => ['id', 'first_name', 'last_name']],
                'Competitions',
            ])
            ->orderBy(['CompetitionsUsers.result_time' => 'DESC'])
            ->all();

        return $this->jsonResponse(['success' => true, 'data' => $results]);
    }
}
