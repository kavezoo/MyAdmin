<?php
declare(strict_types=1);

namespace App\Controller\Admin;

use App\Utility\AdminCountry;
use App\Utility\LocaleDateParser;
use App\Utility\LocaleNumberParser;
use App\Utility\SetupValue;
use Cake\Http\Response;

/**
 * Setups Controller — typed application settings (per working country).
 *
 * @property \App\Model\Table\SetupsTable $Setups
 */
class SetupsController extends AppController
{
    protected int $indexLimit = 100;

    protected int $indexMaxLimit = 1000;

    /**
     * @return \Cake\Http\Response|null|void
     */
    public function index()
    {
        $this->set('title', __('Setups'));
        $this->viewBuilder()->setVar('breadcrumb', __('Setups'));

        $queryCountryId = $this->request->getQuery('country_id');
        if ($queryCountryId !== null && $queryCountryId !== '') {
            $newId = (int)$queryCountryId;
            $redirect = $this->redirect(['action' => 'index', '?' => []]);
            if ($redirect instanceof Response) {
                return AdminCountry::set($newId, $this->request, $redirect);
            }
        }

        $workingCountryId = AdminCountry::id($this->request);
        $countryOptions = AdminCountry::options();
        if ($workingCountryId < 1 || $countryOptions === []) {
            $this->Flash->error(__('No visible country is available. Add or enable a country first.'));
            $this->set([
                'setups' => [],
                'setupTypeOptions' => SetupValue::typeOptions(),
                'workingCountryId' => 0,
                'countryOptions' => [],
            ]);

            return;
        }

        $redirect = $this->applyIndexListState('Setups');
        if ($redirect !== null) {
            return $redirect;
        }

        $paginateOptions = $this->indexPaginateOptions([
            'sortableFields' => [
                'id',
                'name',
                'slug',
                'type',
                'pos',
                'visible',
                'created',
                'modified',
            ],
        ]);
        $query = $this->Setups->find()
            ->where(['Setups.country_id' => $workingCountryId]);
        $query = $this->applyIndexSearch($query, $this->Setups);
        $redirect = $this->resolveIndexPageForLastVisited('Setups', $query, $paginateOptions);
        if ($redirect !== null) {
            return $redirect;
        }
        $setups = $this->paginate($query, $paginateOptions);
        $this->setLastVisitedForIndex('Setups');
        $this->set(compact('setups', 'workingCountryId', 'countryOptions'));
        $this->set('setupTypeOptions', SetupValue::typeOptions());
    }

    /**
     * @return \Cake\Http\Response|null|void
     */
    public function add()
    {
        $setup = $this->newEntityWithSchemaDefaults($this->Setups);
        if ($setup->get('type') === null || $setup->get('type') === '') {
            $setup->set('type', SetupValue::TYPE_STRING);
        }
        $setup->set('country_id', AdminCountry::id($this->request));

        if ($this->request->is('post')) {
            try {
                $data = $this->request->getData();
                $data['country_id'] = AdminCountry::id($this->request);
                $setup = $this->Setups->patchEntity($setup, $data);
                if ($this->Setups->save($setup)) {
                    $this->rememberLastVisited('Setups', $setup->id);
                    $this->Flash->success(__('The setup has been saved.'));

                    return $this->redirectToIndexList('Setups');
                }
            } catch (\Throwable $e) {
                // Unexpected errors → user-facing flash
            }
            $this->Flash->error(__('The record could not be saved. Please try again.'));
        }

        $this->setFormOptions($setup);
        $this->set('title', __('New setup'));
        $this->viewBuilder()->setVar('breadcrumb', __('Setups'));
        $this->render('form');
    }

