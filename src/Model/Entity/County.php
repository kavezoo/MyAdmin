<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * County Entity — `counties` (megye / region).
 *
 * @property int $id
 * @property int $country_id
 * @property string $name
 * @property string $shortname
 * @property string $capitalcity
 * @property string $region
 * @property int $pos
 * @property bool $visible
 * @property \Cake\I18n\DateTime $created
 * @property \Cake\I18n\DateTime $modified
 * @property \App\Model\Entity\Country|null $country
 * @property \App\Model\Entity\City[] $cities
 */
class County extends Entity
{
    /**
     * @var array<string, bool>
     */
    protected array $_accessible = [
        'country_id' => true,
        'name' => true,
        'shortname' => true,
        'capitalcity' => true,
        'region' => true,
        'pos' => true,
        'visible' => true,
        'created' => true,
        'modified' => true,
        'country' => true,
        'cities' => true,
    ];
}
