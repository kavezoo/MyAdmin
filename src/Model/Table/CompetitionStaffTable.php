<?php
declare(strict_types=1);

namespace App\Model\Table;

use App\Model\Table\Concerns\UsesDatabaseColumnDefaultsTrait;
use App\Utility\CompetitionStaff as CompetitionStaffRoles;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * Competition day staff (check-in / judge).
 *
 * @property \App\Model\Table\CompetitionsTable&\Cake\ORM\Association\BelongsTo $Competitions
 * @property \App\Model\Table\UsersTable&\Cake\ORM\Association\BelongsTo $Users
 */
class CompetitionStaffTable extends Table
{
    use UsesDatabaseColumnDefaultsTrait;

    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('competition_staff');
        $this->setDisplayField('id');
        $this->setPrimaryKey('id');
        $this->setEntityClass(\App\Model\Entity\CompetitionStaff::class);

        $this->addBehavior('Timestamp');

        $this->belongsTo('Competitions', [
            'foreignKey' => 'competition_id',
            'joinType' => 'INNER',
            'className' => 'Competitions',
        ]);
        $this->belongsTo('Users', [
            'foreignKey' => 'user_id',
            'joinType' => 'INNER',
            'className' => 'Users',
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
            ->scalar('staff_role')
            ->maxLength('staff_role', 20)
            ->requirePresence('staff_role', 'create')
            ->notEmptyString('staff_role')
            ->inList('staff_role', CompetitionStaffRoles::ROLES);

        $validator
            ->boolean('visible')
            ->allowEmptyString('visible');

        return $validator;
    }

    public function buildRules(RulesChecker $rules): RulesChecker
    {
        $rules->add($rules->existsIn(['competition_id'], 'Competitions'), ['errorField' => 'competition_id']);
        $rules->add($rules->existsIn(['user_id'], 'Users'), ['errorField' => 'user_id']);
        $rules->add($rules->isUnique(
            ['competition_id', 'user_id', 'staff_role'],
            __('This user is already assigned to this role for the competition.')
        ));

        return $rules;
    }

    /**
     * @return list<\App\Model\Entity\CompetitionStaff>
     */
    public function listForCompetition(string $competitionId, ?string $staffRole = null): array
    {
        $query = $this->find()
            ->contain(['Users'])
            ->where([
                'CompetitionStaff.competition_id' => $competitionId,
                'CompetitionStaff.visible' => true,
            ])
            ->orderBy(['CompetitionStaff.staff_role' => 'ASC', 'CompetitionStaff.id' => 'ASC']);
        if ($staffRole !== null && $staffRole !== '') {
            $query->where(['CompetitionStaff.staff_role' => $staffRole]);
        }

        return $query->all()->toList();
    }
}
