<?php
declare(strict_types=1);

namespace App\Model\Table;

use App\Auth\AppRoles;
use App\Model\Table\Concerns\PreventsDeleteWithChildrenTrait;
use App\Model\Table\Concerns\UsesDatabaseColumnDefaultsTrait;
use Cake\Datasource\EntityInterface;
use Cake\ORM\Query\SelectQuery;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * Clubs Table.
 *
 * Club president = Users row with role `clubpresident` and `club_id` = this club
 * (no FK on clubs — assignment via {@see assignClubPresident()}).
 * `user_count` = CounterCache from Users.club_id.
 *
 * @property \App\Model\Table\CountriesTable&\Cake\ORM\Association\BelongsTo $Countries
 * @property \App\Model\Table\UsersTable&\Cake\ORM\Association\HasMany $Users
 */
class ClubsTable extends Table
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

        $this->setTable('clubs');
        $this->setDisplayField('name');
        $this->setPrimaryKey('id');
        $this->setEntityClass(\App\Model\Entity\Club::class);

        $this->addBehavior('Timestamp');

        // Explicit EventLog (also auto-attached in Application) — national club fee date, …
        if (!$this->hasBehavior('EventLog')) {
            $this->addBehavior('EventLog');
        }

        $this->belongsTo('Countries', [
            'foreignKey' => 'country_id',
            'joinType' => 'INNER',
            'className' => 'Countries',
        ]);
        $this->hasMany('Users', [
            'foreignKey' => 'club_id',
            'className' => 'Users',
            'dependent' => false,
        ]);
    }

    protected function relatedChildrenCountField(): string
    {
        return 'user_count';
    }

    /**
     * @deprecated Use {@see countRelatedChildren()} / `user_count` CounterCache.
     */
    public function countRelatedUsers(EntityInterface $entity): int
    {
        return $this->countRelatedChildren($entity);
    }

    /**
     * Current club president user (role=clubpresident + club_id), or null.
     *
     * @return \Cake\Datasource\EntityInterface|null
     */
    public function findClubPresident(int $clubId): ?EntityInterface
    {
        if ($clubId < 1) {
            return null;
        }

        return $this->Users->find()
            ->where([
                'Users.club_id' => $clubId,
                'Users.role' => AppRoles::CLUBPRESIDENT,
            ])
            ->orderBy(['Users.modified' => 'DESC'])
            ->first();
    }

    /**
     * Assign (or clear) club president for a club in one country.
     *
     * Previous clubpresident(s) for this club → role `member` (keep club_id).
     * Selected user → role `clubpresident`, club_id set.
     */
    public function assignClubPresident(int $clubId, int $countryId, ?string $userId): bool
    {
        if ($clubId < 1 || $countryId < 1) {
            return false;
        }

        $userId = $userId !== null ? trim($userId) : '';
        $previous = $this->Users->find()
            ->where([
                'Users.club_id' => $clubId,
                'Users.role' => AppRoles::CLUBPRESIDENT,
                'Users.country_id' => $countryId,
            ])
            ->all();

        foreach ($previous as $prev) {
            if ($userId !== '' && (string)$prev->get('id') === $userId) {
                continue;
            }
            $prev->set('role', AppRoles::MEMBER);
            if (!$this->Users->save($prev, [
                'checkRules' => false,
                'accessibleFields' => [
                    'role' => true,
                    'modified' => true,
                ],
            ])) {
                return false;
            }
        }

        if ($userId === '') {
            return true;
        }

        $user = $this->Users->find()
            ->where([
                'Users.id' => $userId,
                'Users.country_id' => $countryId,
                'Users.role !=' => AppRoles::NEW,
            ])
            ->first();
        if ($user === null) {
            return false;
        }

        $user->set('club_id', $clubId);
        $user->set('role', AppRoles::CLUBPRESIDENT);

        return (bool)$this->Users->save($user, [
            'checkRules' => false,
            'accessibleFields' => [
                'club_id' => true,
                'role' => true,
                'modified' => true,
            ],
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

        $validator
            ->date('national_membership_fee_date')
            ->allowEmptyDate('national_membership_fee_date');

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
