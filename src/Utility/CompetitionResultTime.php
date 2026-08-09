<?php
declare(strict_types=1);

namespace App\Utility;

/**
 * Competition result time: stored as seconds (decimal), displayed mm:ss.SSS.
 */
final class CompetitionResultTime
{
    /**
     * Parse POST payload / form into seconds, or null if empty/invalid.
     *
     * Accepts: time_seconds (float), time_ms (int), time / result_time as
     * "mm:ss", "mm:ss.SSS", "hh:mm:ss", or plain number (seconds).
     *
     * @param array<string, mixed> $data
     */
    public static function parseFromRequest(array $data): ?float
    {
        if (array_key_exists('time_ms', $data) && $data['time_ms'] !== '' && $data['time_ms'] !== null) {
            if (!is_numeric($data['time_ms'])) {
                return null;
            }

            return round(((float)$data['time_ms']) / 1000, 3);
        }
        if (array_key_exists('time_seconds', $data) && $data['time_seconds'] !== '' && $data['time_seconds'] !== null) {
            if (!is_numeric($data['time_seconds'])) {
                return null;
            }

            return round((float)$data['time_seconds'], 3);
        }

        foreach (['result_time', 'time'] as $key) {
            if (!array_key_exists($key, $data) || $data[$key] === null || $data[$key] === '') {
                continue;
            }
            $raw = $data[$key];
            if (is_numeric($raw)) {
                return round((float)$raw, 3);
            }
            if (is_string($raw)) {
                return static::parseFormatted($raw);
            }
        }

        return null;
    }

    public static function parseFormatted(string $value): ?float
    {
        $value = trim($value);
        if ($value === '') {
            return null;
        }
        if (is_numeric($value)) {
            return round((float)$value, 3);
        }
        // hh:mm:ss(.fff) or mm:ss(.fff)
        if (!preg_match('/^(\d{1,3}):([0-5]?\d)(?::([0-5]?\d))?(?:\.(\d{1,3}))?$/', $value, $m)) {
            return null;
        }
        if (isset($m[3]) && $m[3] !== '') {
            $hours = (int)$m[1];
            $minutes = (int)$m[2];
            $seconds = (int)$m[3];
        } else {
            $hours = 0;
            $minutes = (int)$m[1];
            $seconds = (int)$m[2];
        }
        $frac = isset($m[4]) && $m[4] !== '' ? (float)('0.' . str_pad($m[4], 3, '0')) : 0.0;

        return round($hours * 3600 + $minutes * 60 + $seconds + $frac, 3);
    }

    public static function format(?float $seconds): string
    {
        if ($seconds === null || $seconds < 0) {
            return '';
        }
        $whole = (int)floor($seconds);
        $ms = (int)round(($seconds - $whole) * 1000);
        if ($ms >= 1000) {
            $whole++;
            $ms = 0;
        }
        $h = intdiv($whole, 3600);
        $m = intdiv($whole % 3600, 60);
        $s = $whole % 60;
        $frac = sprintf('%03d', $ms);
        if ($h > 0) {
            return sprintf('%d:%02d:%02d.%s', $h, $m, $s, $frac);
        }

        return sprintf('%d:%02d.%s', $m, $s, $frac);
    }
}
