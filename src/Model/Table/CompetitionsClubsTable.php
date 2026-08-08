<?php
declare(strict_types=1);

namespace App\Model\Table;

use App\Model\Table\Concerns\PreventsDeleteWithChildrenTrait;
use App\Model\Table\Concerns\UsesDatabaseColumnDefaultsTrait;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * Competition teams (alcsapatok) — one row per club team on a competition.
 * Display name: Subclubs.name (not stored here).
 *
 * @property \App\Model\Table\ClubsTable&\Cake\ORM\Association\BelongsTo $Clubs
 * @property \App\Model\Table\CompetitionsTable&\Cake\ORM\Association\BelongsTo $Competitions
 * @property \App\Model\Table\SubclubsTable&\Cake\ORM\Association\BelongsTo $Subclubs
 * @property \App\Model\Table\CompetitionsUsersTable&\Cake\ORM\Association\HasMany $CompetitionsUsers
 */
class CompetitionsClubsTable extends Table
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

        $this->setTable('competitions_clubs');
        $this->setDisplayField('id');
        $this->setPrimaryKey('id');
        $this->setEntityClass(\App\Model\Entity\CompetitionsClub::class);

        $this->addBehavior('Timestamp');
        if (!$this->hasBehavior('EventLog')) {
            $this->addBehavior('EventLog');
        }

        $this->belongsTo('Clubs', [
            'foreignKey' => 'club_id',
            'joinType' => 'INNER',
            'className' => 'Clubs',
        ]);
        $this->belongsTo('Competitions', [
            'foreignKey' => 'competition_id',
            'joinType' => 'INNER',
            'className' => 'Competitions',
        ]);
        // LEFT: when CompetitionsUsers contain CompetitionsClubs→Subclubs and
        // competition_club_id is NULL (pending), INNER would drop the whole applicant row.
        $this->belongsTo('Subclubs', [
            'foreignKey' => 'subclub_id',
            'joinType' => 'LEFT',
            'className' => 'Subclubs',
        ]);
        $this->hasMany('CompetitionsUsers', [
            'foreignKey' => 'competition_club_id',
            'className' => 'CompetitionsUsers',
            'dependent' => false,
        ]);
    }

    public function validationDefault(Validator $validator): Validator
    {
        $validator
            ->integer('club_id')
            ->requirePresence('club_id', 'create')
            ->notEmptyString('club_id');

        $validator
            ->uuid('competition_id')
            ->requirePresence('competition_id', 'create')
            ->notEmptyString('competition_id');

        $validator
            ->integer('subclub_id')
            ->notEmptyString('subclub_id');

        $validator
            ->boolean('visible')
            ->allowEmptyString('visible');

        return $validator;
    }

    public function buildRules(RulesChecker $rules): RulesChecker
    {
        $rules->add($rules->existsIn(['club_id'], 'Clubs'), ['errorField' => 'club_id']);
        $rules->add($rules->existsIn(['competition_id'], 'Competitions'), ['errorField' => 'competition_id']);
        $rules->add($rules->existsIn(['subclub_id'], 'Subclubs'), ['errorField' => 'subclub_id']);
        $rules->add($rules->isUnique(['competition_id', 'club_id', 'subclub_id'], [
            'message' => __('This sub-team is already entered for this competition.'),
        ]), ['errorField' => 'subclub_id']);

        return $rules;
    }

    /**
     * Team members block delete.
     */
    protected function relatedChildrenCountField(): string
    {
        return 'user_count';
    }

    public function meetsMinimumTeamSize(\App\Model\Entity\CompetitionsClub $team, int $minimumTeamSize): bool
    {
        if ($minimumTeamSize < 1) {
            return true;
        }

        return (int)$team->user_count >= $minimumTeamSize;
    }

    /**
     * Teams that already have at least `$minimumTeamSize` assigned members.
     *
     * @param iterable<\App\Model\Entity\CompetitionsClub> $teams
     * @return list<\App\Model\Entity\CompetitionsClub>
     */
    public function filterMeetingMinimum(iterable $teams, int $minimumTeamSize): array
    {
        $out = [];
        foreach ($teams as $team) {
            if ($this->meetsMinimumTeamSize($team, $minimumTeamSize)) {
                $out[] = $team;
            }
        }

        return $out;
    }
}
