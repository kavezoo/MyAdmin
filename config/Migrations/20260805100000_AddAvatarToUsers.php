<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class AddAvatarToUsers extends BaseMigration
{
    public function up(): void
    {
        if (!$this->hasTable('users')) {
            return;
        }
        $table = $this->table('users');
        if ($table->hasColumn('avatar')) {
            return;
        }

        $table
            ->addColumn('avatar', 'string', [
                'after' => 'phone',
                'default' => null,
                'limit' => 255,
                'null' => true,
                'comment' => 'Web path to profile picture (uploads/avatars/)',
            ])
            ->update();
    }

    public function down(): void
    {
        if (!$this->hasTable('users')) {
            return;
        }
        $table = $this->table('users');
        if (!$table->hasColumn('avatar')) {
            return;
        }
        $table->removeColumn('avatar')->update();
    }
}
