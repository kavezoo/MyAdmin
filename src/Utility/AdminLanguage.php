<?php
declare(strict_types=1);

namespace App\Utility;

use Cake\I18n\DateTime;
use Cake\I18n\I18n;
use Cake\ORM\Locator\LocatorAwareTrait;
use Locale;

/**
 * Login language select — Languages table + Translate names.
 */
class AdminLanguage
{
    use LocatorAwareTrait;

    /**
     * code => translated display name (for current / given UI locale).
     *
     * @return array<string, string>
     */
    public static function options(?string $uiLocale = null): array
    {
        $uiLocale = AdminCountry::normalizeTranslateLocale(
            ($uiLocale !== null && $uiLocale !== '') ? $uiLocale : I18n::getLocale()
        );

        /** @var \App\Model\Table\LanguagesTable $languages */
        $languages = (new self())->fetchTable('Languages');
        try {
            $rows = $languages->find('visibleTranslated', locale: $uiLocale)->all();
        } catch (\Throwable) {
            return static::fallbackOptions($uiLocale);
        }

        $out = [];
        foreach ($rows as $row) {
            $code = trim((string)$row->get('code'));
            if ($code === '') {
                continue;
            }
            $name = trim((string)$row->get('name'));
            if ($name === '') {
                $name = static::displayName($code, $uiLocale) ?: $code;
            }
            $out[$code] = $name;
        }

        if ($out === []) {
            return static::fallbackOptions($uiLocale);
        }

        return $out;
    }

    /**
     * @return array<string, string>
     */
    protected static function fallbackOptions(string $uiLocale): array
    {
        $out = [];
        foreach (BrowserLocale::availableLocales() as $code) {
            $out[$code] = static::displayName($code, $uiLocale) ?: $code;
        }

        return $out;
    }

    /**
     * Human label for one locale code in a display locale (ICU).
     */
    public static function displayName(string $localeCode, ?string $inLocale = null): string
    {
        $localeCode = str_replace('-', '_', trim($localeCode));
        if ($localeCode === '') {
            return '';
        }
        $inLocale = AdminCountry::normalizeTranslateLocale(
            ($inLocale !== null && $inLocale !== '') ? $inLocale : I18n::getLocale()
        );
        $inLocaleTag = str_replace('_', '-', $inLocale);
        $tag = str_replace('_', '-', $localeCode);

        if (class_exists(Locale::class)) {
            $name = Locale::getDisplayName($tag, $inLocaleTag);
            if (is_string($name) && $name !== '' && strcasecmp($name, $tag) !== 0) {
                // Prefer capitalized first letter for UI
                return mb_convert_case($name, MB_CASE_TITLE, 'UTF-8') === $name
                    ? $name
                    : $name;
            }
            $lang = Locale::getDisplayLanguage($tag, $inLocaleTag);
            if (is_string($lang) && $lang !== '') {
                return $lang;
            }
        }

        return $localeCode;
    }

    /**
     * Sync languages + i18n name for every available locale (ICU).
     * Always includes en_GB (canonical English name column) and hu_HU translations.
     *
     * @return array{created: int, updated: int, translations: int}
     */
    public static function syncFromCountries(): array
    {
        /** @var \App\Model\Table\LanguagesTable $languages */
        $languages = (new self())->fetchTable('Languages');

        $localeCodes = BrowserLocale::availableLocales();
        if ($localeCodes === []) {
            $localeCodes = ['en_GB', 'hu_HU'];
        }
        if (!in_array('en_GB', $localeCodes, true)) {
            array_unshift($localeCodes, 'en_GB');
        }

        $targets = $localeCodes;
        foreach (['en_GB', 'hu_HU'] as $must) {
            if (!in_array($must, $targets, true)) {
                $targets[] = $must;
            }
        }

        $created = 0;
        $updated = 0;
        $translations = 0;
        $pos = 10;
        $now = new DateTime();

        foreach ($localeCodes as $code) {
            $enName = static::displayName($code, 'en_GB') ?: $code;
            $existing = $languages->find()->where(['Languages.code' => $code])->first();
            $wasNew = $existing === null;

            if ($wasNew) {
                $entity = $languages->newEntity([
                    'code' => $code,
                    'name' => $enName,
                    'visible' => true,
                    'pos' => $code === 'en_GB' ? 1 : $pos,
                    'created' => $now,
                    'modified' => $now,
                ]);
            } else {
                $entity = $existing;
                $entity->set('name', $enName);
                $entity->set('visible', true);
                $entity->set('modified', $now);
            }

            foreach ($targets as $targetLocale) {
                $label = static::displayName($code, $targetLocale) ?: $enName;
                $entity->translation($targetLocale)->set(['name' => $label], ['guard' => false]);
                $translations++;
            }

            $languages->getBehavior('Translate')->setLocale('en_GB');
            if ($languages->save($entity)) {
                if ($wasNew) {
                    $created++;
                } else {
                    $updated++;
                }
            }

            if ($code !== 'en_GB') {
                $pos += 10;
            }
        }

        return compact('created', 'updated', 'translations');
    }
}
