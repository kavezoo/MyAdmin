<?php
declare(strict_types=1);

namespace App\Auth;

use App\Model\Entity\EventLog;
use App\Utility\ActivityLogSetup;
use Cake\Http\ServerRequest;

/**
 * Event log visibility / search ACL.
 */
class EventLogAccess
{
    /**
     * President+ may search country (or all countries if superuser).
     *
     * @return list<string>
     */
    public static function searchRoles(): array
    {
        return AppRoles::globalSearchRoles();
    }

    public static function canSearch(?ServerRequest $request = null): bool
    {
        // CakeDC `is_superuser` flag (even if Users.role is not `superuser`) — same as Admin panel ACL.
        if (CurrentUser::isSuperuser($request)) {
            return true;
        }

        return in_array(CurrentUser::role($request), static::searchRoles(), true);
    }

    public static function canFilterAllCountries(?ServerRequest $request = null): bool
    {
        return CurrentUser::isSuperuser($request);
    }

    public static function canViewOwn(?ServerRequest $request = null): bool
    {
        if (CurrentUser::role($request) === '') {
            return false;
        }

        return ActivityLogSetup::usersCanViewOwn(null, $request);
    }

    public static function canView(EventLog $log, ?ServerRequest $request = null): bool
    {
        $identity = $request?->getAttribute('identity');
        $myId = '';
        if (is_object($identity) && method_exists($identity, 'getIdentifier')) {
            $myId = (string)$identity->getIdentifier();
        } elseif (is_object($identity) && method_exists($identity, 'get')) {
            $myId = (string)($identity->get('id') ?? '');
        }

        if ($myId !== '' && (string)($log->user_id ?? '') === $myId) {
            return static::canViewOwn($request);
        }

        if (!static::canSearch($request)) {
            return false;
        }

        if (static::canFilterAllCountries($request)) {
            return true;
        }

        $myCountry = CurrentUser::countryId($request);
        if ($myCountry < 1) {
            return false;
        }

        return (int)($log->country_id ?? 0) === $myCountry;
    }
}
