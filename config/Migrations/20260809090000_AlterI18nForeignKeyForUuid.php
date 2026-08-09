<?php
declare(strict_types=1);

use Migrations\BaseMigration;

/**
 * i18n.foreign_key: INT → VARCHAR(36) so Translate EAV works with UUID PKs
 * (Competitions) as well as integer PKs (Countries, Continents, Languages, Setups).
 */
class AlterI18nForeignKeyForUuid extends BaseMigration
{
    public function up(): void
    {
        if (!$this->hasTable('i18n')) {
            return;
        }

        $table = $this->table('i18n');
        if (!$table->hasColumn('foreign_key')) {
            return;
        }

        // Drop unique + field indexes that include foreign_key, alter column, recreate.
        $table
            ->removeIndexByName('I18N_LOCALE_FIELD')
            ->removeIndexByName('I18N_FIELD')
            ->update();

        $table
            ->changeColumn('foreign_key', 'string', [
                'limit' => 36,
                'null' => false,
                'default' => null,
                'comment' => 'Parent PK (int as string or UUID)',
            ])
            ->update();

        $table
            ->addIndex(['locale', 'model', 'foreign_key', 'field'], [
                'name' => 'I18N_LOCALE_FIELD',
                'unique' => true,
            ])
            ->addIndex(['model', 'foreign_key', 'field'], [
                'name' => 'I18N_FIELD',
            ])
            ->update();
    }

    public function down(): void
    {
        if (!$this->hasTable('i18n')) {
            return;
        }

        $table = $this->table('i18n');
        if (!$table->hasColumn('foreign_key')) {
            return;
        }

        // Only safe if no UUID foreign_keys exist.
        $table
            ->removeIndexByName('I18N_LOCALE_FIELD')
            ->removeIndexByName('I18N_FIELD')
            ->update();

        $table
            ->changeColumn('foreign_key', 'integer', [
                'limit' => 11,
                'null' => false,
                'default' => null,
            ])
            ->update();

        $table
            ->addIndex(['locale', 'model', 'foreign_key', 'field'], [
                'name' => 'I18N_LOCALE_FIELD',
                'unique' => true,
            ])
            ->addIndex(['model', 'foreign_key', 'field'], [
                'name' => 'I18N_FIELD',
            ])
            ->update();
    }
}
