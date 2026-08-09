<?php
declare(strict_types=1);

use Migrations\BaseMigration;

/**
 * Who recorded the result time at the judge desk (email from close API).
 */
class AddCompetitionsUsersResultRecordedByEmail extends BaseMigration
{
    public function up(): void
    {
        if (!$this->hasTable('competitions_users')) {
            return;
        }
        $table = $this->table('competitions_users');
        if ($table->hasColumn('result_recorded_by_email')) {
            return;
        }
        $table->addColumn('result_recorded_by_email', 'string', [
            'limit' => 255,
            'null' => true,
            'default' => null,
            'after' => 'result_time',
            'comment' => 'Email of judge/device that closed the competitor (POST /judge/close)',
        ]);
        $table->update();
    }

    public function down(): void
    {
        if (!$this->hasTable('competitions_users')) {
            return;
        }
        $table = $this->table('competitions_users');
        if (!$table->hasColumn('result_recorded_by_email')) {
            return;
        }
        $table->removeColumn('result_recorded_by_email');
        $table->update();
    }
}
