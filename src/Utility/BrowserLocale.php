<?php
declare(strict_types=1);

namespace App\Utility;

use Cake\Core\Configure;
use Cake\Http\Cookie\Cookie;
use Cake\Http\Cookie\CookieInterface;
use Cake\Http\Response;
use Cake\Http\ServerRequest;
use Cake\I18n\DateTime;
use Cake\ORM\Locator\LocatorAwareTrait;

/**
 * Resolve / persist UI locale against visible Countries.locale values.
 *
 * Resolution order (guest / auth screens):
 * 1. Session App.uiLocale
 * 2. Cookie AppUiLocale (≥ 1 year)
 * 3. Browser Accept-Language
 * 4. App.defaultLocale / hu_HU / first available
 *
 * After login: {@see forLoggedIn()} — Users.country_id locale when set,
 * otherwise the login-screen session/cookie language for the whole Admin UI.
 */
class BrowserLocale
{
    use LocatorAwareTrait;

    public const SESSION_KEY = 'App.uiLocale';

    public const COOKIE_NAME = 'AppUiLocale';

    /**
     * Distinct `countries.locale` values for visible countries (DB casing).
     *
     * @return list<string>
     */
    public static function availableLocales(): array
    {
        static $cache = null;
        if (is_array($cache)) {
            return $cache;
        }

        /** @var \App\Model\Table\CountriesTable $countries */
        $countries = (new self())->fetchTable('Countries');
        $rows = $countries->find()
            ->select(['Countries.locale'])
            ->where([
                'Countries.visible' => true,
                'Countries.locale IS NOT' => null,
                'Countries.locale !=' => '',
            ])
            ->distinct(['Countries.locale'])
            ->orderBy(['Countries.locale' => 'ASC'])
            ->all();

        $out = [];
        foreach ($rows as $row) {
            $locale = trim((string)$row->get('locale'));
            if ($locale !== '') {
                $out[] = $locale;
            }
        }

        $cache = $out;

        return $cache;
    }

    /**
     * Canonical locale string from DB list, or null if unknown.
     */
    public static function canonicalize(string $locale): ?string
    {
        $locale = trim($locale);
        if ($locale === '') {
            return null;
        }
        $want = static::normalize($locale);
        foreach (static::availableLocales() as $canonical) {
            if (static::normalize($canonical) === $want) {
                return $canonical;
            }
        }

        return null;
    }

    /**
     * Best locale for this request (e.g. hu_HU), matched to a visible country locale.
     */
    public static function detect(ServerRequest $request): string
    {
        $available = static::availableLocales();
        $fallback = static::fallbackLocale($available);

        if ($available === []) {
            return $fallback;
        }

        $byNormalized = [];
        foreach ($available as $locale) {
            $byNormalized[static::normalize($locale)] = $locale;
        }

        $accepted = $request->acceptLanguage();
        if ($accepted === []) {
            return $fallback;
        }

        foreach ($accepted as $tag) {
            $normalized = static::normalize((string)$tag);
            if ($normalized === '') {
                continue;
            }

            if (isset($byNormalized[$normalized])) {
                return $byNormalized[$normalized];
            }

            $primary = explode('_', $normalized, 2)[0];
            $region = explode('_', $normalized, 2)[1] ?? '';
            $candidates = [];
            foreach ($byNormalized as $key => $canonical) {
                if (explode('_', $key, 2)[0] === $primary) {
                    $candidates[$key] = $canonical;
                }
            }
            if ($candidates === []) {
                continue;
            }

            if ($region !== '') {
                $want = $primary . '_' . $region;
                if (isset($candidates[$want])) {
                    return $candidates[$want];
                }
            }

            $std = $primary . '_' . strtoupper($primary);
            $stdKey = static::normalize($std);
            if (isset($candidates[$stdKey])) {
                return $candidates[$stdKey];
            }

            $defaultKey = static::normalize((string)Configure::read('App.defaultLocale', 'hu_HU'));
            if (isset($candidates[$defaultKey])) {
                return $candidates[$defaultKey];
            }

            ksort($candidates);

            return (string)reset($candidates);
        }

        return $fallback;
    }

    /**
     * Persist locale in session (and optionally prepare cookie via {@see withCookie()}).
     */
    public static function remember(ServerRequest $request, string $locale): void
    {
        $canonical = static::canonicalize($locale);
        if ($canonical === null) {
            return;
        }
        $request->getSession()->write(self::SESSION_KEY, $canonical);
    }

