<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * EventLog Entity — append-only audit row.
 *
 * @property int $id
 * @property int|null $country_id
 * @property string|null $user_id
 * @property string|null $actor_role
 * @property string $module
 * @property string $action
 * @property string|null $entity
 * @property string|null $entity_id
 * @property string|null $description
 * @property string|null $url
 * @property string|null $http_method
 * @property string|null $ip
 * @property string|null $user_agent
 * @property string|null $request_data
 * @property \Cake\I18n\DateTime $created
 * @property \App\Model\Entity\Country|null $country
 * @property \CakeDC\Users\Model\Entity\User|null $user
 */
class EventLog extends Entity
{
    /**
     * @var array<string, bool>
     */
    protected array $_accessible = [
        'country_id' => true,
        'user_id' => true,
        'actor_role' => true,
        'module' => true,
        'action' => true,
        'entity' => true,
        'entity_id' => true,
        'description' => true,
        'url' => true,
        'http_method' => true,
        'ip' => true,
        'user_agent' => true,
        'request_data' => true,
        'created' => true,
        'country' => true,
        'user' => true,
    ];
}
