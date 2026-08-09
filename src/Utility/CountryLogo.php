<?php
declare(strict_types=1);

namespace App\Utility;

use Cake\Http\Exception\BadRequestException;
use Psr\Http\Message\UploadedFileInterface;

/**
 * National pipe association logo per country (webroot/uploads/countries/{id}.png).
 *
 * PNG preferred so transparent areas stay transparent in competition announcements.
 */
class CountryLogo
{
    public const RECOMMENDED_SIZE = 1000;

    public const MAX_BYTES = 5 * 1024 * 1024;

    public const RELATIVE_DIR = 'uploads/countries';

    /**
     * @var list<string>
     */
    protected const ALLOWED_MIME = [
        'image/jpeg',
        'image/png',
        'image/webp',
    ];

    public static function storedPathFor(int $countryId): string
    {
        return static::RELATIVE_DIR . '/' . static::filenameFor($countryId);
    }

    public static function filenameFor(int $countryId): string
    {
        if ($countryId < 1) {
            throw new BadRequestException('Invalid country.');
        }

        return $countryId . '.png';
    }

    public static function store(int $countryId, UploadedFileInterface $file): string
    {
        if ($countryId < 1) {
            throw new BadRequestException('Invalid country.');
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

        $filename = static::filenameFor($countryId);
        $fullPath = $dir . DIRECTORY_SEPARATOR . $filename;
        imagealphablending($canvas, false);
        imagesavealpha($canvas, true);
        if (!imagepng($canvas, $fullPath, 6)) {
            imagedestroy($canvas);
            throw new BadRequestException(__('The image could not be saved.'));
        }
        imagedestroy($canvas);

        // Drop legacy JPEG if present.
        $legacyJpg = $dir . DIRECTORY_SEPARATOR . $countryId . '.jpg';
        if (is_file($legacyJpg)) {
            @unlink($legacyJpg);
        }

        return static::storedPathFor($countryId);
    }

    public static function deleteStored(?string $storedPath, int $countryId = 0): void
    {
        $paths = [];
        if ($storedPath !== null && trim($storedPath) !== '') {
            $paths[] = WWW_ROOT . ltrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $storedPath), DIRECTORY_SEPARATOR);
        }
        if ($countryId > 0) {
            $dir = WWW_ROOT . static::RELATIVE_DIR . DIRECTORY_SEPARATOR;
            $paths[] = $dir . static::filenameFor($countryId);
            $paths[] = $dir . $countryId . '.jpg';
        }
        foreach ($paths as $path) {
            if (is_file($path)) {
                @unlink($path);
            }
        }
    }

    public static function displayPath(int $countryId, ?string $storedPath): string
    {
        $canonical = $countryId > 0 ? static::storedPathFor($countryId) : '';
        if ($canonical !== '' && is_file(static::fullPath($canonical))) {
            return $canonical;
        }

        if ($countryId > 0) {
            $legacy = static::RELATIVE_DIR . '/' . $countryId . '.jpg';
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

    public static function publicUrlFor(int $countryId, ?string $storedPath): string
    {
        $path = static::displayPath($countryId, $storedPath);
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
