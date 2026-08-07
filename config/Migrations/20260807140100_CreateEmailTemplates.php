<?php
declare(strict_types=1);

use Migrations\BaseMigration;

/**
 * Per-language email templates (subject + HTML/text body).
 */
class CreateEmailTemplates extends BaseMigration
{
    public function change(): void
    {
        $this->table('email_templates')
            ->addColumn('language_id', 'integer', [
                'null' => false,
                'signed' => true,
                'comment' => 'FK → languages.id',
            ])
            ->addColumn('slug', 'string', [
                'limit' => 100,
                'null' => false,
                'comment' => 'Template key e.g. membership_application',
            ])
            ->addColumn('name', 'string', [
                'limit' => 150,
                'null' => false,
                'comment' => 'Admin label',
            ])
            ->addColumn('subject', 'string', [
                'limit' => 255,
                'null' => false,
            ])
            ->addColumn('body_html', 'text', [
                'null' => false,
            ])
            ->addColumn('body_text', 'text', [
                'null' => false,
            ])
            ->addColumn('enabled', 'boolean', [
                'default' => true,
                'null' => false,
            ])
            ->addColumn('visible', 'boolean', [
                'default' => true,
                'null' => false,
            ])
            ->addColumn('pos', 'integer', [
                'default' => 1000,
                'null' => false,
                'signed' => true,
            ])
            ->addColumn('created', 'datetime', ['null' => false])
            ->addColumn('modified', 'datetime', ['null' => false])
            ->addIndex(['language_id'])
            ->addIndex(['slug'])
            ->addIndex(['enabled'])
            ->addIndex(['visible'])
            ->addIndex(['pos'])
            ->addIndex(['language_id', 'slug'], ['unique' => true, 'name' => 'email_templates_language_slug'])
            ->create();
    }
}
