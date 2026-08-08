<?php
declare(strict_types=1);

use Migrations\BaseMigration;

/**
 * Competitions schema fixes for president / clubpresident / member flows.
 *
 * - competitions: counter + empty-string defaults
 * - competitions_clubs: team name; nullable acceptance; user_count default
 * - competitions_users: competition_id + status + result fields + unique applicant
 * - subclubs: unsigned club_id + visible/pos defaults
 */
class AlterCompetitionsSchema extends BaseMigration
{
    public function up(): void
    {
        if ($this->hasTable('competitions')) {
            $this->execute(
                "ALTER TABLE `competitions`
                    MODIFY `subtitle` varchar(250) NOT NULL DEFAULT '',
                    MODIFY `subtitle2` varchar(250) NOT NULL DEFAULT '',
                    MODIFY `special_lunch` varchar(250) NOT NULL DEFAULT '',
                    MODIFY `racing_pipe_1_title` varchar(250) NOT NULL DEFAULT '',
                    MODIFY `racing_pipe_1_count` int(10) unsigned NOT NULL DEFAULT 0,
                    MODIFY `racing_pipe_2_title` varchar(250) NOT NULL DEFAULT '',
                    MODIFY `racing_pipe_2_count` int(10) unsigned NOT NULL DEFAULT 0,
                    MODIFY `racing_pipe_3_title` varchar(250) NOT NULL DEFAULT '',
                    MODIFY `racing_pipe_3_count` int(10) unsigned NOT NULL DEFAULT 0,
                    MODIFY `user_count` int(10) unsigned NOT NULL DEFAULT 0 COMMENT 'CounterCache: applicants',
                    MODIFY `national_pipe_club_member_count` int(10) unsigned NOT NULL DEFAULT 0,
                    MODIFY `attendant_count` int(10) unsigned NOT NULL DEFAULT 0 COMMENT 'Kísérők száma',
                    MODIFY `national_competition` tinyint(1) unsigned NOT NULL DEFAULT 0 COMMENT 'Országos verseny'"
            );
        }

        if ($this->hasTable('competitions_clubs')) {
            $cc = $this->table('competitions_clubs');
            if (!$cc->hasColumn('name')) {
                $cc->addColumn('name', 'string', [
                    'limit' => 250,
                    'null' => false,
                    'default' => '',
                    'after' => 'competition_id',
                    'comment' => 'Alcsapat / team display name',
                ])->update();
            }
            $this->execute(
                "ALTER TABLE `competitions_clubs`
                    MODIFY `date_of_application_acceptance` datetime NULL DEFAULT NULL,
                    MODIFY `application_datetime` datetime NULL DEFAULT NULL,
                    MODIFY `user_count` int(10) unsigned NOT NULL DEFAULT 0 COMMENT 'CounterCache: team members',
                    MODIFY `subclub_id` int(10) unsigned NULL DEFAULT NULL"
            );
            try {
                $this->table('competitions_clubs')
                    ->addIndex(
                        ['competition_id', 'club_id', 'name'],
                        ['unique' => true, 'name' => 'competitions_clubs_comp_club_name']
                    )
                    ->update();
            } catch (\Throwable $e) {
                // index may already exist
            }
        }

        if ($this->hasTable('competitions_users')) {
            $cu = $this->table('competitions_users');
            if (!$cu->hasColumn('competition_id')) {
                $cu->addColumn('competition_id', 'string', [
                    'limit' => 36,
                    'null' => false,
                    'default' => '',
                    'after' => 'id',
                    'comment' => 'FK → competitions.id',
                ])->update();
            }
            if (!$cu->hasColumn('status')) {
                $cu->addColumn('status', 'string', [
                    'limit' => 20,
                    'null' => false,
                    'default' => 'pending',
                    'after' => 'competition_club_id',
                    'comment' => 'pending|assigned|withdrawn|invalid',
                ])->update();
            }
            if (!$cu->hasColumn('result_rank')) {
                $cu->addColumn('result_rank', 'integer', [
                    'null' => true,
                    'default' => null,
                    'signed' => false,
                    'after' => 'comment',
                    'comment' => 'Archive: placement',
                ])->update();
            }
            if (!$cu->hasColumn('result_score')) {
                $cu->addColumn('result_score', 'string', [
                    'limit' => 50,
                    'null' => true,
                    'default' => null,
                    'after' => 'result_rank',
                ])->update();
            }
            if (!$cu->hasColumn('result_note')) {
                $cu->addColumn('result_note', 'string', [
                    'limit' => 250,
                    'null' => true,
                    'default' => null,
                    'after' => 'result_score',
                ])->update();
            }
            $this->execute(
                "ALTER TABLE `competitions_users`
                    MODIFY `competition_club_id` int(10) unsigned NULL DEFAULT NULL
                        COMMENT 'Assigned team (competitions_clubs.id); NULL = not assigned yet'"
            );
            try {
                $this->table('competitions_users')
                    ->addIndex(['competition_id'], ['name' => 'competitions_users_competition_id'])
                    ->addIndex(
                        ['competition_id', 'user_id'],
                        ['unique' => true, 'name' => 'competitions_users_comp_user']
                    )
                    ->addIndex(['status'], ['name' => 'competitions_users_status'])
                    ->update();
            } catch (\Throwable $e) {
                // indexes may already exist
            }
        }

        if ($this->hasTable('subclubs')) {
            $this->execute(
                "ALTER TABLE `subclubs`
                    MODIFY `club_id` int(10) unsigned NOT NULL,
                    MODIFY `visible` tinyint(1) unsigned NOT NULL DEFAULT 1,
                    MODIFY `pos` int(11) NOT NULL DEFAULT 1000"
            );
        }
    }

    public function down(): void
    {
        // Non-destructive rollback omitted — forward-only competition schema.
    }
}
