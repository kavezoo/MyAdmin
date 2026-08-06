<?php
declare(strict_types=1);

use Migrations\BaseMigration;

/**
 * Designated club president user id (may keep a higher role e.g. president / vp).
 */
class AddClubPresidentIdToClubs extends BaseMigration
{
    public function up(): void
    {
        if (!$this->hasTable('clubs')) {
            return;
        }

        $table = $this->table('clubs');
        if (!$table->hasColumn('club_president_id')) {
            $table
                ->addColumn('club_president_id', 'uuid', [
                    'null' => true,
                    'default' => null,
                    'comment' => 'Designated club president (Users.id); role may stay president/vp',
                    'after' => 'user_count',
                ])
                ->addIndex(['club_president_id'])
                ->update();
        }

        // Backfill from current role=clubpresident + club_id rows.
        $this->execute(
            'UPDATE clubs c
             INNER JOIN (
                 SELECT u.club_id, u.id AS user_id
                 FROM users u
                 INNER JOIN (
                     SELECT club_id, MAX(modified) AS max_modified
                     FROM users
                     WHERE role = \'clubpresident\' AND club_id > 0
                     GROUP BY club_id
                 ) latest ON latest.club_id = u.club_id AND latest.max_modified = u.modified
                 WHERE u.role = \'clubpresident\' AND u.club_id > 0
             ) src ON src.club_id = c.id
             SET c.club_president_id = src.user_id
             WHERE c.club_president_id IS NULL'
        );
    }

    public function down(): void
    {
        if (!$this->hasTable('clubs')) {
            return;
        }

        $table = $this->table('clubs');
        if ($table->hasColumn('club_president_id')) {
            $table->removeIndex(['club_president_id'])->update();
            $table->removeColumn('club_president_id')->update();
        }
    }
}
