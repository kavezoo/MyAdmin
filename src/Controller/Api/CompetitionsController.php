<?php
declare(strict_types=1);

namespace App\Controller\Api;

use Cake\I18n\DateTime;

/**
 * Member competition browse / apply — /api/v1/competitions
 */
class CompetitionsController extends ApiController
{
    public function index()
    {
        $identity = $this->Authentication->getIdentity();
        if ($identity === null) {
            return $this->jsonResponse(['success' => false, 'message' => 'Nincs identity.'], 401);
        }
        $user = $identity->getOriginalData();
        $role = is_object($user) ? (string)($user->role ?? '') : (string)($user['role'] ?? '');

        // OpenAccess: still return competitions list without fee gates (easier Flutter UI wiring).
        if (!$this->isApiOpenAccess()) {
            $membershipStatus = is_object($user) ? (string)($user->membership_status ?? '') : (string)($user['membership_status'] ?? '');
            if ($role === 'new' || $membershipStatus !== 'approved') {
                return $this->jsonResponse(['success' => false, 'message' => 'Csak elfogadott tagok tekinthetik meg a versenyeket.'], 403);
            }

            $currentYear = (int)date('Y');
            $feeDate = is_object($user) ? ($user->national_membership_fee_date ?? null) : ($user['national_membership_fee_date'] ?? null);
            if (!$feeDate || (int)$feeDate->format('Y') !== $currentYear) {
                return $this->jsonResponse(['success' => false, 'message' => 'Az idei tagdíjad nincs rendezve.'], 403);
            }

            $clubId = is_object($user) ? ($user->club_id ?? null) : ($user['club_id'] ?? null);
            if ($clubId) {
                $clubsTable = $this->fetchTable('Clubs');
                $club = $clubsTable->get($clubId);
                if (!$club->national_membership_fee_date || (int)$club->national_membership_fee_date->format('Y') !== $currentYear) {
                    return $this->jsonResponse(['success' => false, 'message' => 'A klubod nem fizette be az idei szövetségi tagdíjat.'], 403);
                }
            }
        }

        $today = DateTime::now()->format('Y-m-d');
        $competitionsTable = $this->fetchTable('Competitions');

        $competitions = $competitionsTable->find()
            ->where([
                'visible' => 1,
                'first_date_of_application <=' => $today,
                'application_deadline >=' => $today,
            ])
            ->all();

        return $this->jsonResponse(['success' => true, 'data' => $competitions]);
    }

    public function apply($id = null)
    {
        $identity = $this->Authentication->getIdentity();
        if ($identity === null) {
            return $this->jsonResponse(['success' => false, 'message' => 'Nincs identity.'], 401);
        }
        $user = $identity->getOriginalData();
        $userId = is_object($user) ? $user->id : ($user['id'] ?? null);
        $role = is_object($user) ? (string)($user->role ?? '') : (string)($user['role'] ?? '');

        if (!$this->isApiOpenAccess() && $role === 'new') {
            return $this->jsonResponse(['success' => false, 'message' => 'Jogosulatlan kérés.'], 403);
        }

        $competitionsUsersTable = $this->fetchTable('CompetitionsUsers');

        $existing = $competitionsUsersTable->find()
            ->where(['competition_id' => $id, 'user_id' => $userId])
            ->first();

        if ($existing) {
            return $this->jsonResponse(['success' => false, 'message' => 'Már jelentkeztél erre a versenyre.'], 400);
        }

        $data = $this->request->getData();
        if (!is_array($data)) {
            $data = [];
        }
        $data['competition_id'] = $id;
        $data['user_id'] = $userId;
        $data['status'] = 'pending';

        $entity = $competitionsUsersTable->newEmptyEntity();
        $entity = $competitionsUsersTable->patchEntity($entity, $data, [
            'fields' => [
                'competition_id', 'user_id', 'status', 'companion_count', 'lunch_for_the_attendant',
                'special_lunch', 'racing_pipe_1_qty', 'racing_pipe_2_qty', 'racing_pipe_3_qty', 'comment',
            ],
        ]);

        if ($competitionsUsersTable->save($entity)) {
            return $this->jsonResponse(['success' => true, 'message' => 'Sikeres jelentkezés!', 'data' => $entity]);
        }

        return $this->jsonResponse(['success' => false, 'errors' => $entity->getErrors()], 422);
    }
}
