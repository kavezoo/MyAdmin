<?php
declare(strict_types=1);

use Migrations\BaseMigration;

/**
 * competitions: drop summary/per-member fields that belong only on applications.
 * Keep racing_pipe_*_title labels; lunch_for_the_attendant stays as sum counter (DEFAULT 0).
 */
class DropCompetitionSummaryColumns extends BaseMigration
{
    public function up(): void
    {
        if (!$this->hasTable('competitions')) {
            return;
        }

        $table = $this->table('competitions');
        foreach (['special_lunch', 'racing_pipe_1_count', 'racing_pipe_2_count', 'racing_pipe_3_count'] as $col) {
            if ($table->hasColumn($col)) {
                $table->removeColumn($col);
            }
        }
        $table->update();
    }

    public function down(): void
    {
        if (!$this->hasTable('competitions')) {
            return;
        }

        $table = $this->table('competitions');
        if (!$table->hasColumn('special_lunch')) {
            $table->addColumn('special_lunch', 'string', [
                'limit' => 250,
                'null' => false,
                'default' => '',
                'after' => 'lunch_for_the_attendant',
            ]);
        }
        foreach ([1, 2, 3] as $i) {
            $count = 'racing_pipe_' . $i . '_count';
            $title = 'racing_pipe_' . $i . '_title';
            if (!$table->hasColumn($count)) {
                $table->addColumn($count, 'integer', [
                    'null' => false,
                    'default' => 0,
                    'signed' => false,
                    'after' => $title,
                ]);
            }
        }
        $table->update();
    }
}
