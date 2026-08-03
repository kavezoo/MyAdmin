<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Behavior\Translate\TranslateTrait;
use Cake\ORM\Entity;

/**
 * Continent Entity — matches `continents` table (+ Translate on `name`).
 *
 * @property int $id
 * @property string $code AFR|ASI|EUR|NAM|SAM|OCE|ANT
 * @property string $name English canonical; Translate overlays UI locale
 * @property bool $visible
 * @property int $pos
 * @property \Cake\I18n\DateTime $created
 * @property \Cake\I18n\DateTime $modified
 * @property \App\Model\Entity\Country[] $countries
 */
class Continent extends Entity
{
    use TranslateTrait;

    /**
     * Seed reference — Admin does not mass-assign continents (no Continents CRUD yet).
     *
     * @var array<string, bool>
     */
    protected array $_accessible = [
        'code' => true,
        'name' => true,
        'visible' => true,
        'pos' => true,
        'countries' => true,
    ];
}
