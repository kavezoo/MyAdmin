<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * CountryVisibility Entity — junction countries ↔ countries (per-active visibility).
 *
 * @property int $id
 * @property int $country_id
 * @property int $visible_country_id
 * @property bool $visible
 * @property int $pos
 * @property \Cake\I18n\DateTime $created
 * @property \Cake\I18n\DateTime $modified
 * @property \App\Model\Entity\Country|null $country
 * @property \App\Model\Entity\Country|null $visible_country
 */
class CountryVisibility extends Entity
{
    /**
     * @var array<string, bool>
     */
    protected array $_accessible = [
        'country_id' => true,
        'visible_country_id' => true,
        'visible' => true,
        'pos' => true,
        'created' => true,
        'modified' => true,
        'country' => true,
        'visible_country' => true,
    ];
}
