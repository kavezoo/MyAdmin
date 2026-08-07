<?php
declare(strict_types=1);

namespace App\Model\Table;

use App\Model\Table\Concerns\UsesDatabaseColumnDefaultsTrait;
use ArrayObject;
use Cake\Datasource\EntityInterface;
use Cake\Event\EventInterface;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * Counties — megyék / régiók (országhoz kötve).
 *
 * @property \App\Model\Table\CountriesTable&\Cake\ORM\Association\BelongsTo $Countries
 * @property \App\Model\Table\CitiesTable&\Cake\ORM\Association\HasMany $Cities
 */
class CountiesTable extends Table
{
    use UsesDatabaseColumnDefaultsTrait;

    /**
     * @param array<string, mixed> $config
     * @return void
     */
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('counties');
        $this->setDisplayField('name');
        $this->setPrimaryKey('id');
        $this->setEntityClass(\App\Model\Entity\County::class);

        $this->addBehavior('Timestamp');

        $this->belongsTo('Countries', [
            'foreignKey' => 'country_id',
            'joinType' => 'INNER',
            'className' => 'Countries',
        ]);
        $this->hasMany('Cities', [
            'foreignKey' => 'county_id',
            'className' => 'Cities',
            'dependent' => false,
        ]);
    }

    public function validationDefault(Validator $validator): Validator
    {
        $validator
            ->nonNegativeInteger('country_id')
            ->requirePresence('country_id', 'create')
            ->notEmptyString('country_id');

        $validator
            ->scalar('name')
            ->maxLength('name', 100)
            ->requirePresence('name', 'create')
            ->notEmptyString('name');

        $validator
            ->scalar('shortname')
            ->maxLength('shortname', 100)
            ->allowEmptyString('shortname');

        $validator
            ->boolean('visible')
            ->allowEmptyString('visible');

        return $validator;
    }

    public function buildRules(RulesChecker $rules): RulesChecker
    {
        $rules->add($rules->existsIn(['country_id'], 'Countries'), ['errorField' => 'country_id']);

        return $rules;
    }

    /**
     * @param \Cake\Event\EventInterface<\Cake\ORM\Table> $event
     * @param \Cake\Datasource\EntityInterface $entity
     * @param \ArrayObject<string, mixed> $options
     * @return void
     */
    public function beforeDelete(EventInterface $event, EntityInterface $entity, ArrayObject $options): void
    {
        if (!$this->canDelete($entity)) {
            $entity->setError('_delete', [
                __('Cannot delete this record because it has related child records.'),
            ]);
            $event->stopPropagation();
            $event->setResult(false);
        }
    }

    /**
     * Block delete while cities reference this county (live count — no CounterCache column yet).
     *
     * @param \Cake\Datasource\EntityInterface $entity
     * @return bool
     */
    public function canDelete(EntityInterface $entity): bool
    {
        $id = $entity->get($this->getPrimaryKey());
        if ($id === null || $id === '') {
            return true;
        }

        return $this->Cities->find()
            ->where(['Cities.county_id' => $id])
            ->count() === 0;
    }
}
