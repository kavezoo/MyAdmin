<?php
declare(strict_types=1);

namespace App\Utility;

use Cake\Http\Exception\BadRequestException;
use Psr\Http\Message\UploadedFileInterface;

/**
 * Club logo storage (webroot/uploads/clubs/{club.id}.png).
 *
 * Recommended source: square 1000×1000 px PNG (transparency preserved).
 * Legacy `{id}.jpg` files are still served if present.
 */
class ClubLogo
{
    public const RECOMMENDED_SIZE = 1000;

    public const MAX_BYTES = 5 * 1024 * 1024;

    public const RELATIVE_DIR = 'uploads/clubs';

    /**
     * @var list<string>
     */
    protected const ALLOWED_MIME = [
        'image/jpeg',
        'image/png',
        'image/webp',
    ];

    /**
     * Canonical stored path for a club (DB + filesystem).
     */
    public static function storedPathFor(int $clubId): string
    {
        return static::RELATIVE_DIR . '/' . static::filenameFor($clubId);
    }

    public static function filenameFor(int $clubId): string
    {
        if ($clubId < 1) {
            throw new BadRequestException('Invalid club.');
        }

        return $clubId . '.png';
    }

    /**
     * Store uploaded image as PNG (alpha preserved); returns web-relative path.
     */
    public static function store(int $clubId, UploadedFileInterface $file): string
    {
        if ($clubId < 1) {
            throw new BadRequestException('Invalid club.');
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

        imagealphablending($canvas, false);
        imagesavealpha($canvas, true);
        $transparent = imagecolorallocatealpha($canvas, 0, 0, 0, 127);
        if ($transparent !== false) {
            imagefilledrectangle($canvas, 0, 0, $newW, $newH, $transparent);
        }
        imagealphablending($canvas, true);

        imagecopyresampled($canvas, $image, 0, 0, 0, 0, $newW, $newH, $width, $height);
        imagedestroy($image);

        $dir = WWW_ROOT . static::RELATIVE_DIR;
        if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
            imagedestroy($canvas);
            throw new BadRequestException(__('Could not create upload directory.'));
        }

        $filename = static::filenameFor($clubId);
        $fullPath = $dir . DIRECTORY_SEPARATOR . $filename;
        imagealphablending($canvas, false);
        imagesavealpha($canvas, true);
        if (!imagepng($canvas, $fullPath, 6)) {
            imagedestroy($canvas);
            throw new BadRequestException(__('The image could not be saved.'));
        }
        imagedestroy($canvas);

        $legacyJpg = $dir . DIRECTORY_SEPARATOR . $clubId . '.jpg';
        if (is_file($legacyJpg)) {
            @unlink($legacyJpg);
        }

        return static::storedPathFor($clubId);
    }

    /**
     * Remove stored file for club (legacy path + canonical `{id}.png` / `{id}.jpg`).
     */
    public static function deleteStored(?string $storedPath, int $clubId = 0): void
    {
        $paths = [];
        if ($storedPath !== null && trim($storedPath) !== '') {
            $paths[] = WWW_ROOT . ltrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $storedPath), DIRECTORY_SEPARATOR);
        }
        if ($clubId > 0) {
            $dir = WWW_ROOT . static::RELATIVE_DIR . DIRECTORY_SEPARATOR;
            $paths[] = $dir . static::filenameFor($clubId);
            $paths[] = $dir . $clubId . '.jpg';
        }
        foreach ($paths as $path) {
            if (is_file($path)) {
                @unlink($path);
            }
        }
    }

    /**
     * Stored path for templates (canonical PNG, legacy JPG / DB path).
     */
    public static function displayPath(int $clubId, ?string $storedPath): string
    {
        $canonical = $clubId > 0 ? static::storedPathFor($clubId) : '';
        if ($canonical !== '' && is_file(static::fullPath($canonical))) {
            return $canonical;
        }

        if ($clubId > 0) {
            $legacy = static::RELATIVE_DIR . '/' . $clubId . '.jpg';
            if (is_file(static::fullPath($legacy))) {
                return $legacy;
            }
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
    public static function publicUrlFor(int $clubId, ?string $storedPath): string
    {
        $path = static::displayPath($clubId, $storedPath);
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
}
