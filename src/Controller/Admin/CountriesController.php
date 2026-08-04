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
     * Apply UI locale to Countries + Continents Translate behaviors.
     *
     * @return void
     */
    protected function setTranslateLocales(): void
    {
        AdminCountry::applyTranslateLocale(I18n::getLocale());
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
        $redirect = $this->applyIndexListState('Countries');
        if ($redirect !== null) {
            return $redirect;
        }

        $paginateOptions = $this->indexPaginateOptions([
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
        ]);

        $query = $this->applyIndexSearch(
            $this->Countries->find()->contain(['Continents']),
            $this->Countries
        );
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
                $data = $this->request->getData();
                $country = $this->Countries->patchEntity($country, $data, [
                    'fields' => ['iso2', 'name', 'locale', 'continent_id', 'visible', 'pos', 'user_count'],
                    'accessibleFields' => [
                        'iso2' => true,
                        'name' => true,
                        'locale' => true,
                        'continent_id' => true,
                        'visible' => true,
                        'pos' => true,
                        'user_count' => true,
                    ],
                ]);
                if ($country->get('user_count') === null) {
                    $country->set('user_count', 0);
                }
                if ($this->Countries->save($country)) {
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
        $country = $this->Countries->get($id, contain: ['Continents']);
        $this->rememberLastVisited('Countries', $country->id);

        if ($this->request->is(['patch', 'post', 'put'])) {
            try {
                $data = $this->request->getData();
                if (CountryAccess::canEditFully()) {
                    $country = $this->Countries->patchEntity($country, $data, [
                        'fields' => ['iso2', 'name', 'locale', 'continent_id', 'visible', 'pos'],
                        'accessibleFields' => [
                            'iso2' => true,
                            'name' => true,
                            'locale' => true,
                            'continent_id' => true,
                            'visible' => true,
                            'pos' => true,
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
            $country = $this->Countries->get($id, contain: ['Continents']);
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
                    'created' => $country->created ? \App\Utility\LocaleDateParser::format($country->created, 'datetime_short') : '',
                    'modified' => $country->modified ? \App\Utility\LocaleDateParser::format($country->modified, 'datetime_short') : '',
                    'can_delete' => CountryAccess::canDelete() && $this->Countries->canDelete($country),
                ],
            ], JSON_UNESCAPED_UNICODE));
    }

    /**
     * @return void
     */
    protected function setFormOptions(): void
    {
        $continents = $this->Countries->Continents->find('list', keyField: 'id', valueField: 'name')
            ->orderBy(['Continents.name' => 'ASC'])
            ->toArray();

        $this->set(compact('continents'));
    }
}
