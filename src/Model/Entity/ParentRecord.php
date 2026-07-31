<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * ParentRecord Entity (table: parents — "Parent" is a reserved PHP keyword)
 *
 * @property int $id
 * @property string $name
 * @property int $pos
 * @property bool $visible
 * @property int $sample_count
 * @property \Cake\I18n\DateTime $created
 * @property \Cake\I18n\DateTime $modified
 *
 * @property \App\Model\Entity\Sample[] $samples
 */
class ParentRecord extends Entity
{
    /**
     * @var array<string, bool>
     */
    protected array $_accessible = [
        'name' => true,
        'pos' => true,
        'visible' => true,
        'sample_count' => true,
        'created' => true,
        'modified' => true,
        'samples' => true,
    ];
}
