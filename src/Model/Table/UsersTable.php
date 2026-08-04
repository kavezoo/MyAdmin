<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\Datasource\EntityInterface;
use Cake\ORM\RulesChecker;
use Cake\Validation\Validator;
use CakeDC\Users\Model\Entity\User;
use CakeDC\Users\Model\Table\UsersTable as CakeDCUsersTable;

/**
 * App Users table — CakeDC Users + country_id + CakePHP 5.3 OneTimeLogin wrappers.
 *
 * CounterCache: Countries.user_count (registration / country change / delete).
 *
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

        return $validator;
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
