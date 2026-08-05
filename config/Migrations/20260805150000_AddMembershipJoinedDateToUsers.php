<?php
declare(strict_types=1);

use Migrations\BaseMigration;

/**
 * Membership join date — non-null = application accepted / member since that day.
 */
class AddMembershipJoinedDateToUsers extends BaseMigration
{
    public function up(): void
    {
        if (!$this->hasTable('users')) {
            return;
        }

        $table = $this->table('users');
        if (!$table->hasColumn('membership_joined_date')) {
            $table
                ->addColumn('membership_joined_date', 'date', [
                    'null' => true,
                    'default' => null,
                    'comment' => 'Member since (non-null = application approved)',
                    'after' => 'membership_status',
                ])
                ->addIndex(['membership_joined_date'])
                ->update();
        }

        // Backfill existing approved members (best-effort from modified date).
        $this->execute(
            "UPDATE users
             SET membership_joined_date = DATE(COALESCE(modified, created, CURDATE()))
             WHERE role = 'member'
               AND membership_status = 'approved'
               AND membership_joined_date IS NULL"
        );
    }

    public function down(): void
    {
        if (!$this->hasTable('users')) {
            return;
        }

        $table = $this->table('users');
        if ($table->hasColumn('membership_joined_date')) {
            $table->removeIndex(['membership_joined_date'])->update();
            $table->removeColumn('membership_joined_date')->update();
        }
    }
}
