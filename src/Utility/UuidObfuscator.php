<?php
declare(strict_types=1);

namespace App\Utility;

/**
 * Obfuscate UUID dashes for QR / mobile API URLs.
 *
 * Plain:  8-4-4-4-12 hex segments separated by "-".
 * Token:  each "-" replaced by 8 random [A-Za-z0-9] chars → 64-char token.
 * API strips the four 8-char fillers and restores the canonical UUID.
 */
final class UuidObfuscator
{
    public const FILLER_LENGTH = 8;

    public const TOKEN_LENGTH = 64; // 32 hex + 4 * 8 fillers

    /**
     * @var non-empty-string
     */
    private const FILLER_ALPHABET = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';

    public static function encode(string $uuid): string
    {
        $uuid = strtolower(trim($uuid));
        if (!static::isUuid($uuid)) {
            throw new \InvalidArgumentException('Invalid UUID.');
        }
        $parts = explode('-', $uuid);
        // 5 parts: 8,4,4,4,12
        $out = $parts[0];
        for ($i = 1; $i < 5; $i++) {
            $out .= static::randomFiller() . $parts[$i];
        }

        return $out;
    }

    /**
     * Restore canonical UUID, or null if token is malformed.
     */
    public static function decode(string $token): ?string
    {
        $token = trim($token);
        if (strlen($token) !== self::TOKEN_LENGTH) {
            return null;
        }

        // Layout: 8 + F8 + 4 + F8 + 4 + F8 + 4 + F8 + 12
        $p1 = substr($token, 0, 8);
        $p2 = substr($token, 16, 4);
        $p3 = substr($token, 28, 4);
        $p4 = substr($token, 40, 4);
        $p5 = substr($token, 52, 12);

        $uuid = strtolower($p1 . '-' . $p2 . '-' . $p3 . '-' . $p4 . '-' . $p5);
        if (!static::isUuid($uuid)) {
            return null;
        }

        return $uuid;
    }

    public static function isUuid(string $uuid): bool
    {
        return (bool)preg_match(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i',
            trim($uuid)
        );
    }

    /**
     * Concatenate two obfuscated UUIDs (competition + competitor) → 128-char token.
     */
    public static function encodePair(string $competitionUuid, string $userUuid): string
    {
        return static::encode($competitionUuid) . static::encode($userUuid);
    }

    public const PAIR_TOKEN_LENGTH = 128; // 2 × 64

    /**
     * Split a 128-char pair token into [competitionUuid, userUuid], or null.
     *
     * @return array{0: string, 1: string}|null
     */
    public static function decodePair(string $token): ?array
    {
        $token = trim($token);
        if (strlen($token) !== self::PAIR_TOKEN_LENGTH) {
            return null;
        }
        $competitionId = static::decode(substr($token, 0, self::TOKEN_LENGTH));
        $userId = static::decode(substr($token, self::TOKEN_LENGTH, self::TOKEN_LENGTH));
        if ($competitionId === null || $userId === null) {
            return null;
        }

        return [$competitionId, $userId];
    }

    protected static function randomFiller(): string
    {
        $alphabet = self::FILLER_ALPHABET;
        $max = strlen($alphabet) - 1;
        $out = '';
        for ($i = 0; $i < self::FILLER_LENGTH; $i++) {
            $out .= $alphabet[random_int(0, $max)];
        }

        return $out;
    }
}
