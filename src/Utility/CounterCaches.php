<?php
declare(strict_types=1);

namespace App\Utility;

use App\Utility\CompetitionApplication;
use Cake\ORM\Locator\LocatorAwareTrait;
use Cake\ORM\Query\SelectQuery;
use Cake\ORM\Table;

/**
 * CounterCache / `*_count` helpers — keep parent summary columns in sync.
 *
 * Prefer ORM `save()` / `delete()` so Cake CounterCache runs automatically.
 * Use {@see rebuildAll()} after import / drift (`bin/cake rebuild_counter_caches`).
 */
final class CounterCaches
{
    use LocatorAwareTrait;

    /**
     * Rebuild every known CounterCache / summary column in the app.
     *
     * @return list<string> Human-readable steps completed
     */
    public static function rebuildAll(): array
    {
        $steps = [];
        $self = new self();

        $users = $self->fetchTable('Users');
        $countries = $self->fetchTable('Countries');
        $clubs = $self->fetchTable('Clubs');
        $cities = $self->fetchTable('Cities');

        if ($countries->hasBehavior('Translate')) {
            $countries->removeBehavior('Translate');
        }

        if ($users->hasBehavior('CounterCache')) {
            $users->getBehavior('CounterCache')->updateCounterCache('Countries');
            $steps[] = 'Countries.user_count';
            $users->getBehavior('CounterCache')->updateCounterCache('Clubs');
            $steps[] = 'Clubs.user_count';
        }

        $setups = $self->fetchTable('Setups');
        if (
            $setups->hasBehavior('CounterCache')
            && $countries->getSchema()->hasColumn('setup_count')
        ) {
            $setups->getBehavior('CounterCache')->updateCounterCache('Countries');
            $steps[] = 'Countries.setup_count';
        }

        $competitions = $self->fetchTable('Competitions');
        if (
            $competitions->hasBehavior('CounterCache')
            && $clubs->getSchema()->hasColumn('competition_count')
        ) {
            $competitions->getBehavior('CounterCache')->updateCounterCache('Clubs');
            $steps[] = 'Clubs.competition_count';
        }

        if ($clubs->hasBehavior('CounterCache')) {
            if ($countries->getSchema()->hasColumn('club_count')) {
                $clubs->getBehavior('CounterCache')->updateCounterCache('Countries');
                $steps[] = 'Countries.club_count';
            }
            if ($cities->getSchema()->hasColumn('club_count')) {
                $clubs->getBehavior('CounterCache')->updateCounterCache('Cities');
                $steps[] = 'Cities.club_count';
            }
        }

        if ($cities->hasBehavior('CounterCache')) {
            $counties = $self->fetchTable('Counties');
            if ($counties->getSchema()->hasColumn('city_count')) {
                $cities->getBehavior('CounterCache')->updateCounterCache('Counties');
                $steps[] = 'Counties.city_count';
            }
        }

        $steps = array_merge($steps, self::rebuildCompetitionCounters());

        return $steps;
    }

