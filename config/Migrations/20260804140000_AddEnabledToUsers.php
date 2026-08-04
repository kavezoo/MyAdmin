<?php
declare(strict_types=1);

use Migrations\BaseMigration;

/**
 * users.enabled — admin/president lock-out (separate from CakeDC active / activation).
 * Idempotent: skips if column already exists.
 */
class AddEnabledToUsers extends BaseMigration
{
    public function up(): void
    {
        $table = $this->table('users');
        if ($table->hasColumn('enabled')) {
            return;
        }

        $table
            ->addColumn('enabled', 'boolean', [
                'default' => true,
                'null' => false,
                'limit' => 1,
                'signed' => false,
                'after' => 'active',
                'comment' => 'Admin/president lock-out; login requires enabled=1 (with active=1)',
            ])
            ->addIndex(['enabled'], ['name' => 'enabled'])
            ->update();
    }

    public function down(): void
    {
        $table = $this->table('users');
        if (!$table->hasColumn('enabled')) {
            return;
        }

        $table->removeIndexByName('enabled')->removeColumn('enabled')->update();
    }
}
