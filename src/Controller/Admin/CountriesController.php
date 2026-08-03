<?php
declare(strict_types=1);

namespace App\Controller\Admin;

use Cake\Http\Response;
use Cake\I18n\I18n;

/**
 * Countries Controller — reference data (`countries` + Continents + Translate).
 *
 * Schema-driven fields: iso2, name, locale, continent_id, visible, pos, user_count.
 * Admin may only change `visible` and `pos`. No add/delete. No i18n list in modal.
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
        $locale = I18n::getLocale();
        $this->Countries->getBehavior('Translate')->setLocale($locale);
        $this->Countries->Continents->getBehavior('Translate')->setLocale($locale);
    }

    /**
     * @return \Cake\Http\Response|null|void
     */
    public function index()
    {
        $this->set('title', __('Countries'));
        $this->viewBuilder()->setVar('breadcrumb', __('Countries'));

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
        $this->set(compact('countries'));
    }

    /**
     * @param string|null $id
     * @return \Cake\Http\Response|null|void
     */
    public function edit(?string $id = null)
    {
        $this->setTranslateLocales();
        $country = $this->Countries->get($id, contain: ['Continents']);
        $this->rememberLastVisited('Countries', $country->id);

        if ($this->request->is(['patch', 'post', 'put'])) {
            try {
                // Seed fields (iso2, name, locale, continent_id) are not patchable
                $data = $this->request->getData();
                $country = $this->Countries->patchEntity($country, [
                    'visible' => $data['visible'] ?? $country->visible,
                    'pos' => $data['pos'] ?? $country->pos,
                ], [
                    'fields' => ['visible', 'pos'],
                ]);
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

        $this->set(compact('country'));
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
        $this->setTranslateLocales();
        $country = $this->Countries->get($id, contain: ['Continents']);
        $this->rememberLastVisited('Countries', $country->id);
        $this->set(compact('country'));
        $this->set('title', __('Country details'));
        $this->viewBuilder()->setVar('breadcrumb', __('Countries'));
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
                    'can_delete' => false,
                ],
            ], JSON_UNESCAPED_UNICODE));
    }
}
