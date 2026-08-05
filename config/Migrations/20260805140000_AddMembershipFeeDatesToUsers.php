<?php
declare(strict_types=1);

use Migrations\BaseMigration;

/**
 * Membership fee payment dates (validity year = calendar year on date).
 */
class AddMembershipFeeDatesToUsers extends BaseMigration
{
    public function up(): void
    {
        if (!$this->hasTable('users')) {
            return;
        }

        $table = $this->table('users');
        if (!$table->hasColumn('club_membership_fee_date')) {
            $table
                ->addColumn('club_membership_fee_date', 'date', [
                    'null' => true,
                    'default' => null,
                    'comment' => 'Local club membership fee paid on (year on date = membership year)',
                    'after' => 'membership_status',
                ])
                ->addIndex(['club_membership_fee_date'])
                ->update();
        }

        $table = $this->table('users');
        if (!$table->hasColumn('national_membership_fee_date')) {
            $table
                ->addColumn('national_membership_fee_date', 'date', [
                    'null' => true,
                    'default' => null,
                    'comment' => 'National association fee paid on (e.g. MPE in Hungary)',
                    'after' => 'club_membership_fee_date',
                ])
                ->addIndex(['national_membership_fee_date'])
                ->update();
        }
    }

    public function down(): void
    {
        if (!$this->hasTable('users')) {
            return;
        }

        $table = $this->table('users');
        if ($table->hasColumn('national_membership_fee_date')) {
            $table->removeIndex(['national_membership_fee_date'])->update();
            $table->removeColumn('national_membership_fee_date')->update();
        }

        $table = $this->table('users');
        if ($table->hasColumn('club_membership_fee_date')) {
            $table->removeIndex(['club_membership_fee_date'])->update();
            $table->removeColumn('club_membership_fee_date')->update();
        }
    }
}
