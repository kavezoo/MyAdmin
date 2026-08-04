<?php
declare(strict_types=1);

namespace App\Auth;

/**
 * Countries Admin access:
 * - Users.role `superuser`: add, delete, full edit
 * - Users.role `admin`: edit visible + pos only
 * - CakeDC `is_superuser` flag is NOT used here
 */
class CountryAccess
{
    public static function canAdd(?\Cake\Http\ServerRequest $request = null): bool
    {
        return CurrentUser::isSuperuser($request);
    }

    public static function canDelete(?\Cake\Http\ServerRequest $request = null): bool
    {
        return CurrentUser::isSuperuser($request);
    }

    /**
     * Full field edit (iso2, name, locale, continent, …).
     */
    public static function canEditFully(?\Cake\Http\ServerRequest $request = null): bool
    {
        return CurrentUser::isSuperuser($request);
    }

    /**
     * Edit visible + pos (admin or superuser).
     */
    public static function canEditMeta(?\Cake\Http\ServerRequest $request = null): bool
    {
        $role = CurrentUser::role($request);

        return $role === AppRoles::SUPERUSER || $role === AppRoles::ADMIN;
    }
}
