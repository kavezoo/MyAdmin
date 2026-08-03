<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Behavior\Translate\TranslateTrait;
use Cake\ORM\Entity;

/**
 * Country Entity — matches `countries` table (+ Translate on `name`).
 *
 * Admin mass-assignment: only `visible` and `pos` (seed: iso2, name, locale, continent_id).
 *
 * @property int $id
 * @property string $iso2
 * @property string $name English canonical; Translate overlays UI locale
 * @property string $locale Primary locale (e.g. hu_HU)
 * @property int $continent_id FK → continents.id
 * @property bool $visible
 * @property int $pos
 * @property int $user_count
 * @property \Cake\I18n\DateTime $created
 * @property \Cake\I18n\DateTime $modified
 * @property \App\Model\Entity\Continent $continent
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
    ];
}
