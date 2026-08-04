<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class CreateEventLogs extends BaseMigration
{
    public function up(): void
    {
        if ($this->hasTable('event_logs')) {
            return;
        }

        $this->table('event_logs', ['id' => false, 'primary_key' => ['id']])
            ->addColumn('id', 'biginteger', [
                'autoIncrement' => true,
                'signed' => false,
                'null' => false,
            ])
            ->addColumn('country_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
                'signed' => false,
            ])
            ->addColumn('user_id', 'uuid', [
                'default' => null,
                'null' => true,
            ])
            ->addColumn('actor_role', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => true,
            ])
            ->addColumn('module', 'string', [
                'default' => null,
                'limit' => 100,
                'null' => false,
            ])
            ->addColumn('action', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => false,
            ])
            ->addColumn('entity', 'string', [
                'default' => null,
                'limit' => 100,
                'null' => true,
            ])
            ->addColumn('entity_id', 'string', [
                'default' => null,
                'limit' => 64,
                'null' => true,
            ])
            ->addColumn('description', 'string', [
                'default' => null,
                'limit' => 500,
                'null' => true,
            ])
            ->addColumn('url', 'string', [
                'default' => null,
                'limit' => 500,
                'null' => true,
            ])
            ->addColumn('http_method', 'string', [
                'default' => null,
                'limit' => 10,
                'null' => true,
            ])
            ->addColumn('ip', 'string', [
                'default' => null,
                'limit' => 45,
                'null' => true,
            ])
            ->addColumn('user_agent', 'string', [
                'default' => null,
                'limit' => 500,
                'null' => true,
            ])
            ->addColumn('request_data', 'text', [
                'default' => null,
                'null' => true,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'null' => false,
            ])
            ->addIndex(['country_id'])
            ->addIndex(['user_id'])
            ->addIndex(['module'])
            ->addIndex(['action'])
            ->addIndex(['entity', 'entity_id'], ['name' => 'entity_entity_id'])
            ->addIndex(['created'])
            ->addIndex(['country_id', 'created'], ['name' => 'country_created'])
            ->addIndex(['user_id', 'created'], ['name' => 'user_created'])
            ->addForeignKey('country_id', 'countries', 'id', [
                'delete' => 'SET_NULL',
                'update' => 'CASCADE',
            ])
            ->addForeignKey('user_id', 'users', 'id', [
                'delete' => 'SET_NULL',
                'update' => 'CASCADE',
            ])
            ->create();
    }

    public function down(): void
    {
        if ($this->hasTable('event_logs')) {
            $this->table('event_logs')->drop()->save();
        }
    }
}
