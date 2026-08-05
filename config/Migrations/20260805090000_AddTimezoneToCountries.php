<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class AddTimezoneToCountries extends BaseMigration
{
    public function up(): void
    {
        if (!$this->hasTable('countries')) {
            return;
        }
        $table = $this->table('countries');
        if ($table->hasColumn('timezone')) {
            return;
        }

        $table
            ->addColumn('timezone', 'string', [
                'after' => 'locale',
                'default' => 'UTC',
                'limit' => 64,
                'null' => false,
                'comment' => 'IANA timezone e.g. Europe/Budapest',
            ])
            ->addIndex(['timezone'])
            ->update();
    }

    public function down(): void
    {
        if (!$this->hasTable('countries')) {
            return;
        }
        $table = $this->table('countries');
        if (!$table->hasColumn('timezone')) {
            return;
        }
        $table->removeColumn('timezone')->update();
    }
}
