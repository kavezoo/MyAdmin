<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * Club Entity.
 *
 * @property int $id
 * @property int $country_id
 * @property string $name
 * @property bool $enabled
 * @property bool $visible
 * @property int $pos
 * @property int $user_count
 * @property string|null $club_president_id
 * @property \Cake\I18n\Date|null $national_membership_fee_date
 * @property \Cake\I18n\DateTime $created
 * @property \Cake\I18n\DateTime $modified
 * @property \App\Model\Entity\Country|null $country
 * @property \CakeDC\Users\Model\Entity\User[] $users
 */
class Club extends Entity
{
    /**
     * @var array<string, bool>
     */
    protected array $_accessible = [
        'country_id' => true,
        'name' => true,
        'enabled' => true,
        'visible' => true,
        'pos' => true,
        'user_count' => false,
        'club_president_id' => true,
        'national_membership_fee_date' => true,
        'created' => true,
        'modified' => true,
        'country' => true,
        'users' => true,
    ];
}
