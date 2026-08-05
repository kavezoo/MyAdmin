<?php
declare(strict_types=1);

namespace App\Utility;

use Cake\Http\Cookie\Cookie;
use Cake\Http\Cookie\CookieInterface;
use Cake\Http\Response;
use Cake\Http\ServerRequest;
use Cake\I18n\DateTime;
use Cake\I18n\I18n;
use Cake\ORM\Locator\LocatorAwareTrait;
use Cake\Routing\Router;

/**
 * Admin „working country” — which country context the Admin UI uses.
 *
 * Resolution order (until Users.country_id after login exists):
 * 1. Session Admin.workingCountryId
 * 2. Cookie AdminWorkingCountryId (≥ 1 year)
 * 3. Default: Countries.iso2 = HU (fallback: first visible country)
 *
 * Country *names* in Select2 / labels always follow the **page locale**
 * (I18n / App.adminLocale) via Countries Translate — not a language switcher.
 * Only `Countries.visible = true` rows appear in option lists.
 *
 * Set via Setups index Select2 (or later: login / user profile).
 */
class AdminCountry
{
    use LocatorAwareTrait;

    public const SESSION_KEY = 'Admin.workingCountryId';

    public const COOKIE_NAME = 'AdminWorkingCountryId';

    public const DEFAULT_ISO2 = 'HU';

    /**
     * Map non-seed / alias locales to Translate i18n.locale values.
     * (e.g. adminLocale en_UK → seeded en_GB country names.)
     */
    public static function normalizeTranslateLocale(string $locale): string
    {
        $locale = trim($locale);
        if ($locale === '') {
            return 'en_GB';
        }

        $aliases = [
            'en_UK' => 'en_GB',
            'en' => 'en_GB',
            'eng' => 'en_GB',
            'hu' => 'hu_HU',
            'de' => 'de_DE',
            'sk' => 'sk_SK',
        ];

        return $aliases[$locale] ?? $locale;
    }

    /**
     * Apply page locale to Countries (+ Continents) Translate behaviors.
     * Also applies Samples / Parents via {@see AdminTranslate::applyLocales()}.
     */
    public static function applyTranslateLocale(?string $locale = null): void
    {
        $locale = static::normalizeTranslateLocale($locale ?? I18n::getLocale());
        /** @var \App\Model\Table\CountriesTable $countries */
        $countries = (new self())->fetchTable('Countries');
        $countries->getBehavior('Translate')->setLocale($locale);
        if ($countries->hasAssociation('Continents') && $countries->Continents->hasBehavior('Translate')) {
            $countries->Continents->getBehavior('Translate')->setLocale($locale);
        }
        AdminTranslate::applyLocales(['Samples', 'Parents'], $locale);
    }

    /**
     * Current working country id (always a positive int when Countries exist).
     */
    public static function id(?ServerRequest $request = null): int
    {
        $request ??= Router::getRequest();
        $fromSession = null;
        $fromCookie = null;

        if ($request !== null) {
            $sessionVal = $request->getSession()->read(self::SESSION_KEY);
            if (is_numeric($sessionVal) && (int)$sessionVal > 0) {
                $fromSession = (int)$sessionVal;
            }
            $cookieVal = $request->getCookie(self::COOKIE_NAME);
            if (is_numeric($cookieVal) && (int)$cookieVal > 0) {
                $fromCookie = (int)$cookieVal;
            }
        }

        foreach ([$fromSession, $fromCookie] as $candidate) {
            if ($candidate !== null && static::isValidCountryId($candidate)) {
                if ($fromSession === null && $request !== null) {
                    $request->getSession()->write(self::SESSION_KEY, $candidate);
                }

                return $candidate;
            }
        }

        $defaultId = static::defaultCountryId();
        if ($request !== null && $defaultId > 0) {
            $request->getSession()->write(self::SESSION_KEY, $defaultId);
        }

        return $defaultId;
    }

