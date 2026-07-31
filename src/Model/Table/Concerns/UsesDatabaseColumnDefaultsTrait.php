<?php
declare(strict_types=1);

namespace App\Model\Table\Concerns;

use ArrayObject;
use Cake\Database\TypeFactory;
use Cake\Datasource\EntityInterface;
use Cake\Event\EventInterface;

/**
 * Column defaults come from the database schema — never hardcode them in PHP
 * (e.g. pos=1000, visible=true).
 *
 * - beforeMarshal: empty string / null for columns that have a schema DEFAULT → unset
 *   so INSERT omits the column and MySQL applies the DEFAULT.
 * - applySchemaDefaults(): fill an empty entity for add-form display (values match DB);
 *   fields are marked not dirty so unchanged values stay omitted on INSERT.
 *
 * Note: `$data` in beforeMarshal is an ArrayObject — do not use
 * `array_key_exists($key, $data)` (PHP 8+ TypeError); use `getArrayCopy()`.
 */
trait UsesDatabaseColumnDefaultsTrait
{
    /**
     * @param \Cake\Event\EventInterface<\Cake\ORM\Table> $event
     * @param \ArrayObject<string, mixed> $data
     * @param \ArrayObject<string, mixed> $options
     * @return void
     */
    public function beforeMarshal(EventInterface $event, ArrayObject $data, ArrayObject $options): void
    {
        $payload = $data->getArrayCopy();
        foreach ($this->columnsWithDatabaseDefault() as $field) {
            if (!array_key_exists($field, $payload)) {
                continue;
            }
            if ($payload[$field] === '' || $payload[$field] === null) {
                unset($data[$field]);
            }
        }
    }

    /**
     * Apply DB column defaults onto a new/empty entity (add form UI).
     * Skips primary key. Marks fields not dirty so INSERT can still omit them.
     *
     * @param \Cake\Datasource\EntityInterface $entity
     * @param list<string>|null $only Only these fields (null = all with a DEFAULT)
     * @return void
     */
    public function applySchemaDefaults(EntityInterface $entity, ?array $only = null): void
    {
        $schema = $this->getSchema();
        $primary = (array)$this->getPrimaryKey();

        foreach ($this->columnsWithDatabaseDefault() as $field) {
            if ($only !== null && !in_array($field, $only, true)) {
                continue;
            }
            if (in_array($field, $primary, true)) {
                continue;
            }
            if ($entity->get($field) !== null) {
                continue;
            }

            $default = $schema->getColumn($field)['default'] ?? null;
            $entity->set($field, $this->castSchemaDefault($field, $default));
            $entity->setDirty($field, false);
        }
    }

    /**
     * @return list<string>
     */
    public function columnsWithDatabaseDefault(): array
    {
        $fields = [];
        $schema = $this->getSchema();
        foreach ($schema->columns() as $field) {
            $column = $schema->getColumn($field);
            if (($column['default'] ?? null) !== null) {
                $fields[] = $field;
            }
        }

        return $fields;
    }

    /**
     * @param string $field
     * @param mixed $default
     * @return mixed
     */
    protected function castSchemaDefault(string $field, mixed $default): mixed
    {
        $type = $this->getSchema()->getColumnType($field);
        if ($type === null) {
            return $default;
        }

        return TypeFactory::build($type)->marshal($default);
    }
}
