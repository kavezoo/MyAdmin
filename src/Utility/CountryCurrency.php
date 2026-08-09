<?php
declare(strict_types=1);

namespace App\Utility;

use App\Model\Entity\Competition;
use Cake\Datasource\EntityInterface;
use Cake\ORM\Locator\LocatorAwareTrait;

/**
 * Official ISO 4217 currency for a country / competition.
 */
class CountryCurrency
{
    use LocatorAwareTrait;

    /**
     * Common currencies for Select2 (code => label).
     *
     * @return array<string, string>
     */
    public static function options(): array
    {
        $codes = [
            'HUF', 'EUR', 'CZK', 'PLN', 'RON', 'BGN', 'RSD', 'UAH',
            'GBP', 'CHF', 'USD', 'CAD', 'SEK', 'NOK', 'DKK', 'TRY',
        ];
        $out = [];
        foreach ($codes as $code) {
            $out[$code] = $code;
        }

        return $out;
    }

    /**
     * Fallback when countries.currency is missing — iso2 → ISO 4217.
     */
    public static function fromIso2(string $iso2): string
    {
        $iso2 = strtoupper(trim($iso2));
        if ($iso2 === '') {
            return 'HUF';
        }

        static $map = [
            'HU' => 'HUF',
            'AT' => 'EUR', 'BE' => 'EUR', 'CY' => 'EUR', 'DE' => 'EUR', 'EE' => 'EUR',
            'ES' => 'EUR', 'FI' => 'EUR', 'FR' => 'EUR', 'GR' => 'EUR', 'HR' => 'EUR',
            'IE' => 'EUR', 'IT' => 'EUR', 'LT' => 'EUR', 'LU' => 'EUR', 'LV' => 'EUR',
            'MT' => 'EUR', 'NL' => 'EUR', 'PT' => 'EUR', 'SI' => 'EUR', 'SK' => 'EUR',
            'PL' => 'PLN', 'CZ' => 'CZK', 'RO' => 'RON', 'BG' => 'BGN', 'RS' => 'RSD',
            'UA' => 'UAH', 'GB' => 'GBP', 'CH' => 'CHF', 'US' => 'USD', 'CA' => 'CAD',
            'SE' => 'SEK', 'NO' => 'NOK', 'DK' => 'DKK', 'TR' => 'TRY', 'RU' => 'RUB',
        ];

        return $map[$iso2] ?? 'EUR';
    }

    public static function forCountryId(int $countryId): string
    {
        if ($countryId < 1) {
            return 'HUF';
        }

        try {
            $country = (new static())->getTableLocator()->get('Countries')->find()
                ->select(['id', 'iso2', 'currency'])
                ->where(['Countries.id' => $countryId])
                ->first();
            if ($country === null) {
                return 'HUF';
            }
            $stored = strtoupper(trim((string)($country->get('currency') ?? '')));
            if (preg_match('/^[A-Z]{3}$/', $stored) === 1) {
                return $stored;
            }

            return static::fromIso2((string)($country->get('iso2') ?? ''));
        } catch (\Throwable) {
            return 'HUF';
        }
    }

    public static function normalize(mixed $currency, ?int $fallbackCountryId = null): string
    {
        $code = strtoupper(trim((string)$currency));
        if (preg_match('/^[A-Z]{3}$/', $code) === 1) {
            return $code;
        }
        if ($fallbackCountryId !== null && $fallbackCountryId > 0) {
            return static::forCountryId($fallbackCountryId);
        }

        return 'HUF';
    }
}
