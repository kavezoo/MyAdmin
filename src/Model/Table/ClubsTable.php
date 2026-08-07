<?php
declare(strict_types=1);

namespace App\Model\Table;

use App\Auth\AppRoles;
use App\Model\Table\Concerns\PreventsDeleteWithChildrenTrait;
use App\Model\Table\Concerns\UsesDatabaseColumnDefaultsTrait;
use App\Utility\MembershipFee;
use Cake\Datasource\EntityInterface;
use Cake\ORM\Query\SelectQuery;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * Clubs Table.
 *
 * Club president: `clubs.club_president_id` → Users.id (via {@see assignClubPresident()}).
 * Also mirrors into NOT NULL `clubpresident_id` ('' when none).
 * Member / editor → role becomes `clubpresident`; president / vp keep their role.
 * `user_count` = CounterCache from Users.club_id.
 *
 * @property \App\Model\Table\CountriesTable&\Cake\ORM\Association\BelongsTo $Countries
 * @property \App\Model\Table\CitiesTable&\Cake\ORM\Association\BelongsTo $Cities
 * @property \App\Model\Table\UsersTable&\Cake\ORM\Association\HasMany $Users
 */
class ClubsTable extends Table
{
    use PreventsDeleteWithChildrenTrait;
    use UsesDatabaseColumnDefaultsTrait;

    /**
     * NOT NULL string columns without DB DEFAULT — empty string on INSERT.
     *
     * @var list<string>
     */
    protected const STRING_DEFAULTS = [
        'short_name',
        'email',
        'address',
        'phone',
        'web',
        'facebook',
        'insta',
        'clubpresident_id',
    ];

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
        $this->addBehavior('CounterCache', [
            'Countries' => ['club_count'],
        ]);

        // Explicit EventLog (also auto-attached in Application) — national club fee date, …
        if (!$this->hasBehavior('EventLog')) {
            $this->addBehavior('EventLog');
        }