    /**
     * Persist working country (session + cookie). Returns $response with cookie set.
     */
    public static function set(int $countryId, ServerRequest $request, Response $response): Response
    {
        if (!static::isValidCountryId($countryId)) {
            return $response;
        }

        $request->getSession()->write(self::SESSION_KEY, $countryId);

        $cookie = Cookie::create(self::COOKIE_NAME, (string)$countryId, [
            'expires' => new DateTime('+400 days'),
            'path' => '/',
            'httponly' => true,
            'samesite' => CookieInterface::SAMESITE_LAX,
        ]);

        return $response->withCookie($cookie);
    }

    /**
     * Registration country Select2: id => endonim_name only.
     * Only `Countries.visible = true`, sorted by endonym so people find their own country.
     *
     * @return array<int, string>
     */
    public static function registerOptions(): array
    {
        /** @var \App\Model\Table\CountriesTable $countries */
        $countries = (new self())->fetchTable('Countries');
        $rows = $countries->find()
            ->select([
                'Countries.id',
                'Countries.endonim_name',
                'Countries.iso2',
            ])
            ->where(['Countries.visible' => true])
            ->orderBy([
                'Countries.endonim_name' => 'ASC',
                'Countries.id' => 'ASC',
            ])
            ->disableHydration()
            ->all();

        $out = [];
        foreach ($rows as $row) {
            $id = (int)$row['id'];
            $label = trim((string)($row['endonim_name'] ?? ''));
            if ($label === '') {
                $label = (string)($row['iso2'] ?? $id);
            }
            $out[$id] = $label;
        }

        return $out;
    }

    /**
     * id => countries.locale for the registration Select2 (visible countries only).
     *
     * @return array<int, string>
     */
    public static function registerLocaleMap(): array
    {
        /** @var \App\Model\Table\CountriesTable $countries */
        $countries = (new self())->fetchTable('Countries');
        $rows = $countries->find()
            ->select(['Countries.id', 'Countries.locale'])
            ->where(['Countries.visible' => true])
            ->disableHydration()
            ->all();

        $out = [];
        foreach ($rows as $row) {
            $out[(int)$row['id']] = (string)$row['locale'];
        }

        return $out;
    }

    /**
     * Registration / auth country Select2: id => lowercase iso2 (for flag icons).
     *
     * @return array<int, string>
     */
    public static function registerFlagMap(): array
    {
        /** @var \App\Model\Table\CountriesTable $countries */
        $countries = (new self())->fetchTable('Countries');
        $rows = $countries->find()
            ->select(['Countries.id', 'Countries.iso2'])
            ->where(['Countries.visible' => true])
            ->disableHydration()
            ->all();

        $out = [];
        foreach ($rows as $row) {
            $id = (int)($row['id'] ?? 0);
            $iso = strtolower(trim((string)($row['iso2'] ?? '')));
            if ($id > 0 && preg_match('/^[a-z]{2}$/', $iso)) {
                $out[$id] = $iso;
            }
        }

        return $out;
    }

    /**
     * Country id => lowercase iso2 (any countries; for complete-profile / mixed lists).
     *
     * @param list<int>|null $onlyIds null = all
     * @return array<int, string>
     */
    public static function iso2Map(?array $onlyIds = null): array
    {
        /** @var \App\Model\Table\CountriesTable $countries */
        $countries = (new self())->fetchTable('Countries');
        $query = $countries->find()
            ->select(['Countries.id', 'Countries.iso2'])
            ->disableHydration();
        if ($onlyIds !== null) {
            $onlyIds = array_values(array_filter(array_map('intval', $onlyIds), static fn(int $id): bool => $id > 0));
            if ($onlyIds === []) {
                return [];
            }
            $query->where(['Countries.id IN' => $onlyIds]);
        }
        $out = [];
        foreach ($query->all() as $row) {
            $id = (int)($row['id'] ?? 0);
            $iso = strtolower(trim((string)($row['iso2'] ?? '')));
            if ($id > 0 && preg_match('/^[a-z]{2}$/', $iso)) {
                $out[$id] = $iso;
            }
        }

        return $out;
    }

