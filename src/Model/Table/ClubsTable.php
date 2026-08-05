<?php
declare(strict_types=1);

namespace App\Model\Table;

use App\Model\Table\Concerns\UsesDatabaseColumnDefaultsTrait;
use Cake\ORM\Query\SelectQuery;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * Clubs Table.
 *
 * @property \App\Model\Table\CountriesTable&\Cake\ORM\Association\BelongsTo $Countries
 * @property \App\Model\Table\UsersTable&\Cake\ORM\Association\HasMany $Users
 */
class ClubsTable extends Table
{
    use UsesDatabaseColumnDefaultsTrait;

    /**
     * @param array<string, mixed> $config
     * @return void
     */
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('clubs');
        $this->setDisplayField('name');
        $this->setPrimaryKey('id');
        $this->setEntityClass(\App\Model\Entity\Club::class);

        $this->addBehavior('Timestamp');

        $this->belongsTo('Countries', [
            'foreignKey' => 'country_id',
            'joinType' => 'INNER',
            'className' => 'Countries',
        ]);
        $this->hasMany('Users', [
            'foreignKey' => 'club_id',
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
            ->nonNegativeInteger('country_id')
            ->requirePresence('country_id', 'create')
            ->notEmptyString('country_id');

        $validator
            ->scalar('name')
            ->maxLength('name', 150)
            ->requirePresence('name', 'create')
            ->notEmptyString('name');

        $validator
            ->boolean('enabled')
            ->allowEmptyString('enabled');

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
        $rules->add($rules->existsIn(['country_id'], 'Countries'), ['errorField' => 'country_id']);

        return $rules;
    }

    /**
     * Profile / complete-profile: enabled + visible clubs for one country (pos, name).
     *
     * @param int $includeClubId Always list the user's current club (even if hidden/disabled).
     *
     * @return array<int, string>
     */
    public function optionsForCountry(int $countryId, int $includeClubId = 0): array
    {
        if ($countryId < 1) {
            return [];
        }

        $options = $this->findSelectableForCountry($countryId)
            ->find('list', keyField: 'id', valueField: 'name')
            ->toArray();

        if ($includeClubId > 0 && !isset($options[$includeClubId])) {
            $options = array_merge($this->optionsForClubIds([$includeClubId]), $options);
        }

        return $options;
    }

    /**
     * Country ids with at least one selectable club (enabled + visible).
     *
     * @return list<int>
     */
    public function countryIdsWithSelectableClubs(): array
    {
        $ids = $this->findSelectable()
            ->select(['Clubs.country_id'])
            ->distinct(['Clubs.country_id'])
            ->disableHydration()
            ->all()
            ->extract('country_id')
            ->toList();

        return array_values(array_unique(array_filter(array_map('intval', $ids), static fn(int $id): bool => $id > 0)));
    }

    /**
     * @deprecated Use countryIdsWithSelectableClubs()
     * @return list<int>
     */
    public function countryIdsWithVisibleClubs(): array
    {
        return $this->countryIdsWithSelectableClubs();
    }

    /**
     * @param list<int> $clubIds
     * @return array<int, string>
     */
    public function optionsForClubIds(array $clubIds): array
    {
        $clubIds = array_values(array_unique(array_filter(array_map('intval', $clubIds), static fn(int $id): bool => $id > 0)));
        if ($clubIds === []) {
            return [];
        }

        return $this->find('list', keyField: 'id', valueField: 'name')
            ->where(['Clubs.id IN' => $clubIds])
            ->orderBy(['Clubs.pos' => 'ASC', 'Clubs.name' => 'ASC'])
            ->toArray();
    }

    /**
     * Selectable clubs in one country (enabled, visible, pos).
     *
     * @return \Cake\ORM\Query\SelectQuery<\App\Model\Entity\Club>
     */
    public function findSelectableForCountry(int $countryId): SelectQuery
    {
        return $this->findSelectable()
            ->where(['Clubs.country_id' => $countryId]);
    }

    /**
     * @return \Cake\ORM\Query\SelectQuery<\App\Model\Entity\Club>
     */
    public function findSelectable(): SelectQuery
    {
        return $this->find()
            ->where([
                'Clubs.enabled' => true,
                'Clubs.visible' => true,
            ])
            ->orderBy(['Clubs.pos' => 'ASC', 'Clubs.name' => 'ASC']);
    }
}
