<?php
declare(strict_types=1);

use Migrations\BaseMigration;

/**
 * Who collected the on-site fee at check-in (Users.id).
 */
class AddCompetitionsUsersFeePaidBy extends BaseMigration
{
    public function up(): void
    {
        if (!$this->hasTable('competitions_users')) {
            return;
        }
        $table = $this->table('competitions_users');
        if ($table->hasColumn('fee_paid_by')) {
            return;
        }
        $table->addColumn('fee_paid_by', 'uuid', [
            'null' => true,
            'default' => null,
            'after' => 'fee_paid_at',
            'comment' => 'Check-in user (Users.id) who marked fee paid',
        ]);
        $table->addIndex(['fee_paid_by'], ['name' => 'competitions_users_fee_paid_by']);
        $table->update();
    }

    public function down(): void
    {
        if (!$this->hasTable('competitions_users')) {
            return;
        }
        $table = $this->table('competitions_users');
        if (!$table->hasColumn('fee_paid_by')) {
            return;
        }
        $table->removeIndexByName('competitions_users_fee_paid_by');
        $table->removeColumn('fee_paid_by');
        $table->update();
    }
}
