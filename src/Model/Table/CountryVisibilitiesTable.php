<?php
declare(strict_types=1);

namespace App\Model\Table;

use App\Model\Table\Concerns\UsesDatabaseColumnDefaultsTrait;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * CountryVisibilities — per active country which other countries are visible.
 *
 * @property \App\Model\Table\CountriesTable&\Cake\ORM\Association\BelongsTo $Countries
 * @property \App\Model\Table\CountriesTable&\Cake\ORM\Association\BelongsTo $VisibleCountries
 */
class CountryVisibilitiesTable extends Table
{
    use UsesDatabaseColumnDefaultsTrait;

    /**
     * @param array<string, mixed> $config
     * @return void
     */
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('country_visibilities');
        $this->setDisplayField('id');
        $this->setPrimaryKey('id');
        $this->setEntityClass(\App\Model\Entity\CountryVisibility::class);

        $this->addBehavior('Timestamp');

        $this->belongsTo('Countries', [
            'foreignKey' => 'country_id',
            'joinType' => 'INNER',
            'className' => 'Countries',
        ]);
        $this->belongsTo('VisibleCountries', [
            'foreignKey' => 'visible_country_id',
            'joinType' => 'INNER',
            'className' => 'Countries',
            'propertyName' => 'visible_country',
        ]);
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
            ->nonNegativeInteger('visible_country_id')
            ->requirePresence('visible_country_id', 'create')
            ->notEmptyString('visible_country_id');

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
        $rules->add($rules->existsIn(['country_id'], 'Countries'), ['errorField' => 'country_id']);
        $rules->add($rules->existsIn(['visible_country_id'], 'VisibleCountries'), ['errorField' => 'visible_country_id']);
        $rules->add($rules->isUnique(['country_id', 'visible_country_id']), ['errorField' => 'visible_country_id']);

        return $rules;
    }
}
