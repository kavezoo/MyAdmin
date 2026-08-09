<?php
declare(strict_types=1);

use Migrations\BaseMigration;

/**
 * Snapshot entry fee + pipe line fees + total on competitions_users (check-in / billing).
 */
class AddCompetitionsUsersFeeAmounts extends BaseMigration
{
    public function up(): void
    {
        if (!$this->hasTable('competitions_users')) {
            return;
        }
        $table = $this->table('competitions_users');
        $cols = [
            'entry_fee_amount' => [
                'precision' => 12,
                'scale' => 2,
                'null' => false,
                'default' => '0.00',
                'after' => 'fee_paid_at',
                'comment' => 'Entry fee charged (member/non-member rate at snapshot)',
            ],
            'racing_pipe_1_fee' => [
                'precision' => 12,
                'scale' => 2,
                'null' => false,
                'default' => '0.00',
                'after' => 'entry_fee_amount',
                'comment' => 'Pipe 1 line total (qty × unit)',
            ],
            'racing_pipe_2_fee' => [
                'precision' => 12,
                'scale' => 2,
                'null' => false,
                'default' => '0.00',
                'after' => 'racing_pipe_1_fee',
            ],
            'racing_pipe_3_fee' => [
                'precision' => 12,
                'scale' => 2,
                'null' => false,
                'default' => '0.00',
                'after' => 'racing_pipe_2_fee',
            ],
            'fee_total' => [
                'precision' => 12,
                'scale' => 2,
                'null' => false,
                'default' => '0.00',
                'after' => 'racing_pipe_3_fee',
                'comment' => 'Sum to pay: entry + pipe line fees',
            ],
        ];
        foreach ($cols as $name => $options) {
            if (!$table->hasColumn($name)) {
                $table->addColumn($name, 'decimal', $options);
            }
        }
        $table->update();
    }

    public function down(): void
    {
        if (!$this->hasTable('competitions_users')) {
            return;
        }
        $table = $this->table('competitions_users');
        foreach (['fee_total', 'racing_pipe_3_fee', 'racing_pipe_2_fee', 'racing_pipe_1_fee', 'entry_fee_amount'] as $col) {
            if ($table->hasColumn($col)) {
                $table->removeColumn($col);
            }
        }
        $table->update();
    }
}
