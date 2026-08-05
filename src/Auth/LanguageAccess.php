<?php
declare(strict_types=1);

namespace App\Auth;

/**
 * Languages Admin access (same matrix as Countries):
 * - Module (index/view/sidebar): admin or superuser
 * - superuser: add, delete, full edit
 * - admin: edit visible + pos only
 */
class LanguageAccess
{
    /**
     * Sidebar + URL: open Languages module at all.
     */
    public static function canAccessModule(?\Cake\Http\ServerRequest $request = null): bool
    {
        return static::canEditMeta($request);
    }

    public static function canAdd(?\Cake\Http\ServerRequest $request = null): bool
    {
        return CurrentUser::isSuperuser($request);
    }

    public static function canDelete(?\Cake\Http\ServerRequest $request = null): bool
    {
        return CurrentUser::isSuperuser($request);
    }

    /**
     * Full field edit (code, name, endonim_name, …).
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
        if (CurrentUser::isSuperuser($request)) {
            return true;
        }

        return CurrentUser::role($request) === AppRoles::ADMIN;
    }
}
