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
 * 2. Cookie AdminWorkingCountryId (~30 days)
 * 3. Default: Countries.iso2 = HU (fallback: first visible country)
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
            'expires' => new DateTime('+30 days'),
            'path' => '/',
            'httponly' => true,
            'samesite' => CookieInterface::SAMESITE_LAX,
        ]);

        return $response->withCookie($cookie);
    }

    /**
     * Select2 / form options: id => "Name (ISO2)", visible countries only.
     *
     * @return array<int, string>
     */
    public static function options(): array
    {
        /** @var \App\Model\Table\CountriesTable $countries */
        $countries = (new self())->fetchTable('Countries');
        if ($countries->hasBehavior('Translate')) {
            $countries->getBehavior('Translate')->setLocale(I18n::getLocale());
        }

        $rows = $countries->find()
            ->select(['Countries.id', 'Countries.iso2', 'Countries.name'])
            ->where(['Countries.visible' => true])
            ->orderBy(['Countries.name' => 'ASC'])
            ->all();

        $out = [];
        foreach ($rows as $row) {
            $id = (int)$row->get('id');
            $name = (string)$row->get('name');
            $iso = (string)$row->get('iso2');
            $out[$id] = $name . ' (' . $iso . ')';
        }

        return $out;
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
