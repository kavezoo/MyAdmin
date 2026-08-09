<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * Competition day staff assignment (check-in or table judge).
 *
 * @property int $id
 * @property string $competition_id
 * @property string $user_id
 * @property string $staff_role checkin|judge
 * @property bool $visible
 * @property int $pos
 */
class CompetitionStaff extends Entity
{
    /**
     * @var array<string, bool>
     */
    protected array $_accessible = [
        'competition_id' => true,
        'user_id' => true,
        'staff_role' => true,
        'visible' => true,
        'pos' => true,
        'created' => true,
        'modified' => true,
        'competition' => true,
        'user' => true,
    ];
}
