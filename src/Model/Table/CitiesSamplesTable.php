<?php
declare(strict_types=1);

namespace App\Model\Table;

use ArrayObject;
use Cake\Datasource\EntityInterface;
use Cake\Event\EventInterface;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * CitiesSamples Model
 *
 * @property \App\Model\Table\CitiesTable&\Cake\ORM\Association\BelongsTo $Cities
 * @property \App\Model\Table\SamplesTable&\Cake\ORM\Association\BelongsTo $Samples
 *
 * @method \App\Model\Entity\CitiesSample newEmptyEntity()
 * @method \App\Model\Entity\CitiesSample newEntity(array $data, array $options = [])
 * @method array<\App\Model\Entity\CitiesSample> newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\CitiesSample get(mixed $primaryKey, array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, mixed ...$args)
 * @method \App\Model\Entity\CitiesSample findOrCreate($search, ?callable $callback = null, array $options = [])
 * @method \App\Model\Entity\CitiesSample patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method array<\App\Model\Entity\CitiesSample> patchEntities(iterable $entities, array $data, array $options = [])
 * @method \App\Model\Entity\CitiesSample|false save(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method \App\Model\Entity\CitiesSample saveOrFail(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method iterable<\App\Model\Entity\CitiesSample>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\CitiesSample>|false saveMany(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\CitiesSample>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\CitiesSample> saveManyOrFail(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\CitiesSample>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\CitiesSample>|false deleteMany(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\CitiesSample>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\CitiesSample> deleteManyOrFail(iterable $entities, array $options = [])
 *
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class CitiesSamplesTable extends Table
{
    /**
     * Initialize method
     *
     * @param array<string, mixed> $config The configuration for the Table.
     * @return void
     */
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('cities_samples');
        $this->setDisplayField('id');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');

        $this->belongsTo('Cities', [
            'foreignKey' => 'city_id',
            'joinType' => 'INNER',
        ]);
        $this->belongsTo('Samples', [
            'foreignKey' => 'sample_id',
            'joinType' => 'INNER',
        ]);
    }

    /**
     * @param \Cake\Event\EventInterface $event
     * @param \Cake\Datasource\EntityInterface $entity
     * @param \ArrayObject $options
     * @return void
     */
    public function beforeSave(EventInterface $event, EntityInterface $entity, ArrayObject $options): void
    {
        if ($entity->get('pos') === null || $entity->get('pos') === '') {
            $entity->set('pos', 1000);
        }
        if ($entity->get('visible') === null || $entity->get('visible') === '') {
            $entity->set('visible', true);
        }
    }

    /**
     * @param \Cake\Event\EventInterface $event
     * @param \ArrayObject $data
     * @param \ArrayObject $options
     * @return void
     */
    public function beforeMarshal(EventInterface $event, ArrayObject $data, ArrayObject $options): void
    {
        if (!isset($data['pos']) || $data['pos'] === '' || $data['pos'] === null) {
            $data['pos'] = 1000;
        }
        if (!isset($data['visible']) || $data['visible'] === '' || $data['visible'] === null) {
            $data['visible'] = 1;
        }
    }

    /**
     * Default validation rules.
     *
     * @param \Cake\Validation\Validator $validator Validator instance.
     * @return \Cake\Validation\Validator
     */
    public function validationDefault(Validator $validator): Validator
    {
        $validator
            ->integer('city_id')
            ->notEmptyString('city_id');

        $validator
            ->integer('sample_id')
            ->notEmptyString('sample_id');

        $validator
            ->integer('pos')
            ->notEmptyString('pos');

        $validator
            ->notEmptyString('visible');

        return $validator;
    }

    /**
     * Returns a rules checker object that will be used for validating
     * application integrity.
     *
     * @param \Cake\ORM\RulesChecker $rules The rules object to be modified.
     * @return \Cake\ORM\RulesChecker
     */
    public function buildRules(RulesChecker $rules): RulesChecker
    {
        $rules->add($rules->existsIn(['city_id'], 'Cities'), ['errorField' => 'city_id']);
        $rules->add($rules->existsIn(['sample_id'], 'Samples'), ['errorField' => 'sample_id']);

        return $rules;
    }
}
