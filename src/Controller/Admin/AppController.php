<?php
declare(strict_types=1);

namespace App\Controller\Admin;

use App\Controller\AppController as BaseController;
use Cake\Datasource\EntityInterface;
use Cake\Http\Response;
use Cake\I18n\I18n;
use Cake\ORM\Table;

/**
 * Admin Application Controller
 *
 * Shared base for controllers under the Admin prefix.
 * Az admin felület nyelve mindig magyar; nincs nyelvválasztó.
 */
class AppController extends BaseController
{
    /**
     * Index lista: alapértelmezett sor / oldal (`?limit=` nélkül).
     * Child controller felülírhatja az osztály tetején.
     */
    protected int $indexLimit = 10;

    /**
     * Index lista: maximális sor / oldal.
     * Ha valaki a URL-ben nagyobb `?limit=`-et próbál (hack), ennél több soha nem jelenik meg.
     */
    protected int $indexMaxLimit = 100;

    /**
     * @return void
     */
    public function initialize(): void
    {
        parent::initialize();

        I18n::setLocale('hu_HU');
        $this->viewBuilder()->setLayout('admin');
    }

    /**
     * Paginator options for Admin index lists (limit + anti-abuse maxLimit).
     *
     * @param array<string, mixed> $extra e.g. sortableFields
     * @return array<string, mixed>
     */
    protected function indexPaginateOptions(array $extra = []): array
    {
        return array_merge([
            'limit' => $this->indexLimit,
            'maxLimit' => $this->indexMaxLimit,
        ], $extra);
    }

    /**
     * Session key: last worked-on Admin records (per Table alias + global `_last`).
     * Structure: ['Samples' => 12, 'Cities' => 3, '_last' => ['model' => 'Samples', 'id' => 12]]
     * Will be extended later (scroll-to-row, multi-step, …).
     */
    protected const LAST_VISITED_SESSION_KEY = 'Admin.lastVisited';

    /**
     * Remember the record the user last viewed / opened for edit / created.
     *
     * @param string $model Table alias (Samples, Parents, Cities, …)
     * @param int|string|null $id
     * @return void
     */
    protected function rememberLastVisited(string $model, int|string|null $id): void
    {
        if ($model === '' || $id === null || $id === '') {
            return;
        }
        $id = (int)$id;
        if ($id < 1) {
            return;
        }

        $session = $this->request->getSession();
        $all = $session->read(self::LAST_VISITED_SESSION_KEY);
        if (!is_array($all)) {
            $all = [];
        }

        $all[$model] = $id;
        $all['_last'] = [
            'model' => $model,
            'id' => $id,
        ];
        $session->write(self::LAST_VISITED_SESSION_KEY, $all);
    }

    /**
     * Last visited id for this model (index highlight), or null.
     *
     * @param string $model
     * @return int|null
     */
    protected function getLastVisitedId(string $model): ?int
    {
        $all = $this->request->getSession()->read(self::LAST_VISITED_SESSION_KEY);
        if (!is_array($all) || !isset($all[$model])) {
            return null;
        }
        $id = (int)$all[$model];

        return $id > 0 ? $id : null;
    }

    /**
     * Set `$lastVisitedId` for index templates (`last-visited` CSS class).
     *
     * @param string $model
     * @return void
     */
    protected function setLastVisitedForIndex(string $model): void
    {
        $this->set('lastVisitedId', $this->getLastVisitedId($model));
    }

    /**
     * Delete entity and set Flash from model `_delete` error when blocked by children.
     *
     * @param \Cake\ORM\Table $table
     * @param \Cake\Datasource\EntityInterface $entity
     * @return \Cake\Http\Response|null
     */
    protected function deleteEntityOrFail(Table $table, EntityInterface $entity): ?Response
    {
        if ($table->delete($entity)) {
            $this->Flash->success(__('The record has been deleted.'));
        } else {
            $errors = $entity->getError('_delete');
            $message = (is_array($errors) && $errors !== [])
                ? (string)reset($errors)
                : __('The record could not be deleted. Please try again.');
            $this->Flash->error($message);
        }

        return $this->redirect($this->referer(['action' => 'index'], true));
    }

    /**
     * New empty entity with DB column defaults applied (add form display).
     * Does not invent PHP fallbacks — values come from the Table schema.
     * Required NOT NULL columns without a DEFAULT (e.g. *_count) stay null
     * until the controller / request sets them.
     *
     * @param \Cake\ORM\Table $table
     * @return \Cake\Datasource\EntityInterface
     */
    protected function newEntityWithSchemaDefaults(Table $table): EntityInterface
    {
        $entity = $table->newEmptyEntity();
        if (method_exists($table, 'applySchemaDefaults')) {
            /** @var callable $fn */
            $fn = [$table, 'applySchemaDefaults'];
            $fn($entity);
        }

        return $entity;
    }

    /**
     * Expose canDelete for breadcrumb / view (Tables using PreventsDeleteWithChildrenTrait).
     *
     * @param \Cake\ORM\Table $table
     * @param \Cake\Datasource\EntityInterface $entity
     * @return void
     */
    protected function setCanDeleteFlag(Table $table, EntityInterface $entity): void
    {
        $canDelete = true;
        if (method_exists($table, 'canDelete')) {
            /** @var callable $fn */
            $fn = [$table, 'canDelete'];
            $canDelete = (bool)$fn($entity);
        }
        $this->set('canDelete', $canDelete);
    }
}
