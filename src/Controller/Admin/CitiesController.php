<?php
declare(strict_types=1);

namespace App\Controller\Admin;

use Cake\Http\Response;

/**
 * Cities Controller
 *
 * @property \App\Model\Table\CitiesTable $Cities
 */
class CitiesController extends AppController
{
    /** @var int Index rows per page */
    protected int $indexLimit = 100;

    /** @var int Cap for `?limit=` (abuse / oversized requests) */
    protected int $indexMaxLimit = 1000;

    /**
     * @return void
     */
    public function initialize(): void
    {
        parent::initialize();
    }

    /**
     * @return \Cake\Http\Response|null|void
     */
    public function index()
    {
        $this->set('title', __('Cities'));
        $this->viewBuilder()->setVar('breadcrumb', __('Cities'));

        $redirect = $this->applyIndexListState('Cities');
        if ($redirect !== null) {
            return $redirect;
        }

        $paginateOptions = $this->indexPaginateOptionsFor($this->Cities, [
            'sortableFields' => [
                'id',
                'name',
                'pos',
                'visible',
                'sample_count',
                'created',
                'modified',
            ],
        ]);
        $query = $this->applyIndexSearch($this->Cities->find(), $this->Cities);
        $redirect = $this->resolveIndexPageForLastVisited('Cities', $query, $paginateOptions);
        if ($redirect !== null) {
            return $redirect;
        }
        $cities = $this->paginate($query, $paginateOptions);
        $this->setLastVisitedForIndex('Cities');
        $this->set(compact('cities'));
    }

    /**
     * @return \Cake\Http\Response|null|void
     */
    public function add()
    {
        $city = $this->newEntityWithSchemaDefaults($this->Cities);
        // sample_count: NOT NULL, no DB DEFAULT — must set until schema has DEFAULT 0
        if ($city->get('sample_count') === null) {
            $city->set('sample_count', 0);
        }

        if ($this->request->is('post')) {
            try {
                $data = $this->request->getData();
                if (!isset($data['samples']['_ids'])) {
                    $data['samples']['_ids'] = [];
                }
                $city = $this->Cities->patchEntity($city, $data, [
                    'associated' => ['Samples'],
                ]);
                if ($this->Cities->save($city)) {
                    $this->rememberLastVisited('Cities', $city->id);
                    $this->Flash->success(__('The city has been saved.'));

                    return $this->redirectToIndexList('Cities');
                }
            } catch (\Throwable $e) {
                // Unexpected errors (e.g. type errors) → user-facing flash, not raw PHP
            }
            $this->Flash->error(__('The record could not be saved. Please try again.'));
        }

        $this->setFormOptions();
        $this->set(compact('city'));
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
        $city = $this->Cities->get($id, contain: ['Samples']);
        $this->rememberLastVisited('Cities', $city->id);

        if ($this->request->is(['patch', 'post', 'put'])) {
            try {
                $data = $this->request->getData();
                if (!isset($data['samples']['_ids'])) {
                    $data['samples']['_ids'] = [];
                }
                $city = $this->Cities->patchEntity($city, $data, [
                    'associated' => ['Samples'],
                ]);
                if ($this->Cities->save($city)) {
                    $this->rememberLastVisited('Cities', $city->id);
                    $this->Flash->success(__('The city has been saved.'));

                    return $this->redirectToIndexList('Cities');
                }
            } catch (\Throwable $e) {
                // Unexpected errors → user-facing flash, not raw PHP
            }
            $this->Flash->error(__('The record could not be saved. Please try again.'));
        }

        $this->setFormOptions();
        $this->set(compact('city'));
        $this->setCanDeleteFlag($this->Cities, $city);
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
            'Samples' => function ($q) {
                return $q->orderBy(['Samples.name' => 'ASC']);
            },
        ]);
        $this->rememberLastVisited('Cities', $city->id);
        $this->set(compact('city'));
        $this->setCanDeleteFlag($this->Cities, $city);
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
            $city = $this->Cities->get($id, contain: [
                'Samples' => $this->containRelatedForModal('Samples'),
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

        $this->rememberLastVisited('Cities', $city->id);

        return $this->response
            ->withType('application/json')
            ->withStringBody(json_encode([
                'success' => true,
                'record' => [
                    'id' => $city->id,
                    'name' => $city->name,
                    'pos' => \App\Utility\LocaleNumberParser::format($city->pos, decimals: 0),
                    'visible' => (bool)$city->visible,
                    'sample_count' => \App\Utility\LocaleNumberParser::formatCount($city->sample_count, decimals: 0),
                    'samples' => $this->relatedNameLinksForModal($city->samples ?? []),
                    'created' => $city->created ? \App\Utility\LocaleDateParser::format($city->created, 'datetime_short') : '',
                    'modified' => $city->modified ? \App\Utility\LocaleDateParser::format($city->modified, 'datetime_short') : '',
                    'can_delete' => $this->Cities->canDelete($city),
                ],
            ], JSON_UNESCAPED_UNICODE));
    }

    /**
     * Sample list for City form (HABTM Select2 multiple).
     *
     * @return void
     */
    protected function setFormOptions(): void
    {
        $samples = $this->Cities->Samples
            ->find('list', keyField: 'id', valueField: 'name')
            ->orderBy(['Samples.name' => 'ASC'])
            ->toArray();

        $this->set(compact('samples'));
    }
}
