<?php
declare(strict_types=1);

namespace App\Utility;

use App\Auth\CurrentUser;
use Cake\Http\ServerRequest;
use Cake\ORM\Locator\LocatorAwareTrait;
use Cake\Routing\Router;

/**
 * Activity log toggles stored in Setups (per country).
 */
class ActivityLogSetup
{
    use LocatorAwareTrait;

    public const SLUG_LOGGING_ENABLED = 'activity_logging_enabled';

    public const SLUG_USERS_VIEW_ENABLED = 'users_activity_log_visible';

    /**
     * Default setup row definitions (one row per country per slug).
     *
     * @return array<string, array{name: string, type: string, value: string, edit_by: string, visible: bool}>
     */
    public static function definitions(): array
    {
        return [
            self::SLUG_LOGGING_ENABLED => [
                'name' => 'Activity logging enabled',
                'type' => SetupValue::TYPE_BOOLEAN,
                'value' => '1',
                'edit_by' => SetupEditBy::ADMIN,
                'visible' => true,
            ],
            self::SLUG_USERS_VIEW_ENABLED => [
                'name' => 'Users can view own activity log',
                'type' => SetupValue::TYPE_BOOLEAN,
                'value' => '1',
                'edit_by' => SetupEditBy::ADMIN,
                'visible' => true,
            ],
        ];
    }

    /**
     * Ensure activity-log setup rows exist for every visible country.
     */
    public static function ensureRowsForAllCountries(): void
    {
        /** @var \App\Model\Table\SetupsTable $setups */
        $setups = (new self())->fetchTable('Setups');
        $setups->ensureActivityLogSetups();
    }

    /**
     * Write new rows into event_logs when enabled for the country.
     */
    public static function isLoggingEnabled(?int $countryId = null, ?ServerRequest $request = null): bool
    {
        $countryId = static::resolveCountryId($countryId, $request);

        return (bool)Setup::get(self::SLUG_LOGGING_ENABLED, true, $countryId);
    }

    /**
     * Logged-in users may open /users/event-log (own rows only).
     */
    public static function usersCanViewOwn(?int $countryId = null, ?ServerRequest $request = null): bool
    {
        $countryId = static::resolveCountryId($countryId, $request);

        return (bool)Setup::get(self::SLUG_USERS_VIEW_ENABLED, true, $countryId);
    }

    protected static function resolveCountryId(?int $countryId, ?ServerRequest $request): int
    {
        if ($countryId !== null && $countryId > 0) {
            return $countryId;
        }

        $request ??= Router::getRequest();
        $fromUser = CurrentUser::countryId($request);
        if ($fromUser > 0) {
            return $fromUser;
        }

        return AdminCountry::id();
    }
}
