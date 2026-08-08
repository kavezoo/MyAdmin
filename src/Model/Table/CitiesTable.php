<?php
declare(strict_types=1);

namespace App\Model\Table;

use App\Model\Table\Concerns\PreventsDeleteWithChildrenTrait;
use Cake\Datasource\EntityInterface;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * Cities — települések (ország + megye, ZIP / koordináták).
 *
 * CounterCache: Counties.city_count (this table). Cities.club_count via ClubsTable.
 *
 * @property \App\Model\Table\CountriesTable&\Cake\ORM\Association\BelongsTo $Countries
 * @property \App\Model\Table\CountiesTable&\Cake\ORM\Association\BelongsTo $Counties
 * @property \App\Model\Table\ClubsTable&\Cake\ORM\Association\HasMany $Clubs
 */
class CitiesTable extends Table
{
    use PreventsDeleteWithChildrenTrait;

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
        $this->setEntityClass(\App\Model\Entity\City::class);

        $this->addBehavior('CounterCache', [
            'Counties' => [
                'city_count' => function ($event, $entity, $table, $original) {
                    $countyId = (int)($original
                        ? ($entity->getOriginal('county_id') ?? 0)
                        : ($entity->get('county_id') ?? 0));
                    if ($countyId < 1) {
                        return false;
                    }

                    return $table->find()->where(['Cities.county_id' => $countyId])->count();
                },
            ],
        ]);

        $this->belongsTo('Countries', [
            'foreignKey' => 'country_id',
            'joinType' => 'INNER',
            'className' => 'Countries',
        ]);
        $this->belongsTo('Counties', [
            'foreignKey' => 'county_id',
            'joinType' => 'LEFT',
            'className' => 'Counties',
        ]);
        $this->hasMany('Clubs', [
            'foreignKey' => 'city_id',
            'className' => 'Clubs',
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
            ->nonNegativeInteger('county_id')
            ->allowEmptyString('county_id');

        $validator
            ->scalar('name')
            ->maxLength('name', 255)
            ->requirePresence('name', 'create')
            ->notEmptyString('name');

        $validator
            ->scalar('shortname')
            ->maxLength('shortname', 10)
            ->allowEmptyString('shortname');

        $validator
            ->scalar('zip')
            ->maxLength('zip', 10)
            ->allowEmptyString('zip');

        return $validator;
    }

    public function buildRules(RulesChecker $rules): RulesChecker
    {
        $rules->add($rules->existsIn(['country_id'], 'Countries'), ['errorField' => 'country_id']);

        return $rules;
    }

    /**
     * Select2 label: "Name (ZIP)" or just name.
     */
    public function optionLabel(EntityInterface $city): string
    {
        $name = trim((string)$city->get('name'));
        $zip = trim((string)($city->get('zip') ?? ''));
        if ($zip !== '') {
            return $name . ' (' . $zip . ')';
        }

        return $name;
    }

    /**
     * Clubs block city delete (CounterCache club_count).
     */
    protected function relatedChildrenCountField(): string
    {
        return 'club_count';
    }
}
