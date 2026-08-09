<?php
declare(strict_types=1);

namespace App\Model\Table;

use App\Model\Table\Concerns\UsesDatabaseColumnDefaultsTrait;
use App\Utility\CompetitionApplication;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * Competition applicants (members).
 *
 * @property \App\Model\Table\CompetitionsTable&\Cake\ORM\Association\BelongsTo $Competitions
 * @property \App\Model\Table\CompetitionsClubsTable&\Cake\ORM\Association\BelongsTo $CompetitionsClubs
 * @property \App\Model\Table\UsersTable&\Cake\ORM\Association\BelongsTo $Users
 * @property \App\Model\Table\UsersTable&\Cake\ORM\Association\BelongsTo $FeeCollectors
 */
class CompetitionsUsersTable extends Table
{
    use UsesDatabaseColumnDefaultsTrait;

    /**
     * @param array<string, mixed> $config
     * @return void
     */
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('competitions_users');
        $this->setDisplayField('id');
        $this->setPrimaryKey('id');
        $this->setEntityClass(\App\Model\Entity\CompetitionsUser::class);

        $this->addBehavior('Timestamp');
        // CakePHP 5 CounterCache: no legacy `sum` key — use a closure (returns SelectQuery subquery).
        // user_count = assigned (official) only; attendant_count = active applications (pending+assigned);
        // lunch = SUM lunch; national_pipe = SUM pipe qtys; team user_count = assigned on that team.
        $this->addBehavior('CounterCache', [
            'Competitions' => [
                'user_count' => [
                    'conditions' => [
                        'CompetitionsUsers.competition_club_id IS NOT' => null,
                        'CompetitionsUsers.status' => CompetitionApplication::STATUS_ASSIGNED,
                    ],
                ],
                'attendant_count' => [
                    'conditions' => [
                        'CompetitionsUsers.status IN' => CompetitionApplication::activeStatuses(),
                    ],
                ],
                'lunch_for_the_attendant' => function ($event, $entity, $table, $original) {
                    $competitionId = $original
                        ? $entity->getOriginal('competition_id')
                        : $entity->get('competition_id');

                    return \App\Utility\CounterCaches::competitionLunchSumQuery($table, $competitionId);
                },
                'national_pipe_club_member_count' => function ($event, $entity, $table, $original) {
                    $competitionId = $original
                        ? $entity->getOriginal('competition_id')
                        : $entity->get('competition_id');

                    return \App\Utility\CounterCaches::competitionPipeSumQuery($table, $competitionId);
                },
            ],
            'CompetitionsClubs' => [
                'user_count' => [
                    'conditions' => [
                        'CompetitionsUsers.status' => CompetitionApplication::STATUS_ASSIGNED,
                    ],
                ],
            ],
        ]);
        if (!$this->hasBehavior('EventLog')) {
            $this->addBehavior('EventLog');
        }

        $this->belongsTo('Competitions', [
            'foreignKey' => 'competition_id',
            'joinType' => 'INNER',
            'className' => 'Competitions',
        ]);
        $this->belongsTo('CompetitionsClubs', [
            'foreignKey' => 'competition_club_id',
            'joinType' => 'LEFT',
            'className' => 'CompetitionsClubs',
        ]);
        $this->belongsTo('Users', [
            'foreignKey' => 'user_id',
            'joinType' => 'INNER',
            'className' => 'Users',
        ]);
        $this->belongsTo('FeeCollectors', [
            'foreignKey' => 'fee_paid_by',
            'joinType' => 'LEFT',
            'className' => 'Users',
            'propertyName' => 'fee_collector',
        ]);
    }

    public function validationDefault(Validator $validator): Validator
    {
        $validator
            ->uuid('competition_id')
            ->requirePresence('competition_id', 'create')
            ->notEmptyString('competition_id');

        $validator
            ->uuid('user_id')
            ->requirePresence('user_id', 'create')
            ->notEmptyString('user_id');

        $validator
            ->integer('competition_club_id')
            ->allowEmptyString('competition_club_id');

        $validator
            ->scalar('status')
            ->maxLength('status', 20)
            ->inList('status', array_keys(CompetitionApplication::statusOptions()))
            ->notEmptyString('status');

        $validator
            ->decimal('result_time')
            ->allowEmptyString('result_time')
            ->greaterThanOrEqual('result_time', 0);

        $validator
            ->email('result_recorded_by_email')
            ->allowEmptyString('result_recorded_by_email')
            ->maxLength('result_recorded_by_email', 255);

        foreach (['entry_fee_amount', 'racing_pipe_1_fee', 'racing_pipe_2_fee', 'racing_pipe_3_fee', 'lunch_fee', 'fee_total'] as $feeField) {
            $validator
                ->decimal($feeField)
                ->allowEmptyString($feeField)
                ->greaterThanOrEqual($feeField, 0);
        }

        $validator
            ->integer('companion_count')
            ->allowEmptyString('companion_count')
            ->greaterThanOrEqual('companion_count', 0);

        $validator
            ->integer('lunch_for_the_attendant')
            ->allowEmptyString('lunch_for_the_attendant')
            ->greaterThanOrEqual('lunch_for_the_attendant', 0);

        $validator
            ->uuid('fee_paid_by')
            ->allowEmptyString('fee_paid_by');

        $validator
            ->boolean('visible')
            ->allowEmptyString('visible');

        return $validator;
    }

    public function buildRules(RulesChecker $rules): RulesChecker
    {
        $rules->add($rules->existsIn(['competition_id'], 'Competitions'), ['errorField' => 'competition_id']);
        $rules->add($rules->existsIn(['user_id'], 'Users'), ['errorField' => 'user_id']);
        $rules->add($rules->existsIn(['fee_paid_by'], 'FeeCollectors'), [
            'allowNullableNulls' => true,
            'errorField' => 'fee_paid_by',
        ]);
        $rules->add($rules->isUnique(['competition_id', 'user_id'], [
            'message' => __('You have already applied to this competition.'),
        ]), ['errorField' => 'user_id']);

        return $rules;
    }

    /**
     * Snapshot entry + pipe fees + total while unpaid (check-in billing).
     *
     * @param \Cake\Event\EventInterface<\Cake\ORM\Table> $event
     * @param \ArrayObject<string, mixed> $options
     */
    public function beforeSave(\Cake\Event\EventInterface $event, \Cake\Datasource\EntityInterface $entity, \ArrayObject $options): void
    {
        if ($entity->get('fee_paid_at')) {
            return;
        }
        $competitionId = (string)($entity->get('competition_id') ?? '');
        $userId = (string)($entity->get('user_id') ?? '');
        if ($competitionId === '' || $userId === '') {
            return;
        }

        try {
            $competition = $entity->get('competition');
            if ($competition === null) {
                $competition = $this->Competitions->get($competitionId);
            }
            $user = $entity->get('user');
            if ($user === null) {
                $user = $this->Users->get($userId);
            }
            \App\Utility\CompetitionFees::applyDueToEntity($entity, $competition, $user);
        } catch (\Throwable) {
            // Keep previous fee columns if lookup fails.
        }
    }
}
