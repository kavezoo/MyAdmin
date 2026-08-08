<?php
declare(strict_types=1);

namespace App\Controller\Admin;

use App\Utility\AdminCountry;
use App\Utility\AdminCountryScope;
use App\Utility\LocaleDateParser;
use App\Utility\LocaleNumberParser;
use Cake\Http\Response;

/**
 * Global Admin CRUD for clubs (scoped to own country; superuser can switch).
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

        $scoped = $this->beginAdminCountryScopedIndex($this->Clubs);
        if ($scoped instanceof Response) {
            return $scoped;
        }
        $filterCountryId = $scoped['countryId'];

        $redirect = $this->applyIndexListState('Clubs');
        if ($redirect !== null) {
            return $redirect;
        }

        $paginateOptions = $this->indexPaginateOptionsFor($this->Clubs, [
            'sortableFields' => [
                'id', 'name', 'short_name', 'country_id', 'city_id', 'pos',
                'enabled', 'visible', 'user_count', 'competition_count', 'created', 'modified',
                'Countries.name', 'Cities.name',
            ],
            'order' => ['Clubs.pos' => 'ASC', 'Clubs.name' => 'ASC'],
        ], [
            'Countries' => $this->Clubs->Countries->getTarget(),
            'Cities' => $this->Clubs->Cities->getTarget(),
        ]);

        $query = $this->applyAdminCountryWhere(
            $this->Clubs->find()->contain(['Countries', 'Cities']),
            $this->Clubs,
            $filterCountryId
        );
        $query = $this->applyIndexSearch($query, $this->Clubs);

        $redirect = $this->resolveIndexPageForLastVisited('Clubs', $query, $paginateOptions);
        if ($redirect !== null) {
            return $redirect;
        }

        $clubs = $this->paginate($query, $paginateOptions);
        $this->setLastVisitedForIndex('Clubs');
        $this->set(compact('clubs'));
    }

    /**
     * @return \Cake\Http\Response|null|void
     */
    public function add()
    {
        $club = $this->newEntityWithSchemaDefaults($this->Clubs);
        $ownScope = AdminCountryScope::scopeForTable($this->request, $this->Clubs);
        if ($ownScope['countryId'] > 0) {
            $club->country_id = $ownScope['countryId'];
        }
        if ($this->request->is('post')) {
            $data = $this->constrainAdminCountryData($this->request->getData());
            $club = $this->Clubs->patchEntity($club, $data, [
                'fields' => $this->formFields(),
            ]);
            if ($this->Clubs->save($club)) {
                $this->rememberLastVisited('Clubs', $club->id);
                $this->Flash->success(__('The club has been saved.'));

                return $this->redirectToIndexList('Clubs');
            }
            $this->flashEntityErrors($club);
        }

        $this->setFormVars($club);
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
        $club = $this->Clubs->get($id);
        $denied = $this->denyIfOutsideAdminCountryScope($club);
        if ($denied !== null) {
            return $denied;
        }
        $this->rememberLastVisited('Clubs', $club->id);
        if ($this->request->is(['patch', 'post', 'put'])) {
            $data = $this->constrainAdminCountryData($this->request->getData());
            $club = $this->Clubs->patchEntity($club, $data, [
                'fields' => $this->formFields(),
            ]);
            if ($this->Clubs->save($club)) {
                $this->rememberLastVisited('Clubs', $club->id);
                $this->Flash->success(__('The club has been saved.'));

                return $this->redirectToIndexList('Clubs');
            }
            $this->flashEntityErrors($club);
        }

        $this->setFormVars($club);
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
        $club = $this->Clubs->get($id, contain: [
            'Countries',
            'Cities',
            'Users' => fn ($query) => $query->orderBy([
                'Users.first_name' => 'ASC',
                'Users.last_name' => 'ASC',
                'Users.email' => 'ASC',
            ]),
            'Competitions' => fn ($query) => $query->orderBy([
                'Competitions.name' => 'ASC',
            ]),
        ]);
        $denied = $this->denyIfOutsideAdminCountryScope($club);
        if ($denied !== null) {
            return $denied;
        }
        $this->rememberLastVisited('Clubs', $club->id);
        $this->set(compact('club'));
        $this->set('countryLabel', AdminCountry::label((int)$club->country_id));
        $this->setCanDeleteFlag($this->Clubs, $club);
        $this->set('title', __('Club details'));
        $this->viewBuilder()->setVar('breadcrumb', __('Clubs'));
    }

    /**
     * @param string|null $id
     */
    public function delete(?string $id = null): ?Response
    {
        $this->request->allowMethod(['post', 'delete']);
        $club = $this->Clubs->get($id);
        $denied = $this->denyIfOutsideAdminCountryScope($club);
        if ($denied !== null) {
            return $denied;
        }

        return $this->deleteEntityOrFail($this->Clubs, $club);
    }

    /**
     * @param string|null $id
     */
    public function recordGet(?string $id = null): Response
    {
        $this->request->allowMethod(['get']);
        try {
            $club = $this->Clubs->get($id, contain: ['Countries', 'Cities']);
        } catch (\Throwable) {
            return $this->response->withStatus(404)->withType('application/json')->withStringBody((string)json_encode([
                'success' => false, 'message' => __('Record not found.'),
            ], JSON_UNESCAPED_UNICODE));
        }

        if (!AdminCountryScope::entityAllowed($club, $this->request)) {
            return $this->response->withStatus(403)->withType('application/json')->withStringBody((string)json_encode([
                'success' => false, 'message' => __('You are not allowed to access records from another country.'),
            ], JSON_UNESCAPED_UNICODE));
        }

        $this->rememberLastVisited('Clubs', $club->id);

        return $this->response->withType('application/json')->withStringBody((string)json_encode([
            'success' => true,
            'record' => [
                'id' => $club->id,
                'name' => $club->name,
                'short_name' => (string)$club->short_name,
                'country' => AdminCountry::label((int)$club->country_id),
                'city' => $club->city !== null ? (string)$club->city->name : '',
                'address' => (string)$club->address,
                'email' => (string)$club->email,
                'phone' => (string)$club->phone,
                'web' => (string)$club->web,
                'facebook' => (string)$club->facebook,
                'insta' => (string)$club->insta,
                'enabled' => (bool)$club->enabled,
                'visible' => (bool)$club->visible,
                'pos' => LocaleNumberParser::format($club->pos, decimals: 0),
                'user_count' => LocaleNumberParser::formatCount((int)$club->user_count, decimals: 0),
                'competition_count' => LocaleNumberParser::formatCount((int)($club->competition_count ?? 0), decimals: 0),
                'created' => $club->created ? LocaleDateParser::format($club->created, 'datetime_short') : '',
                'modified' => $club->modified ? LocaleDateParser::format($club->modified, 'datetime_short') : '',
                'can_delete' => $this->Clubs->canDelete($club),
            ],
        ], JSON_UNESCAPED_UNICODE));
    }

    /** @return list<string> */
    protected function formFields(): array
    {
        return [
            'country_id', 'city_id', 'name', 'short_name', 'email', 'address',
            'phone', 'web', 'facebook', 'insta', 'enabled', 'visible', 'pos',
        ];
    }

    protected function setFormVars(object $club): void
    {
        $scopeCountryId = (int)($club->get('country_id') ?? 0);
        if ($scopeCountryId < 1) {
            $scopeCountryId = AdminCountryScope::ownCountryId($this->request);
        }
        $citiesQuery = $this->fetchTable('Cities')->find()
            ->contain(['Countries'])
            ->orderBy(['Countries.name' => 'ASC', 'Cities.name' => 'ASC']);
        if (!AdminCountryScope::canChangeCountry($this->request) && $scopeCountryId > 0) {
            $citiesQuery->where(['Cities.country_id' => $scopeCountryId]);
        }
        $cityOptions = [];
        foreach ($citiesQuery->all() as $city) {
            $country = AdminCountry::label((int)$city->get('country_id'));
            $cityOptions[(int)$city->get('id')] = trim($country . ' — ' . (string)$city->get('name'));
        }
        $this->set('club', $club);
        $formCountryOptions = AdminCountryScope::canChangeCountry($this->request)
            ? AdminCountry::options()
            : (
                $scopeCountryId > 0
                    ? [$scopeCountryId => AdminCountry::label($scopeCountryId)]
                    : []
            );
        $this->set('countryOptions', $formCountryOptions);
        $this->set('cityOptions', $cityOptions);
        $this->set('canChangeCountry', AdminCountryScope::canChangeCountry($this->request));
    }
}
