<?php
declare(strict_types=1);

namespace App\Utility;

use App\Auth\CurrentUser;
use Cake\Core\Configure;
use Cake\Http\ServerRequest;
use Cake\ORM\Locator\LocatorAwareTrait;
use DateTimeZone;

/**
 * Display / parse timezone = registered user's Countries.timezone (IANA).
 *
 * DB / App.defaultTimezone stay UTC. Convert only at LocaleDateParser boundaries.
 */
class AdminTimezone
{
    use LocatorAwareTrait;

    /**
     * Preferred zone when a country has many (capital / business default).
     *
     * @var array<string, string> iso2 => IANA
     */
    protected static array $preferredByIso2 = [
        'US' => 'America/New_York',
        'CA' => 'America/Toronto',
        'AU' => 'Australia/Sydney',
        'RU' => 'Europe/Moscow',
        'BR' => 'America/Sao_Paulo',
        'MX' => 'America/Mexico_City',
        'ID' => 'Asia/Jakarta',
        'CD' => 'Africa/Kinshasa',
        'KZ' => 'Asia/Almaty',
        'MN' => 'Asia/Ulaanbaatar',
        'PT' => 'Europe/Lisbon',
        'ES' => 'Europe/Madrid',
        'NZ' => 'Pacific/Auckland',
        'UA' => 'Europe/Kyiv',
        'CN' => 'Asia/Shanghai',
        'CL' => 'America/Santiago',
        'AR' => 'America/Argentina/Buenos_Aires',
        'EC' => 'America/Guayaquil',
        'GL' => 'America/Nuuk',
        'UM' => 'Pacific/Wake',
    ];

    protected static ?string $requestTimezone = null;

    /**
     * @var array<int, string>
     */
    protected static array $byCountryId = [];

    public static function clearCache(): void
    {
        static::$requestTimezone = null;
        static::$byCountryId = [];
    }

    /**
     * IANA timezone for the current request (logged-in user's country), else App UTC.
     */
    public static function current(?ServerRequest $request = null): string
    {
        if (static::$requestTimezone !== null) {
            return static::$requestTimezone;
        }

        $countryId = CurrentUser::countryId($request);
        $tz = $countryId > 0 ? static::forCountry($countryId) : null;
        if ($tz === null || $tz === '') {
            $tz = static::appDefault();
        }

        static::$requestTimezone = $tz;

        return $tz;
    }

    /**
     * @return string|null Valid IANA name or null
     */
    public static function forCountry(int $countryId): ?string
    {
        if ($countryId < 1) {
            return null;
        }
        if (isset(static::$byCountryId[$countryId])) {
            return static::$byCountryId[$countryId];
        }

        /** @var \App\Model\Table\CountriesTable $countries */
        $countries = (new self())->fetchTable('Countries');
        $row = $countries->find()
            ->select(['Countries.timezone'])
            ->where(['Countries.id' => $countryId])
            ->disableHydration()
            ->first();

        $tz = is_array($row) ? trim((string)($row['timezone'] ?? '')) : '';
        if ($tz === '' || !static::isValid($tz)) {
            static::$byCountryId[$countryId] = static::appDefault();

            return static::$byCountryId[$countryId];
        }

        static::$byCountryId[$countryId] = $tz;

        return $tz;
    }

    public static function appDefault(): string
    {
        $tz = (string)(Configure::read('App.defaultTimezone') ?: 'UTC');

        return static::isValid($tz) ? $tz : 'UTC';
    }

    public static function isValid(string $timezone): bool
    {
        try {
            new DateTimeZone($timezone);

            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * Resolve primary IANA timezone for an ISO 3166-1 alpha-2 code.
     */
    public static function guessForIso2(string $iso2): string
    {
        $iso2 = strtoupper(trim($iso2));
        if ($iso2 === '') {
            return 'UTC';
        }
        if (isset(static::$preferredByIso2[$iso2])) {
            $preferred = static::$preferredByIso2[$iso2];
            if (static::isValid($preferred)) {
                return $preferred;
            }
        }

        try {
            $list = DateTimeZone::listIdentifiers(DateTimeZone::PER_COUNTRY, $iso2);
        } catch (\Throwable) {
            $list = [];
        }
        if ($list === []) {
            return 'UTC';
        }
        // Prefer a Europe/* / capital-like first match when preferred missing
        foreach ($list as $id) {
            if (str_starts_with($id, 'Europe/')) {
                return $id;
            }
        }

        return $list[0];
    }

    /**
     * @return \DateTimeZone
     */
    public static function dateTimeZone(?ServerRequest $request = null): DateTimeZone
    {
        return new DateTimeZone(static::current($request));
    }
}
