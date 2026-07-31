<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * Samples Model
 *
 * @property \App\Model\Table\ParentsTable&\Cake\ORM\Association\BelongsTo $Parents
 * @property \App\Model\Table\CitiesTable&\Cake\ORM\Association\BelongsToMany $Cities
 *
 * @method \App\Model\Entity\Sample newEmptyEntity()
 * @method \App\Model\Entity\Sample newEntity(array $data, array $options = [])
 * @method array<\App\Model\Entity\Sample> newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\Sample get(mixed $primaryKey, array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, mixed ...$args)
 * @method \App\Model\Entity\Sample findOrCreate($search, ?callable $callback = null, array $options = [])
 * @method \App\Model\Entity\Sample patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method array<\App\Model\Entity\Sample> patchEntities(iterable $entities, array $data, array $options = [])
 * @method \App\Model\Entity\Sample|false save(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method \App\Model\Entity\Sample saveOrFail(\Cake\Datasource\EntityInterface $entity, array $options = [])
 *
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class SamplesTable extends Table
{
    /**
     * @param array<string, mixed> $config
     * @return void
     */
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('samples');
        $this->setDisplayField('name');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');

        $this->belongsTo('Parents', [
            'foreignKey' => 'parent_id',
            'joinType' => 'INNER',
        ]);
        $this->belongsToMany('Cities', [
            'foreignKey' => 'sample_id',
            'targetForeignKey' => 'city_id',
            'joinTable' => 'cities_samples',
            'through' => 'CitiesSamples',
        ]);
    }

    /**
     * @param \Cake\Validation\Validator $validator
     * @return \Cake\Validation\Validator
     */
    public function validationDefault(Validator $validator): Validator
    {
        $validator
            ->nonNegativeInteger('parent_id')
            ->notEmptyString('parent_id');

        $validator
            ->scalar('name')
            ->maxLength('name', 100)
            ->requirePresence('name', 'create')
            ->notEmptyString('name');

        $validator
            ->integer('szam')
            ->requirePresence('szam', 'create')
            ->notEmptyString('szam');

        $validator
            ->numeric('netto')
            ->requirePresence('netto', 'create')
            ->notEmptyString('netto');

        $validator
            ->date('datum')
            ->requirePresence('datum', 'create')
            ->notEmptyDate('datum');

        $validator
            ->time('ido')
            ->requirePresence('ido', 'create')
            ->notEmptyTime('ido');

        $validator
            ->dateTime('datumido')
            ->requirePresence('datumido', 'create')
            ->notEmptyDateTime('datumido');

        $validator
            ->boolean('logikai')
            ->notEmptyString('logikai');

        $validator
            ->integer('pos')
            ->notEmptyString('pos');

        $validator
            ->boolean('visible')
            ->notEmptyString('visible');

        $validator
            ->nonNegativeInteger('city_count')
            ->allowEmptyString('city_count');

        return $validator;
    }

    /**
     * @param \Cake\ORM\RulesChecker $rules
     * @return \Cake\ORM\RulesChecker
     */
    public function buildRules(RulesChecker $rules): RulesChecker
    {
        $rules->add($rules->existsIn(['parent_id'], 'Parents'), ['errorField' => 'parent_id']);

        return $rules;
    }
}
