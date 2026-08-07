<?php
declare(strict_types=1);

use App\Utility\EmailTemplateDefaults;
use App\Utility\EmailTemplateService;
use App\Utility\EmailTemplateSlugs;
use Cake\Datasource\ConnectionManager;
use Cake\I18n\DateTime;
use Migrations\BaseMigration;

/**
 * Seed membership email templates for en/hu/de/fr/it/sk.
 */
class SeedEmailTemplates extends BaseMigration
{
    public function up(): void
    {
        if (!$this->hasTable('email_templates') || !$this->hasTable('languages')) {
            return;
        }

        $conn = ConnectionManager::get('default');
        $now = DateTime::now()->format('Y-m-d H:i:s');

        foreach (array_keys(EmailTemplateSlugs::options()) as $slug) {
            foreach (EmailTemplateDefaults::locales() as $locale) {
                $languageId = EmailTemplateService::languageIdForLocale($locale);
                if ($languageId < 1) {
                    continue;
                }
                $row = EmailTemplateDefaults::forSlugLocale($slug, $locale);
                $conn->execute(
                    'INSERT INTO email_templates
                        (language_id, slug, name, subject, body_html, body_text, enabled, visible, created, modified)
                     VALUES
                        (:language_id, :slug, :name, :subject, :body_html, :body_text, 1, 1, :created, :modified)
                     ON DUPLICATE KEY UPDATE
                        name = VALUES(name),
                        subject = VALUES(subject),
                        body_html = VALUES(body_html),
                        body_text = VALUES(body_text),
                        enabled = 1,
                        visible = 1,
                        modified = VALUES(modified)',
                    [
                        'language_id' => $languageId,
                        'slug' => $slug,
                        'name' => $row['name'],
                        'subject' => $row['subject'],
                        'body_html' => $row['body_html'],
                        'body_text' => $row['body_text'],
                        'created' => $now,
                        'modified' => $now,
                    ],
                    [
                        'language_id' => 'integer',
                        'slug' => 'string',
                        'name' => 'string',
                        'subject' => 'string',
                        'body_html' => 'string',
                        'body_text' => 'string',
                        'created' => 'string',
                        'modified' => 'string',
                    ]
                );
            }
        }
    }

    public function down(): void
    {
        if (!$this->hasTable('email_templates')) {
            return;
        }
        $conn = ConnectionManager::get('default');
        foreach (array_keys(EmailTemplateSlugs::options()) as $slug) {
            $conn->execute(
                'DELETE FROM email_templates WHERE slug = :slug',
                ['slug' => $slug],
                ['slug' => 'string']
            );
        }
    }
}
