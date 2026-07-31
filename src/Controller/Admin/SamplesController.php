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
        $this->set('title', __('Samples'));
        $this->viewBuilder()->setVar('breadcrumb', __('Samples'));

        $query = $this->Samples->find()
            ->contain(['Parents', 'Cities']);

        $samples = $this->paginate($query, $this->indexPaginateOptions([
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
        ]));

        $this->setLastVisitedForIndex('Samples');
        $this->set(compact('samples'));
    }

    /**
     * @return \Cake\Http\Response|null|void
     */
    public function add()
    {
        $sample = $this->newEntityWithSchemaDefaults($this->Samples);
        if ($sample->get('city_count') === null) {
            $sample->set('city_count', 0);
        }

        if ($this->request->is('post')) {
            try {
                $sample = $this->Samples->patchEntity($sample, $this->request->getData(), [
                    'associated' => ['Cities'],
                ]);
                $this->normalizeCounters($sample);

                if ($this->Samples->save($sample)) {
                    $this->rememberLastVisited('Samples', $sample->id);
                    $this->Flash->success(__('The sample has been saved.'));

                    return $this->redirect(['action' => 'index']);
                }
            } catch (\Throwable $e) {
                // Unexpected errors → user-facing flash, not raw PHP
            }
            $this->Flash->error(__('The record could not be saved. Please try again.'));
        }

        $this->setFormOptions($sample);
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
        $this->rememberLastVisited('Samples', $sample->id);

        if ($this->request->is(['patch', 'post', 'put'])) {
            try {
                $sample = $this->Samples->patchEntity($sample, $this->request->getData(), [
                    'associated' => ['Cities'],
                ]);
                $this->normalizeCounters($sample);

                if ($this->Samples->save($sample)) {
                    $this->rememberLastVisited('Samples', $sample->id);
                    $this->Flash->success(__('The sample has been saved.'));

                    return $this->redirect(['action' => 'index']);
                }
            } catch (\Throwable $e) {
                // Unexpected errors → user-facing flash, not raw PHP
            }
            $this->Flash->error(__('The record could not be saved. Please try again.'));
        }

        $this->setFormOptions($sample);
        $this->set(compact('sample'));
        $this->setCanDeleteFlag($this->Samples, $sample);
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
        $this->rememberLastVisited('Samples', $sample->id);
        $this->set(compact('sample'));
        $this->setCanDeleteFlag($this->Samples, $sample);
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

        return $this->deleteEntityOrFail($this->Samples, $this->Samples->get($id));
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

        $this->rememberLastVisited('Samples', $sample->id);

        $cities = [];
        foreach ($sample->cities ?? [] as $city) {
            $cities[] = [
                'id' => $city->id,
                'name' => $city->name,
            ];
        }

        $record = [
            'id' => $sample->id,
            'parent' => $sample->parent->name ?? '',
            'name' => $sample->name,
            'szam' => \App\Utility\LocaleNumberParser::format($sample->szam, decimals: 0),
            'netto' => \App\Utility\LocaleNumberParser::format($sample->netto, decimals: 2) . ' ' . \App\Utility\LocaleNumberParser::currencySymbol(),
            'datum' => $sample->datum ? $sample->datum->format('Y.m.d.') : '',
            'ido' => $sample->ido ? $sample->ido->format('H:i') : '',
            'datumido' => $sample->datumido ? $sample->datumido->format('Y.m.d. H:i') : '',
            'logikai' => (bool)$sample->logikai,
            'pos' => \App\Utility\LocaleNumberParser::format($sample->pos, decimals: 0),
            'visible' => (bool)$sample->visible,
            'city_count' => \App\Utility\LocaleNumberParser::formatCount($sample->city_count, decimals: 0),
            'cities' => $cities,
            'created' => $sample->created ? $sample->created->format('Y.m.d. H:i') : '',
            'modified' => $sample->modified ? $sample->modified->format('Y.m.d. H:i') : '',
            'can_delete' => $this->Samples->canDelete($sample),
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

        $this->rememberLastVisited('Parents', $parent->id);

        return $this->response
            ->withType('application/json')
            ->withStringBody(json_encode([
                'success' => true,
                'record' => [
                    'id' => $parent->id,
                    'name' => $parent->name,
                    'pos' => \App\Utility\LocaleNumberParser::format($parent->pos, decimals: 0),
                    'visible' => (bool)$parent->visible,
                    'sample_count' => \App\Utility\LocaleNumberParser::formatCount($parent->sample_count, decimals: 0),
                    'created' => $parent->created ? $parent->created->format('Y.m.d. H:i') : '',
                    'modified' => $parent->modified ? $parent->modified->format('Y.m.d. H:i') : '',
                    'can_delete' => $this->fetchTable('Parents')->canDelete($parent),
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
                // visible / pos → DB DEFAULT; sample_count: NOT NULL, no DEFAULT
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
            return $this->jsonSelect2Error(__('Please enter a value.'), 400);
        }

        try {
            $entity = $table->newEntity($data);

            if (!$table->save($entity)) {
                $message = $this->firstEntityError($entity)
                    ?? __('The record could not be saved. Please try again.');

                return $this->jsonSelect2Error($message, 422);
            }
        } catch (\Throwable $e) {
            return $this->jsonSelect2Error(
                __('The record could not be saved. Please try again.'),
                500
            );
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
     * @param string $message
     * @param int $status
     * @return \Cake\Http\Response
     */
    protected function jsonSelect2Error(string $message, int $status = 400): Response
    {
        return $this->response
            ->withStatus($status)
            ->withType('application/json')
            ->withStringBody(json_encode([
                'success' => false,
                'message' => $message,
            ], JSON_UNESCAPED_UNICODE));
    }

    /**
     * First validation / rule error message from an entity (human-readable).
     *
     * @param \Cake\Datasource\EntityInterface $entity
     * @return string|null
     */
    protected function firstEntityError(\Cake\Datasource\EntityInterface $entity): ?string
    {
        foreach ($entity->getErrors() as $fieldErrors) {
            if (!is_array($fieldErrors)) {
                continue;
            }
            foreach ($fieldErrors as $message) {
                if (is_string($message) && $message !== '') {
                    return $message;
                }
            }
        }

        return null;
    }

    /**
     * Select lists for Sample form (Parent + Cities).
     *
     * Parents: only visible; order pos ASC, then name ASC.
     * On edit, the currently assigned parent stays in the list even if invisible.
     *
     * @param \App\Model\Entity\Sample|null $sample
     * @return void
     */
    protected function setFormOptions(?\App\Model\Entity\Sample $sample = null): void
    {
        $parentConditions = ['Parents.visible' => true];
        if ($sample !== null && $sample->parent_id) {
            $parentConditions = [
                'OR' => [
                    ['Parents.visible' => true],
                    ['Parents.id' => $sample->parent_id],
                ],
            ];
        }

        $parents = $this->Samples->Parents
            ->find('list', keyField: 'id', valueField: 'name')
            ->where($parentConditions)
            ->orderBy(['Parents.pos' => 'ASC', 'Parents.name' => 'ASC'])
            ->toArray();

        $cities = $this->Samples->Cities
            ->find('list', keyField: 'id', valueField: 'name')
            ->orderBy(['name' => 'ASC'])
            ->toArray();

        $this->set(compact('parents', 'cities'));
    }

    /**
     * city_count from selected cities. visible / logikai / pos: DB DEFAULT — do not invent PHP values.
     *
     * @param \App\Model\Entity\Sample $sample
     * @return void
     */
    protected function normalizeCounters(\App\Model\Entity\Sample $sample): void
    {
        $cityIds = (array)$this->request->getData('cities._ids');
        $sample->city_count = count(array_filter($cityIds));
    }
}
