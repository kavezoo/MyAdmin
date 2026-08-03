<?php
declare(strict_types=1);

namespace App\Model\Table;

use App\Model\Table\Concerns\UsesDatabaseColumnDefaultsTrait;
use Cake\ORM\Behavior\Translate\EavStrategy;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * Continents Model — matches `continents` schema.
 *
 * Columns: code, name, visible, pos, created, modified
 * - `name` Translate EAV → i18n (model=Continents)
 * - hasMany Countries via continent_id
 *
 * @property \App\Model\Table\CountriesTable&\Cake\ORM\Association\HasMany $Countries
 *
 * @method \App\Model\Entity\Continent newEmptyEntity()
 * @method \App\Model\Entity\Continent newEntity(array $data, array $options = [])
 * @method array<\App\Model\Entity\Continent> newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\Continent get(mixed $primaryKey, array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, mixed ...$args)
 * @method \App\Model\Entity\Continent findOrCreate($search, ?callable $callback = null, array $options = [])
 * @method \App\Model\Entity\Continent patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method array<\App\Model\Entity\Continent> patchEntities(iterable $entities, array $data, array $options = [])
 * @method \App\Model\Entity\Continent|false save(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method \App\Model\Entity\Continent saveOrFail(\Cake\Datasource\EntityInterface $entity, array $options = [])
 *
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 * @mixin \Cake\ORM\Behavior\TranslateBehavior
 */
class ContinentsTable extends Table
{
    use UsesDatabaseColumnDefaultsTrait;

    /**
     * @param array<string, mixed> $config
     * @return void
     */
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('continents');
        $this->setDisplayField('name');
        $this->setPrimaryKey('id');
        $this->setEntityClass(\App\Model\Entity\Continent::class);

        $this->addBehavior('Timestamp');
        $this->addBehavior('Translate', [
            'strategyClass' => EavStrategy::class,
            'fields' => ['name'],
            'defaultLocale' => 'en_GB',
            'allowEmptyTranslations' => false,
        ]);

        $this->hasMany('Countries', [
            'foreignKey' => 'continent_id',
            'dependent' => false,
        ]);
    }

    /**
     * @param \Cake\Validation\Validator $validator
     * @return \Cake\Validation\Validator
     */
    public function validationDefault(Validator $validator): Validator
    {
        $validator
            ->scalar('code')
            ->maxLength('code', 3)
            ->requirePresence('code', 'create')
            ->notEmptyString('code');

        $validator
            ->scalar('name')
            ->maxLength('name', 64)
            ->requirePresence('name', 'create')
            ->notEmptyString('name');

        $validator
            ->boolean('visible')
            ->allowEmptyString('visible');

        $validator
            ->integer('pos')
            ->allowEmptyString('pos');

        return $validator;
    }

    /**
     * @param \Cake\ORM\RulesChecker $rules
     * @return \Cake\ORM\RulesChecker
     */
    public function buildRules(RulesChecker $rules): RulesChecker
    {
        $rules->add($rules->isUnique(['code']), ['errorField' => 'code']);

        return $rules;
    }
}
