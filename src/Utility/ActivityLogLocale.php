<?php
declare(strict_types=1);

namespace App\Utility;

use Cake\I18n\I18n;

/**
 * Run translated callbacks in a country's primary locale (activity log text at write time).
 */
class ActivityLogLocale
{
    /**
     * @template T
     * @param callable(): T $callback
     * @return T
     */
    public static function runForCountry(int $countryId, callable $callback): mixed
    {
        $previous = I18n::getLocale();
        $locale = AdminCountry::localeForCountry($countryId);
        if ($locale !== null && $locale !== '') {
            I18n::setLocale($locale);
            AdminCountry::applyTranslateLocale($locale);
        }

        try {
            return $callback();
        } finally {
            I18n::setLocale($previous);
            AdminCountry::applyTranslateLocale($previous);
        }
    }
}
