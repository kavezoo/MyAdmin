<?php
declare(strict_types=1);

use Migrations\BaseMigration;

/**
 * Club logo image path (webroot-relative), similar to users.avatar.
 */
class AddLogoToClubs extends BaseMigration
{
    public function up(): void
    {
        if (!$this->hasTable('clubs')) {
            return;
        }

        $table = $this->table('clubs');
        if (!$table->hasColumn('logo')) {
            $table
                ->addColumn('logo', 'string', [
                    'limit' => 255,
                    'null' => true,
                    'default' => null,
                    'comment' => 'Club logo path (uploads/clubs/{id}.jpg)',
                    'after' => 'short_name',
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
        if ($table->hasColumn('logo')) {
            $table->removeColumn('logo')->update();
        }
    }
}
