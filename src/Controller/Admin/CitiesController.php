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
    protected int $indexLimit = 10;

    /** @var int Cap for `?limit=` (abuse / oversized requests) */
    protected int $indexMaxLimit = 100;

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

        $cities = $this->paginate($this->Cities->find(), $this->indexPaginateOptions([
            'sortableFields' => [
                'id',
                'name',
                'pos',
                'visible',
                'sample_count',
                'created',
                'modified',
            ],
        ]));
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
                $this->normalizeSampleCount($city);
                if ($this->Cities->save($city)) {
                    $this->rememberLastVisited('Cities', $city->id);
                    $this->Flash->success(__('The city has been saved.'));

                    return $this->redirect(['action' => 'index']);
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
                $this->normalizeSampleCount($city);
                if ($this->Cities->save($city)) {
                    $this->rememberLastVisited('Cities', $city->id);
                    $this->Flash->success(__('The city has been saved.'));

                    return $this->redirect(['action' => 'index']);
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
                'Samples' => function ($q) {
                    return $q->orderBy(['Samples.name' => 'ASC']);
                },
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

        $samples = [];
        foreach ($city->samples ?? [] as $sample) {
            $samples[] = [
                'id' => $sample->id,
                'name' => $sample->name,
            ];
        }

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
                    'samples' => $samples,
                    'created' => $city->created ? $city->created->format('Y.m.d. H:i') : '',
                    'modified' => $city->modified ? $city->modified->format('Y.m.d. H:i') : '',
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

    /**
     * sample_count from selected samples._ids.
     *
     * @param \App\Model\Entity\City $city
     * @return void
     */
    protected function normalizeSampleCount(\App\Model\Entity\City $city): void
    {
        $sampleIds = (array)$this->request->getData('samples._ids');
        $city->sample_count = count(array_filter($sampleIds));
    }
}
