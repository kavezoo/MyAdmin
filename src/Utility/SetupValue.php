<?php
declare(strict_types=1);

namespace App\Utility;

use Cake\I18n\Date;
use Cake\I18n\DateTime;
use Cake\I18n\Time;
use Cake\Utility\Text;

/**
 * Setup (EAV) value types: normalize, validate, display, PHP cast.
 */
class SetupValue
{
    public const TYPE_STRING = 'string';
    public const TYPE_TEXT = 'text';
    public const TYPE_INTEGER = 'integer';
    public const TYPE_FLOAT = 'float';
    public const TYPE_BOOLEAN = 'boolean';
    public const TYPE_DATE = 'date';
    public const TYPE_TIME = 'time';
    public const TYPE_DATETIME = 'datetime';
    public const TYPE_JSON = 'json';
    public const TYPE_ARRAY = 'array';

    /**
     * @return list<string>
     */
    public static function typeList(): array
    {
        return [
            self::TYPE_STRING,
            self::TYPE_TEXT,
            self::TYPE_INTEGER,
            self::TYPE_FLOAT,
            self::TYPE_BOOLEAN,
            self::TYPE_DATE,
            self::TYPE_TIME,
            self::TYPE_DATETIME,
            self::TYPE_JSON,
            self::TYPE_ARRAY,
        ];
    }

    /**
     * Select options: type => translated label (call in view/controller with locale set).
     *
     * @return array<string, string>
     */
    public static function typeOptions(): array
    {
        $labels = [
            self::TYPE_STRING => __('String'),
            self::TYPE_TEXT => __('Text'),
            self::TYPE_INTEGER => __('Integer'),
            self::TYPE_FLOAT => __('Float'),
            self::TYPE_BOOLEAN => __('Boolean'),
            self::TYPE_DATE => __('Date'),
            self::TYPE_TIME => __('Time'),
            self::TYPE_DATETIME => __('Date and time'),
            self::TYPE_JSON => __('JSON'),
            self::TYPE_ARRAY => __('Array'),
        ];
        $out = [];
        foreach (static::typeList() as $type) {
            $out[$type] = $labels[$type] ?? $type;
        }

        return $out;
    }

    public static function isValidType(string $type): bool
    {
        return in_array($type, static::typeList(), true);
    }

    /**
     * Suggest slug from name (lowercase a-z0-9_).
     */
    public static function suggestSlug(string $name): string
    {
        $slug = Text::slug(mb_strtolower(trim($name)), ['replacement' => '_']);
        $slug = strtolower($slug);
        $slug = str_replace('-', '_', $slug);
        $slug = (string)preg_replace('/[^a-z0-9_]+/', '', $slug);
        $slug = (string)preg_replace('/_+/', '_', $slug);

        return trim($slug, '_');
    }

    public static function isValidSlug(string $slug): bool
    {
        return (bool)preg_match('/^[a-z0-9]+(?:_[a-z0-9]+)*$/', $slug);
    }

