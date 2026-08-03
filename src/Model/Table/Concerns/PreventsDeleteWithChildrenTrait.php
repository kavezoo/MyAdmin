<?php
declare(strict_types=1);

namespace App\Model\Table\Concerns;

use ArrayObject;
use Cake\Datasource\EntityInterface;
use Cake\Event\EventInterface;

/**
 * Blocks entity delete while related children exist; allows delete when childless
 * (join / dependent cleanup is configured on the association where needed).
 *
 * Child count = CounterCache column (`*_count`), not a live JOIN COUNT.
 * Tables must:
 * - keep that column in sync via CounterCache (child Table for belongsTo/hasMany;
 *   through Table for HABTM, with cascadeCallbacks => true)
 * - implement relatedChildrenCountField() (e.g. 'city_count', 'sample_count')
 */
trait PreventsDeleteWithChildrenTrait
{
    /**
     * @param \Cake\Event\EventInterface<\Cake\ORM\Table> $event
     * @param \Cake\Datasource\EntityInterface $entity
     * @param \ArrayObject<string, mixed> $options
     * @return void
     */
    public function beforeDelete(EventInterface $event, EntityInterface $entity, ArrayObject $options): void
    {
        if ($this->countRelatedChildren($entity) > 0) {
            $entity->setError('_delete', [
                __('Cannot delete this record because it has related child records.'),
            ]);
            $event->stopPropagation();
            $event->setResult(false);
        }
    }

    /**
     * Whether the UI may offer delete (CounterCache `*_count` field).
     *
     * @param \Cake\Datasource\EntityInterface $entity
     * @return bool
     */
    public function canDelete(EntityInterface $entity): bool
    {
        return $this->countRelatedChildren($entity) === 0;
    }

    /**
     * Related children that block deletion — CounterCache column value.
     * Prefers a fresh DB read when the entity has a primary key (avoids stale in-memory value).
     *
     * @param \Cake\Datasource\EntityInterface $entity
     * @return int
     */
    public function countRelatedChildren(EntityInterface $entity): int
    {
        $field = $this->relatedChildrenCountField();
        $id = $entity->get($this->getPrimaryKey());

        if ($id !== null && $id !== '') {
            $row = $this->find()
                ->select([$field])
                ->where([$this->aliasField($this->getPrimaryKey()) => $id])
                ->disableHydration()
                ->first();
            if (is_array($row) && array_key_exists($field, $row)) {
                return (int)$row[$field];
            }
        }

        return (int)($entity->get($field) ?? 0);
    }

    /**
     * CounterCache column name (e.g. city_count, sample_count).
     *
     * @return string
     */
    abstract protected function relatedChildrenCountField(): string;
}
