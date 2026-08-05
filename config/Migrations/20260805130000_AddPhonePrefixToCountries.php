<?php
declare(strict_types=1);

use Migrations\BaseMigration;

/**
 * countries.phone_prefix — E.164 calling prefix for user phone inputs (e.g. +36).
 */
class AddPhonePrefixToCountries extends BaseMigration
{
    public function up(): void
    {
        if (!$this->hasTable('countries')) {
            return;
        }

        $table = $this->table('countries');
        if ($table->hasColumn('phone_prefix')) {
            return;
        }

        $table
            ->addColumn('phone_prefix', 'string', [
                'default' => '',
                'limit' => 16,
                'null' => false,
                'after' => 'timezone',
                'comment' => 'E.164 calling prefix e.g. +36',
            ])
            ->addIndex(['phone_prefix'])
            ->update();
    }

    public function down(): void
    {
        if (!$this->hasTable('countries')) {
            return;
        }

        $table = $this->table('countries');
        if (!$table->hasColumn('phone_prefix')) {
            return;
        }

        $table->removeIndex(['phone_prefix'])->update();
        $table->removeColumn('phone_prefix')->update();
    }
}
