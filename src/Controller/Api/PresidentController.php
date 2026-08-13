<?php
declare(strict_types=1);

namespace App\Controller\Api;

use Cake\Event\EventInterface;
use Cake\Http\Exception\ForbiddenException;

/**
 * Clubpresident-oriented Flutter endpoints under /api/v1/president/...
 */
class PresidentController extends ApiController
{
    public function beforeFilter(EventInterface $event)
    {
        parent::beforeFilter($event);

        // During openAccess (dev) skip hard role gate so endpoints are callable.
        if ($this->isApiOpenAccess()) {
            return;
        }

        $identity = $this->Authentication->getIdentity();
        if ($identity === null) {
            throw new ForbiddenException('Authentication required.');
        }
        $user = $identity->getOriginalData();
        $role = is_object($user) ? (string)($user->role ?? '') : (string)($user['role'] ?? '');
        if ($role !== 'clubpresident') {
            throw new ForbiddenException('Csak klubelnökök férhetnek hozzá.');
        }
    }

    /**
     * Current API user entity/array from identity, or null.
     */
    protected function apiUser(): object|array|null
    {
        $identity = $this->Authentication->getIdentity();
        if ($identity === null) {
            return null;
        }

        return $identity->getOriginalData();
    }

    // GET /api/v1/president/pending-members
    public function pendingMembers()
    {
        $president = $this->apiUser();
        if ($president === null) {
            return $this->jsonResponse(['success' => false, 'message' => 'Nincs identity (dev user hiányzik).'], 401);
        }

        $clubId = is_object($president) ? ($president->club_id ?? null) : ($president['club_id'] ?? null);
        $usersTable = $this->fetchTable('Users');
        $pending = $usersTable->find()
            ->where([
                'Users.club_id' => $clubId,
                'Users.role' => 'new',
            ])
            ->all();

        return $this->jsonResponse(['success' => true, 'data' => $pending]);
    }

    // POST /api/v1/president/approve-member/{id}
    public function approveMember($id = null)
    {
        $president = $this->apiUser();
        if ($president === null) {
            return $this->jsonResponse(['success' => false, 'message' => 'Nincs identity (dev user hiányzik).'], 401);
        }

        $usersTable = $this->fetchTable('Users');
        $member = $usersTable->get($id);
        $presidentClubId = is_object($president) ? ($president->club_id ?? null) : ($president['club_id'] ?? null);
        if ((string)$member->club_id !== (string)$presidentClubId) {
            return $this->jsonResponse(['success' => false, 'message' => 'Ez a tag nem a te klubodhoz tartozik.'], 403);
        }

        $member->role = 'member';
        $member->membership_status = 'approved';
        $member->membership_joined_date = date('Y-m-d');

        if ($usersTable->save($member)) {
            return $this->jsonResponse(['success' => true, 'message' => 'Tag sikeresen elfogadva!']);
        }

        return $this->jsonResponse(['success' => false, 'errors' => $member->getErrors()], 422);
    }

    // GET /api/v1/president/competitions/{id}/applicants
    public function competitionApplicants($id = null)
    {
        $competitionsUsersTable = $this->fetchTable('CompetitionsUsers');
        $applicants = $competitionsUsersTable->find()
            ->where(['CompetitionsUsers.competition_id' => $id])
            ->contain(['Users' => ['fields' => ['id', 'first_name', 'last_name', 'email']]])
            ->all();

        return $this->jsonResponse(['success' => true, 'data' => $applicants]);
    }

    // POST /api/v1/president/competitions/{id}/subclubs
    public function createSubclub($competitionId = null)
    {
        $president = $this->apiUser();
        if ($president === null) {
            return $this->jsonResponse(['success' => false, 'message' => 'Nincs identity (dev user hiányzik).'], 401);
        }

        // Domain table: competitions_clubs (teams).
        $clubsTable = $this->fetchTable('Clubs');
        $teamsTable = $this->fetchTable('CompetitionsClubs');
        $clubId = is_object($president) ? (int)($president->club_id ?? 0) : (int)($president['club_id'] ?? 0);
        if ($clubId < 1) {
            return $this->jsonResponse(['success' => false, 'message' => 'Dev user has no club_id.'], 422);
        }
        $club = $clubsTable->get($clubId);

        $count = $teamsTable->find()
            ->where(['club_id' => $club->id, 'competition_id' => $competitionId])
            ->count();
        $nextNumber = $count + 1;
        $name = trim((string)($club->short_name ?: $club->name)) . ' ' . $nextNumber;

        $team = $teamsTable->newEntity([
            'name' => $name,
            'club_id' => $club->id,
            'competition_id' => $competitionId,
        ]);

        if ($teamsTable->save($team)) {
            return $this->jsonResponse(['success' => true, 'data' => $team]);
        }

        return $this->jsonResponse(['success' => false, 'errors' => $team->getErrors()], 422);
    }

    // POST /api/v1/president/assign-member
    public function assignMemberToSubclub()
    {
        $data = $this->request->getData();
        if (!is_array($data) || empty($data['competitions_user_id'])) {
            return $this->jsonResponse(['success' => false, 'message' => 'competitions_user_id required.'], 422);
        }

        $competitionsUsersTable = $this->fetchTable('CompetitionsUsers');
        $compUser = $competitionsUsersTable->get($data['competitions_user_id']);
        if (array_key_exists('competitions_club_id', $data)) {
            $compUser->competition_club_id = $data['competitions_club_id'];
        }
        $compUser->status = 'assigned';

        if ($competitionsUsersTable->save($compUser)) {
            return $this->jsonResponse(['success' => true, 'message' => 'Tag beosztva az alcsapatba.']);
        }

        return $this->jsonResponse(['success' => false, 'errors' => $compUser->getErrors()], 422);
    }
}
