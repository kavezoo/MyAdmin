<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Behavior\Translate\TranslateTrait;
use Cake\ORM\Entity;

/**
 * Competition announcement text template (Translate EAV for description only).
 *
 * @property int $id
 * @property int $country_id
 * @property string $label
 * @property string $description
 * @property bool $enabled
 * @property bool $visible
 * @property int $pos
 * @property \Cake\I18n\DateTime $created
 * @property \Cake\I18n\DateTime $modified
 */
class CompetitionTextTemplate extends Entity
{
    use TranslateTrait;

    /**
     * @var array<string, bool>
     */
    protected array $_accessible = [
        'country_id' => true,
        'label' => true,
        'description' => true,
        'enabled' => true,
        'visible' => true,
        'pos' => true,
        'created' => true,
        'modified' => true,
        'country' => true,
        '_translations' => true,
    ];
}
