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
     * Login language Select2: "{name in current UI language} ({endonym})".
     * Source: `languages` rows with `visible = true` (Europe + en_US + en_CA).
     * Sorted by the UI-language label.
     *
     * @return array<string, string>
     */
    public static function loginOptions(?string $uiLocale = null): array
    {
        $uiLocale = AdminCountry::normalizeTranslateLocale(
            ($uiLocale !== null && $uiLocale !== '') ? $uiLocale : I18n::getLocale()
        );

        /** @var \App\Model\Table\LanguagesTable $languages */
        $languages = (new self())->fetchTable('Languages');
        try {
            $rows = $languages->find()
                ->select(['Languages.code', 'Languages.endonim_name', 'Languages.name', 'Languages.pos'])
                ->where(['Languages.visible' => true])
                ->orderBy([
                    'CASE WHEN Languages.code = \'en_GB\' THEN 0 WHEN Languages.code LIKE \'en_%\' THEN 1 ELSE 2 END' => 'ASC',
                    'Languages.pos' => 'ASC',
                    'Languages.code' => 'ASC',
                ])
                ->disableHydration()
                ->all()
                ->toList();
        } catch (\Throwable) {
            $rows = [];
        }

        if ($rows === []) {
            return static::fallbackLoginOptions($uiLocale);
        }

        $meta = [];
        foreach ($rows as $row) {
            $code = trim((string)($row['code'] ?? ''));
            if ($code === '') {
                continue;
            }
            $storedEndo = trim((string)($row['endonim_name'] ?? ''));
            $meta[$code] = [
                'langUi' => static::languageName($code, $uiLocale),
                'langNative' => $storedEndo !== ''
                    ? $storedEndo
                    : static::languageName($code, $code),
                'regionUi' => static::regionName($code, $uiLocale),
            ];
        }

        $labels = [];
        foreach ($meta as $code => $m) {
            $labels[$code] = static::formatLoginLabel($m['langUi'], $m['langNative'], '');
        }

        $counts = array_count_values($labels);
        foreach ($labels as $code => $label) {
            if (($counts[$label] ?? 0) < 2) {
                continue;
            }
            $region = $meta[$code]['regionUi'];
            if ($region === '') {
                continue;
            }
            $labels[$code] = static::formatLoginLabel(
                $meta[$code]['langUi'],
                $meta[$code]['langNative'],
                $region
            );
        }

        asort($labels, SORT_NATURAL | SORT_FLAG_CASE);

        return $labels;
    }

    /**
     * Single login row / “Selected language” label in the current UI locale.
     */
    public static function loginLabel(string $localeCode, ?string $uiLocale = null): string
    {
        $uiLocale = AdminCountry::normalizeTranslateLocale(
            ($uiLocale !== null && $uiLocale !== '') ? $uiLocale : I18n::getLocale()
        );
        $localeCode = str_replace('-', '_', trim($localeCode));
        if ($localeCode === '') {
            return '';
        }

        return static::formatLoginLabel(
            static::languageName($localeCode, $uiLocale),
            static::languageName($localeCode, $localeCode),
            ''
        );
    }

    /**
     * @return string "Angol (English)" or "Német — Ausztria (Deutsch)" when $regionUi set
     */
    protected static function formatLoginLabel(string $langUi, string $langNative, string $regionUi): string
    {
        $langUi = static::capitalizeLabel($langUi);
        $langNative = static::capitalizeLabel($langNative);
        $regionUi = static::capitalizeLabel($regionUi);

        if ($langUi === '') {
            $langUi = $langNative !== '' ? $langNative : '';
        }
        if ($langUi === '') {
            return $regionUi;
        }

        $head = $regionUi !== '' ? $langUi . ' — ' . $regionUi : $langUi;
        if ($langNative === '' || strcasecmp($langNative, $langUi) === 0) {
            return $head;
        }

        return $head . ' (' . $langNative . ')';
    }

    /**
     * Flag file stem for a locale: region of hu_HU → hu, en_GB → gb.
     */
    public static function flagIsoForLocale(string $localeCode): string
    {
        $localeCode = str_replace('-', '_', trim($localeCode));
        if ($localeCode === '') {
            return '';
        }
        $parts = explode('_', $localeCode);
        $region = strtolower((string)end($parts));
        if (preg_match('/^[a-z]{2}$/', $region)) {
            return $region;
        }
        $lang = strtolower((string)$parts[0]);

        return preg_match('/^[a-z]{2}$/', $lang) ? $lang : '';
    }

    /**
     * locale code => flag iso2 for Select2 icons.
     *
     * @param list<string>|array<string, mixed> $localeCodes
     * @return array<string, string>
     */
    public static function flagMapForLocales(array $localeCodes): array
    {
        $out = [];
        foreach ($localeCodes as $key => $value) {
            // Prefer map keys when options are code => label.
            $code = is_int($key) ? trim((string)$value) : trim((string)$key);
            if ($code === '') {
                continue;
            }
            $iso = static::flagIsoForLocale($code);
            if ($iso !== '') {
                $out[$code] = $iso;
            }
        }

        return $out;
    }

    /**
     * Language name only (no region), in $inLocale.
     */
    public static function languageName(string $localeCode, ?string $inLocale = null): string
    {
        $localeCode = str_replace('-', '_', trim($localeCode));
        if ($localeCode === '') {
            return '';
        }
        $inLocale = AdminCountry::normalizeTranslateLocale(
            ($inLocale !== null && $inLocale !== '') ? $inLocale : I18n::getLocale()
        );
        $tag = str_replace('_', '-', $localeCode);
        $inTag = str_replace('_', '-', $inLocale);

        if (class_exists(Locale::class)) {
            $lang = Locale::getDisplayLanguage($tag, $inTag);
            if (is_string($lang) && $lang !== '' && strcasecmp($lang, $tag) !== 0) {
                return $lang;
            }
        }

        $full = static::displayName($localeCode, $inLocale);

        return $full !== '' ? $full : $localeCode;
    }

    /**
     * Region / country name only, in $inLocale.
     */
    public static function regionName(string $localeCode, ?string $inLocale = null): string
    {
        $localeCode = str_replace('-', '_', trim($localeCode));
        if ($localeCode === '') {
            return '';
        }
        $inLocale = AdminCountry::normalizeTranslateLocale(
            ($inLocale !== null && $inLocale !== '') ? $inLocale : I18n::getLocale()
        );
        $tag = str_replace('_', '-', $localeCode);
        $inTag = str_replace('_', '-', $inLocale);

        if (!class_exists(Locale::class)) {
            return '';
        }
        $region = Locale::getDisplayRegion($tag, $inTag);
        if (!is_string($region) || $region === '' || strcasecmp($region, $tag) === 0) {
            return '';
        }

        return $region;
    }

    /**
     * Language / locale endonym: name written in that language (magyar, English, 中文, …).
     */
    public static function endonym(string $localeCode): string
    {
        $localeCode = str_replace('-', '_', trim($localeCode));
        if ($localeCode === '') {
            return '';
        }

        return static::displayName($localeCode, $localeCode);
    }

    /**
     * Endonym label for UI (capitalized): Magyar (Magyarország), English (United Kingdom), …
     */
    public static function endonymLabel(string $localeCode): string
    {
        $label = static::endonym($localeCode);
        if ($label === '') {
            $label = str_replace('-', '_', trim($localeCode));
        }

        return static::capitalizeLabel($label);
    }

    /**
     * Uppercase first letter for Select2 labels (ICU often returns lowercase language names).
     */
    protected static function capitalizeLabel(string $label): string
    {
        $label = trim($label);
        if ($label === '') {
            return '';
        }

        return mb_strtoupper(mb_substr($label, 0, 1, 'UTF-8'), 'UTF-8')
            . mb_substr($label, 1, null, 'UTF-8');
    }

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
    protected static function fallbackLoginOptions(string $uiLocale): array
    {
        $out = [];
        foreach (BrowserLocale::availableLocales() as $code) {
            $out[$code] = static::loginLabel($code, $uiLocale);
        }
        if ($out === []) {
            return static::fallbackOptions($uiLocale);
        }
        asort($out, SORT_NATURAL | SORT_FLAG_CASE);

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
     * Sync languages from every distinct `countries.locale`.
     * - `name`: English canonical (ICU)
     * - `endonim_name`: language endonym (ICU, in itself)
     * - `visible`: true for Europe country locales + en_US + en_CA; else false
     * Translate targets: visible codes + en_GB + hu_HU
     *
     * @return array{created: int, updated: int, translations: int, visible: int}
     */
    public static function syncFromCountries(): array
    {
        /** @var \App\Model\Table\LanguagesTable $languages */
        $languages = (new self())->fetchTable('Languages');
        /** @var \App\Model\Table\CountriesTable $countries */
        $countries = (new self())->fetchTable('Countries');

        $connection = $languages->getConnection();
        $connection->execute('DELETE FROM i18n WHERE model = \'Languages\'');

        $rows = $countries->find()
            ->select([
                'Countries.locale',
                'continent_code' => 'Continents.code',
            ])
            ->join([
                'Continents' => [
                    'table' => 'continents',
                    'type' => 'INNER',
                    'conditions' => 'Continents.id = Countries.continent_id',
                ],
            ])
            ->where([
                'Countries.locale IS NOT' => null,
                'Countries.locale !=' => '',
            ])
            ->disableHydration()
            ->all();

        /** @var array<string, array{continents: array<string, true>}> $byLocale */
        $byLocale = [];
        foreach ($rows as $row) {
            $code = trim((string)($row['locale'] ?? ''));
            if ($code === '') {
                continue;
            }
            $continent = trim((string)($row['continent_code'] ?? ''));
            if (!isset($byLocale[$code])) {
                $byLocale[$code] = ['continents' => []];
            }
            if ($continent !== '') {
                $byLocale[$code]['continents'][$continent] = true;
            }
        }

        // Always keep US / CA English even if somehow missing from countries.
        foreach (['en_US', 'en_CA', 'en_GB', 'hu_HU'] as $must) {
            if (!isset($byLocale[$must])) {
                $byLocale[$must] = ['continents' => []];
            }
        }

        $localeCodes = array_keys($byLocale);
        sort($localeCodes);

        $visibleCodes = [];
        foreach ($byLocale as $code => $info) {
            if (static::isLoginVisibleLocale($code, $info['continents'])) {
                $visibleCodes[] = $code;
            }
        }

        $targets = array_values(array_unique(array_merge($visibleCodes, ['en_GB', 'hu_HU'])));
        sort($targets);

        $created = 0;
        $updated = 0;
        $translations = 0;
        $visible = 0;
        $pos = 10;
        $now = new DateTime();

        foreach ($localeCodes as $code) {
            $enName = static::displayName($code, 'en_GB') ?: $code;
            $endonim = static::capitalizeLabel(static::languageName($code, $code));
            if ($endonim === '') {
                $endonim = static::endonymLabel($code) ?: $enName;
            }
            if (mb_strlen($endonim) > 150) {
                $endonim = mb_substr($endonim, 0, 150);
            }
            if (mb_strlen($enName) > 150) {
                $enName = mb_substr($enName, 0, 150);
            }

            $isVisible = static::isLoginVisibleLocale($code, $byLocale[$code]['continents']);
            if ($isVisible) {
                $visible++;
            }

            $existing = $languages->find()->where(['Languages.code' => $code])->first();
            $wasNew = $existing === null;

            if ($wasNew) {
                $entity = $languages->newEntity([
                    'code' => $code,
                    'name' => $enName,
                    'endonim_name' => $endonim,
                    'visible' => $isVisible,
                    'pos' => $code === 'en_GB' ? 1 : ($code === 'en_US' ? 2 : ($code === 'en_CA' ? 3 : $pos)),
                    'created' => $now,
                    'modified' => $now,
                ]);
            } else {
                $entity = $existing;
                $entity->set('name', $enName);
                $entity->set('endonim_name', $endonim);
                $entity->set('visible', $isVisible);
                $entity->set('modified', $now);
                if (in_array($code, ['en_GB', 'en_US', 'en_CA'], true)) {
                    $entity->set('pos', $code === 'en_GB' ? 1 : ($code === 'en_US' ? 2 : 3));
                }
            }

            foreach ($targets as $targetLocale) {
                $label = static::displayName($code, $targetLocale) ?: $enName;
                $entity->translation($targetLocale)->set('name', $label, ['guard' => false]);
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

            if (!in_array($code, ['en_GB', 'en_US', 'en_CA'], true)) {
                $pos += 10;
            }
        }

        return compact('created', 'updated', 'translations', 'visible');
    }

    /**
     * Login-visible language: used in Europe, or US/Canadian English.
     *
     * @param array<string, true> $continentCodes
     */
    public static function isLoginVisibleLocale(string $localeCode, array $continentCodes): bool
    {
        $localeCode = str_replace('-', '_', trim($localeCode));
        if (in_array($localeCode, ['en_US', 'en_CA'], true)) {
            return true;
        }

        return isset($continentCodes['EUR']);
    }
}
