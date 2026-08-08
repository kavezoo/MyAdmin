<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * Named sub-club (optional link from competitions_clubs.subclub_id).
 *
 * @property int $id
 * @property string $name
 * @property int $club_id
 * @property string $competition_id
 * @property string $user_id
 * @property bool $visible
 * @property int $pos
 */
class Subclub extends Entity
{
    /**
     * @var array<string, bool>
     */
    protected array $_accessible = [
        'name' => true,
        'club_id' => true,
        'competition_id' => true,
        'user_id' => true,
        'visible' => true,
        'pos' => true,
        'created' => true,
        'modified' => true,
        'club' => true,
        'competition' => true,
        'user' => true,
    ];
}
