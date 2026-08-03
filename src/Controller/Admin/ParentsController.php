<?php
declare(strict_types=1);

namespace App\Controller\Admin;

use Cake\Http\Response;

/**
 * Parents Controller
 *
 * @property \App\Model\Table\ParentsTable $Parents
 */
class ParentsController extends AppController
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
        $this->set('title', __('Parents'));
        $this->viewBuilder()->setVar('breadcrumb', __('Parents'));

        $redirect = $this->applyIndexListState('Parents');
        if ($redirect !== null) {
            return $redirect;
        }

        $paginateOptions = $this->indexPaginateOptions([
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
        $query = $this->applyIndexSearch($this->Parents->find(), $this->Parents);
        $redirect = $this->resolveIndexPageForLastVisited('Parents', $query, $paginateOptions);
        if ($redirect !== null) {
            return $redirect;
        }
        $parents = $this->paginate($query, $paginateOptions);
        $this->setLastVisitedForIndex('Parents');
        $this->set(compact('parents'));
    }

    /**
     * @return \Cake\Http\Response|null|void
     */
    public function add()
    {
        $parent = $this->newEntityWithSchemaDefaults($this->Parents);
        if ($parent->get('sample_count') === null) {
            $parent->set('sample_count', 0);
        }

        if ($this->request->is('post')) {
            try {
                $parent = $this->Parents->patchEntity($parent, $this->request->getData());
                if ($parent->sample_count === null) {
                    $parent->sample_count = 0;
                }
                if ($this->Parents->save($parent)) {
                    $this->rememberLastVisited('Parents', $parent->id);
                    $this->Flash->success(__('The parent has been saved.'));

                    return $this->redirectToIndexList('Parents');
                }
            } catch (\Throwable $e) {
                // Unexpected errors → user-facing flash, not raw PHP
            }
            $this->Flash->error(__('The record could not be saved. Please try again.'));
        }

        $this->set(compact('parent'));
        $this->set('title', __('New parent'));
        $this->viewBuilder()->setVar('breadcrumb', __('Parents'));
        $this->render('form');
    }

    /**
     * @param string|null $id
     * @return \Cake\Http\Response|null|void
     */
    public function edit(?string $id = null)
    {
        $parent = $this->Parents->get($id);
        $this->rememberLastVisited('Parents', $parent->id);

        if ($this->request->is(['patch', 'post', 'put'])) {
            try {
                $parent = $this->Parents->patchEntity($parent, $this->request->getData());
                if ($this->Parents->save($parent)) {
                    $this->rememberLastVisited('Parents', $parent->id);
                    $this->Flash->success(__('The parent has been saved.'));

                    return $this->redirectToIndexList('Parents');
                }
            } catch (\Throwable $e) {
                // Unexpected errors → user-facing flash, not raw PHP
            }
            $this->Flash->error(__('The record could not be saved. Please try again.'));
        }

        $this->set(compact('parent'));
        $this->setCanDeleteFlag($this->Parents, $parent);
        $this->set('title', __('Edit parent'));
        $this->viewBuilder()->setVar('breadcrumb', __('Parents'));
        $this->render('form');
    }

    /**
     * @param string|null $id
     * @return \Cake\Http\Response|null|void
     */
    public function view(?string $id = null)
    {
        $parent = $this->Parents->get($id, contain: [
            'Samples' => function ($q) {
                return $q->orderBy(['Samples.name' => 'ASC']);
            },
        ]);
        $this->rememberLastVisited('Parents', $parent->id);
        $this->set(compact('parent'));
        $this->setCanDeleteFlag($this->Parents, $parent);
        $this->set('title', __('Parent details'));
        $this->viewBuilder()->setVar('breadcrumb', __('Parents'));
    }

    /**
     * @param string|null $id
     * @return \Cake\Http\Response|null
     */
    public function delete(?string $id = null): ?Response
    {
        $this->request->allowMethod(['post', 'delete']);

        return $this->deleteEntityOrFail($this->Parents, $this->Parents->get($id));
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
            $parent = $this->Parents->get($id, contain: [
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
                    'samples' => $this->relatedNameLinksForModal($parent->samples ?? []),
                    'created' => $parent->created ? \App\Utility\LocaleDateParser::format($parent->created, 'datetime_short') : '',
                    'modified' => $parent->modified ? \App\Utility\LocaleDateParser::format($parent->modified, 'datetime_short') : '',
                    'can_delete' => $this->Parents->canDelete($parent),
                ],
            ], JSON_UNESCAPED_UNICODE));
    }
}
