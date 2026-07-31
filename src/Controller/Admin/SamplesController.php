<?php
declare(strict_types=1);

namespace App\Controller\Admin;

use Cake\Http\Response;

/**
 * Samples Controller
 *
 * @property \App\Model\Table\SamplesTable $Samples
 */
class SamplesController extends AppController
{
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
        $this->set('title', __('Samples'));
        $this->viewBuilder()->setVar('breadcrumb', __('Samples'));

        $query = $this->Samples->find()
            ->contain(['Parents', 'Cities']);

        $samples = $this->paginate($query, [
            'limit' => 10,
            'sortableFields' => [
                'id',
                'Parents.name',
                'name',
                'szam',
                'netto',
                'datum',
                'ido',
                'datumido',
                'logikai',
                'pos',
                'visible',
                'city_count',
                'created',
                'modified',
            ],
        ]);

        $this->set(compact('samples'));
    }

    /**
     * @return \Cake\Http\Response|null|void
     */
    public function add()
    {
        $sample = $this->Samples->newEmptyEntity();
        $sample->pos = 1000;
        $sample->visible = true;
        $sample->logikai = true;
        $sample->city_count = 0;

        if ($this->request->is('post')) {
            $sample = $this->Samples->patchEntity($sample, $this->request->getData(), [
                'associated' => ['Cities'],
            ]);
            $this->normalizeCounters($sample);

            if ($this->Samples->save($sample)) {
                $this->Flash->success(__('The sample has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The record could not be saved. Please try again.'));
        }

        $this->setFormOptions();
        $this->set(compact('sample'));
        $this->set('title', __('New sample'));
        $this->viewBuilder()->setVar('breadcrumb', __('Samples'));
        $this->render('form');
    }

    /**
     * @param string|null $id
     * @return \Cake\Http\Response|null|void
     */
    public function edit(?string $id = null)
    {
        $sample = $this->Samples->get($id, contain: ['Parents', 'Cities']);

        if ($this->request->is(['patch', 'post', 'put'])) {
            $sample = $this->Samples->patchEntity($sample, $this->request->getData(), [
                'associated' => ['Cities'],
            ]);
            $this->normalizeCounters($sample);

            if ($this->Samples->save($sample)) {
                $this->Flash->success(__('The sample has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The record could not be saved. Please try again.'));
        }

        $this->setFormOptions();
        $this->set(compact('sample'));
        $this->set('title', __('Edit sample'));
        $this->viewBuilder()->setVar('breadcrumb', __('Samples'));
        $this->render('form');
    }

    /**
     * @param string|null $id
     * @return \Cake\Http\Response|null|void
     */
    public function view(?string $id = null)
    {
        $sample = $this->Samples->get($id, contain: [
            'Parents',
            'Cities' => function ($q) {
                return $q->orderBy(['Cities.name' => 'ASC']);
            },
        ]);
        $this->set(compact('sample'));
        $this->set('title', __('Sample details'));
        $this->viewBuilder()->setVar('breadcrumb', __('Samples'));
    }

    /**
     * @param string|null $id
     * @return \Cake\Http\Response|null
     */
    public function delete(?string $id = null): ?Response
    {
        $this->request->allowMethod(['post', 'delete']);
        $sample = $this->Samples->get($id);

        if ($this->Samples->delete($sample)) {
            $this->Flash->success(__('The record has been deleted.'));
        } else {
            $this->Flash->error(__('The record could not be deleted. Please try again.'));
        }

        return $this->redirect(['action' => 'index']);
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
        $this->viewBuilder()->setClassName('Json');

        try {
            $sample = $this->Samples->get($id, contain: [
                'Parents',
                'Cities' => function ($q) {
                    return $q->orderBy(['Cities.name' => 'ASC']);
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

        $cities = [];
        foreach ($sample->cities ?? [] as $city) {
            $cities[] = $city->name;
        }

        $record = [
            'id' => $sample->id,
            'parent' => $sample->parent->name ?? '',
            'name' => $sample->name,
            'szam' => \App\Utility\LocaleNumberParser::format($sample->szam, decimals: 0),
            'netto' => \App\Utility\LocaleNumberParser::format($sample->netto, decimals: 2) . ' HUF',
            'datum' => $sample->datum ? $sample->datum->format('Y.m.d.') : '',
            'ido' => $sample->ido ? $sample->ido->format('H:i') : '',
            'datumido' => $sample->datumido ? $sample->datumido->format('Y.m.d. H:i') : '',
            'logikai' => (bool)$sample->logikai,
            'pos' => \App\Utility\LocaleNumberParser::format($sample->pos, decimals: 0),
            'visible' => (bool)$sample->visible,
            'city_count' => \App\Utility\LocaleNumberParser::format($sample->city_count, decimals: 0),
            'cities' => implode(', ', $cities),
            'created' => $sample->created ? $sample->created->format('Y.m.d. H:i') : '',
            'modified' => $sample->modified ? $sample->modified->format('Y.m.d. H:i') : '',
        ];

        return $this->response
            ->withType('application/json')
            ->withStringBody(json_encode([
                'success' => true,
                'record' => $record,
            ], JSON_UNESCAPED_UNICODE));
    }

    /**
     * JSON: parent (linked) record for index modal.
     *
     * @param string|null $id
     * @return \Cake\Http\Response
     */
    public function parentGet(?string $id = null): Response
    {
        $this->request->allowMethod(['get']);

        try {
            $parent = $this->Samples->Parents->get($id);
        } catch (\Throwable $e) {
            return $this->response
                ->withStatus(404)
                ->withType('application/json')
                ->withStringBody(json_encode([
                    'success' => false,
                    'message' => __('Record not found.'),
                ], JSON_UNESCAPED_UNICODE));
        }

        return $this->response
            ->withType('application/json')
            ->withStringBody(json_encode([
                'success' => true,
                'record' => [
                    'id' => $parent->id,
                    'name' => $parent->name,
                    'pos' => \App\Utility\LocaleNumberParser::format($parent->pos, decimals: 0),
                    'visible' => (bool)$parent->visible,
                    'sample_count' => \App\Utility\LocaleNumberParser::format($parent->sample_count, decimals: 0),
                    'created' => $parent->created ? $parent->created->format('Y.m.d. H:i') : '',
                    'modified' => $parent->modified ? $parent->modified->format('Y.m.d. H:i') : '',
                ],
            ], JSON_UNESCAPED_UNICODE));
    }

    /**
     * JSON: create Parent from Select2 „+” modal (single select).
     *
     * @return \Cake\Http\Response
     */
    public function select2Create(): Response
    {
        $this->request->allowMethod(['post']);

        return $this->select2CreateEntity(
            $this->fetchTable('Parents'),
            [
                'name' => trim((string)$this->request->getData('name')),
                'pos' => 1000,
                'visible' => true,
                'sample_count' => 0,
            ],
            'name'
        );
    }

    /**
     * JSON: create City from Select2 „+” modal (multiple select).
     * Accepts the same payload shape as select2Create; extra modal fields can be merged later.
     *
     * @return \Cake\Http\Response
     */
    public function select2CreateCity(): Response
    {
        $this->request->allowMethod(['post']);

        return $this->select2CreateEntity(
            $this->fetchTable('Cities'),
            [
                'name' => trim((string)$this->request->getData('name')),
                'pos' => 1000,
                'visible' => true,
                'sample_count' => 0,
            ],
            'name'
        );
    }

    /**
     * Shared Select2 inline-create response.
     *
     * @param \Cake\ORM\Table $table
     * @param array<string, mixed> $data
     * @param string $textField Field used as Select2 option label
     * @return \Cake\Http\Response
     */
    protected function select2CreateEntity(
        \Cake\ORM\Table $table,
        array $data,
        string $textField = 'name'
    ): Response {
        $text = trim((string)($data[$textField] ?? ''));
        if ($text === '') {
            return $this->response
                ->withStatus(400)
                ->withType('application/json')
                ->withStringBody(json_encode([
                    'success' => false,
                    'message' => __('Please enter a value.'),
                ], JSON_UNESCAPED_UNICODE));
        }

        $entity = $table->newEntity($data);

        if (!$table->save($entity)) {
            return $this->response
                ->withStatus(500)
                ->withType('application/json')
                ->withStringBody(json_encode([
                    'success' => false,
                    'message' => __('Failed to save the new value.'),
                ], JSON_UNESCAPED_UNICODE));
        }

        return $this->response
            ->withType('application/json')
            ->withStringBody(json_encode([
                'success' => true,
                'id' => $entity->get($table->getPrimaryKey()),
                'text' => (string)$entity->get($textField),
            ], JSON_UNESCAPED_UNICODE));
    }

    /**
     * @return void
     */
    protected function setFormOptions(): void
    {
        $parents = $this->Samples->Parents
            ->find('list', keyField: 'id', valueField: 'name')
            ->orderBy(['name' => 'ASC'])
            ->toArray();

        $cities = $this->Samples->Cities
            ->find('list', keyField: 'id', valueField: 'name')
            ->orderBy(['name' => 'ASC'])
            ->toArray();

        $this->set(compact('parents', 'cities'));
    }

    /**
     * @param \App\Model\Entity\Sample $sample
     * @return void
     */
    protected function normalizeCounters(\App\Model\Entity\Sample $sample): void
    {
        $cityIds = (array)$this->request->getData('cities._ids');
        $sample->city_count = count(array_filter($cityIds));
        if ($sample->pos === null || $sample->pos === '') {
            $sample->pos = 1000;
        }
        if ($sample->visible === null) {
            $sample->visible = false;
        }
        if ($sample->logikai === null) {
            $sample->logikai = false;
        }
    }
}
