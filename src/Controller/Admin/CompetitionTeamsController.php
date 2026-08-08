<?php
declare(strict_types=1);

namespace App\Controller\Admin;

use App\Model\Table\CompetitionsClubsTable;
use App\Utility\LocaleDateParser;
use App\Utility\LocaleNumberParser;
use Cake\Http\Exception\NotFoundException;
use Cake\Http\Response;

/**
 * Admin CRUD for competition sub-teams (competitions_clubs).
 *
 * @property \App\Model\Table\CompetitionsClubsTable $CompetitionsClubs
 */
class CompetitionTeamsController extends AppController
{
    protected CompetitionsClubsTable $CompetitionsClubs;

    public function initialize(): void
    {
        parent::initialize();
        $this->CompetitionsClubs = $this->fetchTable('CompetitionsClubs');
    }

    /**
     * @param string|null $id
     */
    public function view(?string $id = null): void
    {
        $team = $this->getTeam($id, ['Competitions', 'Subclubs', 'Clubs']);
        $this->rememberLastVisited('CompetitionTeams', $team->id);

        $minimum = (int)($team->competition->minimum_team_size ?? 3);
        $meetsMinimum = $this->CompetitionsClubs->meetsMinimumTeamSize($team, $minimum);

        $this->set(compact('team', 'minimum', 'meetsMinimum'));
        $this->set('teamName', (string)($team->subclub->name ?? ''));
        $this->setCanDeleteFlag($this->CompetitionsClubs, $team);
        $this->set('title', __('Sub-team details'));
        $this->viewBuilder()->setVar('breadcrumb', __('Sub-teams'));
    }

    /**
     * @param string|null $id
     * @return \Cake\Http\Response|null|void
     */
    public function edit(?string $id = null)
    {
        $team = $this->getTeam($id, ['Competitions', 'Subclubs']);
        $this->rememberLastVisited('CompetitionTeams', $team->id);

        if ($this->request->is(['patch', 'post', 'put'])) {
            $team = $this->CompetitionsClubs->patchEntity($team, $this->request->getData(), [
                'fields' => ['visible', 'pos'],
            ]);
            if ($this->CompetitionsClubs->save($team)) {
                $this->Flash->success(__('The sub-team has been saved.'));

                return $this->redirect([
                    'prefix' => 'Admin',
                    'controller' => 'Competitions',
                    'action' => 'view',
                    $team->competition_id,
                    '#' => 'sub-teams',
                ]);
            }
            $this->flashEntityErrors($team);
        }

        $this->set(compact('team'));
        $this->set('teamName', (string)($team->subclub->name ?? ''));
        $this->setCanDeleteFlag($this->CompetitionsClubs, $team);
        $this->set('title', __('Edit sub-team'));
        $this->viewBuilder()->setVar('breadcrumb', __('Sub-teams'));
        $this->render('form');
    }

    /**
     * @param string|null $id
     */
    public function delete(?string $id = null): ?Response
    {
        $this->request->allowMethod(['post', 'delete']);
        $team = $this->getTeam($id);
        $competitionId = (string)$team->competition_id;

        $response = $this->deleteEntityOrFail($this->CompetitionsClubs, $team);
        if ($response !== null && $this->request->getData('redirect') === 'competition') {
            return $this->redirect([
                'prefix' => 'Admin',
                'controller' => 'Competitions',
                'action' => 'view',
                $competitionId,
            ]);
        }

        return $response;
    }

    /**
     * @param string|null $id
     */
    public function recordGet(?string $id = null): Response
    {
        $this->request->allowMethod(['get']);

        try {
            $team = $this->getTeam($id, ['Competitions', 'Subclubs', 'Clubs']);
        } catch (\Throwable) {
            return $this->response
                ->withStatus(404)
                ->withType('application/json')
                ->withStringBody((string)json_encode([
                    'success' => false,
                    'message' => __('Record not found.'),
                ], JSON_UNESCAPED_UNICODE));
        }

        $this->rememberLastVisited('CompetitionTeams', $team->id);
        $minimum = (int)($team->competition->minimum_team_size ?? 3);

        return $this->response
            ->withType('application/json')
            ->withStringBody((string)json_encode([
                'success' => true,
                'record' => [
                    'id' => $team->id,
                    'competition' => (string)($team->competition->name ?? ''),
                    'club' => (string)($team->club->name ?? ''),
                    'name' => (string)($team->subclub->name ?? ''),
                    'user_count' => LocaleNumberParser::format($team->user_count, decimals: 0),
                    'minimum_team_size' => LocaleNumberParser::format($minimum, decimals: 0),
                    'application_datetime' => $team->application_datetime
                        ? LocaleDateParser::format($team->application_datetime, 'datetime_short')
                        : '',
                    'visible' => (bool)$team->visible,
                    'pos' => LocaleNumberParser::format($team->pos, decimals: 0),
                    'created' => $team->created
                        ? LocaleDateParser::format($team->created, 'datetime_short')
                        : '',
                    'modified' => $team->modified
                        ? LocaleDateParser::format($team->modified, 'datetime_short')
                        : '',
                    'can_delete' => $this->CompetitionsClubs->canDelete($team),
                ],
            ], JSON_UNESCAPED_UNICODE));
    }

    /**
     * @param list<string> $contain
     */
    protected function getTeam(?string $id, array $contain = []): \App\Model\Entity\CompetitionsClub
    {
        if ($id === null || $id === '') {
            throw new NotFoundException(__('Record not found.'));
        }

        /** @var \App\Model\Entity\CompetitionsClub $team */
        $team = $this->CompetitionsClubs->get($id, contain: $contain);

        return $team;
    }
}
