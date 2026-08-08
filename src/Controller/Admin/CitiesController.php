<?php
declare(strict_types=1);

namespace App\Controller\Admin;

use App\Auth\CurrentUser;
use App\Utility\AdminCountry;
use Cake\Http\Response;

/**
 * Cities Controller — settlements reference data (`cities`).
 *
 * @property \App\Model\Table\CitiesTable $Cities
 * @property \App\Model\Table\CountiesTable $Counties
 */
class CitiesController extends AppController
{
    protected int $indexLimit = 100;

    protected int $indexMaxLimit = 1000;

    /**
     * Session key: Cities index country filter.
     */
    protected const CITIES_FILTER_COUNTRY_SESSION = 'Admin.citiesFilterCountryId';

    /**
     * @param \App\Model\Entity\City|null $city
     * @return void
     */
    protected function setAccessFlags(?\App\Model\Entity\City $city = null): void
    {
        $this->set('canAdd', true);
        $this->set('canEdit', true);
        $this->set('canDelete', $city !== null);
    }

    /**
     * Index country filter: query → session → logged-in user country → 0 (all).
     *
     * @return array{0: int, 1: string, 2: array<int, string>}
     */
    protected function resolveCountryFilter(): array
    {
        $session = $this->request->getSession();
        $userCountryId = CurrentUser::countryId($this->request);
        $query = $this->request->getQueryParams();

        if (array_key_exists('country_id', $query)) {
            $raw = $query['country_id'];
            if (is_array($raw)) {
                $raw = end($raw);
            }
            $filterCountryId = (int)$raw;
            if ($filterCountryId > 0 && !AdminCountry::isValidCountryId($filterCountryId)) {
                $filterCountryId = $userCountryId > 0 ? $userCountryId : 0;
            }
            $session->write(self::CITIES_FILTER_COUNTRY_SESSION, $filterCountryId);
        } else {
            $saved = $session->read(self::CITIES_FILTER_COUNTRY_SESSION);
            if ($saved !== null && is_numeric($saved)) {
                $filterCountryId = (int)$saved;
                if ($filterCountryId > 0 && !AdminCountry::isValidCountryId($filterCountryId)) {
                    $filterCountryId = $userCountryId > 0 ? $userCountryId : 0;
                }
            } else {
                $filterCountryId = $userCountryId > 0 ? $userCountryId : 0;
                $session->write(self::CITIES_FILTER_COUNTRY_SESSION, $filterCountryId);
            }
        }

        $filterCountryLabel = $filterCountryId > 0 ? AdminCountry::label($filterCountryId) : '';
        $countryOptions = [];
        if ($filterCountryId > 0) {
            $countryOptions[$filterCountryId] = $filterCountryLabel !== ''
                ? $filterCountryLabel
                : (string)$filterCountryId;
        }

        return [$filterCountryId, $filterCountryLabel, $countryOptions];
    }

    /**
     * @return \Cake\Http\Response|null|void
     */
    public function index()
    {
        $this->set('title', __('Cities'));
        $this->viewBuilder()->setVar('breadcrumb', __('Cities'));
        $this->setAccessFlags();

        $scoped = $this->beginAdminCountryScopedIndex($this->Cities);
        if ($scoped instanceof Response) {
            return $scoped;
        }
        $filterCountryId = $scoped['countryId'];

        $redirect = $this->applyIndexListState('Cities');
        if ($redirect !== null) {
            return $redirect;
        }

        $paginateOptions = $this->indexPaginateOptionsFor($this->Cities, [
            'sortableFields' => [
                'id',
                'name',
                'shortname',
                'zip',
                'country_id',
                'county_id',
                'Countries.name',
                'Counties.name',
            ],
            'order' => [
                'Cities.name' => 'ASC',
            ],
        ], [
            'Countries' => $this->Cities->Countries->getTarget(),
        ]);

        $query = $this->applyAdminCountryWhere(
            $this->Cities->find()->contain(['Countries', 'Counties']),
            $this->Cities,
            $filterCountryId
        );
        $query = $this->applyIndexSearch($query, $this->Cities);

        $redirect = $this->resolveIndexPageForLastVisited('Cities', $query, $paginateOptions);
        if ($redirect !== null) {
            return $redirect;
        }

        $cities = $this->paginate($query, $paginateOptions);
        $this->setLastVisitedForIndex('Cities');

        $flagIds = array_values(array_unique(array_filter([
            $filterCountryId,
            \App\Auth\CurrentUser::countryId($this->request),
        ])));

        $this->set(compact('cities'));
        $this->set('countryFlags', AdminCountry::iso2Map($flagIds));
    }

