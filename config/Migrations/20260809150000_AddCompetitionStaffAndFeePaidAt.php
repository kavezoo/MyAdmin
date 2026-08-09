<?php
declare(strict_types=1);

use Migrations\BaseMigration;

/**
 * Competition day staff (check-in / judge) + fee payment timestamp on applicants.
 */
class AddCompetitionStaffAndFeePaidAt extends BaseMigration
{
    public function up(): void
    {
        if (!$this->hasTable('competition_staff')) {
            $this->table('competition_staff')
                ->addColumn('competition_id', 'uuid', [
                    'null' => false,
                    'comment' => 'FK competitions.id',
                ])
                ->addColumn('user_id', 'uuid', [
                    'null' => false,
                    'comment' => 'FK users.id',
                ])
                ->addColumn('staff_role', 'string', [
                    'limit' => 20,
                    'null' => false,
                    'comment' => 'checkin|judge',
                ])
                ->addColumn('visible', 'boolean', [
                    'null' => false,
                    'default' => true,
                ])
                ->addColumn('pos', 'integer', [
                    'null' => false,
                    'default' => 1000,
                ])
                ->addColumn('created', 'datetime', ['null' => false])
                ->addColumn('modified', 'datetime', ['null' => false])
                ->addIndex(['competition_id', 'user_id', 'staff_role'], [
                    'unique' => true,
                    'name' => 'competition_staff_unique',
                ])
                ->addIndex(['user_id'], ['name' => 'competition_staff_user'])
                ->addIndex(['staff_role'], ['name' => 'competition_staff_role'])
                ->addIndex(['competition_id'], ['name' => 'competition_staff_competition'])
                ->create();
        }

        if ($this->hasTable('competitions_users')) {
            $table = $this->table('competitions_users');
            if (!$table->hasColumn('fee_paid_at')) {
                $table->addColumn('fee_paid_at', 'datetime', [
                    'null' => true,
                    'default' => null,
                    'after' => 'comment',
                    'comment' => 'Check-in: when entry fee was collected on site',
                ]);
                $table->update();
            }
        }
    }

    public function down(): void
    {
        if ($this->hasTable('competitions_users')) {
            $table = $this->table('competitions_users');
            if ($table->hasColumn('fee_paid_at')) {
                $table->removeColumn('fee_paid_at');
                $table->update();
            }
        }
        if ($this->hasTable('competition_staff')) {
            $this->table('competition_staff')->drop()->save();
        }
    }
}
