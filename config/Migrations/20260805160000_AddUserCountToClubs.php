<?php
declare(strict_types=1);

use Migrations\BaseMigration;

/**
 * clubs.user_count — CounterCache from Users.club_id (before created).
 */
class AddUserCountToClubs extends BaseMigration
{
    public function up(): void
    {
        if (!$this->hasTable('clubs')) {
            return;
        }
        $table = $this->table('clubs');
        if ($table->hasColumn('user_count')) {
            return;
        }

        $table
            ->addColumn('user_count', 'integer', [
                'default' => 0,
                'null' => false,
                'signed' => false,
                'after' => 'pos',
                'comment' => 'CounterCache: users with this club_id',
            ])
            ->update();

        // Backfill from current users.club_id
        $this->execute(
            'UPDATE `clubs` `c`
             SET `c`.`user_count` = (
                 SELECT COUNT(*) FROM `users` `u` WHERE `u`.`club_id` = `c`.`id`
             )'
        );
    }

    public function down(): void
    {
        if (!$this->hasTable('clubs')) {
            return;
        }
        $table = $this->table('clubs');
        if (!$table->hasColumn('user_count')) {
            return;
        }

        $table->removeColumn('user_count')->update();
    }
}
