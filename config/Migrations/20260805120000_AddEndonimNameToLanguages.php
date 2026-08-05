<?php
declare(strict_types=1);

use Migrations\BaseMigration;

/**
 * languages.endonim_name — required by AdminLanguage / login select.
 */
class AddEndonimNameToLanguages extends BaseMigration
{
    public function up(): void
    {
        if (!$this->hasTable('languages')) {
            return;
        }

        $table = $this->table('languages');
        if ($table->hasColumn('endonim_name')) {
            return;
        }

        $table
            ->addColumn('endonim_name', 'string', [
                'default' => '',
                'limit' => 150,
                'null' => false,
                'after' => 'name',
            ])
            ->addIndex(['endonim_name'])
            ->update();
    }

    public function down(): void
    {
        if (!$this->hasTable('languages')) {
            return;
        }

        $table = $this->table('languages');
        if (!$table->hasColumn('endonim_name')) {
            return;
        }

        $table->removeIndex(['endonim_name'])->update();
        $table->removeColumn('endonim_name')->update();
    }
}
