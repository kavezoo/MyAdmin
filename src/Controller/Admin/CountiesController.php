<?php
declare(strict_types=1);

namespace App\Controller\Admin;

use App\Auth\CurrentUser;
use App\Utility\AdminCountry;
use Cake\Http\Response;

/**
 * Counties Controller — regions reference data (`counties`).
 *
 * @property \App\Model\Table\CountiesTable $Counties
 */
class CountiesController extends AppController
{
    protected int $indexLimit = 100;

    protected int $indexMaxLimit = 1000;

    /**
     * Session key: Counties index country filter.
     */
    protected const COUNTIES_FILTER_COUNTRY_SESSION = 'Admin.countiesFilterCountryId';

    /**
     * @param \App\Model\Entity\County|null $county
     * @return void
     */
    protected function setAccessFlags(?\App\Model\Entity\County $county = null): void
    {
        $this->set('canAdd', true);
        $this->set('canEdit', true);
        $canDelete = true;
        if ($canDelete && $county !== null) {
            $canDelete = $this->Counties->canDelete($county);
        } elseif ($county === null) {
            $canDelete = false;
        }
        $this->set('canDelete', $canDelete);
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
            $session->write(self::COUNTIES_FILTER_COUNTRY_SESSION, $filterCountryId);
        } else {
            $saved = $session->read(self::COUNTIES_FILTER_COUNTRY_SESSION);
            if ($saved !== null && is_numeric($saved)) {
                $filterCountryId = (int)$saved;
                if ($filterCountryId > 0 && !AdminCountry::isValidCountryId($filterCountryId)) {
                    $filterCountryId = $userCountryId > 0 ? $userCountryId : 0;
                }
            } else {
                $filterCountryId = $userCountryId > 0 ? $userCountryId : 0;
                $session->write(self::COUNTIES_FILTER_COUNTRY_SESSION, $filterCountryId);
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
        $this->set('title', __('Counties'));
        $this->viewBuilder()->setVar('breadcrumb', __('Counties'));
        $this->setAccessFlags();

        $scoped = $this->beginAdminCountryScopedIndex($this->Counties);
        if ($scoped instanceof Response) {
            return $scoped;
        }
        $filterCountryId = $scoped['countryId'];

        $redirect = $this->applyIndexListState('Counties');
        if ($redirect !== null) {
            return $redirect;
        }

        $paginateOptions = $this->indexPaginateOptionsFor($this->Counties, [
            'sortableFields' => [
                'id',
                'name',
                'shortname',
                'capitalcity',
                'region',
                'country_id',
                'Countries.name',
                'pos',
                'visible',
                'created',
                'modified',
            ],
            'order' => [
                'Counties.name' => 'ASC',
            ],
        ], [
            'Countries' => $this->Counties->Countries->getTarget(),
        ]);

        $query = $this->applyAdminCountryWhere(
            $this->Counties->find()->contain(['Countries']),
            $this->Counties,
            $filterCountryId
        );
        $query = $this->applyIndexSearch($query, $this->Counties);

        $redirect = $this->resolveIndexPageForLastVisited('Counties', $query, $paginateOptions);
        if ($redirect !== null) {
            return $redirect;
        }

        $counties = $this->paginate($query, $paginateOptions);
        $this->setLastVisitedForIndex('Counties');

        $deletableCountyIds = [];
        foreach ($counties as $county) {
            if ($this->Counties->canDelete($county)) {
                $deletableCountyIds[(int)$county->id] = true;
            }
        }

        $flagIds = array_values(array_unique(array_filter([
            $filterCountryId,
            CurrentUser::countryId($this->request),
        ])));

        $this->set(compact('counties', 'deletableCountyIds'));
        $this->set('countryFlags', AdminCountry::iso2Map($flagIds));
        $this->set('canDeleteCounty', true);
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
        $county = $this->newEntityWithSchemaDefaults($this->Counties);

        if ($this->request->is('post')) {
            try {
                $county = $this->Counties->patchEntity($county, $this->request->getData(), [
                    'fields' => [
                        'country_id',
                        'name',
                        'shortname',
                        'capitalcity',
                        'region',
                        'visible',
                        'pos',
                    ],
                ]);
                if ($this->Counties->save($county)) {
                    $this->rememberLastVisited('Counties', $county->id);
                    $this->Flash->success(__('The county has been saved.'));

                    return $this->redirectToIndexList('Counties');
                }
            } catch (\Throwable $e) {
                // Unexpected errors → user-facing flash
            }
            $this->Flash->error(__('The record could not be saved. Please try again.'));
        }

        $this->setFormOptions($county);
        $this->set(compact('county'));
        $this->setAccessFlags($county);
        $this->set('title', __('New county'));
        $this->viewBuilder()->setVar('breadcrumb', __('Counties'));
        $this->render('form');
    }

    /**
     * @param string|null $id
     * @return \Cake\Http\Response|null|void
     */
    public function edit(?string $id = null)
    {
        $county = $this->Counties->get($id);
        $this->rememberLastVisited('Counties', $county->id);

        if ($this->request->is(['patch', 'post', 'put'])) {
            try {
                $county = $this->Counties->patchEntity($county, $this->request->getData(), [
                    'fields' => [
                        'country_id',
                        'name',
                        'shortname',
                        'capitalcity',
                        'region',
                        'visible',
                        'pos',
                    ],
                ]);
                if ($this->Counties->save($county)) {
                    $this->rememberLastVisited('Counties', $county->id);
                    $this->Flash->success(__('The county has been saved.'));

                    return $this->redirectToIndexList('Counties');
                }
            } catch (\Throwable $e) {
                // Unexpected errors → user-facing flash
            }
            $this->Flash->error(__('The record could not be saved. Please try again.'));
        }

        $this->setFormOptions($county);
        $this->set(compact('county'));
        $this->setAccessFlags($county);
        $this->set('title', __('Edit county'));
        $this->viewBuilder()->setVar('breadcrumb', __('Counties'));
        $this->render('form');
    }

    /**
     * @param string|null $id
     * @return \Cake\Http\Response|null|void
     */
    public function view(?string $id = null)
    {
        $county = $this->Counties->get($id, contain: [
            'Countries',
            'Cities' => function ($q) {
                return $q->orderBy(['Cities.name' => 'ASC']);
            },
        ]);
        $this->rememberLastVisited('Counties', $county->id);
        $this->set(compact('county'));
        $this->setAccessFlags($county);
        $this->set('countryLabel', AdminCountry::label((int)$county->country_id));
        $this->set('title', __('County details'));
        $this->viewBuilder()->setVar('breadcrumb', __('Counties'));
    }

    /**
     * @param string|null $id
     * @return \Cake\Http\Response|null
     */
    public function delete(?string $id = null): ?Response
    {
        $this->request->allowMethod(['post', 'delete']);

        return $this->deleteEntityOrFail($this->Counties, $this->Counties->get($id));
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
            $county = $this->Counties->get($id, contain: ['Countries']);
        } catch (\Throwable $e) {
            return $this->response
                ->withStatus(404)
                ->withType('application/json')
                ->withStringBody(json_encode([
                    'success' => false,
                    'message' => __('Record not found.'),
                ], JSON_UNESCAPED_UNICODE));
        }

        $this->rememberLastVisited('Counties', $county->id);

        return $this->response
            ->withType('application/json')
            ->withStringBody(json_encode([
                'success' => true,
                'record' => [
                    'id' => $county->id,
                    'name' => $county->name,
                    'shortname' => $county->shortname,
                    'capitalcity' => $county->capitalcity,
                    'region' => $county->region,
                    'country' => AdminCountry::label((int)$county->country_id),
                    'visible' => (bool)$county->visible,
                    'pos' => \App\Utility\LocaleNumberParser::format($county->pos, decimals: 0),
                    'created' => $county->created ? \App\Utility\LocaleDateParser::format($county->created, 'datetime_short') : '',
                    'modified' => $county->modified ? \App\Utility\LocaleDateParser::format($county->modified, 'datetime_short') : '',
                    'can_delete' => $this->Counties->canDelete($county),
                ],
            ], JSON_UNESCAPED_UNICODE));
    }

    /**
     * @param \App\Model\Entity\County $county
     * @return void
     */
    protected function setFormOptions(\App\Model\Entity\County $county): void
    {
        $countryOptions = AdminCountry::options();
        if ($county->isNew() && empty($county->country_id)) {
            $defaultCountryId = AdminCountry::id($this->request);
            if ($defaultCountryId > 0) {
                $county->country_id = $defaultCountryId;
            }
        }

        $this->set(compact('countryOptions'));
    }
}
