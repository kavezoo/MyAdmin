<?php
declare(strict_types=1);

use Migrations\BaseMigration;

/**
 * Competition announcement text templates + venue fields on competitions.
 */
class CompetitionTextTemplatesAndVenue extends BaseMigration
{
    public function up(): void
    {
        if (!$this->hasTable('competition_text_templates')) {
            $this->table('competition_text_templates')
                ->addColumn('country_id', 'integer', [
                    'null' => false,
                    'signed' => false,
                    'comment' => 'FK → countries.id',
                ])
                ->addColumn('label', 'string', [
                    'limit' => 150,
                    'null' => false,
                    'comment' => 'Admin/President Select2 label',
                ])
                ->addColumn('name', 'string', [
                    'limit' => 250,
                    'null' => false,
                    'default' => '',
                ])
                ->addColumn('title', 'string', [
                    'limit' => 250,
                    'null' => false,
                    'default' => '',
                ])
                ->addColumn('subtitle', 'string', [
                    'limit' => 250,
                    'null' => false,
                    'default' => '',
                ])
                ->addColumn('subtitle2', 'string', [
                    'limit' => 250,
                    'null' => false,
                    'default' => '',
                ])
                ->addColumn('description', 'text', [
                    'null' => false,
                    'default' => null,
                    'limit' => null,
                ])
                ->addColumn('racing_pipe_1_title', 'string', [
                    'limit' => 250,
                    'null' => false,
                    'default' => '',
                ])
                ->addColumn('racing_pipe_2_title', 'string', [
                    'limit' => 250,
                    'null' => false,
                    'default' => '',
                ])
                ->addColumn('racing_pipe_3_title', 'string', [
                    'limit' => 250,
                    'null' => false,
                    'default' => '',
                ])
                ->addColumn('enabled', 'boolean', [
                    'null' => false,
                    'default' => true,
                ])
                ->addColumn('visible', 'boolean', [
                    'null' => false,
                    'default' => true,
                ])
                ->addColumn('pos', 'integer', [
                    'null' => false,
                    'default' => 1000,
                    'signed' => true,
                ])
                ->addColumn('created', 'datetime', ['null' => false])
                ->addColumn('modified', 'datetime', ['null' => false])
                ->addIndex(['country_id'], ['name' => 'country_id'])
                ->addIndex(['label'], ['name' => 'label'])
                ->addIndex(['enabled'], ['name' => 'enabled'])
                ->addIndex(['visible'], ['name' => 'visible'])
                ->addIndex(['pos'], ['name' => 'pos'])
                ->create();
        }

        if ($this->hasTable('competitions')) {
            $table = $this->table('competitions');
            if (!$table->hasColumn('city_id')) {
                $table->addColumn('city_id', 'integer', [
                    'null' => false,
                    'default' => 0,
                    'signed' => false,
                    'after' => 'club_id',
                    'comment' => '0 = none; soft FK → cities.id',
                ]);
            }
            if (!$table->hasColumn('venue_address')) {
                $table->addColumn('venue_address', 'string', [
                    'limit' => 255,
                    'null' => false,
                    'default' => '',
                    'after' => 'city_id',
                    'comment' => 'Street address (manual)',
                ]);
            }
            if (!$table->hasColumn('google_maps_url')) {
                $table->addColumn('google_maps_url', 'string', [
                    'limit' => 1000,
                    'null' => false,
                    'default' => '',
                    'after' => 'venue_address',
                    'comment' => 'Google Maps share / embed URL',
                ]);
            }
            if (!$table->hasColumn('competition_text_template_id')) {
                $table->addColumn('competition_text_template_id', 'integer', [
                    'null' => true,
                    'default' => null,
                    'signed' => false,
                    'after' => 'google_maps_url',
                    'comment' => 'Last applied competition_text_templates.id',
                ]);
            }
            if (!$table->hasIndex(['city_id'])) {
                $table->addIndex(['city_id'], ['name' => 'city_id']);
            }
            if (!$table->hasIndex(['competition_text_template_id'])) {
                $table->addIndex(['competition_text_template_id'], ['name' => 'competition_text_template_id']);
            }
            $table->update();
        }
    }

    public function down(): void
    {
        if ($this->hasTable('competitions')) {
            $table = $this->table('competitions');
            foreach (['competition_text_template_id', 'google_maps_url', 'venue_address', 'city_id'] as $col) {
                if ($table->hasColumn($col)) {
                    $table->removeColumn($col);
                }
            }
            $table->update();
        }

        if ($this->hasTable('competition_text_templates')) {
            $this->table('competition_text_templates')->drop()->save();
        }
    }
}
