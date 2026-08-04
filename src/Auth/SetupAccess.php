<?php
declare(strict_types=1);

namespace App\Auth;

use App\Model\Entity\Setup;
use App\Utility\SetupEditBy;
use Cake\Http\ServerRequest;

/**
 * Setups module authorization (works before CakeDC Users is installed).
 */
class SetupAccess
{
    public static function canAccessModule(?ServerRequest $request = null): bool
    {
        return in_array(CurrentUser::role($request), AppRoles::setupsModuleRoles(), true);
    }

    public static function canCreate(?ServerRequest $request = null): bool
    {
        return CurrentUser::isSuperuser($request);
    }

    public static function canChangeCountry(?ServerRequest $request = null): bool
    {
        return CurrentUser::isSuperuser($request);
    }

    /**
     * Change name / slug / type / edit_by metadata.
     */
    public static function canEditMetadata(?ServerRequest $request = null): bool
    {
        return CurrentUser::isSuperuser($request);
    }

    public static function canDelete(?ServerRequest $request = null): bool
    {
        return CurrentUser::isSuperuser($request);
    }

    public static function canEditValue(Setup|string $setupOrEditBy, ?ServerRequest $request = null): bool
    {
        $editBy = $setupOrEditBy instanceof Setup
            ? SetupEditBy::normalizeStored((string)($setupOrEditBy->edit_by ?? SetupEditBy::ADMIN))
            : SetupEditBy::normalizeStored($setupOrEditBy);

        return SetupEditBy::allows($editBy, CurrentUser::role($request));
    }

    /**
     * edit_by levels the current user may assign when creating (superuser only in practice).
     *
     * @return array<string, string>
     */
    public static function assignableEditByOptions(?ServerRequest $request = null): array
    {
        if (!CurrentUser::isSuperuser($request)) {
            return [];
        }

        return SetupEditBy::options();
    }
}
