<?php
declare(strict_types=1);

namespace App\Model\Table;

use App\Model\Entity\Subclub;
use App\Model\Table\Concerns\UsesDatabaseColumnDefaultsTrait;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * Named sub-clubs (alcsapat név) — linked from competitions_clubs.subclub_id.
 *
 * Default display name: "{club.short_name} {n}" (serial per club + competition).
 */
class SubclubsTable extends Table
{
    use UsesDatabaseColumnDefaultsTrait;

    /**
     * @var list<string>
     */
    protected const STRING_DEFAULTS = [
        'name',
    ];

    /**
     * @param array<string, mixed> $config
     * @return void
     */
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('subclubs');
        $this->setDisplayField('name');
        $this->setPrimaryKey('id');
        $this->setEntityClass(Subclub::class);

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
        $this->belongsTo('Users', [
            'foreignKey' => 'user_id',
            'joinType' => 'LEFT',
            'className' => 'Users',
        ]);
        $this->hasMany('CompetitionsClubs', [
            'foreignKey' => 'subclub_id',
            'className' => 'CompetitionsClubs',
            'dependent' => false,
        ]);
    }

    public function validationDefault(Validator $validator): Validator
    {
        $validator
            ->scalar('name')
            ->maxLength('name', 250)
            ->requirePresence('name', 'create')
            ->notEmptyString('name');

        $validator
            ->integer('club_id')
            ->requirePresence('club_id', 'create')
            ->notEmptyString('club_id');

        $validator
            ->uuid('competition_id')
            ->requirePresence('competition_id', 'create')
            ->notEmptyString('competition_id');

        $validator
            ->uuid('user_id')
            ->requirePresence('user_id', 'create')
            ->notEmptyString('user_id');

        $validator
            ->boolean('visible')
            ->allowEmptyString('visible');

        return $validator;
    }

    public function buildRules(RulesChecker $rules): RulesChecker
    {
        $rules->add($rules->existsIn(['club_id'], 'Clubs'), ['errorField' => 'club_id']);
        $rules->add($rules->existsIn(['competition_id'], 'Competitions'), ['errorField' => 'competition_id']);
        $rules->add($rules->existsIn(['user_id'], 'Users'), ['errorField' => 'user_id']);

        return $rules;
    }

    /**
     * Club short name used as name prefix (fallback: club name / "Team").
     */
    public function namePrefixForClub(int $clubId): string
    {
        $club = $this->Clubs->get($clubId);
        $short = trim((string)$club->short_name);
        if ($short === '') {
            $short = trim((string)$club->name);
        }
        if ($short === '') {
            $short = (string)__('Team');
        }

        return $short;
    }

    /**
     * Next default name: "{short_name} {n}" — serial **per club + competition**.
     * New competition → always starts at 1. Empty competition id → "{short} 1".
     */
    public function suggestNextName(int $clubId, string $competitionId): string
    {
        $short = $this->namePrefixForClub($clubId);
        $competitionId = trim($competitionId);
        if ($clubId < 1 || $competitionId === '') {
            return $short . ' 1';
        }

        $max = 0;
        $rows = $this->find()
            ->select(['name'])
            ->where([
                'Subclubs.club_id' => $clubId,
                'Subclubs.competition_id' => $competitionId,
            ])
            ->disableHydration()
            ->all();
        $pattern = '/^' . preg_quote($short, '/') . '\s+(\d+)\s*$/u';
        foreach ($rows as $row) {
            $name = trim((string)($row['name'] ?? ''));
            if ($name !== '' && preg_match($pattern, $name, $m)) {
                $max = max($max, (int)$m[1]);
            }
        }

        // Also consider how many teams already exist on this competition (renamed names).
        $teamCount = $this->CompetitionsClubs->find()
            ->where([
                'CompetitionsClubs.club_id' => $clubId,
                'CompetitionsClubs.competition_id' => $competitionId,
            ])
            ->count();
        $max = max($max, $teamCount);

        return $short . ' ' . ($max + 1);
    }

    /**
     * Create a subclub row (name + club + competition + creator).
     */
    public function createNamed(
        int $clubId,
        string $competitionId,
        string $userId,
        string $name,
        bool $visible = true,
    ): ?Subclub {
        $name = trim($name);
        if ($clubId < 1 || $competitionId === '' || $userId === '' || $name === '') {
            return null;
        }

        /** @var \App\Model\Entity\Subclub $subclub */
        $subclub = $this->newEmptyEntity();
        $this->applySchemaDefaults($subclub);
        $subclub = $this->patchEntity($subclub, [
            'club_id' => $clubId,
            'competition_id' => $competitionId,
            'user_id' => $userId,
            'name' => $name,
            'visible' => $visible,
        ], [
            'fields' => ['club_id', 'competition_id', 'user_id', 'name', 'visible'],
        ]);
        $subclub->club_id = $clubId;
        $subclub->competition_id = $competitionId;
        $subclub->user_id = $userId;
        $subclub->name = $name;
        $subclub->visible = $visible;

        $saved = $this->save($subclub);

        return $saved instanceof Subclub ? $saved : null;
    }
}
