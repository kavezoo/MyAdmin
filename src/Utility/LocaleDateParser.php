<?php
declare(strict_types=1);

namespace App\Utility;

use Cake\I18n\I18n;
use DateTimeImmutable;

/**
 * Locale-aware date / datetime / time normalizer (form â†’ DB) and display formatter.
 *
 * Accepts many UI formats (Tempus Dominus / Intl / JeffAdmin5), e.g.:
 * - `2024.03.15.` / `2024. 03. 15.` (hu Intl with spaces)
 * - `15.03.2024` / `03/15/2024` / `2024-03-15`
 * - `2024.03.15 14:30:00` / `2024. 03. 15. 14:30:00` / ISO `T`
 *
 * Output for save:
 * - date: Y-m-d
 * - datetime: Y-m-d H:i:s
 * - time: H:i:s
 */
class LocaleDateParser
{
    /**
     * @var array<string, array{
     *   dateOrder: 'YMD'|'DMY'|'MDY',
     *   displayDate: string,
     *   displayDateTime: string,
     *   displayDateTimeShort: string,
     *   displayTime: string,
     *   displayTimeShort: string,
     *   useTwentyFourHour: bool
     * }>
     */
    protected static array $formats = [
        'hu_HU' => [
            'dateOrder' => 'YMD',
            'displayDate' => 'Y.m.d.',
            'displayDateTime' => 'Y.m.d. H:i:s',
            'displayDateTimeShort' => 'Y.m.d. H:i',
            'displayTime' => 'H:i:s',
            'displayTimeShort' => 'H:i',
            'useTwentyFourHour' => true,
        ],
        'de_DE' => [
            'dateOrder' => 'DMY',
            'displayDate' => 'd.m.Y',
            'displayDateTime' => 'd.m.Y H:i:s',
            'displayDateTimeShort' => 'd.m.Y H:i',
            'displayTime' => 'H:i:s',
            'displayTimeShort' => 'H:i',
            'useTwentyFourHour' => true,
        ],
        'sk_SK' => [
            'dateOrder' => 'DMY',
            'displayDate' => 'd.m.Y',
            'displayDateTime' => 'd.m.Y H:i:s',
            'displayDateTimeShort' => 'd.m.Y H:i',
            'displayTime' => 'H:i:s',
            'displayTimeShort' => 'H:i',
            'useTwentyFourHour' => true,
        ],
        'fr_FR' => [
            'dateOrder' => 'DMY',
            'displayDate' => 'd/m/Y',
            'displayDateTime' => 'd/m/Y H:i:s',
            'displayDateTimeShort' => 'd/m/Y H:i',
            'displayTime' => 'H:i:s',
            'displayTimeShort' => 'H:i',
            'useTwentyFourHour' => true,
        ],
        'en_GB' => [
            'dateOrder' => 'DMY',
            'displayDate' => 'd/m/Y',
            'displayDateTime' => 'd/m/Y H:i:s',
            'displayDateTimeShort' => 'd/m/Y H:i',
            'displayTime' => 'H:i:s',
            'displayTimeShort' => 'H:i',
            'useTwentyFourHour' => true,
        ],
        'en_US' => [
            'dateOrder' => 'MDY',
            'displayDate' => 'm/d/Y',
            'displayDateTime' => 'm/d/Y g:i:s A',
            'displayDateTimeShort' => 'm/d/Y g:i A',
            'displayTime' => 'g:i:s A',
            'displayTimeShort' => 'g:i A',
            'useTwentyFourHour' => false,
        ],
    ];

    /**
     * Locales that also accept DMY when primary is YMD (e.g. hu typed as 31.01.2024).
     *
     * @var list<string>
     */
    protected static array $alsoAcceptDmy = ['hu_HU'];

