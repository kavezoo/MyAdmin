<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Behavior\Translate\TranslateTrait;
use Cake\ORM\Entity;

/**
 * Language Entity — UI locale row (login language select).
 *
 * @property int $id
 * @property string $code
 * @property string $name
 * @property string $endonim_name Endonym (language name in itself)
 * @property bool $visible
 * @property int $pos
 * @property \Cake\I18n\DateTime $created
 * @property \Cake\I18n\DateTime $modified
 */
class Language extends Entity
{
    use TranslateTrait;

    /**
     * @var array<string, bool>
     */
    protected array $_accessible = [
        'code' => true,
        'name' => true,
        'endonim_name' => true,
        'visible' => true,
        'pos' => true,
        'created' => true,
        'modified' => true,
        '_translations' => true,
    ];
}
