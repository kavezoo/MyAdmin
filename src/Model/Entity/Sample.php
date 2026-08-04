<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Behavior\Translate\TranslateTrait;
use Cake\ORM\Entity;

/**
 * Sample Entity
 *
 * @property int $id
 * @property int $parent_id
 * @property string $name
 * @property string|null $description
 * @property int $szam
 * @property float $netto
 * @property \Cake\I18n\Date $datum
 * @property \Cake\I18n\Time $ido
 * @property \Cake\I18n\DateTime $datumido
 * @property bool $logikai
 * @property int $pos
 * @property bool $visible
 * @property int $city_count
 * @property \Cake\I18n\DateTime $created
 * @property \Cake\I18n\DateTime $modified
 *
 * @property \App\Model\Entity\ParentRecord $parent
 * @property \App\Model\Entity\City[] $cities
 */
class Sample extends Entity
{
    use TranslateTrait;

    /**
     * @var array<string, bool>
     */
    protected array $_accessible = [
        'parent_id' => true,
        'name' => true,
        'description' => true,
        'szam' => true,
        'netto' => true,
        'datum' => true,
        'ido' => true,
        'datumido' => true,
        'logikai' => true,
        'pos' => true,
        'visible' => true,
        'city_count' => true,
        'created' => true,
        'modified' => true,
        'parent' => true,
        'cities' => true,
        '_translations' => true,
    ];
}
