<?php
declare(strict_types=1);

namespace App\Utility;

use Cake\I18n\I18n;
use DateTimeImmutable;

/**
 * Locale-aware date / datetime / time normalizer for form → DB.
 *
 * Output:
 * - date: Y-m-d
 * - datetime: Y-m-d H:i:s
 * - time: H:i:s
 */
class LocaleDateParser
{
    /**
     * @var array<string, array{dateOrder: 'YMD'|'DMY'|'MDY'}>
     */
    protected static array $formats = [
        'hu_HU' => ['dateOrder' => 'YMD'], // UI often yyyy-mm-dd; also accept DMY
        'de_DE' => ['dateOrder' => 'DMY'],
        'sk_SK' => ['dateOrder' => 'DMY'],
        'fr_FR' => ['dateOrder' => 'DMY'],
        'en_GB' => ['dateOrder' => 'DMY'],
        'en_US' => ['dateOrder' => 'MDY'],
    ];

    /**
     * Locales that also accept DMY when primary is YMD (e.g. hu typed as 31.01.2024).
     *
     * @var list<string>
     */
    protected static array $alsoAcceptDmy = ['hu_HU'];

    /**
     * @return array{dateOrder: 'YMD'|'DMY'|'MDY'}
     */
    public static function formatFor(?string $locale = null): array
    {
        $locale = $locale ?: I18n::getLocale();
        if (isset(static::$formats[$locale])) {
            return static::$formats[$locale];
        }
        $lang = substr($locale, 0, 2);
        foreach (static::$formats as $key => $fmt) {
            if (str_starts_with($key, $lang . '_')) {
                return $fmt;
            }
        }

        return static::$formats['en_US'];
    }

    /**
     * Detect date / datetime / time-like strings.
     */
    public static function looksLikeDateOrTime(string $value): bool
    {
        $value = trim($value);
        if ($value === '') {
            return false;
        }
        if (preg_match('/^\d{1,2}:\d{2}(:\d{2})?$/', $value)) {
            return true;
        }
        if (preg_match('/^\d{4}-\d{2}-\d{2}([ T]\d{1,2}:\d{2}(:\d{2})?)?$/', $value)) {
            return true;
        }
        // 31.01.2024 / 01/31/2024 / 31-01-2024 / 2024.01.31 (+ optional time)
        if (preg_match(
            '/^\d{1,4}[.\/\-\s]\d{1,2}[.\/\-\s]\d{1,4}([ T]\d{1,2}:\d{2}(:\d{2})?)?$/u',
            $value
        )) {
            return true;
        }

        return false;
    }

    /**
     * Normalize to SQL-friendly date/datetime/time or return original.
     */
    public static function normalize(mixed $value, ?string $locale = null): mixed
    {
        if (!is_string($value)) {
            return $value;
        }
        $trimmed = trim($value);
        if ($trimmed === '' || !static::looksLikeDateOrTime($trimmed)) {
            return $value;
        }

        // Time only
        if (preg_match('/^(\d{1,2}):(\d{2})(?::(\d{2}))?$/', $trimmed, $m)) {
            return sprintf('%02d:%02d:%02d', (int)$m[1], (int)$m[2], isset($m[3]) ? (int)$m[3] : 0);
        }

        $locale = $locale ?: I18n::getLocale();
        $datePart = $trimmed;
        $timePart = null;

        if (preg_match('/^(.+?)[ T](\d{1,2}:\d{2}(?::\d{2})?)$/', $trimmed, $m)) {
            $datePart = trim($m[1]);
            $timePart = $m[2];
        }

        $normalizedDate = static::normalizeDatePart($datePart, $locale);
        if ($normalizedDate === null) {
            return $value;
        }

        if ($timePart === null) {
            return $normalizedDate;
        }

        if (preg_match('/^(\d{1,2}):(\d{2})(?::(\d{2}))?$/', $timePart, $tm)) {
            $timeSql = sprintf('%02d:%02d:%02d', (int)$tm[1], (int)$tm[2], isset($tm[3]) ? (int)$tm[3] : 0);

            return $normalizedDate . ' ' . $timeSql;
        }

        return $value;
    }

    /**
     * @return string|null Y-m-d or null
     */
    protected static function normalizeDatePart(string $datePart, string $locale): ?string
    {
        // Already ISO
        if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $datePart, $m)) {
            if (checkdate((int)$m[2], (int)$m[3], (int)$m[1])) {
                return sprintf('%04d-%02d-%02d', (int)$m[1], (int)$m[2], (int)$m[3]);
            }

            return null;
        }

        $parts = preg_split('/[.\/\-\s]+/u', $datePart) ?: [];
        $parts = array_values(array_filter($parts, static fn($p) => $p !== ''));
        if (count($parts) !== 3) {
            return null;
        }

        $orders = [static::formatFor($locale)['dateOrder']];
        if (in_array($locale, static::$alsoAcceptDmy, true) || str_starts_with($locale, 'hu')) {
            $orders[] = 'DMY';
            $orders[] = 'YMD';
        }
        // Always try YMD if first part looks like a year
        if (strlen($parts[0]) === 4) {
            array_unshift($orders, 'YMD');
        }
        $orders = array_values(array_unique($orders));

        foreach ($orders as $order) {
            $ymd = static::mapParts($parts, $order);
            if ($ymd === null) {
                continue;
            }
            [$y, $m, $d] = $ymd;
            if ($y < 1000 || $y > 9999) {
                continue;
            }
            if (!checkdate($m, $d, $y)) {
                continue;
            }

            return sprintf('%04d-%02d-%02d', $y, $m, $d);
        }

        // Last resort: DateTimeImmutable parse (locale-agnostic fallbacks)
        try {
            $dt = new DateTimeImmutable($datePart);

            return $dt->format('Y-m-d');
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * @param list<string> $parts
     * @return array{0: int, 1: int, 2: int}|null [y,m,d]
     */
    protected static function mapParts(array $parts, string $order): ?array
    {
        $a = (int)$parts[0];
        $b = (int)$parts[1];
        $c = (int)$parts[2];

        return match ($order) {
            'YMD' => [$a, $b, $c],
            'DMY' => [$c, $b, $a],
            'MDY' => [$c, $a, $b],
            default => null,
        };
    }
}
