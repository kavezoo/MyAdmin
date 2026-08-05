<?php
declare(strict_types=1);

use Migrations\BaseMigration;

/**
 * Club annual fee paid to the national association (year on date = membership year).
 */
class AddNationalMembershipFeeDateToClubs extends BaseMigration
{
    public function up(): void
    {
        if (!$this->hasTable('clubs')) {
            return;
        }

        $table = $this->table('clubs');
        if (!$table->hasColumn('national_membership_fee_date')) {
            $table
                ->addColumn('national_membership_fee_date', 'date', [
                    'null' => true,
                    'default' => null,
                    'comment' => 'National association club fee paid on (year on date = membership year)',
                    'after' => 'user_count',
                ])
                ->addIndex(['national_membership_fee_date'])
                ->update();
        }
    }

    public function down(): void
    {
        if (!$this->hasTable('clubs')) {
            return;
        }

        $table = $this->table('clubs');
        if ($table->hasColumn('national_membership_fee_date')) {
            $table->removeIndex(['national_membership_fee_date'])->update();
            $table->removeColumn('national_membership_fee_date')->update();
        }
    }
}
