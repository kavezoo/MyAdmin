<?php
declare(strict_types=1);

namespace App\Controller\President;

use App\Auth\AppRoles;
use App\Auth\MembershipProfile;
use App\Utility\AdminCountry;
use App\Utility\LocaleDateParser;
use App\Utility\LocaleNumberParser;
use App\Utility\MembershipFee;
use Cake\Datasource\EntityInterface;
use Cake\Http\Exception\ForbiddenException;
use Cake\Http\Exception\NotFoundException;
use Cake\Http\Response;

/**
 * Country clubs CRUD (president / vice president).
 *
 * Scope: clubs where `country_id` = officer country.
 * Club president Select2: same `country_id`, exclude `role=new` (member+ OK);
 * assignment sets `role=clubpresident` + `club_id`.
 *
 * @property \App\Model\Table\ClubsTable $Clubs
 */
class ClubsController extends AppController
{
    protected int $indexLimit = 50;

    protected int $indexMaxLimit = 500;

    /**
     * @return \Cake\Http\Response|null|void
     */
    public function index()
    {
        $this->set('title', __('Clubs'));
        $this->viewBuilder()->setVar('breadcrumb', __('Clubs'));

        $countryId = $this->officerCountryId();
        if ($countryId < 1) {
            $this->Flash->warning(__('Your account is not assigned to a country yet. Contact an administrator.'));
            $this->set('clubs', $this->emptyPaginated($this->indexLimit));
            $this->set('countryLabel', '');
            $this->set('countryId', 0);
            $this->set('clubPresidents', []);

            return;
        }

        $this->set('canAdd', true);
        $this->set('canEdit', true);

        $redirect = $this->applyIndexListState('Clubs');
        if ($redirect !== null) {
            return $redirect;
        }

        $paginateOptions = $this->indexPaginateOptionsFor($this->Clubs, [
            'sortableFields' => [
                'id',
                'name',
                'pos',
                'enabled',
                'visible',
                'user_count',
                'created',
                'modified',
            ],
            'order' => [
                'Clubs.pos' => 'ASC',
                'Clubs.name' => 'ASC',
            ],
        ]);

        $query = $this->Clubs->find()
            ->where(['Clubs.country_id' => $countryId]);
        $query = $this->applyIndexSearch($query, $this->Clubs);

        $redirect = $this->resolveIndexPageForLastVisited('Clubs', $query, $paginateOptions);
        if ($redirect !== null) {
            return $redirect;
        }

        $clubs = $this->paginate($query, $paginateOptions);
        $this->setLastVisitedForIndex('Clubs');

        $clubIds = [];
        foreach ($clubs as $club) {
            $clubIds[] = (int)$club->id;
        }
        $clubPresidents = $this->loadClubPresidentsMap($clubIds, $countryId);

        $this->set(compact('clubs', 'clubPresidents'));
        $this->set('countryId', $countryId);
        $this->set('countryLabel', AdminCountry::label($countryId));
    }

    /**
     * @return \Cake\Http\Response|null|void
     */
    public function add()
    {
        $countryId = $this->requireOfficerCountryId();
        $this->set('canAdd', true);
        $this->set('canEdit', true);

        $club = $this->newEntityWithSchemaDefaults($this->Clubs);
        $club->set('country_id', $countryId);

        if ($this->request->is('post')) {
            try {
                $data = $this->request->getData();
                $presidentId = $this->extractPresidentId($data);
                unset($data['club_president_id'], $data['country_id']);
                $data['country_id'] = $countryId;

                $club = $this->Clubs->patchEntity($club, $data, [
                    'accessibleFields' => [
                        'country_id' => true,
                        'name' => true,
                        'enabled' => true,
                        'visible' => true,
                        'pos' => true,
                    ],
                ]);
                if ($this->Clubs->save($club)) {
                    $this->Clubs->assignClubPresident((int)$club->id, $countryId, $presidentId);
                    $this->rememberLastVisited('Clubs', $club->id);
                    $this->Flash->success(__('The club has been saved.'));

                    return $this->redirectToIndexList('Clubs');
                }
            } catch (\Throwable $e) {
                // Unexpected errors → user-facing flash
            }
            $this->Flash->error(__('The record could not be saved. Please try again.'));
        }

        $this->setClubFormVars($club, $countryId, null);
        $this->set('title', __('New club'));
        $this->viewBuilder()->setVar('breadcrumb', __('Clubs'));
        $this->render('form');
    }

