<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * Row in the CakePHP Translate EAV `i18n` table.
 *
 * Used by Countries.name and Continents.name (and future Translate models).
 *
 * @property int $id
 * @property string $locale
 * @property string $model Table alias (Countries, Continents, …)
 * @property int $foreign_key Parent record id
 * @property string $field Translated field name
 * @property string|null $content
 * @property bool $visible Per-locale visibility flag
 */
class I18nTranslation extends Entity
{
    /**
     * @var array<string, bool>
     */
    protected array $_accessible = [
        'locale' => true,
        'model' => true,
        'foreign_key' => true,
        'field' => true,
        'content' => true,
        'visible' => true,
    ];
}
