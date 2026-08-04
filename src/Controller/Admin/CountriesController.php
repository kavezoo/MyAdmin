<?php
declare(strict_types=1);

namespace App\Controller\Admin;

use App\Auth\CountryAccess;
use App\Utility\AdminCountry;
use Cake\Http\Exception\ForbiddenException;
use Cake\Http\Response;
use Cake\I18n\I18n;

/**
 * Countries Controller — reference data (`countries` + Continents + Translate).
 *
 * Access:
 * - superuser: add, delete, full edit
 * - admin: edit visible + pos only
 *
 * @property \App\Model\Table\CountriesTable $Countries
 */
class CountriesController extends AppController
{
    protected int $indexLimit = 100;

    protected int $indexMaxLimit = 1000;

    /**
     * Session key: Countries index „visible only” filter.
     */
    protected const COUNTRIES_VISIBLE_ONLY_SESSION_KEY = 'Admin.countriesVisibleOnly';

    /**
     * Apply UI locale to Countries + Continents Translate behaviors.
     *
     * @return void
     */
    protected function setTranslateLocales(): void
    {
        AdminCountry::applyTranslateLocale(I18n::getLocale());
    }

    /**
     * Countries index filter: only `visible=1` rows (default) or all.
     *
     * Query: `visible_only=1|0` → session; otherwise last session value; first visit → true.
     *
     * @return bool
     */
    protected function resolveCountriesVisibleOnly(): bool
    {
        $session = $this->request->getSession();
        $query = $this->request->getQueryParams();

        if (array_key_exists('visible_only', $query)) {
            $raw = $query['visible_only'];
            if (is_array($raw)) {
                $raw = end($raw);
            }
            $visibleOnly = in_array((string)$raw, ['1', 'true', 'on'], true);
            $session->write(self::COUNTRIES_VISIBLE_ONLY_SESSION_KEY, $visibleOnly);

            return $visibleOnly;
        }

        $saved = $session->read(self::COUNTRIES_VISIBLE_ONLY_SESSION_KEY);
        if ($saved === null) {
            return true;
        }

        return (bool)$saved;
    }

    /**
     * Breadcrumb flags for Countries CRUD.
     *
     * @param \App\Model\Entity\Country|null $country
     * @return void
     */
    protected function setCountryAccessFlags(?\App\Model\Entity\Country $country = null): void
    {
        $this->set('canAdd', CountryAccess::canAdd());
        $canDelete = CountryAccess::canDelete();
        if ($canDelete && $country !== null) {
            $canDelete = $this->Countries->canDelete($country);
        } elseif ($country === null) {
            $canDelete = false;
        }
        $this->set('canDelete', $canDelete);
        $this->set('canEditFully', CountryAccess::canEditFully());
    }

    /**
     * @return \Cake\Http\Response|null|void
     */
    public function index()
    {
        if (!CountryAccess::canEditMeta()) {
            throw new ForbiddenException(__('You are not allowed to access countries.'));
        }

        $this->set('title', __('Countries'));
        $this->viewBuilder()->setVar('breadcrumb', __('Countries'));
        $this->setCountryAccessFlags();

        $this->setTranslateLocales();

        // Read `visible_only` before list-state rewrite (that query key is not persisted in index URL).
        $countriesVisibleOnly = $this->resolveCountriesVisibleOnly();
        $this->set(compact('countriesVisibleOnly'));

        $redirect = $this->applyIndexListState('Countries');
        if ($redirect !== null) {
            return $redirect;
        }

        $paginateOptions = $this->indexPaginateOptionsFor($this->Countries, [
            'sortableFields' => [
                'id',
                'iso2',
                'name',
                'locale',
                'continent_id',
                'Continents.name',
                'pos',
                'visible',
                'user_count',
                'created',
                'modified',
            ],
            'order' => [
                'Continents.name' => 'ASC',
                'Countries.name' => 'ASC',
            ],
        ], [
            'Continents' => $this->Countries->Continents->getTarget(),
        ]);

        $query = $this->applyIndexSearch(
            $this->Countries->find()->contain(['Continents']),
            $this->Countries
        );
        if ($countriesVisibleOnly) {
            $query->where(['Countries.visible' => true]);
        }
        $redirect = $this->resolveIndexPageForLastVisited('Countries', $query, $paginateOptions);
        if ($redirect !== null) {
            return $redirect;
        }

        $countries = $this->paginate($query, $paginateOptions);
        $this->setLastVisitedForIndex('Countries');

        $deletableCountryIds = [];
        if (CountryAccess::canDelete()) {
            foreach ($countries as $country) {
                if ($this->Countries->canDelete($country)) {
                    $deletableCountryIds[(int)$country->id] = true;
                }
            }
        }

        $this->set(compact('countries', 'deletableCountryIds'));
        $this->set('canDeleteCountry', CountryAccess::canDelete());
    }

