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
        $rows = $countries->find()
            ->select(['Countries.id', 'Countries.locale'])
            ->where(['Countries.visible' => true])
            ->all();

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
                'Countries.visible' => true,
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
            'visibleTranslated',
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
        $ids = $countries->find()
            ->select(['Countries.id'])
            ->where(['Countries.visible' => true])
            ->orderBy(['Countries.id' => 'ASC'])
            ->all()
            ->extract('id')
            ->toList();

        return array_map('intval', $ids);
    }

    public static function isValidCountryId(int $countryId): bool
    {
        if ($countryId < 1) {
            return false;
        }
        /** @var \App\Model\Table\CountriesTable $countries */
        $countries = (new self())->fetchTable('Countries');

        // Qualify columns: i18n also has `visible` (Translate join).
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
