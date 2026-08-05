<?php
declare(strict_types=1);

use Migrations\BaseMigration;

/**
 * clubs.enabled — profile/register club select (with visible + pos).
 */
class AddEnabledToClubs extends BaseMigration
{
    public function up(): void
    {
        if (!$this->hasTable('clubs')) {
            return;
        }
        $table = $this->table('clubs');
        if ($table->hasColumn('enabled')) {
            return;
        }

        $table
            ->addColumn('enabled', 'boolean', [
                'default' => true,
                'null' => false,
                'limit' => 1,
                'signed' => false,
                'after' => 'name',
                'comment' => '0 = not selectable on profile / complete-profile',
            ])
            ->addIndex(['enabled'], ['name' => 'enabled'])
            ->update();
    }

    public function down(): void
    {
        if (!$this->hasTable('clubs')) {
            return;
        }
        $table = $this->table('clubs');
        if (!$table->hasColumn('enabled')) {
            return;
        }

        $table->removeIndexByName('enabled')->removeColumn('enabled')->update();
    }
}
