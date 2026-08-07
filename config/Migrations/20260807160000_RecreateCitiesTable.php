<?php
declare(strict_types=1);

use Migrations\BaseMigration;

/**
 * Recreate domain `cities` after DropDemoSamplesParentsCities wrongly dropped it
 * (demo table shared the name `cities` with the real settlements table).
 */
class RecreateCitiesTable extends BaseMigration
{
    public function up(): void
    {
        if ($this->hasTable('cities')) {
            return;
        }

        $this->table('cities', [
            'id' => false,
            'primary_key' => ['id'],
            'collation' => 'utf8mb4_general_ci',
            'comment' => 'Settlements / ZIP rows; clubs.city_id soft-references id',
        ])
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'limit' => 10,
                'signed' => false,
                'null' => false,
            ])
            ->addColumn('country_id', 'integer', [
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('county_id', 'integer', [
                'limit' => 10,
                'signed' => false,
                'null' => false,
                'default' => 0,
            ])
            ->addColumn('shortname', 'string', [
                'limit' => 10,
                'null' => false,
                'default' => '',
            ])
            ->addColumn('name', 'string', [
                'limit' => 255,
                'null' => false,
            ])
            ->addColumn('zip', 'string', [
                'limit' => 10,
                'null' => true,
                'default' => null,
            ])
            ->addColumn('lat', 'string', [
                'limit' => 20,
                'null' => false,
                'default' => '',
                'comment' => 'gmap',
            ])
            ->addColumn('lng', 'string', [
                'limit' => 20,
                'null' => false,
                'default' => '',
                'comment' => 'gmap',
            ])
            ->addColumn('lat2', 'string', [
                'limit' => 20,
                'null' => false,
                'default' => '',
                'comment' => 'import txt',
            ])
            ->addColumn('lng2', 'string', [
                'limit' => 20,
                'null' => false,
                'default' => '',
                'comment' => 'import txt',
            ])
            ->addIndex(['shortname'])
            ->addIndex(['country_id'])
            ->addIndex(['county_id'])
            ->addIndex(['name'])
            ->create();
    }

    public function down(): void
    {
        // Do not drop — domain table; may hold imported data.
    }
}
