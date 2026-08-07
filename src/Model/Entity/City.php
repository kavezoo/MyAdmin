<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * City Entity — `cities` (település / ZIP row).
 *
 * @property int $id
 * @property int $country_id
 * @property int $county_id
 * @property string $shortname
 * @property string $name
 * @property string|null $zip
 * @property string $lat
 * @property string $lng
 * @property string $lat2
 * @property string $lng2
 * @property \App\Model\Entity\Country|null $country
 * @property \App\Model\Entity\County|null $county
 */
class City extends Entity
{
    /**
     * @var array<string, bool>
     */
    protected array $_accessible = [
        'country_id' => true,
        'county_id' => true,
        'shortname' => true,
        'name' => true,
        'zip' => true,
        'lat' => true,
        'lng' => true,
        'lat2' => true,
        'lng2' => true,
        'country' => true,
        'county' => true,
    ];
}
