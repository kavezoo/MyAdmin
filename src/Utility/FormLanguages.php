<?php
declare(strict_types=1);

namespace App\Utility;

use App\Auth\CurrentUser;
use Cake\Core\Configure;
use Cake\ORM\Locator\LocatorAwareTrait;

/**
 * Form language tabs from countries visible for the **active** country
 * (`Users.country_id` / AdminCountry fallback) via `country_visibilities`.
 *
 * Tabs = own country language + additional languages picked on Countries form.
 * One tab per language short code (SK, HU, EN, …) — own language first.
 */
class FormLanguages
{
    use LocatorAwareTrait;

    /**
     * @return list<array{
     *     locale: string,
     *     code: string,
     *     iso2: string,
     *     country_id: int,
     *     country_name: string,
     *     countries: list<array{iso2: string, name: string, country_id: int}>
     * }>
     */
    public static function tabs(?int $activeCountryId = null): array
    {
        AdminCountry::applyTranslateLocale();

        /** @var \App\Model\Table\CountriesTable $countries */
        $countries = (new self())->fetchTable('Countries');

        $activeCountryId = static::resolveActiveCountryId($activeCountryId);

        $rows = $countries->find('visibleForCountry', activeCountryId: $activeCountryId)
            ->select([
                'Countries.id',
                'Countries.iso2',
                'Countries.locale',
                'Countries.pos',
                'Countries.name',
            ])
            ->where([
                'Countries.locale IS NOT' => null,
                'Countries.locale !=' => '',
            ])
            ->all();

        $byCode = [];
        $orderIndex = 0;
        foreach ($rows as $row) {
            $locale = trim((string)$row->get('locale'));
            if ($locale === '') {
                continue;
            }
            $code = static::shortCode($locale);
            if ($code === '') {
                continue;
            }

            $iso2 = strtoupper(trim((string)$row->get('iso2')));
            $name = trim((string)$row->get('name'));
            if ($name === '') {
                $name = $iso2 !== '' ? $iso2 : $code;
            }
            $countryId = (int)$row->get('id');
            $countryEntry = [
                'iso2' => $iso2,
                'name' => $name,
                'country_id' => $countryId,
            ];
            $isOwn = $activeCountryId > 0 && $countryId === $activeCountryId;

            if (!isset($byCode[$code])) {
                $byCode[$code] = [
                    'locale' => $locale,
                    'code' => $code,
                    'iso2' => $iso2,
                    'country_id' => $countryId,
                    'country_name' => $name,
                    'countries' => [$countryEntry],
                    '_own' => $isOwn,
                    '_pos' => $orderIndex++,
                    '_seenIso' => [$iso2 => true],
                ];
                continue;
            }

            if ($iso2 !== '' && isset($byCode[$code]['_seenIso'][$iso2])) {
                continue;
            }
            if ($iso2 !== '') {
                $byCode[$code]['_seenIso'][$iso2] = true;
            }
            $byCode[$code]['countries'][] = $countryEntry;
            if ($isOwn) {
                $byCode[$code]['_own'] = true;
                $byCode[$code]['country_id'] = $countryId;
                $byCode[$code]['country_name'] = $name;
                $byCode[$code]['iso2'] = $iso2;
                $byCode[$code]['locale'] = $locale;
            }
        }

        $tabs = array_values($byCode);
        usort($tabs, static function (array $a, array $b): int {
            if ($a['_own'] !== $b['_own']) {
                return $a['_own'] ? -1 : 1;
            }
            if ($a['_pos'] !== $b['_pos']) {
                return $a['_pos'] <=> $b['_pos'];
            }

            return strcmp($a['code'], $b['code']);
        });

        return array_map(static function (array $tab): array {
            unset($tab['_own'], $tab['_pos'], $tab['_seenIso']);

            return $tab;
        }, $tabs);
    }

    /**
     * Locale that maps to entity root fields on the form.
     *
     * Must match the Table TranslateBehavior `defaultLocale` (EAV: main table columns).
     * Prefer that locale when it is among the country’s tabs; otherwise the first tab
     * (own country language).
     *
     * Using App.defaultLocale (e.g. hu_HU) here while Translate defaultLocale is en_GB
     * breaks edit: empty `_translations.en_GB.*` inputs are hoisted onto root by
     * TranslateBehavior::beforeMarshal and wipe required name/title.
     */
    public static function defaultLocaleForForm(?int $activeCountryId = null): string
    {
        $tabs = static::tabs($activeCountryId);
        if ($tabs === []) {
            return static::eavDefaultLocale();
        }

        $preferred = static::eavDefaultLocale();
        foreach ($tabs as $tab) {
            $locale = (string)($tab['locale'] ?? '');
            if ($locale === $preferred || (static::isEnglish($locale) && static::isEnglish($preferred))) {
                return $locale !== '' ? $locale : $preferred;
            }
        }

        return (string)$tabs[0]['locale'];
    }

    /**
     * @return list<string>
     */
    public static function locales(?int $activeCountryId = null): array
    {
        return array_column(static::tabs($activeCountryId), 'locale');
    }

    public static function shortCode(string $locale): string
    {
        $locale = str_replace('-', '_', trim($locale));
        $primary = explode('_', $locale, 2)[0] ?? $locale;

        return strtoupper($primary);
    }

    public static function isEnglish(string $locale, ?string $code = null): bool
    {
        $code ??= static::shortCode($locale);

        return $code === 'EN';
    }

    /**
     * Cake Translate EAV canonical locale — stored on the main table columns.
     * Must stay in sync with Table `addBehavior('Translate', ['defaultLocale' => …])`.
     */
    public static function eavDefaultLocale(): string
    {
        $configured = Configure::read('App.translateDefaultLocale');
        if (is_string($configured) && trim($configured) !== '') {
            return AdminCountry::normalizeTranslateLocale(trim($configured)) ?? 'en_GB';
        }

        return 'en_GB';
    }

    /**
     * App UI / I18n default locale (may differ from EAV defaultLocale).
     */
    public static function translateDefaultLocale(): string
    {
        $configured = Configure::read('App.defaultLocale');
        if (is_string($configured) && trim($configured) !== '') {
            return AdminCountry::normalizeTranslateLocale(trim($configured)) ?? static::eavDefaultLocale();
        }

        return static::eavDefaultLocale();
    }

    protected static function resolveActiveCountryId(?int $activeCountryId): int
    {
        $activeCountryId ??= CurrentUser::countryId();
        if ($activeCountryId < 1) {
            $activeCountryId = AdminCountry::id();
        }

        return $activeCountryId > 0 ? $activeCountryId : 0;
    }
}
