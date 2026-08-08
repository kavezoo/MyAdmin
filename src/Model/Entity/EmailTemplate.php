<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * Email template (per country + language).
 *
 * @property int $id
 * @property int $country_id
 * @property int $language_id
 * @property string $slug
 * @property string $name
 * @property string $subject
 * @property string $body_html
 * @property string $body_text
 * @property bool $enabled
 * @property bool $visible
 * @property int $pos
 * @property \Cake\I18n\DateTime $created
 * @property \Cake\I18n\DateTime $modified
 * @property \App\Model\Entity\Country|null $country
 * @property \App\Model\Entity\Language|null $language
 */
class EmailTemplate extends Entity
{
    /**
     * @var array<string, bool>
     */
    protected array $_accessible = [
        'country_id' => true,
        'language_id' => true,
        'slug' => true,
        'name' => true,
        'subject' => true,
        'body_html' => true,
        'body_text' => true,
        'enabled' => true,
        'visible' => true,
        'pos' => true,
        'created' => true,
        'modified' => true,
        'country' => true,
        'language' => true,
    ];
}
