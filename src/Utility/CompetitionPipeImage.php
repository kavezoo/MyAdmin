<?php
declare(strict_types=1);

namespace App\Utility;

use Cake\Http\Exception\BadRequestException;
use Psr\Http\Message\UploadedFileInterface;

/**
 * Racing pipe photos: webroot/uploads/competitions/{competitionId}/pipe_{1-3}.png
 */
class CompetitionPipeImage
{
    public const RECOMMENDED_SIZE = 1000;

    public const MAX_BYTES = 5 * 1024 * 1024;

    public const RELATIVE_DIR = 'uploads/competitions';

    /** @var list<string> */
    protected const ALLOWED_MIME = [
        'image/jpeg',
        'image/png',
        'image/webp',
    ];

    public static function fieldName(int $index): string
    {
        return 'racing_pipe_' . $index . '_image';
    }

    public static function storedPathFor(string $competitionId, int $index): string
    {
        $competitionId = trim($competitionId);
        if ($competitionId === '' || $index < 1 || $index > 3) {
            throw new BadRequestException('Invalid competition pipe image.');
        }

        return static::RELATIVE_DIR . '/' . $competitionId . '/pipe_' . $index . '.png';
    }

    public static function store(string $competitionId, int $index, UploadedFileInterface $file): string
    {
        $competitionId = trim($competitionId);
        if ($competitionId === '' || $index < 1 || $index > 3) {
            throw new BadRequestException('Invalid competition pipe image.');
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

        $relative = static::storedPathFor($competitionId, $index);
        $fullDir = WWW_ROOT . static::RELATIVE_DIR . DIRECTORY_SEPARATOR . $competitionId;
        if (!is_dir($fullDir) && !mkdir($fullDir, 0755, true) && !is_dir($fullDir)) {
            imagedestroy($canvas);
            throw new BadRequestException(__('Could not create upload directory.'));
        }

        $fullPath = WWW_ROOT . str_replace('/', DIRECTORY_SEPARATOR, $relative);
        imagealphablending($canvas, false);
        imagesavealpha($canvas, true);
        if (!imagepng($canvas, $fullPath, 6)) {
            imagedestroy($canvas);
            throw new BadRequestException(__('The image could not be saved.'));
        }
        imagedestroy($canvas);

        return $relative;
    }

    public static function deleteStored(?string $storedPath): void
    {
        $storedPath = trim((string)$storedPath);
        if ($storedPath === '') {
            return;
        }
        $full = WWW_ROOT . ltrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $storedPath), DIRECTORY_SEPARATOR);
        if (is_file($full)) {
            @unlink($full);
        }
    }

    public static function publicUrl(?string $storedPath): string
    {
        $storedPath = trim((string)$storedPath);
        if ($storedPath === '' || !is_file(WWW_ROOT . ltrim(str_replace('/', DIRECTORY_SEPARATOR, $storedPath), DIRECTORY_SEPARATOR))) {
            return '';
        }
        $url = '/' . ltrim(str_replace('\\', '/', $storedPath), '/');
        $full = WWW_ROOT . ltrim(str_replace('/', DIRECTORY_SEPARATOR, $storedPath), DIRECTORY_SEPARATOR);
        if (is_file($full)) {
            $url .= '?v=' . (int)filemtime($full);
        }

        return $url;
    }

    public static function imgHtml(?string $storedPath, string $alt): string
    {
        $url = static::publicUrl($storedPath);
        if ($url === '') {
            return '';
        }

        return '<div class="competition-pipe-image-wrap" style="text-align:center;margin:0 0 1rem;">'
            . '<img class="competition-pipe-image" src="' . h($url) . '" alt="' . h($alt) . '"'
            . ' style="display:inline-block;max-width:220px;max-height:220px;width:auto;height:auto;'
            . 'object-fit:contain;background:transparent;">'
            . '</div>';
    }
}
