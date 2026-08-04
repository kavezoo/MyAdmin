<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class CreateLanguages extends BaseMigration
{
    public function up(): void
    {
        if ($this->hasTable('languages')) {
            return;
        }

        $this->table('languages')
            ->addColumn('code', 'string', [
                'default' => null,
                'limit' => 10,
                'null' => false,
            ])
            ->addColumn('name', 'string', [
                'default' => null,
                'limit' => 150,
                'null' => false,
            ])
            ->addColumn('visible', 'boolean', [
                'default' => true,
                'null' => false,
            ])
            ->addColumn('pos', 'integer', [
                'default' => 1000,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'null' => false,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'null' => false,
            ])
            ->addIndex(['code'], ['unique' => true])
            ->addIndex(['name'])
            ->addIndex(['visible'])
            ->addIndex(['pos'])
            ->create();
    }

    public function down(): void
    {
        if ($this->hasTable('languages')) {
            $this->table('languages')->drop()->save();
        }
    }
}
