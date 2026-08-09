<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Behavior\Translate\TranslateTrait;
use Cake\ORM\Entity;

/**
 * Competition entity (UUID PK).
 *
 * Summary counters (`lunch_for_the_attendant`, `user_count`, …) are maintained from applications — not form input.
 * Application pipe labels: `racing_pipe_N_title` (qty on competitions_users).
 * Announcement: `pipe_type`, `pipe_parameters`, `tobacco_type` (Translate) + `tobacco_weight` (g).
 * Text fields: Cake Translate EAV (`_translations`).
 *
 * @property string $id
 * @property int $country_id
 * @property int $club_id
 * @property int $city_id Soft FK cities.id (0 = none)
 * @property string $venue_name Building / place name
 * @property string $venue_address
 * @property string $google_maps_url
 * @property int|null $competition_text_template_id
 * @property string $user_id
 * @property string|null $modified_by Last editor (create = user_id)
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
 * @property string $pipe_type
 * @property string $pipe_parameters
 * @property string $tobacco_type
 * @property string|float|int $tobacco_weight Grams
 * @property string $currency ISO 4217
 * @property string|float|int $entry_fee_member
 * @property string|float|int $entry_fee_non_member
 * @property string|float|int $racing_pipe_1_price_member
 * @property string|float|int $racing_pipe_1_price_non_member
 * @property string|float|int $racing_pipe_2_price_member
 * @property string|float|int $racing_pipe_2_price_non_member
 * @property string|float|int $racing_pipe_3_price_member
 * @property string|float|int $racing_pipe_3_price_non_member
 * @property int $user_count
 * @property int $national_pipe_club_member_count
 * @property int $attendant_count
 * @property bool $visible
 * @property int $pos
 * @property \Cake\I18n\DateTime $created
 * @property \Cake\I18n\DateTime $modified
 * @property \CakeDC\Users\Model\Entity\User|null $user
 * @property \CakeDC\Users\Model\Entity\User|null $modifier
 * @property \App\Model\Entity\City|null $city
 * @property \App\Model\Entity\CompetitionTextTemplate|null $competition_text_template
 */
class Competition extends Entity
{
    use TranslateTrait;

    /**
     * @var array<string, bool>
     */
    protected array $_accessible = [
        'id' => true,
        'country_id' => true,
        'club_id' => true,
        'city_id' => true,
        'venue_name' => true,
        'venue_address' => true,
        'google_maps_url' => true,
        'competition_text_template_id' => true,
        'user_id' => true,
        'modified_by' => true,
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
        'pipe_type' => true,
        'pipe_parameters' => true,
        'tobacco_type' => true,
        'tobacco_weight' => true,
        'currency' => true,
        'entry_fee_member' => true,
        'entry_fee_non_member' => true,
        'lunch_description' => true,
        'lunch_price' => true,
        'racing_pipe_1_price_member' => true,
        'racing_pipe_1_price_non_member' => true,
        'racing_pipe_2_price_member' => true,
        'racing_pipe_2_price_non_member' => true,
        'racing_pipe_3_price_member' => true,
        'racing_pipe_3_price_non_member' => true,
        'racing_pipe_1_image' => true,
        'racing_pipe_2_image' => true,
        'racing_pipe_3_image' => true,
        'user_count' => false,
        'national_pipe_club_member_count' => false,
        'attendant_count' => false,
        'visible' => true,
        'pos' => true,
        'created' => true,
        'modified' => true,
        'country' => true,
        'club' => true,
        'city' => true,
        'competition_text_template' => true,
        'user' => true,
        'modifier' => true,
        'competitions_clubs' => true,
        'competitions_users' => true,
        '_translations' => true,
    ];
}