    /**
     * Session + cookie (≥ 1 year). Returns $response with cookie set.
     */
    public static function persist(ServerRequest $request, Response $response, string $locale): Response
    {
        $canonical = static::canonicalize($locale);
        if ($canonical === null) {
            return $response;
        }

        static::remember($request, $canonical);

        return static::withCookie($response, $canonical);
    }

    public static function withCookie(Response $response, string $locale): Response
    {
        $canonical = static::canonicalize($locale);
        if ($canonical === null) {
            return $response;
        }

        $cookie = Cookie::create(self::COOKIE_NAME, $canonical, [
            'expires' => new DateTime('+400 days'),
            'path' => '/',
            'httponly' => true,
            'samesite' => CookieInterface::SAMESITE_LAX,
        ]);

        return $response->withCookie($cookie);
    }

    /**
     * Session → cookie → detect (+ optional session write).
     */
    public static function resolve(ServerRequest $request, bool $persistSession = true): string
    {
        $fromSession = $request->getSession()->read(self::SESSION_KEY);
        if (is_string($fromSession)) {
            $canonical = static::canonicalize($fromSession);
            if ($canonical !== null) {
                return $canonical;
            }
        }

        $fromCookie = $request->getCookie(self::COOKIE_NAME);
        if (is_string($fromCookie)) {
            $canonical = static::canonicalize($fromCookie);
            if ($canonical !== null) {
                if ($persistSession) {
                    static::remember($request, $canonical);
                }

                return $canonical;
            }
        }

        $detected = static::detect($request);
        if ($persistSession) {
            static::remember($request, $detected);
        }

        return $detected;
    }

    /**
     * Locale stored on the user via Users.country_id → Countries.locale.
     *
     * @param mixed $user Identity / array / entity with country_id
     */
    public static function localeFromUser(mixed $user): ?string
    {
        if ($user === null) {
            return null;
        }

        $countryId = 0;
        if (is_object($user) && method_exists($user, 'get')) {
            $countryId = (int)($user->get('country_id') ?? 0);
        } elseif (is_array($user)) {
            $countryId = (int)($user['country_id'] ?? 0);
        } elseif (is_object($user) && isset($user->country_id)) {
            $countryId = (int)$user->country_id;
        }

        if ($countryId < 1) {
            return null;
        }

        return AdminCountry::localeForCountry($countryId);
    }

    /**
     * Locale for a logged-in request (Admin / profile / whole UI).
     *
     * 1. Users.country_id → Countries.locale (account language from login)
     * 2. Session / cookie (language active on the login screen)
     * 3. App.adminLocale / App.defaultLocale when still unknown
     *
     * @param mixed $identity Request identity attribute
     */
    public static function forLoggedIn(ServerRequest $request, mixed $identity = null): string
    {
        $fromUser = static::localeFromUser($identity);
        if ($fromUser !== null) {
            return $fromUser;
        }

        $resolved = static::resolve($request, persistSession: false);
        if (static::isKnownLocale($resolved)) {
            return $resolved;
        }

        foreach ([
            (string)Configure::read('App.adminLocale', ''),
            (string)Configure::read('App.defaultLocale', 'hu_HU'),
            'hu_HU',
        ] as $candidate) {
            if ($candidate === '') {
                continue;
            }
            $canonical = static::canonicalize($candidate);
            if ($canonical !== null) {
                return $canonical;
            }
        }

        return $resolved;
    }

    public static function isKnownLocale(string $locale): bool
    {
        return static::canonicalize($locale) !== null;
    }

    /**
     * @param list<string> $available
     */
    protected static function fallbackLocale(array $available): string
    {
        $preferred = [
            (string)Configure::read('App.defaultLocale', 'hu_HU'),
            'hu_HU',
            'en_GB',
        ];
        $byNormalized = [];
        foreach ($available as $locale) {
            $byNormalized[static::normalize($locale)] = $locale;
        }
        foreach ($preferred as $want) {
            $key = static::normalize($want);
            if (isset($byNormalized[$key])) {
                return $byNormalized[$key];
            }
        }

        if ($available !== []) {
            return $available[0];
        }

        return (string)Configure::read('App.defaultLocale', 'hu_HU');
    }

    protected static function normalize(string $locale): string
    {
        return strtolower(str_replace('-', '_', trim($locale)));
    }
}
