<?php
declare(strict_types=1);

namespace App\Controller\Member;

use App\Controller\Concerns\IndexListCrudTrait;
use App\Model\Table\CompetitionsTable;
use App\Model\Table\CompetitionsUsersTable;
use App\Model\Table\UsersTable;
use App\Utility\CompetitionApplication;
use App\Utility\MembershipFee;
use Cake\Datasource\ResultSetInterface;
use Cake\Http\Exception\NotFoundException;
use Cake\Http\Response;
use Cake\I18n\DateTime;

/**
 * Member competitions — list / apply / withdraw / archive.
 *
 * @property \App\Model\Table\CompetitionsTable $Competitions
 * @property \App\Model\Table\CompetitionsUsersTable $CompetitionsUsers
 * @property \App\Model\Table\UsersTable $Users
 */
class CompetitionsController extends AppController
{
    use IndexListCrudTrait;

    protected CompetitionsTable $Competitions;

    protected CompetitionsUsersTable $CompetitionsUsers;

    protected UsersTable $Users;

    public function initialize(): void
    {
        parent::initialize();
        $this->Competitions = $this->fetchTable('Competitions');
        $this->CompetitionsUsers = $this->fetchTable('CompetitionsUsers');
        $this->Users = $this->fetchTable('Users');
    }

    /**
     * Upcoming competitions (not ended) for the member's country.
     *
     * @return \Cake\Http\Response|null|void
     */
    public function index()
    {
        $user = $this->currentUserRow();
        $countryId = (int)($user['country_id'] ?? 0);
        $userId = (string)($user['id'] ?? '');
        $clubFeePaid = CompetitionApplication::memberMayApply($user);

        $competitions = $this->findOpenCompetitionsForCountry($countryId);
        $myApplications = $this->applicationsByCompetitionId($userId);

        $this->set(compact('competitions', 'myApplications', 'countryId', 'clubFeePaid'));
        $this->set('title', __('Competitions'));
        $this->viewBuilder()->setVar('breadcrumb', __('Competitions'));
    }

    /**
     * Past competitions the member took part in (archive).
     *
     * @return \Cake\Http\Response|null|void
     */
    public function archive()
    {
        $user = $this->currentUserRow();
        $userId = (string)($user['id'] ?? '');
        $now = DateTime::now()->format('Y-m-d H:i:s');

        $rows = $userId !== ''
            ? $this->CompetitionsUsers->find()
                ->contain(['Competitions' => ['Clubs'], 'CompetitionsClubs' => ['Subclubs']])
                ->where([
                    'CompetitionsUsers.user_id' => $userId,
                    'Competitions.end_datetime IS NOT' => null,
                    'Competitions.end_datetime <' => $now,
                ])
                ->orderBy(['Competitions.end_datetime' => 'DESC'])
                ->all()
            : $this->CompetitionsUsers->find()->where(['1 = 0'])->all();

        $this->set(compact('rows'));
        $this->set('title', __('Competition archive'));
        $this->viewBuilder()->setVar('breadcrumb', __('Archive'));
    }

    /**
     * @param string|null $id
     * @return \Cake\Http\Response|null|void
     */
    public function view(?string $id = null)
    {
        $user = $this->currentUserRow();
        $countryId = (int)($user['country_id'] ?? 0);
        $userId = (string)($user['id'] ?? '');

        $competition = $this->Competitions->get($id, contain: ['Clubs']);
        if ($countryId < 1 || (int)$competition->country_id !== $countryId || !(bool)$competition->visible) {
            throw new NotFoundException(__('Record not found.'));
        }

        $application = null;
        if ($userId !== '') {
            $application = $this->CompetitionsUsers->find()
                ->contain(['CompetitionsClubs' => ['Subclubs']])
                ->where([
                    'CompetitionsUsers.competition_id' => $competition->id,
                    'CompetitionsUsers.user_id' => $userId,
                    'CompetitionsUsers.status IN' => CompetitionApplication::activeStatuses(),
                ])
                ->first();
        }

        $canApply = CompetitionApplication::isApplicationOpen(
            $competition->first_date_of_application,
            $competition->application_deadline
        ) && CompetitionApplication::memberMayApply($user);
        $pastDeadline = CompetitionApplication::isPastDeadline($competition->application_deadline);
        $ended = !CompetitionApplication::isUpcomingOrOngoing($competition->end_datetime);
        $hasApplication = CompetitionApplication::hasApplication($application);
        $canEditApplication = $hasApplication && !$pastDeadline;
        $canWithdraw = $hasApplication && !$pastDeadline;
        $clubFeePaid = CompetitionApplication::memberMayApply($user);

        $this->set(compact(
            'competition',
            'application',
            'canApply',
            'pastDeadline',
            'ended',
            'canEditApplication',
            'canWithdraw',
            'clubFeePaid'
        ));
        $this->set('title', __('Competition'));
        $this->viewBuilder()->setVar('breadcrumb', __('Competitions'));
    }

