<?php
declare(strict_types=1);

use Migrations\BaseMigration;

/**
 * Competition venue place name + national association logo on countries.
 */
class AddVenueNameAndCountryLogo extends BaseMigration
{
    public function up(): void
    {
        if ($this->hasTable('competitions')) {
            $competitions = $this->table('competitions');
            if (!$competitions->hasColumn('venue_name')) {
                $competitions->addColumn('venue_name', 'string', [
                    'limit' => 250,
                    'null' => false,
                    'default' => '',
                    'after' => 'city_id',
                    'comment' => 'Venue / building name (e.g. culture house)',
                ]);
                $competitions->update();
            }
        }

        if ($this->hasTable('countries')) {
            $countries = $this->table('countries');
            if (!$countries->hasColumn('logo')) {
                $countries->addColumn('logo', 'string', [
                    'limit' => 255,
                    'null' => true,
                    'default' => null,
                    'after' => 'phone_prefix',
                    'comment' => 'National pipe association logo (uploads/countries/{id}.png)',
                ]);
                $countries->update();
            }
        }
    }

    public function down(): void
    {
        if ($this->hasTable('competitions')) {
            $competitions = $this->table('competitions');
            if ($competitions->hasColumn('venue_name')) {
                $competitions->removeColumn('venue_name');
                $competitions->update();
            }
        }

        if ($this->hasTable('countries')) {
            $countries = $this->table('countries');
            if ($countries->hasColumn('logo')) {
                $countries->removeColumn('logo');
                $countries->update();
            }
        }
    }
}
