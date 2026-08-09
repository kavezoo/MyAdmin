<?php
declare(strict_types=1);

use Migrations\BaseMigration;

/**
 * Competition text templates keep only description (HTML) text — other fields belong on competitions.
 */
class SlimCompetitionTextTemplatesToDescription extends BaseMigration
{
    /**
     * @var list<string>
     */
    private const DROP_COLUMNS = [
        'name',
        'title',
        'subtitle',
        'subtitle2',
        'racing_pipe_1_title',
        'racing_pipe_2_title',
        'racing_pipe_3_title',
    ];

    public function up(): void
    {
        if ($this->hasTable('i18n')) {
            $fields = array_map(
                static fn (string $f): string => "'" . str_replace("'", "''", $f) . "'",
                self::DROP_COLUMNS,
            );
            $this->execute(
                'DELETE FROM i18n WHERE model = \'CompetitionTextTemplates\' AND field IN ('
                . implode(', ', $fields) . ')'
            );
        }

        if (!$this->hasTable('competition_text_templates')) {
            return;
        }

        $table = $this->table('competition_text_templates');
        foreach (self::DROP_COLUMNS as $column) {
            if ($table->hasColumn($column)) {
                $table->removeColumn($column);
            }
        }
        $table->update();
    }

    public function down(): void
    {
        if (!$this->hasTable('competition_text_templates')) {
            return;
        }

        $table = $this->table('competition_text_templates');
        if (!$table->hasColumn('name')) {
            $table->addColumn('name', 'string', [
                'limit' => 250,
                'null' => false,
                'default' => '',
                'after' => 'label',
            ]);
        }
        if (!$table->hasColumn('title')) {
            $table->addColumn('title', 'string', [
                'limit' => 250,
                'null' => false,
                'default' => '',
                'after' => 'name',
            ]);
        }
        if (!$table->hasColumn('subtitle')) {
            $table->addColumn('subtitle', 'string', [
                'limit' => 250,
                'null' => false,
                'default' => '',
                'after' => 'title',
            ]);
        }
        if (!$table->hasColumn('subtitle2')) {
            $table->addColumn('subtitle2', 'string', [
                'limit' => 250,
                'null' => false,
                'default' => '',
                'after' => 'subtitle',
            ]);
        }
        if (!$table->hasColumn('racing_pipe_1_title')) {
            $table->addColumn('racing_pipe_1_title', 'string', [
                'limit' => 250,
                'null' => false,
                'default' => '',
                'after' => 'description',
            ]);
        }
        if (!$table->hasColumn('racing_pipe_2_title')) {
            $table->addColumn('racing_pipe_2_title', 'string', [
                'limit' => 250,
                'null' => false,
                'default' => '',
                'after' => 'racing_pipe_1_title',
            ]);
        }
        if (!$table->hasColumn('racing_pipe_3_title')) {
            $table->addColumn('racing_pipe_3_title', 'string', [
                'limit' => 250,
                'null' => false,
                'default' => '',
                'after' => 'racing_pipe_2_title',
            ]);
        }
        $table->update();
    }
}
