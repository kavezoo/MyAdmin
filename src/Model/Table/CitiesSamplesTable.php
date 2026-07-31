<?php
declare(strict_types=1);

namespace App\Model\Table;

use App\Model\Table\Concerns\UsesDatabaseColumnDefaultsTrait;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * CitiesSamples Model (HABTM through)
 *
 * pos / visible defaults come from the DB schema — no PHP fallbacks.
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
 *
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class CitiesSamplesTable extends Table
{
    use UsesDatabaseColumnDefaultsTrait;

    /**
     * @param array<string, mixed> $config
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
     * @param \Cake\Validation\Validator $validator
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
        $rules->add($rules->existsIn(['city_id'], 'Cities'), ['errorField' => 'city_id']);
        $rules->add($rules->existsIn(['sample_id'], 'Samples'), ['errorField' => 'sample_id']);

        return $rules;
    }
}
