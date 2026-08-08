<?php
declare(strict_types=1);

use Migrations\BaseMigration;

/**
 * countries.setup_count — CounterCache from setups.country_id (after club_count).
 */
class AddCountriesSetupCount extends BaseMigration
{
    public function up(): void
    {
        if (!$this->hasTable('countries')) {
            return;
        }

        $table = $this->table('countries');
        if (!$table->hasColumn('setup_count')) {
            $table
                ->addColumn('setup_count', 'integer', [
                    'limit' => 10,
                    'signed' => false,
                    'null' => false,
                    'default' => 0,
                    'after' => 'club_count',
                    'comment' => 'CounterCache: setups in this country',
                ])
                ->update();
        }
    }

    public function down(): void
    {
        if (!$this->hasTable('countries')) {
            return;
        }
        $table = $this->table('countries');
        if ($table->hasColumn('setup_count')) {
            $table->removeColumn('setup_count')->update();
        }
    }
}
