<?php
declare(strict_types=1);

namespace App\Controller\Clubpresident;

use App\Auth\MembershipProfile;
use App\Model\Table\CompetitionsClubsTable;
use App\Model\Table\CompetitionsUsersTable;
use App\Utility\CompetitionApplication;
use App\Utility\LocaleDateParser;
use App\Utility\LocaleNumberParser;
use Cake\Event\EventInterface;
use Cake\Http\Exception\NotFoundException;
use Cake\Http\Response;

/**
 * Assign club members who applied to a competition into a sub-team (competitions_clubs → subclubs).
 *
 * Flow: member applies (competitions_users, competition_club_id NULL)
 *     → club president sets competition_club_id (alcsapat).
 */
class CompetitionApplicantsController extends AppController
{
    protected CompetitionsUsersTable $CompetitionsUsers;

    protected CompetitionsClubsTable $CompetitionsClubs;

    public function initialize(): void
    {
        parent::initialize();
        $this->CompetitionsUsers = $this->fetchTable('CompetitionsUsers');
        $this->CompetitionsClubs = $this->fetchTable('CompetitionsClubs');
    }

    public function beforeRender(EventInterface $event): void
    {
        parent::beforeRender($event);
        $this->set('indexListUrl', [
            'prefix' => 'Clubpresident',
            'controller' => 'CompetitionApplicants',
            'action' => 'index',
        ]);
    }

    /**
     * All applications from this club's members, assignable to sub-teams.
     *
     * @return \Cake\Http\Response|null|void
     */
    public function index()
    {
        $clubId = $this->presidentClubId();
        $this->set('title', __('Competition applicants'));
        $this->viewBuilder()->setVar('breadcrumb', __('Competition applicants'));
        $this->set('canAdd', false);
        $this->set('canEdit', true);
        $this->set('canDelete', true);

        $memberIds = $this->fetchTable('Users')->find()
            ->select(['id'])
            ->where(['Users.club_id' => $clubId])
            ->all()
            ->extract('id')
            ->toList();

        $groups = [];
        $teamOptionsByCompetition = [];

        $teams = $this->CompetitionsClubs->find()
            ->contain(['Subclubs', 'Competitions'])
            ->where(['CompetitionsClubs.club_id' => $clubId])
            ->orderBy(['Subclubs.name' => 'ASC'])
            ->all();
        foreach ($teams as $team) {
            $cid = (string)$team->competition_id;
            if (!isset($teamOptionsByCompetition[$cid])) {
                $teamOptionsByCompetition[$cid] = [
                    '' => __('— Unassigned —'),
                ];
            }
            $label = (string)($team->subclub->name ?? ('#' . $team->id));
            $teamOptionsByCompetition[$cid][(string)$team->id] = $label;
        }

        if ($memberIds !== []) {
            $applicants = $this->CompetitionsUsers->find()
                ->contain([
                    'Users',
                    'Competitions',
                    'CompetitionsClubs' => ['Subclubs'],
                ])
                ->where([
                    'CompetitionsUsers.user_id IN' => $memberIds,
                ])
                ->orderBy([
                    'Competitions.application_deadline' => 'DESC',
                    'Competitions.name' => 'ASC',
                    'CompetitionsUsers.created' => 'ASC',
                ])
                ->all();

            foreach ($applicants as $app) {
                $competitionId = (string)$app->competition_id;
                if (!isset($groups[$competitionId])) {
                    $groups[$competitionId] = [
                        'competition' => $app->competition,
                        'applicants' => [],
                    ];
                }
                $groups[$competitionId]['applicants'][] = $app;
            }
        }

        $this->set(compact('groups', 'teamOptionsByCompetition', 'clubId'));
    }

    /**
     * Assign / unassign one applicant to a competitions_clubs (sub-team) row.
     *
     * @return \Cake\Http\Response|null
     */
    public function assign(): ?Response
    {
        $this->request->allowMethod(['post', 'put', 'patch']);
        $clubId = $this->presidentClubId();

        $applicationId = (int)$this->request->getData('application_id');
        $competitionClubIdRaw = $this->request->getData('competition_club_id');
        $competitionClubId = $competitionClubIdRaw === '' || $competitionClubIdRaw === null
            ? 0
            : (int)$competitionClubIdRaw;

        $app = $this->CompetitionsUsers->find()
            ->contain(['Users'])
            ->where(['CompetitionsUsers.id' => $applicationId])
            ->first();

        if ($app === null) {
            $this->Flash->error(__('Applicant not found.'));

            return $this->redirect(['action' => 'index']);
        }

        $memberClubId = (int)($app->user->club_id ?? 0);
        if ($memberClubId !== $clubId) {
            $this->Flash->error(__('This member does not belong to your club.'));

            return $this->redirect(['action' => 'index']);
        }

        if ($competitionClubId > 0) {
            $team = $this->CompetitionsClubs->find()
                ->where([
                    'CompetitionsClubs.id' => $competitionClubId,
                    'CompetitionsClubs.club_id' => $clubId,
                    'CompetitionsClubs.competition_id' => (string)$app->competition_id,
                ])
                ->first();
            if ($team === null) {
                $this->Flash->error(__('Invalid sub-team for this competition.'));

                return $this->redirect(['action' => 'index']);
            }
            $app->competition_club_id = (int)$team->id;
            $app->status = CompetitionApplication::STATUS_ASSIGNED;
        } else {
            $app->competition_club_id = null;
            $app->status = CompetitionApplication::STATUS_PENDING;
        }

        if ($this->CompetitionsUsers->save($app)) {
            $this->Flash->success(
                $competitionClubId > 0
                    ? __('Member assigned to the sub-team.')
                    : __('Member unassigned from the sub-team.')
            );
        } else {
            $this->flashEntityErrors($app);
        }

        return $this->redirect(['action' => 'index', '#' => 'competition-' . $app->competition_id]);
    }