    /**
     * @return \Cake\Http\Response|null|void
     */
    public function add()
    {
        if (!CountryAccess::canAdd()) {
            throw new ForbiddenException(__('Only a superuser can add countries.'));
        }

        $this->setTranslateLocales();
        $country = $this->newEntityWithSchemaDefaults($this->Countries);
        if ($country->get('user_count') === null) {
            $country->set('user_count', 0);
        }

        if ($this->request->is('post')) {
            try {
                $data = $this->normalizeVisibleCountriesData($this->request->getData());
                $country = $this->Countries->patchEntity($country, $data, [
                    'fields' => ['iso2', 'name', 'locale', 'continent_id', 'visible', 'pos', 'user_count', 'visible_countries'],
                    'associated' => ['VisibleCountries'],
                    'accessibleFields' => [
                        'iso2' => true,
                        'name' => true,
                        'locale' => true,
                        'continent_id' => true,
                        'visible' => true,
                        'pos' => true,
                        'user_count' => true,
                        'visible_countries' => true,
                        'visible_countries._ids' => true,
                    ],
                ]);
                if ($country->get('user_count') === null) {
                    $country->set('user_count', 0);
                }
                if ($this->Countries->save($country)) {
                    $newId = (int)$country->id;
                    $this->Countries->ensureSelfVisibility($newId);
                    $this->rememberLastVisited('Countries', $country->id);
                    $this->Flash->success(__('The country has been saved.'));

                    return $this->redirectToIndexList('Countries');
                }
            } catch (\Throwable $e) {
                // Unexpected errors → user-facing flash
            }
            $this->Flash->error(__('The record could not be saved. Please try again.'));
        }

        $this->setFormOptions();
        $this->set(compact('country'));
        $this->setCountryAccessFlags($country);
        $this->set('title', __('New country'));
        $this->viewBuilder()->setVar('breadcrumb', __('Countries'));
        $this->render('form');
    }