    /**
     * @param string|null $id
     * @return \Cake\Http\Response|null|void
     */
    public function edit(?string $id = null)
    {
        $countryId = $this->requireOfficerCountryId();
        $this->set('canAdd', true);
        $this->set('canEdit', true);

        $club = $this->getScopedClub((string)$id, $countryId);
        $this->rememberLastVisited('Clubs', $club->id);
        $president = $this->Clubs->findClubPresident((int)$club->id);

        if ($this->request->is(['patch', 'post', 'put'])) {
            try {
                $data = $this->request->getData();
                $presidentId = $this->extractPresidentId($data);
                unset($data['club_president_id'], $data['country_id']);

                $club = $this->Clubs->patchEntity($club, $data, [
                    'accessibleFields' => [
                        'name' => true,
                        'enabled' => true,
                        'visible' => true,
                        'pos' => true,
                    ],
                ]);
                if ($this->Clubs->save($club)) {
                    $this->Clubs->assignClubPresident((int)$club->id, $countryId, $presidentId);
                    $this->rememberLastVisited('Clubs', $club->id);
                    $this->Flash->success(__('The club has been saved.'));

                    return $this->redirectToIndexList('Clubs');
                }
            } catch (\Throwable $e) {
                // Unexpected errors → user-facing flash
            }
            $this->Flash->error(__('The record could not be saved. Please try again.'));
            $president = $this->Clubs->findClubPresident((int)$club->id);
        }

        $this->setClubFormVars($club, $countryId, $president);
        $this->setCanDeleteFlag($this->Clubs, $club);
        $this->set('title', __('Edit club'));
        $this->viewBuilder()->setVar('breadcrumb', __('Clubs'));
        $this->render('form');
    }

    /**
     * @param string|null $id
     * @return \Cake\Http\Response|null|void
     */
    public function view(?string $id = null)
    {
        $countryId = $this->requireOfficerCountryId();
        $this->set('canAdd', true);
        $this->set('canEdit', true);

        $club = $this->Clubs->get((int)$id, contain: [
            'Users' => function ($q) {
                return $q->orderBy([
                    'Users.first_name' => 'ASC',
                    'Users.email' => 'ASC',
                ]);
            },
        ]);
        if ((int)$club->get('country_id') !== $countryId) {
            throw new NotFoundException(__('Club not found.'));
        }

        $this->rememberLastVisited('Clubs', $club->id);
        $president = $this->Clubs->findClubPresident((int)$club->id);

        $this->set(compact('club', 'president', 'countryId'));
        $this->set('countryLabel', AdminCountry::label($countryId));
        $this->setCanDeleteFlag($this->Clubs, $club);
        $this->set('title', __('Club details'));
        $this->viewBuilder()->setVar('breadcrumb', __('Clubs'));
    }

    /**
     * @param string|null $id
     * @return \Cake\Http\Response|null
     */
    public function delete(?string $id = null): ?Response
    {
        $this->request->allowMethod(['post', 'delete']);
        $countryId = $this->requireOfficerCountryId();
        $club = $this->getScopedClub((string)$id, $countryId);

        return $this->deleteEntityOrFail($this->Clubs, $club);
    }

    /**
     * JSON: club row for index modal.
     *
     * @param string|null $id
     * @return \Cake\Http\Response
     */
    public function recordGet(?string $id = null): Response
    {
        $this->request->allowMethod(['get']);
        $countryId = $this->officerCountryId();
        if ($countryId < 1) {
            return $this->jsonRecordNotFound();
        }

        try {
            $club = $this->getScopedClub((string)$id, $countryId);
        } catch (NotFoundException) {
            return $this->jsonRecordNotFound();
        }

        $this->rememberLastVisited('Clubs', $club->id);
        $president = $this->Clubs->findClubPresident((int)$club->id);

        return $this->response
            ->withType('application/json')
            ->withStringBody(json_encode([
                'success' => true,
                'record' => [
                    'id' => $club->id,
                    'name' => $club->name,
                    'country' => AdminCountry::label((int)$club->country_id),
                    'club_president' => $president !== null
                        ? MembershipProfile::displayName($president)
                        : '',
                    'enabled' => (bool)$club->enabled,
                    'visible' => (bool)$club->visible,
                    'pos' => LocaleNumberParser::format($club->pos, decimals: 0),
                    'user_count' => LocaleNumberParser::formatCount(
                        (int)($club->get('user_count') ?? 0),
                        decimals: 0
                    ),
                    'created' => $club->created
                        ? LocaleDateParser::format($club->created, 'datetime_short')
                        : '',
                    'modified' => $club->modified
                        ? LocaleDateParser::format($club->modified, 'datetime_short')
                        : '',
                    'can_delete' => $this->Clubs->canDelete($club),
                ],
            ], JSON_UNESCAPED_UNICODE));
    }

