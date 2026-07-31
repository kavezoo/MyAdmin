<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * CitiesSample Entity
 *
 * @property int $id
 * @property int $city_id
 * @property int $sample_id
 * @property int $pos
 * @property int $visible
 * @property \Cake\I18n\DateTime $created
 * @property \Cake\I18n\DateTime $modified
 *
 * @property \App\Model\Entity\City $city
 * @property \App\Model\Entity\Sample $sample
 */
class CitiesSample extends Entity
{
    /**
     * Fields that can be mass assigned using newEntity() or patchEntity().
     *
     * Note that when '*' is set to true, this allows all unspecified fields to
     * be mass assigned. For security purposes, it is advised to set '*' to false
     * (or remove it), and explicitly make individual fields accessible as needed.
     *
     * @var array<string, bool>
     */
    protected array $_accessible = [
        'city_id' => true,
        'sample_id' => true,
        'pos' => true,
        'visible' => true,
        'created' => true,
        'modified' => true,
        'city' => true,
        'sample' => true,
    ];
}