    /**
     * Country allowed on the registration form (`visible = true`).
     */
    public static function isRegisterCountryId(int $countryId): bool
    {
        if ($countryId < 1) {
            return false;
        }
        /** @var \App\Model\Table\CountriesTable $countries */
        $countries = (new self())->fetchTable('Countries');

        return $countries->exists([
            'Countries.id' => $countryId,
            'Countries.visible' => true,
        ]);
    }

    /**
     * Select2 / list options: id => "Translated name (ISO2)".
     * Only visible countries; names follow the page locale (Translate).
     *
     * @return array<int, string>
     */
    public static function options(?string $locale = null): array
    {
        return static::buildOptions($locale, withLocaleSuffix: false);
    }

    /**
     * Registration / auth select: id => "Name (ISO2) — locale"
     * Distinguishes variants e.g. Franciaország (FR) — fr_FR vs (FX) — en_FX.
     *
     * @return array<int, string>
     */
    public static function optionsWithLocale(?string $locale = null): array
    {
        return static::buildOptions($locale, withLocaleSuffix: true);
    }

    /**
     * Visible country id => primary locale (countries.locale).
     *
     * @return array<int, string>
     */
    public static function localeMap(): array
    {
        /** @var \App\Model\Table\CountriesTable $countries */
        $countries = (new self())->fetchTable('Countries');
        $ids = $countries->loginVisibleCountryIds();
        if ($ids === []) {
            $rows = $countries->find()
                ->select(['Countries.id', 'Countries.locale'])
                ->where(['Countries.visible' => true])
                ->all();
        } else {
            $rows = $countries->find()
                ->select(['Countries.id', 'Countries.locale'])
                ->where(['Countries.id IN' => $ids])
                ->all();
        }

        $out = [];
        foreach ($rows as $row) {
            $out[(int)$row->get('id')] = (string)$row->get('locale');
        }

        return $out;
    }

    /**
     * Locale string stored on a visible country, or null.
     */
    public static function localeForCountry(int $countryId): ?string
    {
        if ($countryId < 1) {
            return null;
        }
        /** @var \App\Model\Table\CountriesTable $countries */
        $countries = (new self())->fetchTable('Countries');
        $row = $countries->find()
            ->select(['Countries.locale'])
            ->where([
                'Countries.id' => $countryId,
            ])
            ->first();
        if ($row === null) {
            return null;
        }
        $locale = trim((string)$row->get('locale'));

        return $locale !== '' ? $locale : null;
    }

    /**
     * @return array<int, string>
     */
    protected static function buildOptions(?string $locale, bool $withLocaleSuffix): array
    {
        /** @var \App\Model\Table\CountriesTable $countries */
        $countries = (new self())->fetchTable('Countries');
        $rows = $countries->find(
            'loginVisible',
            locale: $locale ?? I18n::getLocale()
        )->all();

        $out = [];
        foreach ($rows as $row) {
            $id = (int)$row->get('id');
            $name = trim((string)$row->get('name'));
            $iso = (string)$row->get('iso2');
            $countryLocale = trim((string)$row->get('locale'));
            if ($name === '') {
                $name = $iso;
            }
            $label = $name . ' (' . $iso . ')';
            if ($withLocaleSuffix && $countryLocale !== '') {
                $label .= ' — ' . $countryLocale;
            }
            $out[$id] = $label;
        }

        return $out;
    }