    /**
     * JSON Select2: countries (visible), UI-locale names + iso2 for flags.
     *
     * @return \Cake\Http\Response
     */
    public function countryOptions(): Response
    {
        $this->request->allowMethod(['get']);

        $preferredId = CurrentUser::countryId($this->request);
        $term = trim((string)$this->request->getQuery('q'));
        $page = max(1, (int)$this->request->getQuery('page'));
        $limit = 30;

        /** @var \App\Model\Table\CountriesTable $countries */
        $countries = $this->fetchTable('Countries');
        $query = $countries->find()
            ->select(['Countries.id', 'Countries.iso2', 'Countries.name', 'Countries.endonim_name'])
            ->where(['Countries.visible' => true]);

        if ($term !== '') {
            $like = '%' . str_replace(['%', '_'], ['\\%', '\\_'], $term) . '%';
            $query->where([
                'OR' => [
                    'Countries.name LIKE' => $like,
                    'Countries.endonim_name LIKE' => $like,
                    'Countries.iso2 LIKE' => $like,
                ],
            ]);
        }

        $rows = $query
            ->orderBy(['Countries.name' => 'ASC', 'Countries.id' => 'ASC'])
            ->limit(400)
            ->all();

        $preferred = null;
        $rest = [];
        foreach ($rows as $row) {
            $id = (int)$row->get('id');
            $item = [
                'id' => (string)$id,
                'text' => AdminCountry::label($id),
                'iso2' => strtolower(trim((string)$row->get('iso2'))),
            ];
            if ($preferredId > 0 && $id === $preferredId) {
                $preferred = $item;
            } else {
                $rest[] = $item;
            }
        }

        $all = $preferred !== null ? array_merge([$preferred], $rest) : $rest;
        // Prepend "All countries" only on first page without search.
        if ($page === 1 && $term === '') {
            array_unshift($all, [
                'id' => '0',
                'text' => (string)__('All countries'),
                'iso2' => '',
            ]);
        }

        $total = count($all);
        $slice = array_slice($all, ($page - 1) * $limit, $limit);

        return $this->response
            ->withType('application/json')
            ->withStringBody((string)json_encode([
                'results' => array_values($slice),
                'pagination' => ['more' => ($page * $limit) < $total],
            ], JSON_UNESCAPED_UNICODE));
    }

    /**
     * @return \Cake\Http\Response|null|void
     */
    public function add()
    {
        $city = $this->Cities->newEmptyEntity();
        $formCountryId = $this->resolveFormCountryId($city);

        if ($this->request->is('post')) {
            try {
                $city = $this->Cities->patchEntity($city, $this->request->getData(), [
                    'fields' => [
                        'country_id',
                        'county_id',
                        'shortname',
                        'name',
                        'zip',
                        'lat',
                        'lng',
                        'lat2',
                        'lng2',
                    ],
                ]);
                if ($this->Cities->save($city)) {
                    $this->rememberLastVisited('Cities', $city->id);
                    $this->Flash->success(__('The city has been saved.'));

                    return $this->redirectToIndexList('Cities');
                }
            } catch (\Throwable $e) {
                // Unexpected errors → user-facing flash
            }
            $this->Flash->error(__('The record could not be saved. Please try again.'));
            $formCountryId = (int)($city->country_id ?? $formCountryId);
        }

        $this->setFormOptions($formCountryId);
        $this->set(compact('city', 'formCountryId'));
        $this->setAccessFlags($city);
        $this->set('title', __('New city'));
        $this->viewBuilder()->setVar('breadcrumb', __('Cities'));
        $this->render('form');
    }

