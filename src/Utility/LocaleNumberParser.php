<?php
declare(strict_types=1);

namespace App\Utility;

use Cake\I18n\I18n;
use NumberFormatter;

/**
 * Locale-aware number string normalizer for form → DB,
 * and display formatter for form inputs.
 *
 * Converts localized inputs (thousand / decimal separators) to a canonical
 * PHP/DB numeric string: optional minus, digits, optional `.` decimal.
 */
class LocaleNumberParser
{
    /**
     * Locale → separator rules (UI / inputmask + parse hints).
     *
     * @var array<string, array{decimal: string, thousand: string, thousand_alt: list<string>}>
     */
    protected static array $formats = [
        'hu_HU' => ['decimal' => ',', 'thousand' => ' ', 'thousand_alt' => ["\u{00A0}", '.']],
        'de_DE' => ['decimal' => ',', 'thousand' => '.', 'thousand_alt' => ["\u{00A0}", ' ']],
        'sk_SK' => ['decimal' => ',', 'thousand' => ' ', 'thousand_alt' => ["\u{00A0}", '.']],
        'fr_FR' => ['decimal' => ',', 'thousand' => ' ', 'thousand_alt' => ["\u{00A0}", '.']],
        'en_US' => ['decimal' => '.', 'thousand' => ',', 'thousand_alt' => ["\u{00A0}", ' ']],
        'en_GB' => ['decimal' => '.', 'thousand' => ',', 'thousand_alt' => ["\u{00A0}", ' ']],
    ];

    /**
     * @return array{decimal: string, thousand: string, thousand_alt: list<string>}
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
     * Config payload for JS inputmask (Admin form).
     *
     * @return array{locale: string, decimal: string, thousand: string}
     */
    public static function jsConfig(?string $locale = null): array
    {
        $locale = $locale ?: I18n::getLocale();
        $fmt = static::formatFor($locale);

        return [
            'locale' => $locale,
            'decimal' => $fmt['decimal'],
            'thousand' => $fmt['thousand'],
        ];
    }

    /**
     * Format a number for display in a locale-aware form field.
     */
    public static function format(mixed $value, ?string $locale = null, ?int $decimals = null): string
    {
        if ($value === null || $value === '') {
            return '';
        }
        if (!is_numeric($value)) {
            return (string)$value;
        }

        $locale = $locale ?: I18n::getLocale();
        $float = (float)$value;

        if (class_exists(NumberFormatter::class)) {
            $formatter = new NumberFormatter($locale, NumberFormatter::DECIMAL);
            if ($decimals !== null) {
                $formatter->setAttribute(NumberFormatter::FRACTION_DIGITS, $decimals);
                $formatter->setAttribute(NumberFormatter::MIN_FRACTION_DIGITS, $decimals);
                $formatter->setAttribute(NumberFormatter::MAX_FRACTION_DIGITS, $decimals);
            }
            $formatted = $formatter->format($float);
            if ($formatted !== false) {
                return str_replace("\u{00A0}", ' ', $formatted);
            }
        }

        $fmt = static::formatFor($locale);
        if ($decimals === null) {
            $decimals = abs($float - round($float)) < 0.0000001 ? 0 : 2;
        }

        return number_format($float, $decimals, $fmt['decimal'], $fmt['thousand']);
    }

    /**
     * Display helper for `*_count` columns: empty cell when null or zero.
     */
    public static function formatCount(mixed $value, ?string $locale = null, int $decimals = 0): string
    {
        if ($value === null || $value === '') {
            return '';
        }
        if (is_numeric($value) && (float)$value == 0.0) {
            return '';
        }

        return static::format($value, $locale, $decimals);
    }

    /**
     * Currency suffix for display (Admin hu → „Ft”, not ISO „HUF”).
     * Future: map other locales / EUR when needed.
     */
    public static function currencySymbol(?string $locale = null): string
    {
        $locale = $locale ?: I18n::getLocale();
        $lang = strtolower(substr($locale, 0, 2));

        return match ($lang) {
            'hu' => 'Ft',
            // 'de', 'sk', 'fr', 'en' => '€', // when EUR is introduced
            default => 'Ft',
        };
    }