    /**
     * Apply to competition (POST).
     *
     * @param string|null $id
     * @return \Cake\Http\Response|null
     */
    public function apply(?string $id = null): ?Response
    {
        $this->request->allowMethod(['post']);
        $user = $this->currentUserRow();
        $userId = (string)($user['id'] ?? '');
        $countryId = (int)($user['country_id'] ?? 0);
        $clubId = (int)($user['club_id'] ?? 0);

        if ($userId === '' || $countryId < 1 || $clubId < 1) {
            $this->Flash->error(__('Complete your club membership before applying.'));

            return $this->redirect(['action' => 'index']);
        }
        if (!CompetitionApplication::memberMayApply($user)) {
            $this->Flash->error(__(
                'You can only apply to competitions after your club membership fee is paid for this year.'
            ));

            return $this->redirect(['action' => 'view', (string)$id]);
        }

        $competition = $this->Competitions->get($id);
        if ((int)$competition->country_id !== $countryId || !(bool)$competition->visible) {
            throw new NotFoundException(__('Record not found.'));
        }
        if (!CompetitionApplication::isApplicationOpen(
            $competition->first_date_of_application,
            $competition->application_deadline
        )) {
            $this->Flash->error(__('Applications for this competition are closed.'));

            return $this->redirect(['action' => 'view', $competition->id]);
        }

        $existing = $this->CompetitionsUsers->find()
            ->where([
                'CompetitionsUsers.competition_id' => $competition->id,
                'CompetitionsUsers.user_id' => $userId,
            ])
            ->first();
        if ($existing !== null && CompetitionApplication::hasApplication($existing)) {
            $this->Flash->info(__('You have already applied to this competition.'));

            return $this->redirect(['action' => 'index']);
        }

        $detailFields = CompetitionApplication::detailFieldsFromData((array)$this->request->getData());
        if ($existing !== null) {
            // Soft-withdrawn / invalid row: reactivate instead of unique-key clash.
            $entity = $this->CompetitionsUsers->patchEntity($existing, array_merge(
                [
                    'competition_club_id' => null,
                    'status' => CompetitionApplication::STATUS_PENDING,
                    'visible' => true,
                ],
                $detailFields
            ));
        } else {
            $entity = $this->CompetitionsUsers->newEntity(array_merge(
                [
                    'competition_id' => $competition->id,
                    'user_id' => $userId,
                    'competition_club_id' => null,
                    'status' => CompetitionApplication::STATUS_PENDING,
                    'visible' => true,
                ],
                $detailFields
            ));
        }

        if ($this->CompetitionsUsers->save($entity)) {
            $this->Flash->success(__('Your application has been submitted. Your club president will assign a team.'));

            return $this->redirect(['action' => 'index']);
        }

        $this->flashEntityErrors($entity);

        return $this->redirect(['action' => 'view', $competition->id]);
    }

    /**
     * Update application details until the deadline (POST).
     *
     * @param string|null $id Competition id
     * @return \Cake\Http\Response|null
     */
    public function updateApplication(?string $id = null): ?Response
    {
        $this->request->allowMethod(['post', 'put', 'patch']);
        $user = $this->currentUserRow();
        $userId = (string)($user['id'] ?? '');
        $countryId = (int)($user['country_id'] ?? 0);

        if ($userId === '') {
            $this->Flash->error(__('You must be logged in.'));

            return $this->redirect(['action' => 'index']);
        }

        $competition = $this->Competitions->get($id);
        if ((int)$competition->country_id !== $countryId || !(bool)$competition->visible) {
            throw new NotFoundException(__('Record not found.'));
        }
        if (CompetitionApplication::isPastDeadline($competition->application_deadline)) {
            $this->Flash->error(__('The application deadline has passed. Only your club president can change these details now.'));

            return $this->redirect(['action' => 'view', $competition->id]);
        }

        $application = $this->CompetitionsUsers->find()
            ->where([
                'CompetitionsUsers.competition_id' => $competition->id,
                'CompetitionsUsers.user_id' => $userId,
                'CompetitionsUsers.status IN' => CompetitionApplication::activeStatuses(),
            ])
            ->first();
        if ($application === null || !CompetitionApplication::hasApplication($application)) {
            $this->Flash->error(__('Application not found.'));

            return $this->redirect(['action' => 'index']);
        }

        $application = $this->CompetitionsUsers->patchEntity(
            $application,
            CompetitionApplication::detailFieldsFromData((array)$this->request->getData())
        );

        if ($this->CompetitionsUsers->save($application)) {
            $this->Flash->success(__('Your application details have been saved.'));

            return $this->redirect(['action' => 'index']);
        }

        $this->flashEntityErrors($application);

        return $this->redirect(['action' => 'view', $competition->id]);
    }

