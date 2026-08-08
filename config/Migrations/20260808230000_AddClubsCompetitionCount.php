<?php
declare(strict_types=1);

use Migrations\BaseMigration;

/**
 * clubs.competition_count — CounterCache from competitions.club_id
 * (after user_count). Organizer clubs for competitions must have national fee paid.
 */
class AddClubsCompetitionCount extends BaseMigration
{
    public function up(): void
    {
        if (!$this->hasTable('clubs')) {
            return;
        }

        $table = $this->table('clubs');
        if (!$table->hasColumn('competition_count')) {
            $table
                ->addColumn('competition_count', 'integer', [
                    'limit' => 10,
                    'signed' => false,
                    'null' => false,
                    'default' => 0,
                    'after' => 'user_count',
                    'comment' => 'CounterCache: competitions hosted by this club',
                ])
                ->update();
        }
    }

    public function down(): void
    {
        if (!$this->hasTable('clubs')) {
            return;
        }
        $table = $this->table('clubs');
        if ($table->hasColumn('competition_count')) {
            $table->removeColumn('competition_count')->update();
        }
    }
}
