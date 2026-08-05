<?php
declare(strict_types=1);

namespace App\Model\Table;

use App\Auth\MembershipProfile;
use App\Utility\PhoneNumber;
use App\Model\Entity\User;
use Cake\Datasource\EntityInterface;
use Cake\Event\EventInterface;
use Cake\ORM\Query\SelectQuery;
use Cake\ORM\RulesChecker;
use Cake\Validation\Validator;
use CakeDC\Users\Model\Table\UsersTable as CakeDCUsersTable;

/**
 * App Users table — CakeDC Users + country_id + club_id + membership onboarding.
 *
 * CounterCache: Countries.user_count (registration / country change / delete).
 *
 * @property \App\Model\Table\CountriesTable&\Cake\ORM\Association\BelongsTo $Countries
 * @property \App\Model\Table\ClubsTable&\Cake\ORM\Association\BelongsTo $Clubs
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
        // Countries.user_count — child (belongsTo) side CounterCache
        $this->addBehavior('CounterCache', [
            'Countries' => ['user_count'],
        ]);
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
            ->notEmptyString('country_id', __('Please select your country.'))
            ->nonNegativeInteger('country_id')
            ->requirePresence('club_id')
            ->notEmptyString('club_id', __('Please select your club.'))
            ->nonNegativeInteger('club_id')
            ->greaterThan('club_id', 0, __('Please select your club.'));

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
            ->notEmptyString('country_id', __('Please select your country.'))
            ->nonNegativeInteger('country_id')
            ->requirePresence('club_id')
            ->notEmptyString('club_id', __('Please select your club.'))
            ->nonNegativeInteger('club_id')
            ->greaterThan('club_id', 0, __('Please select your club.'));

        return $validator;
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

            return $this->Clubs->exists([
                'Clubs.id' => $clubId,
                'Clubs.country_id' => $countryId,
                'Clubs.visible' => true,
                'Clubs.enabled' => true,
            ]);
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