    /**
     * @return array{
     *   dateOrder: 'YMD'|'DMY'|'MDY',
     *   displayDate: string,
     *   displayDateTime: string,
     *   displayDateTimeShort: string,
     *   displayTime: string,
     *   displayTimeShort: string,
     *   useTwentyFourHour: bool
     * }
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
     * Config for JS Tempus Dominus (moment display tokens + Intl locale).
     *
     * @return array{
     *   locale: string,
     *   intl: string,
     *   moment: string,
     *   startOfTheWeek: int,
     *   useTwentyFourHour: bool,
     *   date: string,
     *   datetime: string,
     *   time: string
     * }
     */
    public static function jsConfig(?string $locale = null): array
    {
        $locale = $locale ?: I18n::getLocale();
        $fmt = static::formatFor($locale);

        // Moment tokens (Tempus custom formatInput uses moment)
        $map = [
            'Y.m.d.' => 'YYYY.MM.DD.',
            'Y.m.d. H:i:s' => 'YYYY.MM.DD. HH:mm:ss',
            'd.m.Y' => 'DD.MM.YYYY',
            'd.m.Y H:i:s' => 'DD.MM.YYYY HH:mm:ss',
            'd/m/Y' => 'DD/MM/YYYY',
            'd/m/Y H:i:s' => 'DD/MM/YYYY HH:mm:ss',
            'm/d/Y' => 'MM/DD/YYYY',
            'm/d/Y H:i:s' => 'MM/DD/YYYY HH:mm:ss',
            'm/d/Y g:i:s A' => 'MM/DD/YYYY h:mm:ss A',
            'm/d/Y g:i A' => 'MM/DD/YYYY h:mm A',
            'H:i:s' => 'HH:mm:ss',
            'g:i:s A' => 'h:mm:ss A',
            'g:i A' => 'h:mm A',
        ];

        $lang = strtolower(substr($locale, 0, 2));
        $region = strlen($locale) >= 5 ? strtoupper(substr($locale, 3, 2)) : '';
        // Intl / Tempus: hu-HU, en-US; moment: hu, en, en-gb
        $intl = $region !== '' ? $lang . '-' . $region : $lang;
        $moment = match ($locale) {
            'en_GB' => 'en-gb',
            'en_US' => 'en',
            default => $lang,
        };
        // US Sunday-first; en_GB and EU locales Monday-first (Intl.Locale.weekInfo in JS overrides when available)
        $startOfTheWeek = match ($locale) {
            'en_US' => 0,
            default => 1,
        };

        return [
            'locale' => $locale,
            'intl' => $intl,
            'moment' => $moment,
            'startOfTheWeek' => $startOfTheWeek,
            'useTwentyFourHour' => (bool)$fmt['useTwentyFourHour'],
            'date' => $map[$fmt['displayDate']] ?? 'YYYY-MM-DD',
            'datetime' => $map[$fmt['displayDateTime']] ?? 'YYYY-MM-DD HH:mm:ss',
            'time' => $map[$fmt['displayTime']] ?? 'HH:mm:ss',
        ];
    }

    /**
     * Format a Date/DateTime/string for locale-aware form/list/view/modal display.
     *
     * @param mixed $value DateTimeInterface|Chronos|string|null
     * @param 'date'|'datetime'|'datetime_short'|'time'|'time_short' $type
     */
    public static function format(mixed $value, string $type = 'date', ?string $locale = null): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        $fmt = static::formatFor($locale);
        $phpFormat = match ($type) {
            'datetime' => $fmt['displayDateTime'],
            'datetime_short' => $fmt['displayDateTimeShort'],
            'time' => $fmt['displayTime'],
            'time_short' => $fmt['displayTimeShort'],
            default => $fmt['displayDate'],
        };

        if ($value instanceof \DateTimeInterface) {
            return $value->format($phpFormat);
        }

        // Cake Chronos / I18n Date / DateTime
        if (is_object($value) && method_exists($value, 'format')) {
            try {
                return $value->format($phpFormat);
            } catch (\Throwable) {
                // fall through
            }
        }

