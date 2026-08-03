<?php
declare(strict_types=1);

namespace App\Model\Table;

use App\Model\Table\Concerns\UsesDatabaseColumnDefaultsTrait;
use App\Utility\AdminCountry;
use App\Utility\SetupValue;
use ArrayObject;
use Cake\Event\EventInterface;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * Setups Model — typed application settings (EAV value by type), scoped by country.
 *
 * @property \App\Model\Table\CountriesTable&\Cake\ORM\Association\BelongsTo $Countries
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

        $this->belongsTo('Countries', [
            'foreignKey' => 'country_id',
            'joinType' => 'INNER',
        ]);
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

        if (array_key_exists('value', $copy) && $copy['value'] === null) {
            $data['value'] = '';
            $copy['value'] = '';
        }

        if ($type === '' || !array_key_exists('value', $copy)) {
            return;
        }

        $result = SetupValue::normalize($type, $copy['value']);
        if ($result['ok']) {
            $data['value'] = $result['value'] ?? '';
        }
    }

    /**
     * @param \Cake\Validation\Validator $validator
     * @return \Cake\Validation\Validator
     */
    public function validationDefault(Validator $validator): Validator
    {
        $validator
            ->nonNegativeInteger('country_id')
            ->requirePresence('country_id', 'create')
            ->notEmptyString('country_id');

        $validator
            ->scalar('name')
            ->maxLength('name', 255)
            ->requirePresence('name', 'create')
            ->notEmptyString('name');

        $validator
            ->scalar('slug')
            ->maxLength('slug', 255)
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
            ->scalar('value')
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
        $rules->add($rules->existsIn(['country_id'], 'Countries'), ['errorField' => 'country_id']);
        $rules->add(
            $rules->isUnique(
                ['country_id', 'slug'],
                __('This slug is already in use for this country.')
            ),
            ['errorField' => 'slug']
        );

        return $rules;
    }

    /**
     * Typed PHP value by slug for a country (or default if missing / invisible).
     *
     * @param string $slug Setup slug
     * @param mixed $default Fallback
     * @param int|null $countryId null → AdminCountry::id()
     */
    public function getValue(string $slug, mixed $default = null, ?int $countryId = null): mixed
    {
        $slug = strtolower(trim($slug));
        if ($slug === '' || !SetupValue::isValidSlug($slug)) {
            return $default;
        }
        $countryId ??= AdminCountry::id();
        if ($countryId < 1) {
            return $default;
        }

        $row = $this->find()
            ->select(['type', 'value', 'visible'])
            ->where([
                'slug' => $slug,
                'country_id' => $countryId,
            ])
            ->first();
        if ($row === null || !(bool)$row->get('visible')) {
            return $default;
        }

        $stored = $row->get('value');

        return SetupValue::cast((string)$row->get('type'), $stored !== null ? (string)$stored : null);
    }
}
