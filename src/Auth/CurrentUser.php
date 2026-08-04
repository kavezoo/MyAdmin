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
 * App “superuser” powers = Users.role === `superuser` only.
 * CakeDC `users.is_superuser` is a separate profile badge — not used for ACL.
 */
class CurrentUser
{
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
     * True only when Users.role is `superuser` (not CakeDC is_superuser flag).
     */
    public static function isSuperuser(?ServerRequest $request = null): bool
    {
        return static::role($request) === AppRoles::SUPERUSER;
    }

    /**
     * CakeDC `users.is_superuser` flag from identity (profile badge only).
     * Strict: only true/1/"1" count — never treat empty / "0" as true.
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
