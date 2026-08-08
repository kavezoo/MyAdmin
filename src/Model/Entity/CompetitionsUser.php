<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * Competition applicant (member signup + optional team assignment + results).
 *
 * @property int $id
 * @property string $competition_id
 * @property int|null $competition_club_id
 * @property string $user_id
 * @property string $status
 * @property int|null $lunch_for_the_attendant
 * @property string|null $special_lunch
 * @property int|null $racing_pipe_1_qty
 * @property int|null $racing_pipe_2_qty
 * @property int|null $racing_pipe_3_qty
 * @property string|null $comment
 * @property int|null $result_rank
 * @property string|null $result_score
 * @property string|null $result_note
 * @property bool $visible
 * @property int $pos
 */
class CompetitionsUser extends Entity
{
    /**
     * @var array<string, bool>
     */
    protected array $_accessible = [
        'competition_id' => true,
        'competition_club_id' => true,
        'user_id' => true,
        'status' => true,
        'lunch_for_the_attendant' => true,
        'special_lunch' => true,
        'racing_pipe_1_qty' => true,
        'racing_pipe_2_qty' => true,
        'racing_pipe_3_qty' => true,
        'comment' => true,
        'result_rank' => true,
        'result_score' => true,
        'result_note' => true,
        'visible' => true,
        'pos' => true,
        'created' => true,
        'modified' => true,
        'competition' => true,
        'competitions_club' => true,
        'user' => true,
    ];
}
