<?php
declare(strict_types=1);

use Migrations\BaseMigration;

/**
 * Missing CounterCache columns: Countries.club_count, Counties.city_count, Cities.club_count.
 */
class AddMissingCounterCacheColumns extends BaseMigration
{
    public function change(): void
    {
        $countries = $this->table('countries');
        if (!$countries->hasColumn('club_count')) {
            $countries
                ->addColumn('club_count', 'integer', [
                    'limit' => 10,
                    'signed' => false,
                    'null' => false,
                    'default' => 0,
                    'after' => 'user_count',
                    'comment' => 'CounterCache: clubs in this country',
                ])
                ->update();
        }

        $counties = $this->table('counties');
        if (!$counties->hasColumn('city_count')) {
            $counties
                ->addColumn('city_count', 'integer', [
                    'limit' => 10,
                    'signed' => false,
                    'null' => false,
                    'default' => 0,
                    'after' => 'visible',
                    'comment' => 'CounterCache: cities in this county',
                ])
                ->update();
        }

        $cities = $this->table('cities');
        if (!$cities->hasColumn('club_count')) {
            $cities
                ->addColumn('club_count', 'integer', [
                    'limit' => 10,
                    'signed' => false,
                    'null' => false,
                    'default' => 0,
                    'after' => 'lng2',
                    'comment' => 'CounterCache: clubs in this city',
                ])
                ->update();
        }
    }
}
