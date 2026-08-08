<?php
declare(strict_types=1);

namespace App\Model\Table;

use App\Model\Table\Concerns\PreventsDeleteWithChildrenTrait;
use App\Model\Table\Concerns\UsesDatabaseColumnDefaultsTrait;
use Cake\Datasource\EntityInterface;
use Cake\Event\EventInterface;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Utility\Text;
use Cake\Validation\Validator;

/**
 * Competitions — president competition announcements.
 *
 * @property \App\Model\Table\CountriesTable&\Cake\ORM\Association\BelongsTo $Countries
 * @property \App\Model\Table\ClubsTable&\Cake\ORM\Association\BelongsTo $Clubs
 * @property \App\Model\Table\CompetitionsClubsTable&\Cake\ORM\Association\HasMany $CompetitionsClubs
 * @property \App\Model\Table\CompetitionsUsersTable&\Cake\ORM\Association\HasMany $CompetitionsUsers
 */
class CompetitionsTable extends Table
{
    use PreventsDeleteWithChildrenTrait;
    use UsesDatabaseColumnDefaultsTrait;

    /**
     * @var list<string>
     */
    protected const STRING_DEFAULTS = [
        'subtitle',
        'subtitle2',
        'racing_pipe_1_title',
        'racing_pipe_2_title',
        'racing_pipe_3_title',
        'description',
    ];

    /**
     * @param array<string, mixed> $config
     * @return void
     */
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('competitions');
        $this->setDisplayField('name');
        $this->setPrimaryKey('id');
        $this->setEntityClass(\App\Model\Entity\Competition::class);

        $this->addBehavior('Timestamp');
        if (!$this->hasBehavior('EventLog')) {
            $this->addBehavior('EventLog');
        }

        $this->addBehavior('CounterCache', [
            'Clubs' => ['competition_count'],
        ]);

        $this->belongsTo('Countries', [
            'foreignKey' => 'country_id',
            'joinType' => 'INNER',
            'className' => 'Countries',
        ]);
        $this->belongsTo('Clubs', [
            'foreignKey' => 'club_id',
            'joinType' => 'INNER',
            'className' => 'Clubs',
        ]);
        $this->belongsTo('Users', [
            'foreignKey' => 'user_id',
            'joinType' => 'LEFT',
            'className' => 'Users',
        ]);
        $this->hasMany('CompetitionsClubs', [
            'foreignKey' => 'competition_id',
            'className' => 'CompetitionsClubs',
            'dependent' => false,
        ]);
        $this->hasMany('CompetitionsUsers', [
            'foreignKey' => 'competition_id',
            'className' => 'CompetitionsUsers',
            'dependent' => false,
        ]);
        $this->hasMany('Subclubs', [
            'foreignKey' => 'competition_id',
            'className' => 'Subclubs',
            'dependent' => false,
        ]);
    }

    public function validationDefault(Validator $validator): Validator
    {
        $validator
            ->uuid('id')
            ->allowEmptyString('id', null, 'create');

        $validator
            ->integer('country_id')
            ->requirePresence('country_id', 'create')
            ->notEmptyString('country_id');

        $validator
            ->integer('club_id')
            ->requirePresence('club_id', 'create')
            ->notEmptyString('club_id');

        $validator
            ->uuid('user_id')
            ->requirePresence('user_id', 'create')
            ->notEmptyString('user_id');

        $validator
            ->scalar('name')
            ->maxLength('name', 250)
            ->requirePresence('name', 'create')
            ->notEmptyString('name');

        $validator
            ->scalar('title')
            ->maxLength('title', 250)
            ->requirePresence('title', 'create')
            ->notEmptyString('title');

        $validator
            ->date('first_date_of_application')
            ->requirePresence('first_date_of_application', 'create')
            ->notEmptyDate('first_date_of_application');

        $validator
            ->date('application_deadline')
            ->requirePresence('application_deadline', 'create')
            ->notEmptyDate('application_deadline');

        $validator
            ->dateTime('competition_datetime')
            ->requirePresence('competition_datetime', 'create')
            ->notEmptyDateTime('competition_datetime');

        $validator
            ->dateTime('start_datetime')
            ->allowEmptyDateTime('start_datetime');

        $validator
            ->dateTime('end_datetime')
            ->allowEmptyDateTime('end_datetime');

        $validator
            ->scalar('description')
            ->allowEmptyString('description');

        $validator
            ->scalar('racing_pipe_1_title')
            ->maxLength('racing_pipe_1_title', 250)
            ->allowEmptyString('racing_pipe_1_title');

        $validator
            ->scalar('racing_pipe_2_title')
            ->maxLength('racing_pipe_2_title', 250)
            ->allowEmptyString('racing_pipe_2_title');

        $validator
            ->scalar('racing_pipe_3_title')
            ->maxLength('racing_pipe_3_title', 250)
            ->allowEmptyString('racing_pipe_3_title');

        $validator
            ->nonNegativeInteger('minimum_team_size')
            ->notEmptyString('minimum_team_size');

        $validator
            ->boolean('national_competition')
            ->allowEmptyString('national_competition');

        $validator
            ->boolean('visible')
            ->allowEmptyString('visible');

        return $validator;
    }

    public function buildRules(RulesChecker $rules): RulesChecker
    {
        $rules->add($rules->existsIn(['country_id'], 'Countries'), ['errorField' => 'country_id']);
        $rules->add($rules->existsIn(['club_id'], 'Clubs'), ['errorField' => 'club_id']);

        return $rules;
    }

    /**
     * @param \Cake\Event\EventInterface<\App\Model\Entity\Competition> $event
     * @param \App\Model\Entity\Competition $entity
     */
    public function beforeSave(EventInterface $event, EntityInterface $entity, \ArrayObject $options): void
    {
        if ($entity->isNew() && (string)$entity->get('id') === '') {
            $entity->set('id', Text::uuid());
        }
    }

    /**
     * Any applicant row blocks competition delete (not only assigned `user_count`).
     *
     * @param \Cake\Datasource\EntityInterface $entity
     * @return int
     */
    public function countRelatedChildren(EntityInterface $entity): int
    {
        $id = $entity->get($this->getPrimaryKey());
        if ($id === null || $id === '') {
            return 0;
        }

        return $this->CompetitionsUsers->find()
            ->where(['CompetitionsUsers.competition_id' => $id])
            ->count();
    }

    /**
     * Official (assigned) applicants — list / UI CounterCache column.
     */
    protected function relatedChildrenCountField(): string
    {
        return 'user_count';
    }
}
