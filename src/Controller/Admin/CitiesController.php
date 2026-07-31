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

        $cities = $this->paginate($this->Cities->find(), [
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
        $this->set(compact('cities'));
    }

    /**
     * @return \Cake\Http\Response|null|void
     */
    public function add()
    {
        $city = $this->Cities->newEmptyEntity();
        $city->pos = 1000;
        $city->visible = true;
        $city->sample_count = 0;

        if ($this->request->is('post')) {
            $city = $this->Cities->patchEntity($city, $this->request->getData());
            if ($city->sample_count === null) {
                $city->sample_count = 0;
            }
            if ($this->Cities->save($city)) {
                $this->Flash->success(__('The city has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The record could not be saved. Please try again.'));
        }

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
        $city = $this->Cities->get($id);

        if ($this->request->is(['patch', 'post', 'put'])) {
            $city = $this->Cities->patchEntity($city, $this->request->getData());
            if ($this->Cities->save($city)) {
                $this->Flash->success(__('The city has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The record could not be saved. Please try again.'));
        }

        $this->set(compact('city'));
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
        $city = $this->Cities->get($id, contain: ['Samples']);
        $this->set(compact('city'));
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
        $city = $this->Cities->get($id);

        if ($this->Cities->delete($city)) {
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
            $city = $this->Cities->get($id);
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
                    'id' => $city->id,
                    'name' => $city->name,
                    'pos' => \App\Utility\LocaleNumberParser::format($city->pos, decimals: 0),
                    'visible' => (bool)$city->visible,
                    'sample_count' => \App\Utility\LocaleNumberParser::format($city->sample_count, decimals: 0),
                    'created' => $city->created ? $city->created->format('Y.m.d. H:i') : '',
                    'modified' => $city->modified ? $city->modified->format('Y.m.d. H:i') : '',
                ],
            ], JSON_UNESCAPED_UNICODE));
    }
}
