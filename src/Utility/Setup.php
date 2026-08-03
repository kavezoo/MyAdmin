<?php
declare(strict_types=1);

namespace App\Utility;

use Cake\ORM\Locator\LocatorAwareTrait;

/**
 * Read typed setup values from anywhere (controllers, views, utilities, CLI).
 *
 * Usage:
 *   use App\Utility\Setup;
 *   $title = Setup::get('site_title', 'My Admin');
 */
class Setup
{
    use LocatorAwareTrait;

    /**
     * Typed PHP value by slug, or $default if missing / invisible / invalid slug.
     */
    public static function get(string $slug, mixed $default = null): mixed
    {
        /** @var \App\Model\Table\SetupsTable $table */
        $table = (new self())->fetchTable('Setups');

        return $table->getValue($slug, $default);
    }
}
