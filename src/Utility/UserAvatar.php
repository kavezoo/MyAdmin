<?php
declare(strict_types=1);

namespace App\Utility;

use Cake\Http\Exception\BadRequestException;
use Psr\Http\Message\UploadedFileInterface;

/**
 * User profile picture storage (webroot/uploads/avatars/{user.id}.jpg).
 *
 * Recommended source: square 1000×1000 px. Uploaded images are scaled to fit within 1000×1000.
 */
class UserAvatar
{
    public const RECOMMENDED_SIZE = 1000;

    public const MAX_BYTES = 5 * 1024 * 1024;

    public const RELATIVE_DIR = 'uploads/avatars';

    /**
     * @var list<string>
     */
    protected const ALLOWED_MIME = [
        'image/jpeg',
        'image/png',
        'image/webp',
    ];

    /**
     * Canonical stored path for a user (DB + filesystem).
     */
    public static function storedPathFor(string $userId): string
    {
        return static::RELATIVE_DIR . '/' . static::filenameFor($userId);
    }

    /**
     * Filename = `{user.id}.jpg` (UUID-safe).
     */
    public static function filenameFor(string $userId): string
    {
        $stem = static::safeIdStem($userId);
        if ($stem === '') {
            throw new BadRequestException('Invalid user.');
        }

        return $stem . '.jpg';
    }

    /**
     * Store uploaded image; returns web-relative path (no leading slash).
     */
    public static function store(string $userId, UploadedFileInterface $file): string
    {
        $userId = trim($userId);
        if ($userId === '') {
            throw new BadRequestException('Invalid user.');
        }
        if ($file->getError() !== UPLOAD_ERR_OK) {
            throw new BadRequestException(__('The image could not be uploaded. Please try again.'));
        }
        if ($file->getSize() > static::MAX_BYTES) {
            throw new BadRequestException(__('The image is too large. Maximum size is 5 MB.'));
        }

        $mime = (string)$file->getClientMediaType();
        if (!in_array($mime, static::ALLOWED_MIME, true)) {
            throw new BadRequestException(__('Only JPEG, PNG or WebP images are allowed.'));
        }

        $tmp = $file->getStream()->getMetadata('uri');
        if (!is_string($tmp) || $tmp === '' || !is_file($tmp)) {
            throw new BadRequestException(__('The image could not be read.'));
        }

        $image = match ($mime) {
            'image/jpeg' => @imagecreatefromjpeg($tmp),
            'image/png' => @imagecreatefrompng($tmp),
            'image/webp' => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($tmp) : false,
            default => false,
        };
        if ($image === false) {
            throw new BadRequestException(__('The image could not be processed.'));
        }

        $width = imagesx($image);
        $height = imagesy($image);
        if ($width < 1 || $height < 1) {
            imagedestroy($image);
            throw new BadRequestException(__('The image could not be processed.'));
        }

        $max = static::RECOMMENDED_SIZE;
        $scale = min($max / $width, $max / $height, 1.0);
        $newW = max(1, (int)round($width * $scale));
        $newH = max(1, (int)round($height * $scale));

        $canvas = imagecreatetruecolor($newW, $newH);
        if ($canvas === false) {
            imagedestroy($image);
            throw new BadRequestException(__('The image could not be processed.'));
        }

        imagecopyresampled($canvas, $image, 0, 0, 0, 0, $newW, $newH, $width, $height);
        imagedestroy($image);

        $dir = WWW_ROOT . static::RELATIVE_DIR;
        if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
            imagedestroy($canvas);
            throw new BadRequestException(__('Could not create upload directory.'));
        }

        $filename = static::filenameFor($userId);
        $fullPath = $dir . DIRECTORY_SEPARATOR . $filename;
        if (!imagejpeg($canvas, $fullPath, 90)) {
            imagedestroy($canvas);
            throw new BadRequestException(__('The image could not be saved.'));
        }
        imagedestroy($canvas);

        return static::storedPathFor($userId);
    }

    /**
     * Remove stored file for user (legacy path + canonical `{id}.jpg`).
     */
    public static function deleteStored(?string $storedPath, string $userId = ''): void
    {
        $paths = [];
        if ($storedPath !== null && trim($storedPath) !== '') {
            $paths[] = WWW_ROOT . ltrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $storedPath), DIRECTORY_SEPARATOR);
        }
        $userId = trim($userId);
        if ($userId !== '') {
            $paths[] = WWW_ROOT . static::RELATIVE_DIR . DIRECTORY_SEPARATOR . static::filenameFor($userId);
        }
        foreach ($paths as $path) {
            if (is_file($path)) {
                @unlink($path);
            }
        }
    }

    /**
     * Stored path for templates (canonical file, legacy DB path, or on-disk `{id}.jpg`).
     */
    public static function displayPath(string $userId, ?string $storedPath): string
    {
        $userId = trim($userId);
        $canonical = $userId !== '' ? static::storedPathFor($userId) : '';
        if ($canonical !== '' && is_file(static::fullPath($canonical))) {
            return $canonical;
        }

        $storedPath = trim((string)$storedPath);
        if ($storedPath !== '' && is_file(static::fullPath($storedPath))) {
            return $storedPath;
        }

        return '';
    }

    /**
     * Public URL with cache-buster after upload.
     */
    public static function publicUrlFor(string $userId, ?string $storedPath): string
    {
        $path = static::displayPath($userId, $storedPath);
        if ($path === '') {
            return '';
        }

        $url = static::publicUrl($path);
        $full = static::fullPath($path);
        if (is_file($full)) {
            $url .= '?v=' . (int)filemtime($full);
        }

        return $url;
    }

    /**
     * Public URL path segment for templates (leading slash).
     */
    public static function publicUrl(?string $storedPath): string
    {
        $storedPath = trim((string)$storedPath);
        if ($storedPath === '') {
            return '';
        }

        return '/' . ltrim(str_replace('\\', '/', $storedPath), '/');
    }

    protected static function fullPath(string $relativePath): string
    {
        return WWW_ROOT . ltrim(str_replace('/', DIRECTORY_SEPARATOR, $relativePath), DIRECTORY_SEPARATOR);
    }

    protected static function safeIdStem(string $userId): string
    {
        $userId = trim($userId);
        if ($userId === '') {
            return '';
        }

        return (string)preg_replace('/[^a-zA-Z0-9\-]/', '', $userId);
    }
}
