<?php
declare(strict_types=1);

use App\Utility\EmailTemplateDefaults;
use App\Utility\EmailTemplateService;
use App\Utility\EmailTemplateSlugs;
use Cake\Datasource\ConnectionManager;
use Cake\I18n\DateTime;
use Migrations\BaseMigration;

/**
 * email_templates: country_id + unique (country, language, slug);
 * reseed per country; add member_profile_updated slug.
 */
class EmailTemplatesCountryAndMemberProfile extends BaseMigration
{
    public function up(): void
    {
        if (!$this->hasTable('email_templates') || !$this->hasTable('countries')) {
            return;
        }

        $table = $this->table('email_templates');
        if (!$table->hasColumn('country_id')) {
            $table
                ->addColumn('country_id', 'integer', [
                    'null' => true,
                    'default' => null,
                    'signed' => true,
                    'after' => 'id',
                    'comment' => 'FK → countries.id',
                ])
                ->update();
        }

        if ($table->hasIndex(['language_id', 'slug'])) {
            $table->removeIndexByName('email_templates_language_slug')->update();
        }

        $conn = ConnectionManager::get('default');
        $conn->execute('DELETE FROM email_templates');

        $countries = $conn->execute(
            'SELECT id, locale FROM countries WHERE id > 0 ORDER BY id ASC'
        )->fetchAll('assoc');
        if ($countries === []) {
            return;
        }

        $now = DateTime::now()->format('Y-m-d H:i:s');
        $slugs = array_keys(EmailTemplateSlugs::options());
        // Include new slug even if options() was already updated in code.
        if (!in_array(EmailTemplateSlugs::MEMBER_PROFILE_UPDATED, $slugs, true)) {
            $slugs[] = EmailTemplateSlugs::MEMBER_PROFILE_UPDATED;
        }

        foreach ($countries as $country) {
            $countryId = (int)($country['id'] ?? 0);
            if ($countryId < 1) {
                continue;
            }
            foreach ($slugs as $slug) {
                foreach (EmailTemplateDefaults::locales() as $locale) {
                    $languageId = EmailTemplateService::languageIdForCodeExact($locale);
                    if ($languageId < 1) {
                        $languageId = EmailTemplateService::languageIdForLocale($locale);
                    }
                    if ($languageId < 1) {
                        continue;
                    }
                    $row = EmailTemplateDefaults::forSlugLocale($slug, $locale);
                    $conn->execute(
                        'INSERT INTO email_templates
                            (country_id, language_id, slug, name, subject, body_html, body_text, enabled, visible, created, modified)
                         VALUES
                            (:country_id, :language_id, :slug, :name, :subject, :body_html, :body_text, 1, 1, :created, :modified)',
                        [
                            'country_id' => $countryId,
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
                            'country_id' => 'integer',
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

        $table = $this->table('email_templates');
        $table->changeColumn('country_id', 'integer', [
            'null' => false,
            'signed' => true,
            'comment' => 'FK → countries.id',
        ])->update();

        if (!$table->hasIndex(['country_id', 'language_id', 'slug'])) {
            $table
                ->addIndex(['country_id', 'language_id', 'slug'], [
                    'unique' => true,
                    'name' => 'email_templates_country_language_slug',
                ])
                ->addIndex(['country_id'], ['name' => 'country_id'])
                ->update();
        }
    }

    public function down(): void
    {
        if (!$this->hasTable('email_templates')) {
            return;
        }

        $conn = ConnectionManager::get('default');
        $conn->execute(
            'DELETE FROM email_templates WHERE slug = :slug',
            ['slug' => EmailTemplateSlugs::MEMBER_PROFILE_UPDATED],
            ['slug' => 'string']
        );

        $table = $this->table('email_templates');
        if ($table->hasIndex(['country_id', 'language_id', 'slug'])) {
            $table->removeIndexByName('email_templates_country_language_slug')->update();
        }
        if ($table->hasIndex(['country_id'])) {
            $table->removeIndexByName('country_id')->update();
        }

        // Keep one row per (language_id, slug) — lowest country_id.
        $conn->execute(
            'DELETE t1 FROM email_templates t1
             INNER JOIN email_templates t2
               ON t1.language_id = t2.language_id
              AND t1.slug = t2.slug
              AND t1.country_id > t2.country_id'
        );

        if ($table->hasColumn('country_id')) {
            $table->removeColumn('country_id')->update();
        }

        if (!$table->hasIndex(['language_id', 'slug'])) {
            $table
                ->addIndex(['language_id', 'slug'], [
                    'unique' => true,
                    'name' => 'email_templates_language_slug',
                ])
                ->update();
        }
    }
}
