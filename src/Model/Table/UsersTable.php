<?php
declare(strict_types=1);

namespace App\Model\Table;

use App\Auth\MembershipProfile;
use App\Model\Entity\User;
use App\Utility\PhoneNumber;
use ArrayObject;
use Cake\Datasource\EntityInterface;
use Cake\Event\EventInterface;
use Cake\ORM\Query\SelectQuery;
use Cake\ORM\RulesChecker;
use Cake\Validation\Validator;
use CakeDC\Users\Model\Table\UsersTable as CakeDCUsersTable;

/**
 * App Users table — CakeDC Users + country_id + club_id + membership onboarding.
 *
 * CounterCache: Countries.user_count, Clubs.user_count (registration / country|club change / delete).
 *
 * @property \App\Model\Table\CountriesTable&\Cake\ORM\Association\BelongsTo $Countries
 * @property \App\Model\Table\ClubsTable&\Cake\ORM\Association\BelongsTo $Clubs
 * @property \App\Model\Table\CompetitionsUsersTable&\Cake\ORM\Association\HasMany $CompetitionsUsers
 * @mixin \Cake\ORM\Behavior\CounterCacheBehavior
 */
class UsersTable extends CakeDCUsersTable
{
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setEntityClass(User::class);
        $this->belongsTo('Countries', [
            'foreignKey' => 'country_id',
            'className' => 'Countries',
            'joinType' => 'LEFT',
        ]);
        $this->belongsTo('Clubs', [
            'foreignKey' => 'club_id',
            'className' => 'Clubs',
            'joinType' => 'LEFT',
        ]);
        $this->belongsTo('Languages', [
            'foreignKey' => 'language_id',
            'className' => 'Languages',
            'joinType' => 'LEFT',
        ]);
        $this->hasMany('CompetitionsUsers', [
            'foreignKey' => 'user_id',
            'className' => 'CompetitionsUsers',
        ]);
        // Child-side CounterCache for parent count columns.
        // Soft FK 0 (no club yet): skip Clubs.user_count update (Cake would target id=0).
        $this->addBehavior('CounterCache', [
            'Countries' => [
                'user_count' => function ($event, $entity, $table, $original) {
                    $countryId = (int)($original
                        ? ($entity->getOriginal('country_id') ?? 0)
                        : ($entity->get('country_id') ?? 0));
                    if ($countryId < 1) {
                        return false;
                    }

                    return $table->find()->where(['Users.country_id' => $countryId])->count();
                },
            ],
            'Clubs' => [
                'user_count' => function ($event, $entity, $table, $original) {
                    $clubId = (int)($original
                        ? ($entity->getOriginal('club_id') ?? 0)
                        : ($entity->get('club_id') ?? 0));
                    if ($clubId < 1) {
                        return false;
                    }

                    return $table->find()->where(['Users.club_id' => $clubId])->count();
                },
            ],
        ]);
        // Explicit EventLog (also auto-attached in Application) — membership fee dates, enabled, …
        if (!$this->hasBehavior('EventLog')) {
            $this->addBehavior('EventLog');
        }
    }

    public function validationDefault(Validator $validator): Validator
    {
        $validator = parent::validationDefault($validator);
        $validator
            ->allowEmptyString('country_id')
            ->nonNegativeInteger('country_id');
        $validator
            ->boolean('enabled')
            ->allowEmptyString('enabled');
        $validator
            ->nonNegativeInteger('club_id')
            ->allowEmptyString('club_id');
        $validator
            ->integer('language_id')
            ->allowEmptyString('language_id');
        $validator
            ->scalar('avatar')
            ->maxLength('avatar', 255)
            ->allowEmptyString('avatar');
        $validator
            ->scalar('membership_status')
            ->maxLength('membership_status', 20)
            ->allowEmptyString('membership_status');
        $validator
            ->boolean('application_notified')
            ->allowEmptyString('application_notified');
        $validator
            ->date('club_membership_fee_date')
            ->allowEmptyDate('club_membership_fee_date');
        $validator
            ->date('national_membership_fee_date')
            ->allowEmptyDate('national_membership_fee_date');

        return $validator;
    }

    /**
     * Profile completion for role `new`.
     */
    public function validationProfileComplete(Validator $validator): Validator
    {
        $validator
            ->requirePresence('first_name')
            ->notEmptyString('first_name', __('Please enter your name.'))
            ->maxLength('first_name', 50)
            ->allowEmptyString('phone')
            ->maxLength('phone', 50)
            ->add('phone', 'international', [
                'rule' => static function ($value) {
                    if ($value === null || $value === '') {
                        return true;
                    }

                    return PhoneNumber::isValidStored((string)$value);
                },
                'message' => __('Phone must start with + and contain digits only.'),
            ])
            ->requirePresence('country_id')
            ->notBlank('country_id', __('Please select your country.'))
            ->nonNegativeInteger('country_id')
            ->greaterThan('country_id', 0, __('Please select your country.'))
            ->requirePresence('club_id')
            ->notBlank('club_id', __('Please select your club.'))
            ->nonNegativeInteger('club_id')
            ->greaterThan('club_id', 0, __('Please select your club.'))
            ->integer('language_id')
            ->allowEmptyString('language_id');

        return $validator;
    }

    /**
     * Logged-in user profile edit (member+).
     */
    public function validationProfileEdit(Validator $validator): Validator
    {
        $validator
            ->requirePresence('first_name')
            ->notEmptyString('first_name', __('Please enter your name.'))
            ->maxLength('first_name', 50)
            ->allowEmptyString('phone')
            ->maxLength('phone', 50)
            ->add('phone', 'international', [
                'rule' => static function ($value) {
                    if ($value === null || $value === '') {
                        return true;
                    }

                    return PhoneNumber::isValidStored((string)$value);
                },
                'message' => __('Phone must start with + and contain digits only.'),
            ])
            ->requirePresence('country_id')
            ->notBlank('country_id', __('Please select your country.'))
            ->nonNegativeInteger('country_id')
            ->greaterThan('country_id', 0, __('Please select your country.'))
            ->requirePresence('club_id')
            ->notBlank('club_id', __('Please select your club.'))
            ->nonNegativeInteger('club_id')
            ->greaterThan('club_id', 0, __('Please select your club.'))
            ->integer('language_id')
            ->allowEmptyString('language_id');

        return $validator;
    }

    /**
     * @param \Cake\Event\EventInterface<\Cake\ORM\Table> $event
     * @param \Cake\Datasource\EntityInterface $entity
     * @param \ArrayObject<string, mixed> $options
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
     * Block delete while competition applications or club-president assignment exist.
     */
    public function canDelete(EntityInterface $entity): bool
    {
        $userId = trim((string)$entity->get('id'));
        if ($userId === '') {
            return false;
        }
        if ($this->CompetitionsUsers->exists(['CompetitionsUsers.user_id' => $userId])) {
            return false;
        }

        return !$this->Clubs->exists(['Clubs.club_president_id' => $userId]);
    }

    public function beforeSave(EventInterface $event, EntityInterface $entity, \ArrayObject $options): void
    {
        if ($entity->isDirty('first_name')) {
            $entity->set('first_name', static::formatPersonName($entity->get('first_name')));
        }
        if ($entity->isDirty('last_name')) {
            $entity->set('last_name', static::formatPersonName($entity->get('last_name')));
        }
        if ($entity->isDirty('phone') || $entity->isDirty('country_id')) {
            $prefix = PhoneNumber::prefixForCountryId((int)($entity->get('country_id') ?? 0));
            $entity->set('phone', PhoneNumber::normalizeForStorage($entity->get('phone'), $prefix));
        }
    }

    protected static function formatPersonName(mixed $name): string
    {
        $name = trim(preg_replace('/\s+/u', ' ', (string)$name) ?? '');
        if ($name === '') {
            return '';
        }

        return mb_convert_case($name, MB_CASE_TITLE, 'UTF-8');
    }

    /**
     * Auth finder: CakeDC `active` (email/activation) **and** app `enabled`
     * (admin/president can lock out a user without clearing activation).
     *
     * Used by Form / Cookie / Token / Social identifiers (`finder` => `active`).
     *
     * @param \Cake\ORM\Query\SelectQuery<\Cake\Datasource\EntityInterface> $query
     * @return \Cake\ORM\Query\SelectQuery<\Cake\Datasource\EntityInterface>
     */
    public function findActive(SelectQuery $query): SelectQuery
    {
        return $query->where([
            $this->aliasField('active') => 1,
            $this->aliasField('enabled') => 1,
        ]);
    }

    /**
     * True when the account exists, is activated (`active`), but locked out (`enabled` = 0).
     */
    public function isDisabledForLogin(string $email): bool
    {
        $email = trim($email);
        if ($email === '') {
            return false;
        }

        $row = $this->find()
            ->select(['Users.id', 'Users.active', 'Users.enabled'])
            ->where(['Users.email' => $email])
            ->first();
        if ($row === null) {
            return false;
        }

        return (int)$row->get('active') === 1 && !(bool)$row->get('enabled');
    }

    /**
     * Whether the user id may keep an authenticated session.
     */
    public function isLoginAllowedForId(string $userId): bool
    {
        if ($userId === '') {
            return false;
        }

        return $this->exists([
            'id' => $userId,
            'active' => 1,
            'enabled' => 1,
        ]);
    }

    public function validationRegister(Validator $validator): Validator
    {
        $validator = parent::validationRegister($validator);
        // parent → validationDefault already has nonNegativeInteger('country_id'); do not re-add it.
        $validator
            ->requirePresence('email', 'create')
            ->notEmptyString('email', __('Please enter your email.'))
            ->email('email', false, __('Please enter a valid email.'))
            ->requirePresence('country_id', 'create')
            ->notEmptyString('country_id', __('Please select your country.'))
            ->requirePresence('first_name', 'create')
            ->notEmptyString('first_name', __('Please enter your name.'));

        return $validator;
    }

    public function buildRules(RulesChecker $rules): RulesChecker
    {
        $rules = parent::buildRules($rules);
        $rules->add($rules->isUnique(['email']), '_isUniqueEmailApp', [
            'errorField' => 'email',
            'message' => __d('cake_d_c/users', 'Email already exists'),
        ]);
        $rules->add($rules->existsIn(['country_id'], 'Countries'), [
            'errorField' => 'country_id',
            'allowNullableNulls' => true,
        ]);
        $rules->add($rules->existsIn(['language_id'], 'Languages'), [
            'errorField' => 'language_id',
            'allowNullableNulls' => true,
        ]);
        $rules->add(function ($entity) {
            $clubId = (int)($entity->get('club_id') ?? 0);
            if ($clubId < 1) {
                return true;
            }

            return $this->Clubs->exists(['Clubs.id' => $clubId]);
        }, 'clubExists', [
            'errorField' => 'club_id',
            'message' => __('Please select a valid club.'),
        ]);
        $rules->add(function ($entity) {
            $clubId = (int)($entity->get('club_id') ?? 0);
            if ($clubId < 1) {
                return true;
            }
            $countryId = (int)($entity->get('country_id') ?? 0);
            if ($countryId < 1) {
                return false;
            }

            $allowExisting = 0;
            if (!$entity->isDirty('club_id')) {
                $allowExisting = $clubId;
            } elseif (method_exists($entity, 'getOriginal')) {
                $allowExisting = (int)$entity->getOriginal('club_id');
            }

            return $this->Clubs->isAllowedForProfile($clubId, $countryId, $allowExisting);
        }, 'clubInCountry', [
            'errorField' => 'club_id',
            'message' => __('Please select a club in your country.'),
        ]);

        return $rules;
    }

    /**
     * Registration defaults: username from email when missing; role stays Configure default.
     *
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public function normalizeRegistrationData(array $data): array
    {
        $email = trim((string)($data['email'] ?? ''));
        if ($email !== '' && trim((string)($data['username'] ?? '')) === '') {
            $data['username'] = $email;
        }
        if (!empty($data['country_id'])) {
            $data['country_id'] = (int)$data['country_id'];
        }
        // Admin/president lock-out flag — new accounts start allowed (DB DEFAULT 1).
        if (!array_key_exists('enabled', $data) || $data['enabled'] === null || $data['enabled'] === '') {
            $data['enabled'] = true;
        }
        $data['club_id'] = (int)($data['club_id'] ?? 0);
        $data['membership_status'] = MembershipProfile::STATUS_INCOMPLETE;
        $data['application_notified'] = false;

        return $data;
    }

    public function sendLoginLink(string $name): void
    {
        $this->getBehavior('OneTimeLoginLink')->sendLoginLink($name);
    }

    public function loginWithToken(string $token): ?EntityInterface
    {
        return $this->getBehavior('OneTimeLoginLink')->loginWithToken($token);
    }
}
