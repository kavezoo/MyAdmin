<?php
declare(strict_types=1);

use Migrations\BaseMigration;

/**
 * Competition announcement: pipe type + parameters, tobacco type + weight (g).
 */
class AddCompetitionPipeAndTobaccoFields extends BaseMigration
{
    public function up(): void
    {
        if (!$this->hasTable('competitions')) {
            return;
        }

        $table = $this->table('competitions');
        if (!$table->hasColumn('pipe_type')) {
            $table->addColumn('pipe_type', 'string', [
                'limit' => 250,
                'null' => false,
                'default' => '',
                'after' => 'racing_pipe_3_title',
                'comment' => 'Competition pipe type (announcement)',
            ]);
        }
        if (!$table->hasColumn('pipe_parameters')) {
            $table->addColumn('pipe_parameters', 'string', [
                'limit' => 500,
                'null' => false,
                'default' => '',
                'after' => 'pipe_type',
                'comment' => 'Pipe parameters (announcement)',
            ]);
        }
        if (!$table->hasColumn('tobacco_type')) {
            $table->addColumn('tobacco_type', 'string', [
                'limit' => 250,
                'null' => false,
                'default' => '',
                'after' => 'pipe_parameters',
                'comment' => 'Competition tobacco type (announcement)',
            ]);
        }
        if (!$table->hasColumn('tobacco_weight')) {
            $table->addColumn('tobacco_weight', 'decimal', [
                'precision' => 10,
                'scale' => 2,
                'null' => false,
                'default' => '0.00',
                'after' => 'tobacco_type',
                'comment' => 'Tobacco weight in grams',
            ]);
        }
        $table->update();
    }

    public function down(): void
    {
        if (!$this->hasTable('competitions')) {
            return;
        }

        $table = $this->table('competitions');
        foreach (['tobacco_weight', 'tobacco_type', 'pipe_parameters', 'pipe_type'] as $column) {
            if ($table->hasColumn($column)) {
                $table->removeColumn($column);
            }
        }
        $table->update();

        if ($this->hasTable('i18n')) {
            $this->execute(
                "DELETE FROM i18n WHERE model = 'Competitions' AND field IN ('pipe_type','pipe_parameters','tobacco_type')"
            );
        }
    }
}