    /**
     * Options for “visible countries for this country” multi-select (Countries form).
     * Master list: countries.visible = 1 (+ en_GB always). English first.
     *
     * @return array<int, string>
     */
    public static function masterVisibleOptions(?string $locale = null): array
    {
        /** @var \App\Model\Table\CountriesTable $countries */
        $countries = (new self())->fetchTable('Countries');
        $enId = $countries->englishDefaultCountryId();
        $rows = $countries->find('visibleTranslated', locale: $locale ?? I18n::getLocale())
            ->orderBy([
                'CASE WHEN Countries.locale = \'en_GB\' THEN 0 WHEN Countries.locale LIKE \'en_%\' THEN 1 ELSE 2 END' => 'ASC',
                'Countries.pos' => 'ASC',
            ], true)
            ->all();

        $out = [];
        if ($enId !== null) {
            // Will be filled when row appears; ensure key exists after loop
        }
        foreach ($rows as $row) {
            $id = (int)$row->get('id');
            $name = trim((string)$row->get('name'));
            $iso = (string)$row->get('iso2');
            if ($name === '') {
                $name = $iso;
            }
            $out[$id] = $name . ' (' . $iso . ')';
        }
        if ($enId !== null && !isset($out[$enId])) {
            static::applyTranslateLocale($locale);
            $en = $countries->find()
                ->select(['Countries.id', 'Countries.iso2', 'Countries.name', 'Countries.locale'])
                ->where(['Countries.id' => $enId])
                ->first();
            if ($en !== null) {
                $name = trim((string)$en->get('name')) ?: (string)$en->get('iso2');
                $prepend = [$enId => $name . ' (' . $en->get('iso2') . ')'];
                $out = $prepend + $out;
            }
        } elseif ($enId !== null && isset($out[$enId])) {
            $label = $out[$enId];
            unset($out[$enId]);
            $out = [$enId => $label] + $out;
        }

        return $out;
    }

    /**
     * Label for titles: "Translated name (ISO2)" (even if country is currently invisible).
     */
    public static function label(int $countryId, ?string $locale = null): string
    {
        if ($countryId < 1) {
            return '';
        }

        // Prefer options list (visible + sorted) when present.
        $options = static::options($locale);
        if (isset($options[$countryId])) {
            return $options[$countryId];
        }

        /** @var \App\Model\Table\CountriesTable $countries */
        $countries = (new self())->fetchTable('Countries');
        static::applyTranslateLocale($locale);
        $row = $countries->find()
            ->select(['Countries.id', 'Countries.iso2', 'Countries.name'])
            ->where(['Countries.id' => $countryId])
            ->first();
        if ($row === null) {
            return '';
        }
        $name = trim((string)$row->get('name'));
        $iso = (string)$row->get('iso2');

        return ($name !== '' ? $name : $iso) . ' (' . $iso . ')';
    }

    /**
     * Visible country primary keys (for multi-country setup create).
     *
     * @return list<int>
     */
    public static function visibleIds(): array
    {
        /** @var \App\Model\Table\CountriesTable $countries */
        $countries = (new self())->fetchTable('Countries');
        $ids = $countries->loginVisibleCountryIds();
        if ($ids !== []) {
            return $ids;
        }

        $fallback = $countries->find()
            ->select(['Countries.id'])
            ->where(['Countries.visible' => true])
            ->orderBy(['Countries.id' => 'ASC'])
            ->all()
            ->extract('id')
            ->toList();

        return $countries->ensureEnglishDefaultFirst(array_map('intval', $fallback));
    }

    public static function isValidCountryId(int $countryId): bool
    {
        if ($countryId < 1) {
            return false;
        }
        /** @var \App\Model\Table\CountriesTable $countries */
        $countries = (new self())->fetchTable('Countries');
        $ids = $countries->loginVisibleCountryIds();
        if ($ids !== []) {
            return in_array($countryId, $ids, true);
        }

        return $countries->exists([
            'Countries.id' => $countryId,
            'Countries.visible' => true,
        ]);
    }

    public static function defaultCountryId(): int
    {
        /** @var \App\Model\Table\CountriesTable $countries */
        $countries = (new self())->fetchTable('Countries');
        $row = $countries->find()
            ->select(['Countries.id'])
            ->where([
                'Countries.iso2' => self::DEFAULT_ISO2,
                'Countries.visible' => true,
            ])
            ->first();
        if ($row !== null) {
            return (int)$row->get('id');
        }
        $any = $countries->find()
            ->select(['Countries.id'])
            ->where(['Countries.visible' => true])
            ->orderBy(['Countries.id' => 'ASC'])
            ->first();

        return $any !== null ? (int)$any->get('id') : 0;
    }
}