    /**
     * @param string|null $id
     * @return \Cake\Http\Response|null|void
     */
    public function edit(?string $id = null)
    {
        $setup = $this->Setups->get($id);
        $this->rememberLastVisited('Setups', $setup->id);

        if ($this->request->is(['patch', 'post', 'put'])) {
            try {
                $data = $this->request->getData();
                unset($data['country_id']);
                $setup = $this->Setups->patchEntity($setup, $data, [
                    'accessibleFields' => ['country_id' => false],
                ]);
                if ($this->Setups->save($setup)) {
                    $this->rememberLastVisited('Setups', $setup->id);
                    $this->Flash->success(__('The setup has been saved.'));

                    return $this->redirectToIndexList('Setups');
                }
            } catch (\Throwable $e) {
                // Unexpected errors → user-facing flash
            }
            $this->Flash->error(__('The record could not be saved. Please try again.'));
        }

        $this->setFormOptions($setup);
        $this->setCanDeleteFlag($this->Setups, $setup);
        $this->set('title', __('Edit setup'));
        $this->viewBuilder()->setVar('breadcrumb', __('Setups'));
        $this->render('form');
    }

    /**
     * @param string|null $id
     * @return \Cake\Http\Response|null|void
     */
    public function view(?string $id = null)
    {
        $setup = $this->Setups->get($id, contain: ['Countries']);
        $this->rememberLastVisited('Setups', $setup->id);
        $this->set(compact('setup'));
        $this->set('setupTypeOptions', SetupValue::typeOptions());
        $this->setCanDeleteFlag($this->Setups, $setup);
        $this->set('title', __('Setup details'));
        $this->viewBuilder()->setVar('breadcrumb', __('Setups'));
    }

    /**
     * @param string|null $id
     * @return \Cake\Http\Response|null
     */
    public function delete(?string $id = null): ?Response
    {
        $this->request->allowMethod(['post', 'delete']);

        return $this->deleteEntityOrFail($this->Setups, $this->Setups->get($id));
    }

    /**
     * @param string|null $id
     * @return \Cake\Http\Response
     */
    public function recordGet(?string $id = null): Response
    {
        $this->request->allowMethod(['get']);

        try {
            $setup = $this->Setups->get($id);
        } catch (\Throwable $e) {
            return $this->response
                ->withStatus(404)
                ->withType('application/json')
                ->withStringBody(json_encode([
                    'success' => false,
                    'message' => __('Record not found.'),
                ], JSON_UNESCAPED_UNICODE));
        }

        $this->rememberLastVisited('Setups', $setup->id);
        $typeLabels = SetupValue::typeOptions();
        $type = (string)$setup->type;

        return $this->response
            ->withType('application/json')
            ->withStringBody(json_encode([
                'success' => true,
                'record' => [
                    'id' => $setup->id,
                    'name' => $setup->name,
                    'slug' => $setup->slug,
                    'type' => $typeLabels[$type] ?? $type,
                    'value' => SetupValue::formatForDisplay($type, $setup->value),
                    'pos' => LocaleNumberParser::format($setup->pos, decimals: 0),
                    'visible' => (bool)$setup->visible,
                    'created' => $setup->created ? LocaleDateParser::format($setup->created, 'datetime_short') : '',
                    'modified' => $setup->modified ? LocaleDateParser::format($setup->modified, 'datetime_short') : '',
                    'can_delete' => true,
                ],
            ], JSON_UNESCAPED_UNICODE));
    }

    /**
     * @param \App\Model\Entity\Setup $setup
     * @return void
     */
    protected function setFormOptions($setup): void
    {
        $type = (string)($setup->type ?: SetupValue::TYPE_STRING);
        $this->set(compact('setup'));
        $this->set('setupTypeOptions', SetupValue::typeOptions());
        $this->set('setupValueForm', SetupValue::formatForForm($type, $setup->value !== null ? (string)$setup->value : null));
        $this->set('dateFormat', LocaleDateParser::jsConfig());
        $this->set('numberFormat', LocaleNumberParser::jsConfig());
        $this->set('workingCountryLabel', AdminCountry::options()[(int)$setup->country_id] ?? '');
    }
}