    /**
     * JSON: club president user for linked modal.
     *
     * @param string|null $id User id
     * @return \Cake\Http\Response
     */
    public function userRecordGet(?string $id = null): Response
    {
        $this->request->allowMethod(['get']);
        $countryId = $this->officerCountryId();
        if ($countryId < 1) {
            return $this->jsonRecordNotFound();
        }

        /** @var \App\Model\Table\UsersTable $users */
        $users = $this->fetchTable('Users');
        $user = $users->find()
            ->contain(['Clubs'])
            ->where([
                'Users.id' => (string)$id,
                'Users.country_id' => $countryId,
            ])
            ->first();
        if ($user === null) {
            return $this->jsonRecordNotFound();
        }

        return $this->response
            ->withType('application/json')
            ->withStringBody(json_encode([
                'success' => true,
                'record' => $this->userRecordPayload($user),
            ], JSON_UNESCAPED_UNICODE));
    }

    /**
     * Select2 AJAX: users eligible as club president (same country, not role `new`).
     *
     * Filter: `Users.country_id` = officer country; exclude `role = new` (member and above OK).
     *
     * @return \Cake\Http\Response
     */
    public function userOptions(): Response
    {
        $this->request->allowMethod(['get']);
        $countryId = $this->officerCountryId();
        if ($countryId < 1) {
            return $this->response
                ->withType('application/json')
                ->withStringBody((string)json_encode([
                    'results' => [],
                    'pagination' => ['more' => false],
                ], JSON_UNESCAPED_UNICODE));
        }

        $term = trim((string)$this->request->getQuery('q'));
        $page = max(1, (int)$this->request->getQuery('page'));
        $limit = 20;

        /** @var \App\Model\Table\UsersTable $users */
        $users = $this->fetchTable('Users');
        $query = $users->find()
            ->select(['id', 'email', 'username', 'first_name', 'last_name', 'role', 'club_id'])
            ->where([
                'Users.country_id' => $countryId,
                'Users.role !=' => AppRoles::NEW,
            ])
            ->orderBy(['Users.first_name' => 'ASC', 'Users.email' => 'ASC']);

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
            $results[] = [
                'id' => (string)$user->get('id'),
                'text' => $this->formatUserOptionLabel($user),
            ];
        }

