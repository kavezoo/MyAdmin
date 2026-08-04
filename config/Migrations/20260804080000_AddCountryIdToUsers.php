<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class AddCountryIdToUsers extends BaseMigration
{
    public function change(): void
    {
        $this->table('users')
            ->addColumn('country_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
                'signed' => false,
                'after' => 'role',
            ])
            ->addIndex(['country_id'])
            ->addForeignKey('country_id', 'countries', 'id', [
                'delete' => 'SET_NULL',
                'update' => 'CASCADE',
            ])
            ->update();
    }
}
