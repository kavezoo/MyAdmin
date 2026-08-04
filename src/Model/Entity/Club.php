<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * Club Entity.
 *
 * @property int $id
 * @property int $country_id
 * @property string $name
 * @property bool $visible
 * @property int $pos
 * @property \Cake\I18n\DateTime $created
 * @property \Cake\I18n\DateTime $modified
 * @property \App\Model\Entity\Country|null $country
 */
class Club extends Entity
{
    /**
     * @var array<string, bool>
     */
    protected array $_accessible = [
        'country_id' => true,
        'name' => true,
        'visible' => true,
        'pos' => true,
        'created' => true,
        'modified' => true,
        'country' => true,
    ];
}
