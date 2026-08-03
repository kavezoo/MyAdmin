<?php
declare(strict_types=1);

namespace App\Model\Table;

use App\Model\Table\Concerns\UsesDatabaseColumnDefaultsTrait;
use App\Utility\SetupValue;
use ArrayObject;
use Cake\Event\EventInterface;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * Setups Model — typed application settings (EAV value by type).
 *
 * @method \App\Model\Entity\Setup newEmptyEntity()
 * @method \App\Model\Entity\Setup newEntity(array $data, array $options = [])
 * @method \App\Model\Entity\Setup get(mixed $primaryKey, array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, mixed ...$args)
 * @method \App\Model\Entity\Setup patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\Setup|false save(\Cake\Datasource\EntityInterface $entity, array $options = [])
 *
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class SetupsTable extends Table
{
    use UsesDatabaseColumnDefaultsTrait;

    /**
     * @param array<string, mixed> $config
     * @return void
     */
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('setups');
        $this->setDisplayField('name');
        $this->setPrimaryKey('id');
        $this->setEntityClass(\App\Model\Entity\Setup::class);

        $this->addBehavior('Timestamp');
    }

    /**
     * @param \Cake\Event\EventInterface<\Cake\ORM\Table> $event
     * @param \ArrayObject<string, mixed> $data
     * @param \ArrayObject<string, mixed> $options
     * @return void
     */
    public function beforeMarshal(EventInterface $event, ArrayObject $data, ArrayObject $options): void
    {
        $copy = $data->getArrayCopy();
        $type = isset($copy['type']) && is_string($copy['type']) ? $copy['type'] : '';

        if (isset($copy['slug']) && is_string($copy['slug'])) {
            $data['slug'] = strtolower(trim($copy['slug']));
        }

        if ($type === SetupValue::TYPE_BOOLEAN && !array_key_exists('value', $copy)) {
            $data['value'] = '0';
            $copy['value'] = '0';
        }

        if ($type === '' || !array_key_exists('value', $copy)) {
            return;
        }

        $result = SetupValue::normalize($type, $copy['value']);
        if ($result['ok']) {
            $data['value'] = $result['value'];
        }
        // On failure leave raw value for re-display; validation rule reports the error.
    }

    /**
     * @param \Cake\Validation\Validator $validator
     * @return \Cake\Validation\Validator
     */
    public function validationDefault(Validator $validator): Validator
    {
        $validator
            ->scalar('name')
            ->maxLength('name', 150)
            ->requirePresence('name', 'create')
            ->notEmptyString('name');

        $validator
            ->scalar('slug')
            ->maxLength('slug', 150)
            ->requirePresence('slug', 'create')
            ->notEmptyString('slug')
            ->add('slug', 'format', [
                'rule' => static function ($value) {
                    return is_string($value) && SetupValue::isValidSlug($value);
                },
                'message' => __('The slug may only contain lowercase letters, numbers and underscores.'),
            ]);

        $validator
            ->scalar('type')
            ->maxLength('type', 20)
            ->requirePresence('type', 'create')
            ->notEmptyString('type')
            ->inList('type', SetupValue::typeList(), __('Invalid type.'));

        $validator
            ->allowEmptyString('value')
            ->add('value', 'typedValue', [
                'rule' => static function ($value, $context) {
                    $type = (string)($context['data']['type'] ?? '');
                    if ($type === '' || !SetupValue::isValidType($type)) {
                        return true;
                    }
                    $result = SetupValue::normalize($type, $value);
                    if ($result['ok']) {
                        return true;
                    }

                    return $result['error'] ?: __('The value is not valid for the selected type.');
                },
            ]);

        $validator
            ->allowEmptyString('description');

        $validator
            ->integer('pos')
            ->allowEmptyString('pos');

        $validator
            ->boolean('visible')
            ->allowEmptyString('visible');

        return $validator;
    }

    /**
     * @param \Cake\ORM\RulesChecker $rules
     * @return \Cake\ORM\RulesChecker
     */
    public function buildRules(RulesChecker $rules): RulesChecker
    {
        $rules->add($rules->isUnique(['slug'], __('This slug is already in use.')));

        return $rules;
    }

    /**
     * Typed PHP value by slug (or default if missing / invisible).
     */
    public function getValue(string $slug, mixed $default = null): mixed
    {
        $slug = strtolower(trim($slug));
        if ($slug === '' || !SetupValue::isValidSlug($slug)) {
            return $default;
        }
        $row = $this->find()
            ->select(['type', 'value', 'visible'])
            ->where(['slug' => $slug])
            ->first();
        if ($row === null || !(bool)$row->get('visible')) {
            return $default;
        }

        $stored = $row->get('value');

        return SetupValue::cast((string)$row->get('type'), $stored !== null ? (string)$stored : null);
    }
}