    /**
     * Whether the string looks like a localized number (not a date/time/id token).
     */
    public static function looksLikeNumber(string $value): bool
    {
        $value = trim($value);
        if ($value === '' || $value === '-' || $value === '.' || $value === ',') {
            return false;
        }
        if (preg_match('/^\d{4}-\d{2}-\d{2}/', $value)) {
            return false;
        }
        if (preg_match('/^\d{1,2}:\d{2}(:\d{2})?$/', $value)) {
            return false;
        }
        if (preg_match('/^\d{1,4}([.\/\-]\d{1,4}){2}/', $value)) {
            return false;
        }

        return (bool)preg_match('/^[+\-]?(?:[\d\s\x{00A0}\x{202F}\'.,])+$/u', $value)
            && (bool)preg_match('/\d/u', $value);
    }

    /**
     * Normalize to canonical numeric string (e.g. "1234.56") or return original if not a number.
     */
    public static function normalize(mixed $value, ?string $locale = null): mixed
    {
        if (!is_string($value)) {
            return $value;
        }
        $trimmed = trim($value);
        if ($trimmed === '' || !static::looksLikeNumber($trimmed)) {
            return $value;
        }

        $fmt = static::formatFor($locale);
        $sign = '';
        if (str_starts_with($trimmed, '+') || str_starts_with($trimmed, '-')) {
            $sign = $trimmed[0] === '-' ? '-' : '';
            $trimmed = ltrim(substr($trimmed, 1));
        }

        $trimmed = preg_replace('/[\s\x{00A0}\x{202F}\x{2009}]+/u', '', $trimmed) ?? $trimmed;
        $trimmed = str_replace("'", '', $trimmed);

        $lastComma = strrpos($trimmed, ',');
        $lastDot = strrpos($trimmed, '.');
        $hasComma = $lastComma !== false;
        $hasDot = $lastDot !== false;

        if ($hasComma && $hasDot) {
            // Last separator wins as decimal (1.234,56 and 1,234.56)
            if ($lastComma > $lastDot) {
                $trimmed = str_replace('.', '', $trimmed);
                $trimmed = static::replaceLast($trimmed, ',', '.');
            } else {
                $trimmed = str_replace(',', '', $trimmed);
            }
        } elseif ($hasComma) {
            $trimmed = static::applySingleSeparator($trimmed, ',', $fmt);
        } elseif ($hasDot) {
            $trimmed = static::applySingleSeparator($trimmed, '.', $fmt);
        }

        $trimmed = preg_replace('/[^\d.]/', '', $trimmed) ?? $trimmed;
        if (substr_count($trimmed, '.') > 1) {
            $pos = (int)strrpos($trimmed, '.');
            $trimmed = str_replace('.', '', substr($trimmed, 0, $pos)) . '.' . substr($trimmed, $pos + 1);
        }

        if ($trimmed === '' || $trimmed === '.' || !is_numeric($sign . $trimmed)) {
            return $value;
        }

        return $sign . $trimmed;
    }

    /**
     * @param array{decimal: string, thousand: string, thousand_alt: list<string>} $fmt
     */
    protected static function applySingleSeparator(string $value, string $sep, array $fmt): string
    {
        $count = substr_count($value, $sep);
        if ($count === 0) {
            return $value;
        }

        // Multiple → thousand grouping (1.234.567 or 1,234,567)
        if ($count > 1) {
            return str_replace($sep, '', $value);
        }

        // Single separator
        if (preg_match('/^(.*)' . preg_quote($sep, '/') . '(\d+)$/', $value, $m)) {
            $left = $m[1];
            $frac = $m[2];
            $fracLen = strlen($frac);

            // Locale decimal separator → always decimal
            if ($sep === $fmt['decimal']) {
                return $left . '.' . $frac;
            }

            // Locale thousand (or alt) with exactly 3 trailing digits → thousand (1.234 / 1,234)
            $isThousandSep = $sep === $fmt['thousand'] || in_array($sep, $fmt['thousand_alt'], true);
            if ($isThousandSep && $fracLen === 3) {
                return $left . $frac;
            }

            // Otherwise treat as decimal (e.g. en "12.5" or hu mistyped "12.5")
            return $left . '.' . $frac;
        }

        return str_replace($sep, '', $value);
    }

    protected static function replaceLast(string $haystack, string $needle, string $replace): string
    {
        $pos = strrpos($haystack, $needle);
        if ($pos === false) {
            return $haystack;
        }

        return substr($haystack, 0, $pos) . $replace . substr($haystack, $pos + strlen($needle));
    }
}
