<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * Parents Model
 *
 * @property \App\Model\Table\SamplesTable&\Cake\ORM\Association\HasMany $Samples
 *
 * @method \App\Model\Entity\ParentRecord newEmptyEntity()
 * @method \App\Model\Entity\ParentRecord newEntity(array $data, array $options = [])
 * @method array<\App\Model\Entity\ParentRecord> newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\ParentRecord get(mixed $primaryKey, array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, mixed ...$args)
 * @method \App\Model\Entity\ParentRecord findOrCreate($search, ?callable $callback = null, array $options = [])
 * @method \App\Model\Entity\ParentRecord patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method array<\App\Model\Entity\ParentRecord> patchEntities(iterable $entities, array $data, array $options = [])
 * @method \App\Model\Entity\ParentRecord|false save(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method \App\Model\Entity\ParentRecord saveOrFail(\Cake\Datasource\EntityInterface $entity, array $options = [])
 *
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class ParentsTable extends Table
{
    /**
     * @param array<string, mixed> $config
     * @return void
     */
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('parents');
        $this->setDisplayField('name');
        $this->setPrimaryKey('id');
        $this->setEntityClass(\App\Model\Entity\ParentRecord::class);

        $this->addBehavior('Timestamp');

        $this->hasMany('Samples', [
            'foreignKey' => 'parent_id',
        ]);
    }

    /**
     * @param \Cake\Validation\Validator $validator
     * @return \Cake\Validation\Validator
     */
    public function validationDefault(Validator $validator): Validator
    {
        $validator
            ->scalar('name')
            ->maxLength('name', 100)
            ->requirePresence('name', 'create')
            ->notEmptyString('name');

        $validator
            ->integer('pos')
            ->notEmptyString('pos');

        $validator
            ->boolean('visible')
            ->notEmptyString('visible');

        $validator
            ->nonNegativeInteger('sample_count')
            ->allowEmptyString('sample_count');

        return $validator;
    }
}
