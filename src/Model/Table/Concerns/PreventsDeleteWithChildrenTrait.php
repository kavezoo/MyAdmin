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
 * Tables must implement countRelatedChildren().
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
     * Whether the UI may offer delete (based on live child count).
     *
     * @param \Cake\Datasource\EntityInterface $entity
     * @return bool
     */
    public function canDelete(EntityInterface $entity): bool
    {
        return $this->countRelatedChildren($entity) === 0;
    }

    /**
     * Live number of related child / join records that block deletion.
     *
     * @param \Cake\Datasource\EntityInterface $entity
     * @return int
     */
    abstract public function countRelatedChildren(EntityInterface $entity): int;
}
