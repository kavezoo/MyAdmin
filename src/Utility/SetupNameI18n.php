<?php
declare(strict_types=1);

namespace App\Utility;

use App\Model\Entity\Setup;
use Cake\I18n\I18n;
use Cake\ORM\Table;

/**
 * Seed Setup `name` translations (Translate EAV) from gettext msgids in locale .po files.
 */
class SetupNameI18n
{
    public const DEFAULT_LOCALE = 'en_GB';

    /**
     * Store English msgid in setups.name and write i18n rows for every locale folder.
     */
    public static function seedForEntity(Table $setupsTable, Setup $setup, string $msgid): void
    {
        if (!$setupsTable->hasBehavior('Translate')) {
            return;
        }

        $msgid = trim($msgid);
        if ($msgid === '' || (int)$setup->id < 1) {
            return;
        }

        $behavior = $setupsTable->getBehavior('Translate');
        $previousLocale = I18n::getLocale();

        $behavior->setLocale(self::DEFAULT_LOCALE);
        $entity = $setupsTable->get($setup->id);
        $entity->set('name', $msgid);
        $setupsTable->save($entity, ['checkRules' => false]);

        foreach (static::discoverLocales() as $locale) {
            if ($locale === self::DEFAULT_LOCALE) {
                continue;
            }
            I18n::setLocale($locale);
            $translated = __($msgid);
            $behavior->setLocale($locale);
            $localized = $setupsTable->get($setup->id);
            $localized->set('name', $translated);
            $setupsTable->save($localized, ['checkRules' => false]);
        }

        I18n::setLocale($previousLocale);
        AdminTranslate::applyLocale($setupsTable);
    }

    /**
     * @return list<string>
     */
    public static function discoverLocales(): array
    {
        $root = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . 'locales';
        if (!is_dir($root)) {
            return [self::DEFAULT_LOCALE];
        }

        $locales = [];
        foreach (scandir($root) ?: [] as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }
            $dir = $root . DIRECTORY_SEPARATOR . $entry;
            if (!is_dir($dir)) {
                continue;
            }
            if (is_file($dir . DIRECTORY_SEPARATOR . 'default.po')) {
                $locales[] = $entry;
            }
        }

        if ($locales === []) {
            return [self::DEFAULT_LOCALE];
        }

        sort($locales);

        return $locales;
    }
}