    /**
     * @param string|null $id
     * @return \Cake\Http\Response|null|void
     */
    public function edit(?string $id = null)
    {
        $city = $this->Cities->get($id);
        $this->rememberLastVisited('Cities', $city->id);
        $formCountryId = $this->resolveFormCountryId($city);

        if ($this->request->is(['patch', 'post', 'put'])) {
            try {
                $city = $this->Cities->patchEntity($city, $this->request->getData(), [
                    'fields' => [
                        'country_id',
                        'county_id',
                        'shortname',
                        'name',
                        'zip',
                        'lat',
                        'lng',
                        'lat2',
                        'lng2',
                    ],
                ]);
                if ($this->Cities->save($city)) {
                    $this->rememberLastVisited('Cities', $city->id);
                    $this->Flash->success(__('The city has been saved.'));

                    return $this->redirectToIndexList('Cities');
                }
            } catch (\Throwable $e) {
                // Unexpected errors → user-facing flash
            }
            $this->Flash->error(__('The record could not be saved. Please try again.'));
            $formCountryId = (int)($city->country_id ?? $formCountryId);
        }

        $this->setFormOptions($formCountryId);
        $this->set(compact('city', 'formCountryId'));
        $this->setAccessFlags($city);
        $this->set('title', __('Edit city'));
        $this->viewBuilder()->setVar('breadcrumb', __('Cities'));
        $this->render('form');
    }

    /**
     * @param string|null $id
     * @return \Cake\Http\Response|null|void
     */
    public function view(?string $id = null)
    {
        $city = $this->Cities->get($id, contain: [
            'Countries',
            'Counties',
            'Clubs' => function ($q) {
                return $q->orderBy(['Clubs.name' => 'ASC']);
            },
        ]);
        $this->rememberLastVisited('Cities', $city->id);
        $this->set(compact('city'));
        $this->setAccessFlags($city);
        $this->set('countryLabel', AdminCountry::label((int)$city->country_id));
        $this->set('title', __('City details'));
        $this->viewBuilder()->setVar('breadcrumb', __('Cities'));
    }

    /**
     * @param string|null $id
     * @return \Cake\Http\Response|null
     */
    public function delete(?string $id = null): ?Response
    {
        $this->request->allowMethod(['post', 'delete']);

        return $this->deleteEntityOrFail($this->Cities, $this->Cities->get($id));
    }

    /**
     * JSON: record details for index modal.
     *
     * @param string|null $id
     * @return \Cake\Http\Response
     */
    public function recordGet(?string $id = null): Response
    {
        $this->request->allowMethod(['get']);

        try {
            $city = $this->Cities->get($id, contain: ['Countries', 'Counties']);
        } catch (\Throwable $e) {
            return $this->response
                ->withStatus(404)
                ->withType('application/json')
                ->withStringBody(json_encode([
                    'success' => false,
                    'message' => __('Record not found.'),
                ], JSON_UNESCAPED_UNICODE));
        }

        $this->rememberLastVisited('Cities', $city->id);

        return $this->response
            ->withType('application/json')
            ->withStringBody(json_encode([
                'success' => true,
                'record' => [
                    'id' => $city->id,
                    'name' => $city->name,
                    'shortname' => $city->shortname,
                    'zip' => $city->zip ?? '',
                    'country' => AdminCountry::label((int)$city->country_id),
                    'county' => $city->county !== null ? (string)$city->county->name : '',
                    'lat' => $city->lat,
                    'lng' => $city->lng,
                    'lat2' => $city->lat2,
                    'lng2' => $city->lng2,
                    'can_delete' => true,
                ],
            ], JSON_UNESCAPED_UNICODE));
    }

    /**
     * @param \App\Model\Entity\City $city
     * @return int
     */
    protected function resolveFormCountryId(\App\Model\Entity\City $city): int
    {
        $fromQuery = (int)$this->request->getQuery('form_country_id');
        if ($fromQuery > 0) {
            return $fromQuery;
        }

        $countryId = (int)($city->country_id ?? 0);
        if ($countryId > 0) {
            return $countryId;
        }

        return AdminCountry::id($this->request);
    }

    /**
     * @param int $countryId
     * @return void
     */
    protected function setFormOptions(int $countryId): void
    {
        $countryOptions = AdminCountry::options();
        $countyOptions = [];
        if ($countryId > 0) {
            $countyOptions = $this->fetchTable('Counties')
                ->find('list', keyField: 'id', valueField: 'name')
                ->where(['Counties.country_id' => $countryId])
                ->orderBy(['Counties.name' => 'ASC'])
                ->toArray();
        }

        $this->set(compact('countryOptions', 'countyOptions'));
    }
}