    /**
     * Normalize raw form value to canonical DB string (or null if empty allowed).
     *
     * @return array{ok: bool, value: string|null, error: string|null}
     */
    public static function normalize(string $type, mixed $raw): array
    {
        if (!static::isValidType($type)) {
            return ['ok' => false, 'value' => null, 'error' => __('Invalid type.')];
        }

        if ($type === self::TYPE_BOOLEAN) {
            $bool = filter_var($raw, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            if ($bool === null) {
                // unchecked checkbox → absent / "0"
                $bool = in_array($raw, [1, '1', true, 'true', 'on', 'yes'], true);
            }

            return ['ok' => true, 'value' => $bool ? '1' : '0', 'error' => null];
        }

        if ($raw === null) {
            return ['ok' => true, 'value' => '', 'error' => null];
        }

        $str = is_string($raw) || is_int($raw) || is_float($raw) || is_bool($raw)
            ? trim((string)$raw)
            : '';

        return match ($type) {
            self::TYPE_STRING, self::TYPE_TEXT => ['ok' => true, 'value' => $str, 'error' => null],
            self::TYPE_INTEGER => static::normalizeInteger($str),
            self::TYPE_FLOAT => static::normalizeFloat($str),
            self::TYPE_DATE => static::normalizeDate($str),
            self::TYPE_TIME => static::normalizeTime($str),
            self::TYPE_DATETIME => static::normalizeDateTime($str),
            self::TYPE_JSON => static::normalizeJson($str),
            self::TYPE_ARRAY => static::normalizeArray($str),
            default => ['ok' => false, 'value' => null, 'error' => __('Invalid type.')],
        };
    }

    /**
     * @return array{ok: bool, value: string|null, error: string|null}
     */
    protected static function normalizeInteger(string $str): array
    {
        if ($str === '') {
            return ['ok' => true, 'value' => '', 'error' => null];
        }
        // Locale input may still contain spaces; strip common thousand seps after middleware ideally
        $clean = str_replace([' ', "\u{00A0}"], '', $str);
        $clean = str_replace(',', '.', $clean);
        if (!preg_match('/^-?\d+$/', $clean) && !preg_match('/^-?\d+\.0+$/', $clean)) {
            // allow "1.234" locale? Prefer digits only after number middleware
            if (!preg_match('/^-?\d+$/', preg_replace('/[^\d\-]/', '', $str) ?? '')) {
                return ['ok' => false, 'value' => null, 'error' => __('Please enter a valid integer.')];
            }
        }
        if (str_contains($clean, '.')) {
            $clean = (string)(int)round((float)$clean);
        }
        if (!preg_match('/^-?\d+$/', $clean)) {
            return ['ok' => false, 'value' => null, 'error' => __('Please enter a valid integer.')];
        }

        return ['ok' => true, 'value' => $clean, 'error' => null];
    }

    /**
     * @return array{ok: bool, value: string|null, error: string|null}
     */
    protected static function normalizeFloat(string $str): array
    {
        if ($str === '') {
            return ['ok' => true, 'value' => '', 'error' => null];
        }
        $clean = str_replace([' ', "\u{00A0}"], '', $str);
        // After number middleware: expect 1234.56
        if (!is_numeric($clean)) {
            return ['ok' => false, 'value' => null, 'error' => __('Please enter a valid number.')];
        }

        return ['ok' => true, 'value' => (string)(0 + $clean), 'error' => null];
    }

    /**
     * @return array{ok: bool, value: string|null, error: string|null}
     */
    protected static function normalizeDate(string $str): array
    {
        if ($str === '') {
            return ['ok' => true, 'value' => '', 'error' => null];
        }
        // Expect Y-m-d after date middleware, or parse
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $str)) {
            return ['ok' => true, 'value' => $str, 'error' => null];
        }
        try {
            $d = new Date($str);

            return ['ok' => true, 'value' => $d->format('Y-m-d'), 'error' => null];
        } catch (\Throwable) {
            return ['ok' => false, 'value' => null, 'error' => __('Please enter a valid date.')];
        }
    }

    /**
     * @return array{ok: bool, value: string|null, error: string|null}
     */
    protected static function normalizeTime(string $str): array
    {
        if ($str === '') {
            return ['ok' => true, 'value' => '', 'error' => null];
        }
        if (preg_match('/^\d{2}:\d{2}(:\d{2})?$/', $str)) {
            if (strlen($str) === 5) {
                $str .= ':00';
            }

            return ['ok' => true, 'value' => $str, 'error' => null];
        }
        try {
            $t = new Time($str);

            return ['ok' => true, 'value' => $t->format('H:i:s'), 'error' => null];
        } catch (\Throwable) {
            return ['ok' => false, 'value' => null, 'error' => __('Please enter a valid time.')];
        }
    }

    /**
     * @return array{ok: bool, value: string|null, error: string|null}
     */
    protected static function normalizeDateTime(string $str): array
    {
        if ($str === '') {
            return ['ok' => true, 'value' => '', 'error' => null];
        }
        if (preg_match('/^\d{4}-\d{2}-\d{2}[ T]\d{2}:\d{2}(:\d{2})?$/', $str)) {
            $str = str_replace('T', ' ', $str);
            if (preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}$/', $str)) {
                $str .= ':00';
            }

            return ['ok' => true, 'value' => $str, 'error' => null];
        }
        try {
            $dt = new DateTime($str);

            return ['ok' => true, 'value' => $dt->format('Y-m-d H:i:s'), 'error' => null];
        } catch (\Throwable) {
            return ['ok' => false, 'value' => null, 'error' => __('Please enter a valid date and time.')];
        }
    }

    /**
     * @return array{ok: bool, value: string|null, error: string|null}
     */
    protected static function normalizeJson(string $str): array
    {
        if ($str === '') {
            return ['ok' => true, 'value' => '', 'error' => null];
        }
        try {
            $decoded = json_decode($str, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return ['ok' => false, 'value' => null, 'error' => __('Please enter valid JSON.')];
        }
        if (!is_array($decoded)) {
            return ['ok' => false, 'value' => null, 'error' => __('JSON must be an object or an array.')];
        }
        $encoded = json_encode($decoded, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($encoded === false) {
            return ['ok' => false, 'value' => null, 'error' => __('Please enter valid JSON.')];
        }

        return ['ok' => true, 'value' => $encoded, 'error' => null];
    }

    /**
     * One scalar per line → JSON array.
     *
     * @return array{ok: bool, value: string|null, error: string|null}
     */
    protected static function normalizeArray(string $str): array
    {
        if ($str === '') {
            return ['ok' => true, 'value' => '[]', 'error' => null];
        }
        // Already JSON array?
        if (str_starts_with(ltrim($str), '[')) {
            return static::normalizeJson($str);
        }
        $lines = preg_split('/\R/u', $str) ?: [];
        $items = [];
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }
            $items[] = $line;
        }
        $encoded = json_encode(array_values($items), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($encoded === false) {
            return ['ok' => false, 'value' => null, 'error' => __('Please enter a valid list.')];
        }

        return ['ok' => true, 'value' => $encoded, 'error' => null];
    }

    /**
     * Human-readable preview for index / view / modal.
     */
    public static function formatForDisplay(string $type, ?string $value): string
    {
        $value = $value ?? '';
        if ($value === '' && $type !== self::TYPE_BOOLEAN) {
            return '';
        }

        return match ($type) {
            self::TYPE_BOOLEAN => ((string)$value === '1' || $value === 'true') ? __('Yes') : __('No'),
            self::TYPE_INTEGER => LocaleNumberParser::format($value !== '' ? (int)$value : null, decimals: 0),
            self::TYPE_FLOAT => LocaleNumberParser::format($value !== '' ? (float)$value : null, decimals: 2),
            self::TYPE_DATE => LocaleDateParser::format($value !== '' ? $value : null, 'date'),
            self::TYPE_TIME => LocaleDateParser::format($value !== '' ? $value : null, 'time_short'),
            self::TYPE_DATETIME => LocaleDateParser::format($value !== '' ? $value : null, 'datetime_short'),
            self::TYPE_JSON, self::TYPE_ARRAY => static::formatJsonPreview($value),
            default => $value,
        };
    }

    protected static function formatJsonPreview(string $value): string
    {
        if ($value === '') {
            return '';
        }
        try {
            $decoded = json_decode($value, true, 512, JSON_THROW_ON_ERROR);
            $pretty = json_encode($decoded, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
            $text = is_string($pretty) ? $pretty : $value;
        } catch (\JsonException) {
            $text = $value;
        }
        if (mb_strlen($text) > 120) {
            return mb_substr($text, 0, 117) . '…';
        }

        return $text;
    }

    /**
     * Form field display value (before save).
     */
    public static function formatForForm(string $type, ?string $value): string
    {
        $value = $value ?? '';
        if ($type === self::TYPE_ARRAY && $value !== '') {
            try {
                $decoded = json_decode($value, true, 512, JSON_THROW_ON_ERROR);
                if (is_array($decoded) && array_is_list($decoded)) {
                    return implode("\n", array_map(static fn ($v) => (string)$v, $decoded));
                }
            } catch (\JsonException) {
                // fall through
            }
        }
        if ($type === self::TYPE_JSON && $value !== '') {
            try {
                $decoded = json_decode($value, true, 512, JSON_THROW_ON_ERROR);
                $pretty = json_encode($decoded, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);

                return is_string($pretty) ? $pretty : $value;
            } catch (\JsonException) {
                return $value;
            }
        }
        if ($type === self::TYPE_DATE && $value !== '') {
            return (string)LocaleDateParser::format($value, 'date');
        }
        if ($type === self::TYPE_TIME && $value !== '') {
            return (string)LocaleDateParser::format($value, 'time_short');
        }
        if ($type === self::TYPE_DATETIME && $value !== '') {
            return (string)LocaleDateParser::format($value, 'datetime_short');
        }
        if ($type === self::TYPE_INTEGER && $value !== '') {
            return (string)LocaleNumberParser::format((int)$value, decimals: 0);
        }
        if ($type === self::TYPE_FLOAT && $value !== '') {
            return (string)LocaleNumberParser::format((float)$value, decimals: 2);
        }

        return $value;
    }

    /**
     * Cast stored string to PHP value for application use.
     */
    public static function cast(string $type, ?string $value): mixed
    {
        $value = $value ?? '';
        if ($value === '' && $type !== self::TYPE_BOOLEAN && $type !== self::TYPE_ARRAY) {
            return null;
        }

        return match ($type) {
            self::TYPE_BOOLEAN => $value === '1' || $value === 'true',
            self::TYPE_INTEGER => (int)$value,
            self::TYPE_FLOAT => (float)$value,
            self::TYPE_JSON, self::TYPE_ARRAY => json_decode($value !== '' ? $value : '[]', true),
            self::TYPE_DATE, self::TYPE_TIME, self::TYPE_DATETIME,
            self::TYPE_STRING, self::TYPE_TEXT => $value,
            default => $value,
        };
    }
}
