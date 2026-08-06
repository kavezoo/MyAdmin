<?php
declare(strict_types=1);

namespace App\Utility;

use Cake\Core\Configure;

/**
 * Visible application brand (login logo, browser title, welcome texts).
 *
 * Config (`config/app.php` → `App`):
 * - `App.Name` — short brand (login H1, UI strings) — env `APP_NAME`
 * - `App.Title` — browser document title base — env `APP_TITLE` (falls back to Name)
 */
final class AppBrand
{
    public const DEFAULT_NAME = 'PipeOffice';

    public static function name(): string
    {
        $name = Configure::read('App.Name');
        if (is_string($name) && trim($name) !== '') {
            return trim($name);
        }

        return self::DEFAULT_NAME;
    }

    public static function title(): string
    {
        $title = Configure::read('App.Title');
        if (is_string($title) && trim($title) !== '') {
            return trim($title);
        }

        return static::name();
    }
}