    /**
     * @param string|null $id
     * @return \Cake\Http\Response|null|void
     */
    public function edit(?string $id = null)
    {
        if (!CountryAccess::canEditMeta()) {
            throw new ForbiddenException(__('You are not allowed to edit countries.'));
        }

        $this->setTranslateLocales();
        $country = $this->Countries->get($id, contain: ['Continents', 'VisibleCountries']);
        $this->rememberLastVisited('Countries', $country->id);

        if ($this->request->is(['patch', 'post', 'put'])) {
            try {
                $data = $this->request->getData();
                if (CountryAccess::canEditFully()) {
                    $data = $this->normalizeVisibleCountriesData($data, (int)$country->id);
                    $country = $this->Countries->patchEntity($country, $data, [
                        'fields' => ['iso2', 'name', 'locale', 'continent_id', 'visible', 'pos', 'visible_countries'],
                        'associated' => ['VisibleCountries'],
                        'accessibleFields' => [
                            'iso2' => true,
                            'name' => true,
                            'locale' => true,
                            'continent_id' => true,
                            'visible' => true,
                            'pos' => true,
                            'visible_countries' => true,
                            'visible_countries._ids' => true,
                        ],
                    ]);
                } else {
                    $country = $this->Countries->patchEntity($country, [
                        'visible' => $data['visible'] ?? $country->visible,
                        'pos' => $data['pos'] ?? $country->pos,
                    ], [
                        'fields' => ['visible', 'pos'],
                    ]);
                }
                if ($this->Countries->save($country)) {
                    if (CountryAccess::canEditFully()) {
                        $this->Countries->ensureSelfVisibility((int)$country->id);
                    }
                    $this->rememberLastVisited('Countries', $country->id);
                    $this->Flash->success(__('The country has been saved.'));

                    return $this->redirectToIndexList('Countries');
                }
            } catch (\Throwable $e) {
                // Unexpected errors → user-facing flash
            }
            $this->Flash->error(__('The record could not be saved. Please try again.'));
        }

        $this->setFormOptions();
        $this->set(compact('country'));
        $this->setCountryAccessFlags($country);
        $this->set('title', __('Edit country'));
        $this->viewBuilder()->setVar('breadcrumb', __('Countries'));
        $this->render('form');
    }

    /**
     * @param string|null $id
     * @return \Cake\Http\Response|null|void
     */
    public function view(?string $id = null)
    {
        if (!CountryAccess::canEditMeta()) {
            throw new ForbiddenException(__('You are not allowed to access countries.'));
        }

        $this->setTranslateLocales();
        $country = $this->Countries->get($id, contain: [
            'Continents',
            'Users' => function ($q) {
                return $q->orderBy(['Users.email' => 'ASC']);
            },
            'Setups' => function ($q) {
                return $q->orderBy(['Setups.pos' => 'ASC', 'Setups.name' => 'ASC']);
            },
        ]);
        $this->rememberLastVisited('Countries', $country->id);
        $this->set(compact('country'));
        $this->setCountryAccessFlags($country);
        $this->set('canDeleteSetup', \App\Auth\SetupAccess::canDelete());
        $this->set('title', __('Country details'));
        $this->viewBuilder()->setVar('breadcrumb', __('Countries'));
    }

    /**
     * @param string|null $id
     * @return \Cake\Http\Response|null
     */
    public function delete(?string $id = null): ?Response
    {
        $this->request->allowMethod(['post', 'delete']);
        if (!CountryAccess::canDelete()) {
            throw new ForbiddenException(__('Only a superuser can delete countries.'));
        }

        return $this->deleteEntityOrFail($this->Countries, $this->Countries->get($id));
    }

    /**
     * JSON: record details for index modal (no i18n EAV list).
     *
     * @param string|null $id
     * @return \Cake\Http\Response
     */
    public function recordGet(?string $id = null): Response
    {
        $this->request->allowMethod(['get']);
        if (!CountryAccess::canEditMeta()) {
            return $this->response
                ->withStatus(403)
                ->withType('application/json')
                ->withStringBody(json_encode([
                    'success' => false,
                    'message' => __('You are not allowed to access countries.'),
                ], JSON_UNESCAPED_UNICODE));
        }

        try {
            $this->setTranslateLocales();
            $country = $this->Countries->get($id, contain: [
                'Continents',
                'VisibleCountries' => $this->containRelatedForModal('VisibleCountries'),
            ]);
        } catch (\Throwable $e) {
            return $this->response
                ->withStatus(404)
                ->withType('application/json')
                ->withStringBody(json_encode([
                    'success' => false,
                    'message' => __('Record not found.'),
                ], JSON_UNESCAPED_UNICODE));
        }

        $this->rememberLastVisited('Countries', $country->id);

        $selfId = (int)$country->id;
        $extras = [];
        foreach ($country->visible_countries ?? [] as $partner) {
            if ((int)$partner->get('id') === $selfId) {
                continue;
            }
            $extras[] = $partner;
        }

        return $this->response
            ->withType('application/json')
            ->withStringBody(json_encode([
                'success' => true,
                'record' => [
                    'id' => $country->id,
                    'iso2' => $country->iso2,
                    'name' => $country->name,
                    'locale' => $country->locale,
                    'continent' => $country->continent->name ?? '',
                    'visible' => (bool)$country->visible,
                    'pos' => \App\Utility\LocaleNumberParser::format($country->pos, decimals: 0),
                    'user_count' => \App\Utility\LocaleNumberParser::formatCount($country->user_count, decimals: 0),
                    'additional_languages' => $this->relatedAdditionalLanguagesForModal($extras),
                    'created' => $country->created ? \App\Utility\LocaleDateParser::format($country->created, 'datetime_short') : '',
                    'modified' => $country->modified ? \App\Utility\LocaleDateParser::format($country->modified, 'datetime_short') : '',
                    'can_delete' => CountryAccess::canDelete() && $this->Countries->canDelete($country),
                ],
            ], JSON_UNESCAPED_UNICODE));
    }