    /**
     * Withdraw application — deletes the competitions_users row (CounterCache updates).
     * Only until the application deadline.
     *
     * @param string|null $id Competition id
     * @return \Cake\Http\Response|null
     */
    public function withdraw(?string $id = null): ?Response
    {
        $this->request->allowMethod(['post', 'delete']);
        $user = $this->currentUserRow();
        $userId = (string)($user['id'] ?? '');

        if ($userId === '') {
            $this->Flash->error(__('You must be logged in.'));

            return $this->redirect(['action' => 'index']);
        }

        $application = $this->CompetitionsUsers->find()
            ->contain(['Competitions'])
            ->where([
                'CompetitionsUsers.competition_id' => (string)$id,
                'CompetitionsUsers.user_id' => $userId,
                'CompetitionsUsers.status IN' => CompetitionApplication::activeStatuses(),
            ])
            ->first();

        if ($application === null || !CompetitionApplication::hasApplication($application)) {
            $this->Flash->info(__('You have no application to withdraw for this competition.'));

            return $this->redirect(['action' => 'index']);
        }

        $deadline = $application->competition->application_deadline ?? null;
        if (CompetitionApplication::isPastDeadline($deadline)) {
            $this->Flash->error(__('The application deadline has passed. Contact your club president to withdraw.'));

            return $this->redirect(['action' => 'view', (string)$id]);
        }

        if ($this->CompetitionsUsers->delete($application)) {
            $this->Flash->success(__('Your application has been withdrawn.'));
        } else {
            $this->Flash->error(__('Could not withdraw the application. Please try again.'));
        }

        return $this->redirect(['action' => 'index']);
    }

    /**
     * Open / upcoming competitions for a country (empty result set if country unknown).
     *
     * @return \Cake\Datasource\ResultSetInterface<\App\Model\Entity\Competition>
     */
    protected function findOpenCompetitionsForCountry(int $countryId): ResultSetInterface
    {
        if ($countryId < 1) {
            return $this->Competitions->find()->where(['1 = 0'])->all();
        }

        $now = DateTime::now()->format('Y-m-d H:i:s');

        return $this->Competitions->find()
            ->contain(['Clubs'])
            ->where([
                'Competitions.country_id' => $countryId,
                'Competitions.visible' => true,
                'OR' => [
                    'Competitions.end_datetime IS' => null,
                    'Competitions.end_datetime >=' => $now,
                ],
            ])
            ->orderBy([
                'Competitions.first_date_of_application' => 'ASC',
                'Competitions.application_deadline' => 'ASC',
                'Competitions.name' => 'ASC',
            ])
            ->all();
    }

    /**
     * @return array<string, \App\Model\Entity\CompetitionsUser>
     */
    protected function applicationsByCompetitionId(string $userId): array
    {
        $myApplications = [];
        if ($userId === '') {
            return $myApplications;
        }

        $apps = $this->CompetitionsUsers->find()
            ->contain(['CompetitionsClubs' => ['Subclubs']])
            ->where([
                'CompetitionsUsers.user_id' => $userId,
                'CompetitionsUsers.status IN' => CompetitionApplication::activeStatuses(),
            ])
            ->all();
        foreach ($apps as $app) {
            if (!CompetitionApplication::hasApplication($app)) {
                continue;
            }
            $myApplications[(string)$app->competition_id] = $app;
        }

        return $myApplications;
    }

    /**
     * @return array<string, mixed>
     */
    protected function currentUserRow(): array
    {
        $identity = $this->getRequest()->getAttribute('identity');
        if ($identity === null) {
            return [];
        }
        $userId = '';
        if (method_exists($identity, 'getIdentifier')) {
            $userId = (string)$identity->getIdentifier();
        }
        if ($userId === '') {
            return [];
        }
        $row = $this->Users->find()
            ->select([
                'id',
                'country_id',
                'club_id',
                'first_name',
                'last_name',
                'email',
                MembershipFee::FIELD_CLUB,
            ])
            ->where(['Users.id' => $userId])
            ->disableHydration()
            ->first();

        return is_array($row) ? $row : [];
    }
}
