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
 * @property \Cake\I18n\DateTime|null $fee_paid_at
 * @property string|null $fee_paid_by Check-in Users.id who recorded payment
 * @property string|float $entry_fee_amount
 * @property string|float $racing_pipe_1_fee
 * @property string|float $racing_pipe_2_fee
 * @property string|float $racing_pipe_3_fee
 * @property string|float $fee_total Total to pay (entry + pipes)
 * @property string|float|null $result_time Achieved time in seconds
 * @property string|null $result_recorded_by_email Judge/device email from close API
 * @property int|null $result_rank
 * @property string|null $result_score
 * @property string|null $result_note
 * @property bool $visible
 * @property int $pos
 * @property \App\Model\Entity\User|null $fee_collector
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
        'companion_count' => true,
        'special_lunch' => true,
        'racing_pipe_1_qty' => true,
        'racing_pipe_2_qty' => true,
        'racing_pipe_3_qty' => true,
        'comment' => true,
        'fee_paid_at' => true,
        'fee_paid_by' => true,
        'entry_fee_amount' => true,
        'racing_pipe_1_fee' => true,
        'racing_pipe_2_fee' => true,
        'racing_pipe_3_fee' => true,
        'lunch_fee' => true,
        'fee_total' => true,
        'result_time' => true,
        'result_recorded_by_email' => true,
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
        'fee_collector' => true,
    ];
}
