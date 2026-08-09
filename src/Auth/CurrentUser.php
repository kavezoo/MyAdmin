<?php
declare(strict_types=1);

namespace App\Auth;

use Cake\Core\Configure;
use Cake\Http\ServerRequest;
use Cake\Routing\Router;

/**
 * Resolve current user role from Authentication identity.
 *
 * Order:
 * 1. Request identity — `role` / `role_id`
 * 2. Configure AppRoles.devRole or App.devRole
 * 3. debug → superuser; otherwise → new
 *
 * Superuser powers (Countries full CRUD, Setups create/delete, …):
 * - Users.role === `superuser`, OR
 * - CakeDC users.is_superuser is strictly true (1 / true / "1")
 */
class CurrentUser
{
    /**
     * Logged-in user id (UUID string), or empty string.
     */
    public static function id(?ServerRequest $request = null): string
    {
        $request ??= Router::getRequest();
        if ($request === null) {
            return '';
        }
        $identity = $request->getAttribute('identity');
        if ($identity === null) {
            return '';
        }
        if (is_object($identity) && method_exists($identity, 'getIdentifier')) {
            $id = (string)$identity->getIdentifier();
            if ($id !== '') {
                return $id;
            }
        }

        return (string)(static::identityValue($identity, 'id') ?? '');
    }

    public static function role(?ServerRequest $request = null): string
    {
        $request ??= Router::getRequest();
        if ($request !== null) {
            $fromIdentity = static::roleFromIdentity($request->getAttribute('identity'));
            if ($fromIdentity !== null) {
                return $fromIdentity;
            }
        }

        $configured = Configure::read('AppRoles.devRole');
        if (!is_string($configured) || $configured === '') {
            $configured = Configure::read('App.devRole');
        }
        if (is_string($configured) && AppRoles::isValid($configured)) {
            return strtolower(trim($configured));
        }

        if (Configure::read('debug')) {
            return AppRoles::SUPERUSER;
        }

        return AppRoles::NEW;
    }

    /**
     * Superuser ACL: role `superuser` OR CakeDC `is_superuser` flag (strict).
     */
    public static function isSuperuser(?ServerRequest $request = null): bool
    {
        if (static::role($request) === AppRoles::SUPERUSER) {
            return true;
        }

        return static::isSuperuserFlag($request);
    }

    /**
     * CakeDC `users.is_superuser` from the login identity.
     * Strict: only true / 1 / "1" — never "0", 0, false, null.
     */
    public static function isSuperuserFlag(?ServerRequest $request = null): bool
    {
        $request ??= Router::getRequest();
        if ($request === null) {
            return false;
        }

        return static::truthyFlag(static::identityValue($request->getAttribute('identity'), 'is_superuser'));
    }

    /**
     * @param mixed $value
     */
    public static function truthyFlag(mixed $value): bool
    {
        if ($value === true || $value === 1 || $value === '1') {
            return true;
        }

        // CakePHP BooleanType / PDO sometimes yields these after cast quirks
        if ($value === 'true' || $value === 'on' || $value === 'yes') {
            return true;
        }

        return false;
    }

    /**
     * @param mixed $identity
     * @param string $field
     * @return mixed
     */
    protected static function identityValue(mixed $identity, string $field): mixed
    {
        if ($identity === null) {
            return null;
        }

        if (is_object($identity) && method_exists($identity, 'get')) {
            return $identity->get($field);
        }
        if (is_object($identity) && isset($identity->{$field})) {
            return $identity->{$field};
        }
        if (is_array($identity)) {
            return $identity[$field] ?? null;
        }

        return null;
    }

    /**
     * Logged-in user's Countries.id (`Users.country_id`), or 0.
     */
    public static function countryId(?ServerRequest $request = null): int
    {
        $request ??= Router::getRequest();
        if ($request === null) {
            return 0;
        }
        $raw = static::identityValue($request->getAttribute('identity'), 'country_id');
        if (is_numeric($raw) && (int)$raw > 0) {
            return (int)$raw;
        }

        return 0;
    }

    /**
     * Logged-in user's club (`Users.club_id`), or 0.
     * Identity first; if missing/zero, fresh DB read (club may have been assigned after login).
     */
    public static function clubId(?ServerRequest $request = null): int
    {
        $request ??= Router::getRequest();
        if ($request === null) {
            return 0;
        }
        $raw = static::identityValue($request->getAttribute('identity'), 'club_id');
        if (is_numeric($raw) && (int)$raw > 0) {
            return (int)$raw;
        }

        $identity = $request->getAttribute('identity');
        if ($identity === null) {
            return 0;
        }
        $userId = '';
        if (is_object($identity) && method_exists($identity, 'getIdentifier')) {
            $userId = (string)$identity->getIdentifier();
        } else {
            $userId = (string)(static::identityValue($identity, 'id') ?? '');
        }
        if ($userId === '') {
            return 0;
        }

        try {
            $row = \Cake\ORM\TableRegistry::getTableLocator()->get('Users')->find()
                ->select(['club_id'])
                ->where(['Users.id' => $userId])
                ->disableHydration()
                ->first();
            if (is_array($row) && isset($row['club_id']) && (int)$row['club_id'] > 0) {
                return (int)$row['club_id'];
            }
        } catch (\Throwable $e) {
            return 0;
        }

        return 0;
    }

    /**
     * @param mixed $identity
     */
    protected static function roleFromIdentity(mixed $identity): ?string
    {
        $raw = static::identityValue($identity, 'role');
        if ($raw === null || $raw === '') {
            $raw = static::identityValue($identity, 'role_id');
        }

        if (is_object($raw) && method_exists($raw, '__toString')) {
            $raw = (string)$raw;
        }
        if (!is_string($raw) && !is_int($raw)) {
            return null;
        }
        $role = strtolower(trim((string)$raw));
        if ($role === '' || !AppRoles::isValid($role)) {
            return null;
        }

        return $role;
    }
}
