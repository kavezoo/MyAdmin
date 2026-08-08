<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * Competition entity (UUID PK).
 *
 * Summary counters (`lunch_for_the_attendant`, `user_count`, …) are maintained from applications — not form input.
 * Pipe type labels: `racing_pipe_N_title` (qty lives on competitions_users).
 *
 * @property string $id
 * @property int $country_id
 * @property int $club_id
 * @property string $user_id
 * @property bool $national_competition
 * @property string $name
 * @property string $title
 * @property string $subtitle
 * @property string $subtitle2
 * @property \Cake\I18n\Date $first_date_of_application
 * @property \Cake\I18n\Date $application_deadline
 * @property \Cake\I18n\DateTime $competition_datetime
 * @property \Cake\I18n\DateTime|null $start_datetime
 * @property \Cake\I18n\DateTime|null $end_datetime
 * @property string $description
 * @property int $minimum_team_size
 * @property int $lunch_for_the_attendant Sum of applicant lunch flags (not form)
 * @property string $racing_pipe_1_title
 * @property string $racing_pipe_2_title
 * @property string $racing_pipe_3_title
 * @property int $user_count
 * @property int $national_pipe_club_member_count
 * @property int $attendant_count
 * @property bool $visible
 * @property int $pos
 * @property \Cake\I18n\DateTime $created
 * @property \Cake\I18n\DateTime $modified
 */
class Competition extends Entity
{
    /**
     * @var array<string, bool>
     */
    protected array $_accessible = [
        'id' => true,
        'country_id' => true,
        'club_id' => true,
        'user_id' => true,
        'national_competition' => true,
        'name' => true,
        'title' => true,
        'subtitle' => true,
        'subtitle2' => true,
        'first_date_of_application' => true,
        'application_deadline' => true,
        'competition_datetime' => true,
        'start_datetime' => true,
        'end_datetime' => true,
        'description' => true,
        'minimum_team_size' => true,
        'lunch_for_the_attendant' => false,
        'racing_pipe_1_title' => true,
        'racing_pipe_2_title' => true,
        'racing_pipe_3_title' => true,
        'user_count' => false,
        'national_pipe_club_member_count' => false,
        'attendant_count' => false,
        'visible' => true,
        'pos' => true,
        'created' => true,
        'modified' => true,
        'country' => true,
        'club' => true,
        'user' => true,
        'competitions_clubs' => true,
        'competitions_users' => true,
    ];
}
