<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * Setup Entity — typed setting row for one country.
 *
 * @property int $id
 * @property int $country_id
 * @property string $name
 * @property string $slug
 * @property string $type
 * @property string $value
 * @property bool $visible
 * @property int $pos
 * @property \Cake\I18n\DateTime $created
 * @property \Cake\I18n\DateTime $modified
 * @property \App\Model\Entity\Country|null $country
 */
class Setup extends Entity
{
    /**
     * @var array<string, bool>
     */
    protected array $_accessible = [
        'country_id' => true,
        'name' => true,
        'slug' => true,
        'type' => true,
        'value' => true,
        'visible' => true,
        'pos' => true,
        'created' => true,
        'modified' => true,
    ];
}
