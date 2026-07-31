<?php
declare(strict_types=1);

namespace App\Model\Table;

use App\Model\Table\Concerns\PreventsDeleteWithChildrenTrait;
use App\Model\Table\Concerns\UsesDatabaseColumnDefaultsTrait;
use Cake\Datasource\EntityInterface;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * Cities Model
 *
 * @property \App\Model\Table\SamplesTable&\Cake\ORM\Association\BelongsToMany $Samples
 *
 * @method \App\Model\Entity\City newEmptyEntity()
 * @method \App\Model\Entity\City newEntity(array $data, array $options = [])
 * @method array<\App\Model\Entity\City> newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\City get(mixed $primaryKey, array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, mixed ...$args)
 * @method \App\Model\Entity\City findOrCreate($search, ?callable $callback = null, array $options = [])
 * @method \App\Model\Entity\City patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method array<\App\Model\Entity\City> patchEntities(iterable $entities, array $data, array $options = [])
 * @method \App\Model\Entity\City|false save(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method \App\Model\Entity\City saveOrFail(\Cake\Datasource\EntityInterface $entity, array $options = [])
 *
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class CitiesTable extends Table
{
    use PreventsDeleteWithChildrenTrait;
    use UsesDatabaseColumnDefaultsTrait;

    /**
     * @param array<string, mixed> $config
     * @return void
     */
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('cities');
        $this->setDisplayField('name');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');

        // dependent join cleanup only when delete is allowed (no linked samples).
        $this->belongsToMany('Samples', [
            'foreignKey' => 'city_id',
            'targetForeignKey' => 'sample_id',
            'joinTable' => 'cities_samples',
            'through' => 'CitiesSamples',
            'dependent' => true,
        ]);
    }

    /**
     * @param \Cake\Datasource\EntityInterface $entity
     * @return int
     */
    public function countRelatedChildren(EntityInterface $entity): int
    {
        $id = $entity->get('id');
        if ($id === null || $id === '') {
            return (int)($entity->get('sample_count') ?? 0);
        }

        return $this->getAssociation('Samples')
            ->junction()
            ->find()
            ->where(['city_id' => $id])
            ->count();
    }

    /**
     * @param \Cake\Validation\Validator $validator
     * @return \Cake\Validation\Validator
     */
    public function validationDefault(Validator $validator): Validator
    {
        $validator
            ->scalar('name')
            ->maxLength('name', 50)
            ->requirePresence('name', 'create')
            ->notEmptyString('name');

        // pos / visible: DB DEFAULT — allow empty so INSERT can omit the column
        $validator
            ->integer('pos')
            ->allowEmptyString('pos');

        $validator
            ->boolean('visible')
            ->allowEmptyString('visible');

        $validator
            ->nonNegativeInteger('sample_count')
            ->allowEmptyString('sample_count');

        return $validator;
    }
}
