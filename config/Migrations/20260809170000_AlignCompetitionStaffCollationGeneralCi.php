<?php
declare(strict_types=1);

use Migrations\BaseMigration;

/**
 * competition_staff UUID columns were created as utf8mb4_hungarian_ci (DB default),
 * while competitions.id / users.id are utf8mb4_general_ci → JOIN fails.
 */
class AlignCompetitionStaffCollationGeneralCi extends BaseMigration
{
    public function up(): void
    {
        if (!$this->hasTable('competition_staff')) {
            return;
        }
        $this->execute(
            'ALTER TABLE `competition_staff`
             MODIFY `competition_id` CHAR(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
             MODIFY `user_id` CHAR(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL'
        );
        $this->execute(
            'ALTER TABLE `competition_staff` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci'
        );
    }

    public function down(): void
    {
        // no-op — hungarian_ci was unintentional
    }
}