        return $this->response
            ->withType('application/json')
            ->withStringBody((string)json_encode([
                'results' => $results,
                'pagination' => [
                    'more' => ($page * $limit) < $total,
                ],
            ], JSON_UNESCAPED_UNICODE));
    }

    protected function requireOfficerCountryId(): int
    {
        $countryId = $this->officerCountryId();
        if ($countryId < 1) {
            throw new ForbiddenException(__('Your account is not assigned to a country yet.'));
        }

        return $countryId;
    }

    protected function getScopedClub(string $id, int $countryId): EntityInterface
    {
        $club = $this->Clubs->find()
            ->where([
                'Clubs.id' => (int)$id,
                'Clubs.country_id' => $countryId,
            ])
            ->first();
        if ($club === null) {
            throw new NotFoundException(__('Club not found.'));
        }

        return $club;
    }

    /**
     * @param array<string, mixed> $data
     */
    protected function extractPresidentId(array $data): ?string
    {
        $raw = $data['club_president_id'] ?? null;
        if ($raw === null || $raw === '' || $raw === []) {
            return null;
        }
        if (is_array($raw)) {
            $raw = reset($raw);
        }

        $id = trim((string)$raw);

        return $id !== '' ? $id : null;
    }

    /**
     * @param \Cake\Datasource\EntityInterface|null $president
     */
    protected function setClubFormVars(EntityInterface $club, int $countryId, ?EntityInterface $president): void
    {
        $presidentOptions = [];
        if ($president !== null) {
            $presidentOptions[(string)$president->get('id')] = $this->formatUserOptionLabel($president);
        }

        $this->set(compact('club', 'countryId', 'president', 'presidentOptions'));
        $this->set('countryLabel', AdminCountry::label($countryId));
        $this->set('clubPresidentId', $president !== null ? (string)$president->get('id') : '');
    }

    /**
     * @param list<int> $clubIds
     * @return array<int, \Cake\Datasource\EntityInterface>
     */
    protected function loadClubPresidentsMap(array $clubIds, int $countryId): array
    {
        $clubIds = array_values(array_filter($clubIds, static fn (int $id): bool => $id > 0));
        if ($clubIds === []) {
            return [];
        }

        /** @var \App\Model\Table\UsersTable $users */
        $users = $this->fetchTable('Users');
        $rows = $users->find()
            ->where([
                'Users.club_id IN' => $clubIds,
                'Users.role' => AppRoles::CLUBPRESIDENT,
                'Users.country_id' => $countryId,
            ])
            ->all();

        $map = [];
        foreach ($rows as $user) {
            $cid = (int)$user->get('club_id');
            if (!isset($map[$cid])) {
                $map[$cid] = $user;
            }
        }

        return $map;
    }

    /**
     * @param \Cake\Datasource\EntityInterface $user
     * @return array<string, mixed>
     */
    protected function userRecordPayload(EntityInterface $user): array
    {
        $clubName = '';
        if ($user->get('club') !== null) {
            $club = $user->get('club');
            if (is_object($club) && method_exists($club, 'get')) {
                $clubName = (string)($club->get('name') ?? '');
            }
        }

        return [
            'id' => $user->get('id'),
            'first_name' => MembershipProfile::displayName($user),
            'email' => (string)($user->get('email') ?? ''),
            'phone' => (string)($user->get('phone') ?? ''),
            'role' => (string)($user->get('role') ?? ''),
            'country' => AdminCountry::label((int)($user->get('country_id') ?? 0)),
            'club' => $clubName,
            'active' => (bool)$user->get('active'),
            'enabled' => (int)($user->get('enabled') ?? 0) === 1,
            MembershipProfile::FIELD_JOINED => $user->get(MembershipProfile::FIELD_JOINED)
                ? LocaleDateParser::format($user->get(MembershipProfile::FIELD_JOINED), 'date')
                : '',
            MembershipFee::FIELD_CLUB => $user->get(MembershipFee::FIELD_CLUB)
                ? LocaleDateParser::format($user->get(MembershipFee::FIELD_CLUB), 'date')
                : '',
            MembershipFee::FIELD_NATIONAL => $user->get(MembershipFee::FIELD_NATIONAL)
                ? LocaleDateParser::format($user->get(MembershipFee::FIELD_NATIONAL), 'date')
                : '',
            'created' => $user->get('created')
                ? LocaleDateParser::format($user->get('created'), 'datetime_short')
                : '',
            'modified' => $user->get('modified')
                ? LocaleDateParser::format($user->get('modified'), 'datetime_short')
                : '',
            'can_delete' => false,
        ];
    }

    protected function formatUserOptionLabel(object $user): string
    {
        $name = MembershipProfile::displayName($user);
        $email = trim((string)($user->get('email') ?? ''));
        $role = trim((string)($user->get('role') ?? ''));

        if ($name !== '' && $email !== '') {
            $label = $name . ' <' . $email . '>';
        } elseif ($email !== '') {
            $label = $email;
        } else {
            $label = (string)($user->get('username') ?? $user->get('id'));
        }

        if ($role !== '') {
            $label .= ' (' . AppRoles::label($role) . ')';
        }

        return $label;
    }

    protected function jsonRecordNotFound(): Response
    {
        return $this->response
            ->withStatus(404)
            ->withType('application/json')
            ->withStringBody(json_encode([
                'success' => false,
                'message' => __('Record not found.'),
            ], JSON_UNESCAPED_UNICODE));
    }
}
