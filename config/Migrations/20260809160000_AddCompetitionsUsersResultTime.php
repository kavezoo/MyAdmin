<?php
declare(strict_types=1);

use Migrations\BaseMigration;

/**
 * Applicant result time (seconds) for judge / mobile API.
 */
class AddCompetitionsUsersResultTime extends BaseMigration
{
    public function up(): void
    {
        if (!$this->hasTable('competitions_users')) {
            return;
        }
        $table = $this->table('competitions_users');
        if ($table->hasColumn('result_time')) {
            return;
        }
        $table->addColumn('result_time', 'decimal', [
            'precision' => 12,
            'scale' => 3,
            'null' => true,
            'default' => null,
            'after' => 'fee_paid_at',
            'comment' => 'Achieved time in seconds (judge / Flutter API)',
        ]);
        $table->update();
    }

    public function down(): void
    {
        if (!$this->hasTable('competitions_users')) {
            return;
        }
        $table = $this->table('competitions_users');
        if ($table->hasColumn('result_time')) {
            $table->removeColumn('result_time');
            $table->update();
        }
    }
}