    /**
     * Modal list for Additional languages (exclude self; name + ISO).
     *
     * @param iterable<\Cake\Datasource\EntityInterface> $partners
     * @return list<array{id: mixed, name: string}>
     */
    protected function relatedAdditionalLanguagesForModal(iterable $partners): array
    {
        $items = [];
        foreach ($partners as $partner) {
            $name = trim((string)$partner->get('name'));
            $iso = strtoupper(trim((string)$partner->get('iso2')));
            if ($name === '') {
                $name = $iso !== '' ? $iso : (string)$partner->get('id');
            } elseif ($iso !== '' && !str_contains($name, '(' . $iso . ')')) {
                $name .= ' (' . $iso . ')';
            }
            $items[] = [
                'id' => $partner->get('id'),
                'name' => $name,
            ];
        }
        usort($items, static function (array $a, array $b): int {
            return strcasecmp($a['name'], $b['name']);
        });

        return $items;
    }

    /**
     * @return void
     */
    protected function setFormOptions(): void
    {
        $continents = $this->Countries->Continents->find('list', keyField: 'id', valueField: 'name')
            ->orderBy(['Continents.name' => 'ASC'])
            ->toArray();

        // Extra languages only — own country is stored but not listed in the Select2.
        $visibleCountryOptions = \App\Utility\AdminCountry::masterVisibleOptions();
        $selfCountryId = 0;
        $idParam = $this->request->getParam('pass.0') ?? $this->request->getParam('id');
        if (is_numeric($idParam) && (int)$idParam > 0) {
            $selfCountryId = (int)$idParam;
        }
        if ($selfCountryId > 0) {
            unset($visibleCountryOptions[$selfCountryId]);
        }

        $this->set(compact('continents', 'visibleCountryOptions', 'selfCountryId'));
    }

    /**
     * Merge posted extra languages with mandatory self (own language always on tabs).
     *
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    protected function normalizeVisibleCountriesData(array $data, ?int $selfCountryId = null): array
    {
        $ids = $data['visible_countries']['_ids'] ?? [];
        if (!is_array($ids)) {
            $ids = [];
        }
        $ids = array_map('intval', $ids);
        if ($selfCountryId !== null && $selfCountryId > 0) {
            // Never allow removing self from the posted extras list.
            $ids = array_values(array_filter($ids, static fn(int $id): bool => $id !== $selfCountryId));
            $data['visible_countries']['_ids'] = $this->Countries->ensureSelfFirst($ids, $selfCountryId);
        } else {
            // Add: self is attached after save via ensureSelfVisibility.
            $data['visible_countries']['_ids'] = array_values(array_unique(array_filter(
                $ids,
                static fn(int $id): bool => $id > 0
            )));
        }

        return $data;
    }
}
