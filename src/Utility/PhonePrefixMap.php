<?php
declare(strict_types=1);

namespace App\Utility;

/**
 * ISO 3166-1 alpha-2 → E.164 calling prefix (from config/phone_prefixes_by_iso2.json).
 */
class PhonePrefixMap
{
    /**
     * @return array<string, string>
     */
    public static function rawByIso2(): array
    {
        static $cache = null;
        if (is_array($cache)) {
            return $cache;
        }

        $path = CONFIG . 'phone_prefixes_by_iso2.json';
        if (!is_file($path)) {
            $cache = [];

            return $cache;
        }

        $decoded = json_decode((string)file_get_contents($path), true);
        $cache = is_array($decoded) ? $decoded : [];

        return $cache;
    }

    /**
     * Extract leading calling-code digits from country.io-style values (+1-246 → 1).
     */
    public static function digitsFromRaw(string $raw): string
    {
        $raw = trim($raw);
        if ($raw === '') {
            return '';
        }

        if (preg_match('/^\+?(\d+)/', $raw, $matches) !== 1) {
            return '';
        }

        return $matches[1];
    }

    public static function prefixFromRaw(string $raw): string
    {
        $digits = static::digitsFromRaw($raw);

        return $digits !== '' ? '+' . $digits : '';
    }

    public static function prefixForIso2(string $iso2): string
    {
        $iso2 = strtoupper(trim($iso2));
        if ($iso2 === '') {
            return '';
        }

        $raw = static::rawByIso2()[$iso2] ?? '';

        return static::prefixFromRaw((string)$raw);
    }
}
