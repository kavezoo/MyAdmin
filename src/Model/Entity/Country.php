<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Behavior\Translate\TranslateTrait;
use Cake\ORM\Entity;

/**
 * Country Entity — matches `countries` table (+ Translate on `name`).
 *
 * Default mass-assignment: visible + pos (admin).
 * Superuser patches use accessibleFields for seed columns (iso2, name, locale, continent_id).
 *
 * @property int $id
 * @property string $iso2
 * @property string $name English canonical; Translate overlays UI locale
 * @property string $endonim_name Endonym (native script)
 * @property string $locale Primary locale (e.g. hu_HU)
 * @property string $timezone IANA timezone (e.g. Europe/Budapest)
 * @property string $phone_prefix E.164 calling prefix (e.g. +36)
 * @property string|null $logo National pipe association logo path
 * @property string $currency ISO 4217 official currency
 * @property int $continent_id FK → continents.id
 * @property bool $visible
 * @property int $pos
 * @property int $user_count
 * @property int $club_count
 * @property int $setup_count
 * @property \Cake\I18n\DateTime $created
 * @property \Cake\I18n\DateTime $modified
 * @property \App\Model\Entity\Continent $continent
 * @property \CakeDC\Users\Model\Entity\User[] $users
 * @property \App\Model\Entity\Setup[] $setups
 * @property \App\Model\Entity\Country[] $visible_countries
 */
class Country extends Entity
{
    use TranslateTrait;

    /**
     * @var array<string, bool>
     */
    protected array $_accessible = [
        'visible' => true,
        'pos' => true,
        'continent' => true,
        'users' => true,
        'setups' => true,
        '_translations' => true,
        'visible_countries' => true,
        'visible_countries._ids' => true,
        'user_count' => false,
        'club_count' => false,
        'setup_count' => false,
    ];
}
