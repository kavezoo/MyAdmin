<?php
declare(strict_types=1);

namespace App\Controller\Api;

/**
 * GET/PUT /api/v1/profile
 */
class ProfileController extends ApiController
{
    public function index()
    {
        $identity = $this->Authentication->getIdentity();
        if ($identity === null) {
            return $this->jsonResponse(['success' => false, 'message' => 'Nincs identity (állíts Api.devUserId-t vagy legyen user a DB-ben).'], 401);
        }
        $user = $identity->getOriginalData();
        $userId = is_object($user) ? $user->id : ($user['id'] ?? null);
        $usersTable = $this->fetchTable('Users');

        $profile = $usersTable->get($userId, contain: ['Clubs']);

        return $this->jsonResponse(['success' => true, 'data' => $profile]);
    }

    public function update()
    {
        $identity = $this->Authentication->getIdentity();
        if ($identity === null) {
            return $this->jsonResponse(['success' => false, 'message' => 'Nincs identity.'], 401);
        }
        $user = $identity->getOriginalData();
        $userId = is_object($user) ? $user->id : ($user['id'] ?? null);
        $usersTable = $this->fetchTable('Users');

        $entity = $usersTable->get($userId);
        $entity = $usersTable->patchEntity($entity, $this->request->getData(), [
            'fields' => ['first_name', 'last_name', 'phone', 'language_id'],
        ]);

        if ($usersTable->save($entity)) {
            return $this->jsonResponse(['success' => true, 'message' => 'Profil frissítve.', 'data' => $entity]);
        }

        return $this->jsonResponse(['success' => false, 'errors' => $entity->getErrors()], 422);
    }
}