    /**
     * Delete application (member does not compete / revoke interest).
     *
     * @param string|null $id competitions_users.id
     * @return \Cake\Http\Response|null
     */
    public function delete(?string $id = null): ?Response
    {
        $this->request->allowMethod(['post', 'delete']);
        $clubId = $this->presidentClubId();

        $app = $this->CompetitionsUsers->find()
            ->contain(['Users'])
            ->where(['CompetitionsUsers.id' => (int)$id])
            ->first();

        if ($app === null) {
            $this->Flash->error(__('Applicant not found.'));

            return $this->redirect(['action' => 'index']);
        }

        $memberClubId = (int)($app->user->club_id ?? 0);
        if ($memberClubId !== $clubId) {
            $this->Flash->error(__('This member does not belong to your club.'));

            return $this->redirect(['action' => 'index']);
        }

        $competitionId = (string)$app->competition_id;
        if ($this->CompetitionsUsers->delete($app)) {
            $this->Flash->success(__('Application deleted.'));
        } else {
            $this->Flash->error(__('Could not delete the application. Please try again.'));
        }

        return $this->redirect(['action' => 'index', '#' => 'competition-' . $competitionId]);
    }

    /**
     * Edit application details (lunch / pipes / comment) — allowed after deadline too.
     *
     * @param string|null $id competitions_users.id
     * @return \Cake\Http\Response|null|void
     */
    public function edit(?string $id = null)
    {
        $clubId = $this->presidentClubId();
        $app = $this->CompetitionsUsers->find()
            ->contain([
                'Users',
                'Competitions',
                'CompetitionsClubs' => ['Subclubs'],
            ])
            ->where(['CompetitionsUsers.id' => (int)$id])
            ->first();

        if ($app === null) {
            throw new NotFoundException(__('Applicant not found.'));
        }

        $memberClubId = (int)($app->user->club_id ?? 0);
        if ($memberClubId !== $clubId) {
            $this->Flash->error(__('This member does not belong to your club.'));

            return $this->redirect(['action' => 'index']);
        }

        $this->set('title', __('Edit application'));
        $this->viewBuilder()->setVar('breadcrumb', __('Competition applicants'));
        $this->set('canAdd', false);
        $this->set('canEdit', true);
        $this->set('canDelete', false);

        if ($this->request->is(['post', 'put', 'patch'])) {
            $app = $this->CompetitionsUsers->patchEntity(
                $app,
                CompetitionApplication::detailFieldsFromData((array)$this->request->getData())
            );
            if ($this->CompetitionsUsers->save($app)) {
                $this->Flash->success(__('Application details have been saved.'));

                return $this->redirect(['action' => 'index', '#' => 'competition-' . $app->competition_id]);
            }
            $this->flashEntityErrors($app);
        }

        $pastDeadline = CompetitionApplication::isPastDeadline(
            $app->competition->application_deadline ?? null
        );
        $this->set(compact('app', 'pastDeadline', 'clubId'));
    }

    /**
     * JSON: application details for related-tab / index modal.
     *
     * @param string|null $id competitions_users.id
     */
    public function recordGet(?string $id = null): Response
    {
        $this->request->allowMethod(['get']);
        $clubId = $this->presidentClubId();

        $app = $this->CompetitionsUsers->find()
            ->contain([
                'Users',
                'Competitions',
                'CompetitionsClubs' => ['Subclubs'],
            ])
            ->where(['CompetitionsUsers.id' => (int)$id])
            ->first();

        if ($app === null) {
            return $this->response
                ->withStatus(404)
                ->withType('application/json')
                ->withStringBody((string)json_encode([
                    'success' => false,
                    'message' => __('Record not found.'),
                ], JSON_UNESCAPED_UNICODE));
        }

        $memberClubId = (int)($app->user->club_id ?? 0);
        if ($memberClubId !== $clubId) {
            return $this->response
                ->withStatus(403)
                ->withType('application/json')
                ->withStringBody((string)json_encode([
                    'success' => false,
                    'message' => __('This member does not belong to your club.'),
                ], JSON_UNESCAPED_UNICODE));
        }

        $user = $app->user;
        $memberName = $user !== null
            ? MembershipProfile::displayName($user)
            : (string)$app->user_id;
        if ($memberName === '') {
            $memberName = (string)($user->email ?? $app->user_id);
        }

        return $this->response
            ->withType('application/json')
            ->withStringBody((string)json_encode([
                'success' => true,
                'record' => [
                    'id' => $app->id,
                    'competition' => (string)($app->competition->name ?? ''),
                    'member' => $memberName,
                    'email' => (string)($user->email ?? ''),
                    'team' => (string)($app->competitions_club?->subclub?->name ?? ''),
                    'status' => CompetitionApplication::statusLabel((string)$app->status),
                    'lunch_for_the_attendant' => LocaleNumberParser::format(
                        $app->lunch_for_the_attendant,
                        decimals: 0
                    ),
                    'comment' => (string)($app->comment ?? ''),
                    'created' => $app->created
                        ? LocaleDateParser::format($app->created, 'datetime_short')
                        : '',
                    'modified' => $app->modified
                        ? LocaleDateParser::format($app->modified, 'datetime_short')
                        : '',
                    'can_delete' => false,
                ],
            ], JSON_UNESCAPED_UNICODE));
    }
}
