<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * EventLogs — append-only user activity log.
 *
 * @property \App\Model\Table\CountriesTable&\Cake\ORM\Association\BelongsTo $Countries
 * @property \App\Model\Table\UsersTable&\Cake\ORM\Association\BelongsTo $Users
 */
class EventLogsTable extends Table
{
    /**
     * @param array<string, mixed> $config
     * @return void
     */
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('event_logs');
        $this->setDisplayField('description');
        $this->setPrimaryKey('id');
        $this->setEntityClass(\App\Model\Entity\EventLog::class);

        $this->belongsTo('Countries', [
            'foreignKey' => 'country_id',
            'joinType' => 'LEFT',
            'className' => 'Countries',
        ]);
        $this->belongsTo('Users', [
            'foreignKey' => 'user_id',
            'joinType' => 'LEFT',
            'className' => 'Users',
        ]);
    }

    /**
     * @param \Cake\Validation\Validator $validator
     * @return \Cake\Validation\Validator
     */
    public function validationDefault(Validator $validator): Validator
    {
        $validator
            ->scalar('module')
            ->maxLength('module', 100)
            ->requirePresence('module', 'create')
            ->notEmptyString('module');

        $validator
            ->scalar('action')
            ->maxLength('action', 50)
            ->requirePresence('action', 'create')
            ->notEmptyString('action');

        $validator
            ->allowEmptyString('country_id')
            ->nonNegativeInteger('country_id');

        $validator
            ->uuid('user_id')
            ->allowEmptyString('user_id');

        $validator
            ->scalar('description')
            ->maxLength('description', 500)
            ->allowEmptyString('description');

        return $validator;
    }

    /**
     * @param \Cake\ORM\RulesChecker $rules
     * @return \Cake\ORM\RulesChecker
     */
    public function buildRules(RulesChecker $rules): RulesChecker
    {
        $rules->add($rules->existsIn(['country_id'], 'Countries'), [
            'errorField' => 'country_id',
            'allowNullableNulls' => true,
        ]);
        $rules->add($rules->existsIn(['user_id'], 'Users'), [
            'errorField' => 'user_id',
            'allowNullableNulls' => true,
        ]);

        return $rules;
    }
}
