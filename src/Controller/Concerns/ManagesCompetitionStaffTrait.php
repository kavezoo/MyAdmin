<?php
declare(strict_types=1);

namespace App\Controller\Concerns;

use App\Utility\CompetitionStaff;
use Cake\Http\Response;

/**
 * Assign check-in / judge staff on a competition (President / Clubpresident / Admin).
 */
trait ManagesCompetitionStaffTrait
{
    /**
     * POST: add staff assignment.
     */
    public function staffAdd(?string $competitionId = null): ?Response
    {
        $this->request->allowMethod(['post']);
        $competition = $this->requireCompetitionForStaff($competitionId);
        if ($competition === null) {
            return $this->redirect($this->staffFallbackUrl());
        }

        $data = $this->request->getData();
        $userId = trim((string)($data['user_id'] ?? ''));
        $staffRole = strtolower(trim((string)($data['staff_role'] ?? '')));
        if ($userId === '' || !in_array($staffRole, CompetitionStaff::ROLES, true)) {
            $this->Flash->error(__('Please select a user and a staff role.'));

            return $this->redirect($this->staffReturnUrl((string)$competition->id));
        }

        $table = $this->fetchTable('CompetitionStaff');
        $entity = $table->newEntity([
            'competition_id' => (string)$competition->id,
            'user_id' => $userId,
            'staff_role' => $staffRole,
            'visible' => true,
        ]);
        if ($table->save($entity)) {
            $this->Flash->success(__('Staff member has been assigned.'));
        } else {
            $this->flashEntityErrors($entity);
        }

        return $this->redirect($this->staffReturnUrl((string)$competition->id));
    }

    /**
     * POST: remove staff assignment.
     */
    public function staffDelete(?string $id = null): ?Response
    {
        $this->request->allowMethod(['post', 'delete']);
        $table = $this->fetchTable('CompetitionStaff');
        try {
            $row = $table->get($id);
        } catch (\Throwable) {
            $this->Flash->error(__('Record not found.'));

            return $this->redirect($this->staffFallbackUrl());
        }

        $competition = $this->requireCompetitionForStaff((string)$row->competition_id);
        if ($competition === null) {
            return $this->redirect($this->staffFallbackUrl());
        }

        if ($table->delete($row)) {
            $this->Flash->success(__('Staff assignment removed.'));
        } else {
            $this->Flash->error(__('The record could not be deleted. Please try again.'));
        }

        return $this->redirect($this->staffReturnUrl((string)$competition->id));
    }

    /**
     * Select2 AJAX: search users by name / email in the competition country.
     */
    public function userOptions(): Response
    {
        $this->request->allowMethod(['get']);
        $countryId = $this->staffSearchCountryId();
        if ($countryId < 1) {
            return $this->emptyStaffSelect2Response();
        }

        $term = trim((string)$this->request->getQuery('q'));
        $page = max(1, (int)$this->request->getQuery('page'));
        $limit = 20;

        $query = $this->fetchTable('Users')->find()
            ->select(['id', 'email', 'username', 'first_name', 'last_name', 'role'])
            ->where([
                'Users.country_id' => $countryId,
                'Users.enabled' => true,
            ])
            ->orderBy(['Users.last_name' => 'ASC', 'Users.first_name' => 'ASC', 'Users.email' => 'ASC']);

        if ($term !== '') {
            $like = '%' . str_replace(['%', '_'], ['\\%', '\\_'], $term) . '%';
            $query->where([
                'OR' => [
                    'Users.email LIKE' => $like,
                    'Users.username LIKE' => $like,
                    'Users.first_name LIKE' => $like,
                    'Users.last_name LIKE' => $like,
                ],
            ]);
        }

        $total = (clone $query)->count();
        $rows = $query->limit($limit)->offset(($page - 1) * $limit)->all();

        $results = [];
        foreach ($rows as $user) {
            $label = trim((string)$user->get('last_name') . ' ' . (string)$user->get('first_name'));
            if ($label === '') {
                $label = (string)$user->get('email');
            }
            $role = (string)$user->get('role');
            $results[] = [
                'id' => (string)$user->get('id'),
                'text' => $label . ($role !== '' ? ' (' . $role . ')' : ''),
            ];
        }

        return $this->getResponse()
            ->withType('application/json')
            ->withStringBody((string)json_encode([
                'results' => $results,
                'pagination' => [
                    'more' => ($page * $limit) < $total,
                ],
            ], JSON_UNESCAPED_UNICODE));
    }

    protected function emptyStaffSelect2Response(): Response
    {
        return $this->getResponse()
            ->withType('application/json')
            ->withStringBody((string)json_encode([
                'results' => [],
                'pagination' => ['more' => false],
            ], JSON_UNESCAPED_UNICODE));
    }

    /**
     * Country scope for AJAX user search.
     */
    abstract protected function staffSearchCountryId(): int;

    /**
     * @return \App\Model\Entity\Competition|null
     */
    abstract protected function requireCompetitionForStaff(?string $competitionId);

    /**
     * @return array<string, mixed>
     */
    abstract protected function staffReturnUrl(string $competitionId): array;

    /**
     * @return array<string, mixed>
     */
    abstract protected function staffFallbackUrl(): array;
}
