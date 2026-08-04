<?php
declare(strict_types=1);

namespace App\Auth;

use Cake\Core\Configure;
use Cake\Http\ServerRequest;
use Cake\Routing\Router;

/**
 * Resolve current user role without requiring CakeDC Users / Authentication yet.
 *
 * Order:
 * 1. Request identity (Authentication plugin / CakeDC) — role / role_id string field
 * 2. Configure AppRoles.devRole or App.devRole
 * 3. debug → superuser; otherwise → new (no Setups access)
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

    public static function isSuperuser(?ServerRequest $request = null): bool
    {
        return static::role($request) === AppRoles::SUPERUSER;
    }

    /**
     * @param mixed $identity
     */
    protected static function roleFromIdentity(mixed $identity): ?string
    {
        if ($identity === null) {
            return null;
        }

        $raw = null;
        if (is_object($identity)) {
            if (method_exists($identity, 'get')) {
                $raw = $identity->get('role') ?? $identity->get('role_id') ?? null;
            } elseif (isset($identity->role)) {
                $raw = $identity->role;
            }
        } elseif (is_array($identity)) {
            $raw = $identity['role'] ?? $identity['role_id'] ?? null;
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
