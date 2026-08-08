<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * Competition club team (alcsapat) entry — display name on Subclub.
 *
 * @property int $id
 * @property int $club_id
 * @property int $subclub_id
 * @property string $competition_id
 * @property \Cake\I18n\DateTime|null $application_datetime
 * @property \Cake\I18n\DateTime|null $date_of_application_acceptance
 * @property bool $visible
 * @property int $pos
 * @property int $user_count
 * @property \App\Model\Entity\Subclub|null $subclub
 * @property-read string $name Subclub name (virtual)
 */
class CompetitionsClub extends Entity
{
    /**
     * @var array<string, bool>
     */
    protected array $_accessible = [
        'club_id' => true,
        'subclub_id' => true,
        'competition_id' => true,
        'application_datetime' => true,
        'date_of_application_acceptance' => true,
        'visible' => true,
        'pos' => true,
        'user_count' => false,
        'created' => true,
        'modified' => true,
        'club' => true,
        'competition' => true,
        'subclub' => true,
        'competitions_users' => true,
    ];

    /**
     * @var list<string>
     */
    protected array $_virtual = [
        'name',
    ];

    /**
     * Display name from linked subclub.
     */
    protected function _getName(): string
    {
        if (!empty($this->subclub) && isset($this->subclub->name)) {
            return (string)$this->subclub->name;
        }

        return '';
    }
}
