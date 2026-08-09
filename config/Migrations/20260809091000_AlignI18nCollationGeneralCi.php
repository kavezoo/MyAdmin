<?php
declare(strict_types=1);

use Migrations\BaseMigration;

/**
 * Align i18n collation with the rest of the app (utf8mb4_general_ci).
 * Fixes UUID joins: i18n.foreign_key = competitions.id (was unicode_ci vs general_ci).
 */
class AlignI18nCollationGeneralCi extends BaseMigration
{
    public function up(): void
    {
        if (!$this->hasTable('i18n')) {
            return;
        }

        $this->execute(
            'ALTER TABLE `i18n` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci'
        );
    }

    public function down(): void
    {
        if (!$this->hasTable('i18n')) {
            return;
        }

        $this->execute(
            'ALTER TABLE `i18n` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci'
        );
    }
}
