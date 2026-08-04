<?php
declare(strict_types=1);

namespace App\Utility;

/**
 * Parse / format event_logs.request_data field diffs (from → to).
 */
class EventLogChanges
{
    /**
     * @return array<string, array{from: mixed, to: mixed}>
     */
    public static function fromRequestData(mixed $requestData): array
    {
        $decoded = static::decode($requestData);
        $raw = $decoded['changes'] ?? null;
        if (!is_array($raw) || $raw === []) {
            // Legacy: only field names
            if (!empty($decoded['changed']) && is_array($decoded['changed'])) {
                $out = [];
                foreach ($decoded['changed'] as $field) {
                    if (is_string($field) && $field !== '') {
                        $out[$field] = ['from' => '?', 'to' => '?'];
                    }
                }

                return $out;
            }

            return [];
        }

        $out = [];
        foreach ($raw as $field => $pair) {
            if (!is_string($field) && !is_int($field)) {
                continue;
            }
            $field = (string)$field;
            if (is_array($pair)) {
                $out[$field] = [
                    'from' => $pair['from'] ?? null,
                    'to' => $pair['to'] ?? null,
                ];
            } else {
                $out[$field] = ['from' => null, 'to' => $pair];
            }
        }

        return $out;
    }

    /**
     * One-line summary for index lists.
     *
     * @param array<string, array{from: mixed, to: mixed}> $changes
     */
    public static function summary(array $changes, int $maxLen = 220): string
    {
        if ($changes === []) {
            return '';
        }
        $parts = [];
        foreach ($changes as $field => $pair) {
            $parts[] = $field . ': '
                . static::formatValue($pair['from'] ?? null)
                . ' → '
                . static::formatValue($pair['to'] ?? null);
        }
        $summary = implode('; ', $parts);
        if (strlen($summary) > $maxLen) {
            return substr($summary, 0, $maxLen - 1) . '…';
        }

        return $summary;
    }

    public static function formatValue(mixed $value): string
    {
        if ($value === null) {
            return '∅';
        }
        if (is_bool($value)) {
            return $value ? '1' : '0';
        }
        if (is_array($value) || is_object($value)) {
            $json = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

            return $json !== false ? $json : '[complex]';
        }
        if ($value === '') {
            return '""';
        }

        return (string)$value;
    }

    /**
     * True when both sides are effectively empty (skip noise diffs).
     */
    public static function isEmptyEmpty(mixed $from, mixed $to): bool
    {
        return static::isEmpty($from) && static::isEmpty($to);
    }

    public static function isEmpty(mixed $value): bool
    {
        return $value === null || $value === '' || $value === false;
    }

    /**
     * @return array<string, mixed>
     */
    public static function decode(mixed $requestData): array
    {
        if (is_array($requestData)) {
            return $requestData;
        }
        if (!is_string($requestData) || trim($requestData) === '') {
            return [];
        }
        $decoded = json_decode($requestData, true);

        return is_array($decoded) ? $decoded : [];
    }
}
