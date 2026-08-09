<?php
declare(strict_types=1);

namespace App\Controller\Admin;

use App\Model\Table\CompetitionsUsersTable;
use App\Utility\AdminTranslate;
use App\Utility\CompetitionApplication;
use App\Utility\LocaleDateParser;
use App\Utility\LocaleNumberParser;
use Cake\Http\Exception\NotFoundException;
use Cake\Http\Response;

/**
 * Admin CRUD for competition applicants (competitions_users).
 *
 * @property \App\Model\Table\CompetitionsUsersTable $CompetitionsUsers
 */
class CompetitionApplicantsController extends AppController
{
    protected CompetitionsUsersTable $CompetitionsUsers;

    public function initialize(): void
    {
        parent::initialize();
        $this->CompetitionsUsers = $this->fetchTable('CompetitionsUsers');
    }

    /**
     * @param string|null $id
     */
    public function view(?string $id = null): void
    {
        $app = $this->getApplicant($id);
        $this->rememberLastVisited('CompetitionApplicants', $app->id);
        $this->set(compact('app'));
        $this->set('title', __('Application details'));
        $this->viewBuilder()->setVar('breadcrumb', __('Applicants'));
    }

    /**
     * @param string|null $id
     * @return \Cake\Http\Response|null|void
     */
    public function edit(?string $id = null)
    {
        $app = $this->getApplicant($id);
        $this->rememberLastVisited('CompetitionApplicants', $app->id);

        if ($this->request->is(['post', 'put', 'patch'])) {
            $app = $this->CompetitionsUsers->patchEntity(
                $app,
                CompetitionApplication::detailFieldsFromData((array)$this->request->getData())
            );
            if ($this->CompetitionsUsers->save($app)) {
                $this->Flash->success(__('Application details have been saved.'));

                return $this->redirect([
                    'prefix' => 'Admin',
                    'controller' => 'Competitions',
                    'action' => 'view',
                    $app->competition_id,
                    '#' => 'applicants',
                ]);
            }
            $this->flashEntityErrors($app);
        }

        $this->set(compact('app'));
        $this->set('title', __('Edit application'));
        $this->viewBuilder()->setVar('breadcrumb', __('Applicants'));
        $this->render('form');
    }

    /**
     * @param string|null $id
     */
    public function delete(?string $id = null): ?Response
    {
        $this->request->allowMethod(['post', 'delete']);
        $app = $this->getApplicant($id);
        $competitionId = (string)$app->competition_id;

        if ($this->CompetitionsUsers->delete($app)) {
            $this->Flash->success(__('Application deleted.'));

            return $this->redirect([
                'prefix' => 'Admin',
                'controller' => 'Competitions',
                'action' => 'view',
                $competitionId,
            ]);
        }

        $this->Flash->error(__('Could not delete the application. Please try again.'));

        return $this->redirect([
            'prefix' => 'Admin',
            'controller' => 'Competitions',
            'action' => 'view',
            $competitionId,
        ]);
    }

    /**
     * @param string|null $id
     */
    public function recordGet(?string $id = null): Response
    {
        $this->request->allowMethod(['get']);

        try {
            $app = $this->getApplicant($id);
        } catch (\Throwable) {
            return $this->response
                ->withStatus(404)
                ->withType('application/json')
                ->withStringBody((string)json_encode([
                    'success' => false,
                    'message' => __('Record not found.'),
                ], JSON_UNESCAPED_UNICODE));
        }

        $this->rememberLastVisited('CompetitionApplicants', $app->id);
        $user = $app->user;
        $memberName = $user !== null
            ? \App\Auth\MembershipProfile::displayName($user)
            : (string)$app->user_id;

        return $this->response
            ->withType('application/json')
            ->withStringBody((string)json_encode([
                'success' => true,
                'record' => [
                    'id' => $app->id,
                    'competition' => (string)($app->competition->name ?? ''),
                    'member' => $memberName,
                    'email' => (string)($user->email ?? ''),
                    'club' => (string)($app->competitions_club?->club?->name ?? ''),
                    'team' => (string)($app->competitions_club?->subclub?->name ?? ''),
                    'status' => CompetitionApplication::statusLabel((string)$app->status),
                    'lunch_for_the_attendant' => LocaleNumberParser::format($app->lunch_for_the_attendant, decimals: 0),
                    'comment' => (string)($app->comment ?? ''),
                    'created' => $app->created
                        ? LocaleDateParser::format($app->created, 'datetime_short')
                        : '',
                    'modified' => $app->modified
                        ? LocaleDateParser::format($app->modified, 'datetime_short')
                        : '',
                    'can_delete' => true,
                ],
            ], JSON_UNESCAPED_UNICODE));
    }

    protected function getApplicant(?string $id): \App\Model\Entity\CompetitionsUser
    {
        if ($id === null || $id === '') {
            throw new NotFoundException(__('Record not found.'));
        }

        AdminTranslate::applyLocale($this->CompetitionsUsers->Competitions->getTarget());
        $app = $this->CompetitionsUsers->find()
            ->contain([
                'Users',
                'Competitions',
                'CompetitionsClubs' => ['Subclubs', 'Clubs'],
            ])
            ->where(['CompetitionsUsers.id' => (int)$id])
            ->first();

        if ($app === null) {
            throw new NotFoundException(__('Record not found.'));
        }

        return $app;
    }
}