        if (is_string($value)) {
            $normalized = static::normalize($value, $locale);
            if (!is_string($normalized) || $normalized === $value && !preg_match('/^\d{4}-\d{2}-\d{2}/', $value)) {
                return $value;
            }
            try {
                if (preg_match('/^\d{1,2}:\d{2}/', $normalized)) {
                    $dt = new DateTimeImmutable('1970-01-01 ' . $normalized);
                } else {
                    $dt = new DateTimeImmutable($normalized);
                }

                return $dt->format($phpFormat);
            } catch (\Throwable) {
                return $value;
            }
        }

        return (string)$value;
    }

    /**
     * Detect date / datetime / time-like strings (including hu Intl spaced forms).
     */
    public static function looksLikeDateOrTime(string $value): bool
    {
        $value = trim($value);
        if ($value === '') {
            return false;
        }
        if (preg_match('/^\d{1,2}:\d{2}(:\d{2})?(\s*[AaPp][Mm])?$/', $value)) {
            return true;
        }
        // Localized day period (en AM/PM; hu de./du. etc.) after time
        if (preg_match('/^\d{1,2}:\d{2}(:\d{2})?\s+.+$/u', $value) && preg_match('/\d/', $value)) {
            // Narrow: must look like time + short meridiem token (not a full date)
            if (!preg_match('/\d{4}/', $value) && preg_match('/^\d{1,2}:\d{2}/', $value)) {
                return true;
            }
        }
        // ISO date / datetime (optional fraction / Z)
        if (preg_match(
            '/^\d{4}-\d{2}-\d{2}([ T]\d{1,2}:\d{2}(:\d{2})?(\.\d+)?(Z|[+-]\d{2}:?\d{2})?)?$/',
            $value
        )) {
            return true;
        }
        // Compact: 2024.03.15. / 15.03.2024 / 03/15/2024 (+ optional time / AM|PM)
        if (preg_match(
            '/^\d{1,4}[.\/\-]\d{1,2}[.\/\-]\d{1,4}\.?(\s+\d{1,2}:\d{2}(:\d{2})?(\s*[AaPp][Mm])?)?$/u',
            $value
        )) {
            return true;
        }
        // hu Intl: "2024. 03. 15." / "2024. 03. 15. 14:30:00"
        if (preg_match(
            '/^\d{4}\.\s*\d{1,2}\.\s*\d{1,2}\.?(\s+\d{1,2}:\d{2}(:\d{2})?(\s*[AaPp][Mm])?)?$/u',
            $value
        )) {
            return true;
        }
        // Spaced DMY/MDY: "15. 03. 2024" / "03. 15. 2024"
        if (preg_match(
            '/^\d{1,2}\.\s*\d{1,2}\.\s*\d{4}\.?(\s+\d{1,2}:\d{2}(:\d{2})?(\s*[AaPp][Mm])?)?$/u',
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
        $trimmed = trim(preg_replace('/\s+/u', ' ', $value) ?? $value);
        if ($trimmed === '' || !static::looksLikeDateOrTime($trimmed)) {
            return $value;
        }

        // Time only (24h or 12h with AM/PM / locale dayPeriod)
        $timeOnly = static::normalizeTimePart($trimmed);
        if ($timeOnly !== null && !preg_match('/\d{4}/', $trimmed) && !preg_match('/[.\/\-].*[.\/\-]/', $trimmed)) {
            return $timeOnly;
        }

        $locale = $locale ?: I18n::getLocale();
        $datePart = $trimmed;
        $timePart = null;

        // Split trailing time (allow "2024. 03. 15. 14:30:00" / "03/15/2024 2:30:00 PM")
        if (preg_match('/^(.+?)\s+(\d{1,2}:\d{2}(?::\d{2})?(?:\s*[AaPp][Mm])?)$/u', $trimmed, $m)) {
            $datePart = trim($m[1]);
            $timePart = $m[2];
        } elseif (preg_match('/^(.+?)\s+(\d{1,2}:\d{2}(?::\d{2})?\s+\S+)$/u', $trimmed, $m)) {
            $datePart = trim($m[1]);
            $timePart = $m[2];
        } elseif (preg_match('/^(.+?)[T](\d{1,2}:\d{2}(?::\d{2})?)/', $trimmed, $m)) {
            $datePart = trim($m[1]);
            $timePart = $m[2];
        }

        // Drop trailing dots from date part (hu: "2024. 03. 15.")
        $datePart = rtrim($datePart, ". \t");

        $normalizedDate = static::normalizeDatePart($datePart, $locale);
        if ($normalizedDate === null) {
            return $value;
        }

        if ($timePart === null) {
            return $normalizedDate;
        }

        $timeSql = static::normalizeTimePart($timePart);
        if ($timeSql === null) {
            return $value;
        }

        return $normalizedDate . ' ' . $timeSql;
    }

    /**
     * Normalize a time string to H:i:s (accepts 24h and 12h AM/PM).
     */
    protected static function normalizeTimePart(string $timePart): ?string
    {
        $timePart = trim($timePart);
        if ($timePart === '') {
            return null;
        }

        if (preg_match('/^(\d{1,2}):(\d{2})(?::(\d{2}))?\s*([AaPp][Mm])$/u', $timePart, $m)) {
            $h = (int)$m[1];
            $i = (int)$m[2];
            $s = isset($m[3]) ? (int)$m[3] : 0;
            $ampm = strtoupper($m[4]);
            if ($h < 1 || $h > 12 || $i > 59 || $s > 59) {
                return null;
            }
            if ($ampm === 'AM') {
                $h = $h === 12 ? 0 : $h;
            } else {
                $h = $h === 12 ? 12 : $h + 12;
            }

            return sprintf('%02d:%02d:%02d', $h, $i, $s);
        }

        if (preg_match('/^(\d{1,2}):(\d{2})(?::(\d{2}))?$/', $timePart, $m)) {
            $h = (int)$m[1];
            $i = (int)$m[2];
            $s = isset($m[3]) ? (int)$m[3] : 0;
            if ($h > 23 || $i > 59 || $s > 59) {
                return null;
            }

            return sprintf('%02d:%02d:%02d', $h, $i, $s);
        }

        // Fallback: strtotime for locale day periods if PHP understands them
        $ts = strtotime('1970-01-01 ' . $timePart);
        if ($ts !== false) {
            return date('H:i:s', $ts);
        }

        return null;
    }

    /**
     * @return string|null Y-m-d or null
     */
    protected static function normalizeDatePart(string $datePart, string $locale): ?string
    {
        $datePart = trim($datePart);
        $datePart = rtrim($datePart, '.');

        // Already ISO
        if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $datePart, $m)) {
            if (checkdate((int)$m[2], (int)$m[3], (int)$m[1])) {
                return sprintf('%04d-%02d-%02d', (int)$m[1], (int)$m[2], (int)$m[3]);
            }

            return null;
        }

        // Split on . / - or whitespace (handles "2024. 03. 15")
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
        // First part day-like â†’ prefer DMY
        if (strlen($parts[0]) <= 2 && (int)$parts[0] > 12) {
            array_unshift($orders, 'DMY');
        }
        // Fallbacks: try remaining orders so any locale input can save
        $orders = array_values(array_unique(array_merge($orders, ['YMD', 'DMY', 'MDY'])));

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

        foreach (['Y-m-d', 'Y.m.d', 'd.m.Y', 'd/m/Y', 'm/d/Y'] as $try) {
            $dt = DateTimeImmutable::createFromFormat('!' . $try, $datePart);
            if ($dt instanceof DateTimeImmutable) {
                $err = DateTimeImmutable::getLastErrors();
                if (($err['warning_count'] ?? 0) === 0 && ($err['error_count'] ?? 0) === 0) {
                    return $dt->format('Y-m-d');
                }
            }
        }

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