        $this->belongsTo('Countries', [
            'foreignKey' => 'country_id',
            'joinType' => 'INNER',
            'className' => 'Countries',
        ]);
        $this->belongsTo('Cities', [
            'foreignKey' => 'city_id',
            'joinType' => 'LEFT',
            'className' => 'Cities',
        ]);
        $this->hasMany('Users', [
            'foreignKey' => 'club_id',
            'className' => 'Users',
            'dependent' => false,
        ]);
    }

    /**
     * Remove logo file after the club row is deleted.
     *
     * @param \Cake\Event\EventInterface<\Cake\ORM\Table> $event
     * @param \Cake\Datasource\EntityInterface $entity
     * @param \ArrayObject<string, mixed> $options
     * @return void
     */
    public function afterDelete(\Cake\Event\EventInterface $event, EntityInterface $entity, \ArrayObject $options): void
    {
        $logo = $entity->get('logo');
        $stored = is_string($logo) ? $logo : null;
        \App\Utility\ClubLogo::deleteStored($stored, (int)$entity->get('id'));
    }

    /**
     * @param \Cake\Event\EventInterface<\Cake\ORM\Table> $event
     * @param \Cake\Datasource\EntityInterface $entity
     * @param \ArrayObject<string, mixed> $options
     * @return void
     */
    public function beforeSave(\Cake\Event\EventInterface $event, EntityInterface $entity, \ArrayObject $options): void
    {
        foreach (self::STRING_DEFAULTS as $field) {
            if ($entity->get($field) === null) {
                $entity->set($field, '');
            }
        }

        $cityId = (int)($entity->get('city_id') ?? 0);
        if ($cityId < 1) {
            $entity->set('city_id', 0);
        }

        // Mirror designated president into legacy NOT NULL column.
        $presidentId = trim((string)($entity->get('club_president_id') ?? ''));
        $entity->set('clubpresident_id', $presidentId);
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
     * Designated club president user (`clubs.club_president_id`), with legacy fallback.
     *
     * @return \Cake\Datasource\EntityInterface|null
     */
    public function findClubPresident(int $clubId): ?EntityInterface
    {
        if ($clubId < 1) {
            return null;
        }

        $club = $this->find()
            ->select(['id', 'club_president_id'])
            ->where(['Clubs.id' => $clubId])
            ->first();
        if ($club === null) {
            return null;
        }

        $presidentId = trim((string)($club->get('club_president_id') ?? ''));
        if ($presidentId !== '') {
            $user = $this->Users->find()
                ->where(['Users.id' => $presidentId])
                ->first();
            if ($user !== null) {
                return $user;
            }
        }

        // Legacy: role=clubpresident + club_id (before club_president_id column).
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
     * - Stores `clubs.club_president_id`.
     * - Previous designated / role=`clubpresident` → `member` only if they were pure clubpresident;
     *   president / vicepresident keep their role.
     * - Selected member / editor → role `clubpresident` + club_id.
     * - Selected president / vicepresident → club_id + club_president_id; role unchanged.
     */
    public function assignClubPresident(int $clubId, int $countryId, ?string $userId): bool
    {
        if ($clubId < 1 || $countryId < 1) {
            return false;
        }

        $club = $this->find()
            ->where([
                'Clubs.id' => $clubId,
                'Clubs.country_id' => $countryId,
            ])
            ->first();
        if ($club === null) {
            return false;
        }

        $userId = $userId !== null ? trim($userId) : '';
        $previousDesignatedId = trim((string)($club->get('club_president_id') ?? ''));

        // Demote outgoing designated club elnök when they were only clubpresident.
        if ($previousDesignatedId !== '' && $previousDesignatedId !== $userId) {
            if (!$this->demoteOutgoingClubPresident($previousDesignatedId, $countryId)) {
                return false;
            }
        }

        // Legacy / extra: any other role=clubpresident on this club → member (except new assignee).
        $previous = $this->Users->find()
            ->where([
                'Users.club_id' => $clubId,
                'Users.role' => AppRoles::CLUBPRESIDENT,
                'Users.country_id' => $countryId,
            ])
            ->all();

        foreach ($previous as $prev) {
            $prevId = (string)$prev->get('id');
            if ($userId !== '' && $prevId === $userId) {
                continue;
            }
            if ($previousDesignatedId !== '' && $prevId === $previousDesignatedId) {
                // Already handled above.
                continue;
            }
            if (!AppRoles::shouldDemoteFromClubPresident((string)($prev->get('role') ?? ''))) {
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
            $club->set('club_president_id', null);
            $club->set('clubpresident_id', '');

            return (bool)$this->save($club, [
                'checkRules' => false,
                'accessibleFields' => [
                    'club_president_id' => true,
                    'clubpresident_id' => true,
                    'modified' => true,
                ],
            ]);
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
        $accessible = [
            'club_id' => true,
            'modified' => true,
        ];
        if (AppRoles::shouldPromoteToClubPresident((string)($user->get('role') ?? ''))) {
            $user->set('role', AppRoles::CLUBPRESIDENT);
            $accessible['role'] = true;
        }

        if (!$this->Users->save($user, [
            'checkRules' => false,
            'accessibleFields' => $accessible,
        ])) {
            return false;
        }

        $club->set('club_president_id', $userId);
        $club->set('clubpresident_id', $userId);

        return (bool)$this->save($club, [
            'checkRules' => false,
            'accessibleFields' => [
                'club_president_id' => true,
                'clubpresident_id' => true,
                'modified' => true,
            ],
        ]);
    }

    /**
     * If this user is the designated elnök of `$clubId`, clear `club_president_id`
     * (e.g. officer left the club via profile — role may stay clubpresident elsewhere).
     */
    public function clearDesignatedPresidentIfUser(int $clubId, string $userId): bool
    {
        if ($clubId < 1 || $userId === '') {
            return true;
        }

        $club = $this->find()
            ->select(['id', 'club_president_id'])
            ->where(['Clubs.id' => $clubId])
            ->first();
        if ($club === null) {
            return true;
        }

        $designated = trim((string)($club->get('club_president_id') ?? ''));
        if ($designated === '' || $designated !== $userId) {
            return true;
        }

        $club->set('club_president_id', null);
        $club->set('clubpresident_id', '');

        return (bool)$this->save($club, [
            'checkRules' => false,
            'accessibleFields' => [
                'club_president_id' => true,
                'clubpresident_id' => true,
                'modified' => true,
            ],
        ]);
    }

    /**
     * Demote outgoing club elnök to member only when role is pure clubpresident.
     */
    protected function demoteOutgoingClubPresident(string $userId, int $countryId): bool
    {
        $prev = $this->Users->find()
            ->where([
                'Users.id' => $userId,
                'Users.country_id' => $countryId,
            ])
            ->first();
        if ($prev === null) {
            return true;
        }
        if (!AppRoles::shouldDemoteFromClubPresident((string)($prev->get('role') ?? ''))) {
            return true;
        }
        $prev->set('role', AppRoles::MEMBER);

        return (bool)$this->Users->save($prev, [
            'checkRules' => false,
            'accessibleFields' => [
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
            ->nonNegativeInteger('city_id')
            ->allowEmptyString('city_id');

        $validator
            ->scalar('name')
            ->maxLength('name', 150)
            ->requirePresence('name', 'create')
            ->notEmptyString('name');

        $validator
            ->scalar('short_name')
            ->maxLength('short_name', 250)
            ->allowEmptyString('short_name');

        $validator
            ->scalar('logo')
            ->maxLength('logo', 255)
            ->allowEmptyString('logo');

        $validator
            ->email('email', false, __('Please enter a valid email address.'))
            ->maxLength('email', 50)
            ->allowEmptyString('email');

        $validator
            ->scalar('address')
            ->maxLength('address', 100)
            ->allowEmptyString('address');

        $validator
            ->scalar('phone')
            ->maxLength('phone', 50)
            ->allowEmptyString('phone');

        $validator
            ->scalar('web')
            ->maxLength('web', 1000)
            ->allowEmptyString('web');

        $validator
            ->scalar('facebook')
            ->maxLength('facebook', 1000)
            ->allowEmptyString('facebook');

        $validator
            ->scalar('insta')
            ->maxLength('insta', 1000)
            ->allowEmptyString('insta');

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
        $rules->add(
            function (EntityInterface $entity) {
                $cityId = (int)($entity->get('city_id') ?? 0);
                if ($cityId < 1) {
                    return true;
                }

                return $this->Cities->exists(['Cities.id' => $cityId]);
            },
            'cityExists',
            ['errorField' => 'city_id', 'message' => __('Please select a valid city.')]
        );

        return $rules;
    }

    /**
     * Profile / complete-profile: enabled + visible clubs that paid the national
     * association fee for the current year (pos, name).
     *
     * @param int $includeClubId Always list the user's current club (even if unpaid / hidden / disabled).
     *
     * @return array<int, string>
     */
    public function optionsForCountry(int $countryId, int $includeClubId = 0): array
    {
        if ($countryId < 1) {
            return [];
        }

        $options = $this->findSelectableForCountry($countryId, requireNationalFeePaid: true)
            ->find('list', keyField: 'id', valueField: 'name')
            ->toArray();

        if ($includeClubId > 0 && !isset($options[$includeClubId])) {
            $own = $this->find()
                ->select(['id', 'country_id'])
                ->where(['Clubs.id' => $includeClubId])
                ->disableHydration()
                ->first();
            if ($own !== null && (int)($own['country_id'] ?? 0) === $countryId) {
                $options = array_merge($this->optionsForClubIds([$includeClubId]), $options);
            }
        }

        return $options;
    }

    /**
     * Country ids with at least one selectable club (enabled + visible + fee paid this year).
     *
     * @return list<int>
     */
    public function countryIdsWithSelectableClubs(): array
    {
        $ids = $this->findSelectable(requireNationalFeePaid: true)
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
     * Whether a club may be chosen on profile (same country, enabled, visible, fee paid —
     * or the member's already-assigned club).
     */
    public function isAllowedForProfile(int $clubId, int $countryId, int $allowExistingClubId = 0): bool
    {
        if ($clubId < 1 || $countryId < 1) {
            return false;
        }

        $base = [
            'Clubs.id' => $clubId,
            'Clubs.country_id' => $countryId,
            'Clubs.enabled' => true,
            'Clubs.visible' => true,
        ];
        if (!$this->exists($base)) {
            return false;
        }

        if ($allowExistingClubId > 0 && $clubId === $allowExistingClubId) {
            return true;
        }

        $year = MembershipFee::currentYear();
        $start = sprintf('%04d-01-01', $year);
        $end = sprintf('%04d-12-31', $year);

        return $this->exists($base + [
            'Clubs.national_membership_fee_date >=' => $start,
            'Clubs.national_membership_fee_date <=' => $end,
        ]);
    }

    /**
     * Selectable clubs in one country (enabled, visible, pos; optionally national fee paid).
     *
     * @return \Cake\ORM\Query\SelectQuery<\App\Model\Entity\Club>
     */
    public function findSelectableForCountry(int $countryId, bool $requireNationalFeePaid = false): SelectQuery
    {
        return $this->findSelectable($requireNationalFeePaid)
            ->where(['Clubs.country_id' => $countryId]);
    }

    /**
     * @return \Cake\ORM\Query\SelectQuery<\App\Model\Entity\Club>
     */
    public function findSelectable(bool $requireNationalFeePaid = false): SelectQuery
    {
        $query = $this->find()
            ->where([
                'Clubs.enabled' => true,
                'Clubs.visible' => true,
            ])
            ->orderBy(['Clubs.pos' => 'ASC', 'Clubs.name' => 'ASC']);

        if ($requireNationalFeePaid) {
            $year = MembershipFee::currentYear();
            $query->where([
                'Clubs.national_membership_fee_date >=' => sprintf('%04d-01-01', $year),
                'Clubs.national_membership_fee_date <=' => sprintf('%04d-12-31', $year),
            ]);
        }

        return $query;
    }
}
