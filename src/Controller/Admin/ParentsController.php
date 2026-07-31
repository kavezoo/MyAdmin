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

        $parents = $this->paginate($this->Parents->find(), [
            'limit' => 10,
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
        $this->set(compact('parents'));
    }

    /**
     * @return \Cake\Http\Response|null|void
     */
    public function add()
    {
        $parent = $this->Parents->newEmptyEntity();
        $parent->pos = 1000;
        $parent->visible = true;
        $parent->sample_count = 0;

        if ($this->request->is('post')) {
            $parent = $this->Parents->patchEntity($parent, $this->request->getData());
            if ($parent->sample_count === null) {
                $parent->sample_count = 0;
            }
            if ($this->Parents->save($parent)) {
                $this->Flash->success(__('The parent has been saved.'));

                return $this->redirect(['action' => 'index']);
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

        if ($this->request->is(['patch', 'post', 'put'])) {
            $parent = $this->Parents->patchEntity($parent, $this->request->getData());
            if ($this->Parents->save($parent)) {
                $this->Flash->success(__('The parent has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The record could not be saved. Please try again.'));
        }

        $this->set(compact('parent'));
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
        $parent = $this->Parents->get($id, contain: ['Samples']);
        $this->set(compact('parent'));
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
        $parent = $this->Parents->get($id);

        if ($this->Parents->delete($parent)) {
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

        try {
            $parent = $this->Parents->get($id);
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
}