    /**
     * Competitions + teams — includes closure fields Cake `updateCounterCache()` skips.
     *
     * @return list<string>
     */
    public static function rebuildCompetitionCounters(): array
    {
        $self = new self();
        /** @var \App\Model\Table\CompetitionsUsersTable $competitionsUsers */
        $competitionsUsers = $self->fetchTable('CompetitionsUsers');
        $competitions = $self->fetchTable('Competitions');
        $teams = $self->fetchTable('CompetitionsClubs');

        foreach ($competitions->find()->select(['id'])->all() as $competition) {
            $competitionId = (string)$competition->id;
            $assigned = $competitionsUsers->find()
                ->where([
                    'competition_id' => $competitionId,
                    'competition_club_id IS NOT' => null,
                    'status' => CompetitionApplication::STATUS_ASSIGNED,
                ])
                ->count();
            $attendants = $competitionsUsers->find()
                ->where([
                    'competition_id' => $competitionId,
                    'status IN' => CompetitionApplication::activeStatuses(),
                ])
                ->count();
            $query = $competitionsUsers->find();
            $lunchRow = $query
                ->select([
                    'total' => $query->func()->coalesce([
                        $query->func()->sum('lunch_for_the_attendant'),
                        0,
                    ]),
                ])
                ->where([
                    'competition_id' => $competitionId,
                    'status IN' => CompetitionApplication::activeStatuses(),
                ])
                ->disableHydration()
                ->first();
            $lunch = (int)($lunchRow['total'] ?? 0);

            $pipeQuery = $competitionsUsers->find();
            $pipeRow = $pipeQuery
                ->select([
                    'total' => $pipeQuery->expr(
                        'COALESCE(SUM('
                        . 'COALESCE(CompetitionsUsers.racing_pipe_1_qty, 0)'
                        . ' + COALESCE(CompetitionsUsers.racing_pipe_2_qty, 0)'
                        . ' + COALESCE(CompetitionsUsers.racing_pipe_3_qty, 0)'
                        . '), 0)'
                    ),
                ])
                ->where([
                    'competition_id' => $competitionId,
                    'status IN' => CompetitionApplication::activeStatuses(),
                ])
                ->disableHydration()
                ->first();
            $pipes = (int)($pipeRow['total'] ?? 0);

            $competitions->updateAll(
                [
                    'user_count' => $assigned,
                    'attendant_count' => $attendants,
                    'lunch_for_the_attendant' => $lunch,
                    'national_pipe_club_member_count' => $pipes,
                ],
                ['id' => $competitionId]
            );
        }

        foreach ($teams->find()->select(['id'])->all() as $team) {
            $count = $competitionsUsers->find()
                ->where([
                    'competition_club_id' => (int)$team->id,
                    'status' => CompetitionApplication::STATUS_ASSIGNED,
                ])
                ->count();
            $teams->updateAll(['user_count' => $count], ['id' => (int)$team->id]);
        }

        return [
            'Competitions.user_count',
            'Competitions.attendant_count',
            'Competitions.lunch_for_the_attendant',
            'Competitions.national_pipe_club_member_count',
            'CompetitionsClubs.user_count',
        ];
    }

    /**
     * Lunch SUM subquery for CounterCache closure (active applications).
     *
     * @param \Cake\ORM\Table $table CompetitionsUsers
     * @param mixed $competitionId
     * @return \Cake\ORM\Query\SelectQuery<\Cake\Datasource\EntityInterface>|false
     */
    public static function competitionLunchSumQuery(Table $table, mixed $competitionId): SelectQuery|false
    {
        if ($competitionId === null || $competitionId === '') {
            return false;
        }
        $query = $table->find();

        return $query
            ->select([
                'count' => $query->func()->coalesce([
                    $query->func()->sum('CompetitionsUsers.lunch_for_the_attendant'),
                    0,
                ]),
            ], true)
            ->where([
                'CompetitionsUsers.competition_id' => $competitionId,
                'CompetitionsUsers.status IN' => CompetitionApplication::activeStatuses(),
            ])
            ->orderBy([], true);
    }

    /**
     * Pipe qty SUM subquery for CounterCache closure (active applications).
     *
     * @param \Cake\ORM\Table $table CompetitionsUsers
     * @param mixed $competitionId
     * @return \Cake\ORM\Query\SelectQuery<\Cake\Datasource\EntityInterface>|false
     */
    public static function competitionPipeSumQuery(Table $table, mixed $competitionId): SelectQuery|false
    {
        if ($competitionId === null || $competitionId === '') {
            return false;
        }
        $query = $table->find();

        return $query
            ->select([
                'count' => $query->expr(
                    'COALESCE(SUM('
                    . 'COALESCE(CompetitionsUsers.racing_pipe_1_qty, 0)'
                    . ' + COALESCE(CompetitionsUsers.racing_pipe_2_qty, 0)'
                    . ' + COALESCE(CompetitionsUsers.racing_pipe_3_qty, 0)'
                    . '), 0)'
                ),
            ], true)
            ->where([
                'CompetitionsUsers.competition_id' => $competitionId,
                'CompetitionsUsers.status IN' => CompetitionApplication::activeStatuses(),
            ])
            ->orderBy([], true);
    }
}
