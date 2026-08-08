<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * Club Entity.
 *
 * @property int $id
 * @property int $country_id
 * @property int $city_id
 * @property string $clubpresident_id Legacy/sync column (same user as club_president_id; '' if none)
 * @property string $name
 * @property string $short_name
 * @property string|null $logo Club logo web-relative path
 * @property string $email
 * @property bool $enabled
 * @property string $address
 * @property string $phone
 * @property string $web
 * @property string $facebook
 * @property string $insta
 * @property bool $visible
 * @property int $pos
 * @property int $user_count
 * @property int $competition_count
 * @property string|null $club_president_id Designated club president (Users.id)
 * @property \Cake\I18n\Date|null $national_membership_fee_date
 * @property \Cake\I18n\DateTime $created
 * @property \Cake\I18n\DateTime $modified
 * @property \App\Model\Entity\Country|null $country
 * @property \App\Model\Entity\City|null $city
 * @property \CakeDC\Users\Model\Entity\User[] $users
 */
class Club extends Entity
{
    /**
     * @var array<string, bool>
     */
    protected array $_accessible = [
        'country_id' => true,
        'city_id' => true,
        'clubpresident_id' => true,
        'name' => true,
        'short_name' => true,
        'logo' => true,
        'email' => true,
        'enabled' => true,
        'address' => true,
        'phone' => true,
        'web' => true,
        'facebook' => true,
        'insta' => true,
        'visible' => true,
        'pos' => true,
        'user_count' => false,
        'competition_count' => false,
        'club_president_id' => true,
        'national_membership_fee_date' => true,
        'created' => true,
        'modified' => true,
        'country' => true,
        'city' => true,
        'users' => true,
    ];

    /**
     * @param mixed $logo Stored web path string; ignore upload objects from form binding.
     */
    protected function _setLogo(mixed $logo): ?string
    {
        if ($logo === null || $logo === '') {
            return null;
        }
        if (!is_string($logo)) {
            return null;
        }

        return $logo;
    }
}
