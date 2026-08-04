<?php
declare(strict_types=1);

namespace App\Middleware;

use Authentication\AuthenticationServiceInterface;
use Cake\Http\Response;
use Cake\ORM\Locator\LocatorAwareTrait;
use Cake\Routing\Router;
use CakeDC\Users\Utility\UsersUrl;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Drop identity when `users.enabled` (or `active`) is off mid-session.
 *
 * Login / Cookie / Token already use `find('active')` (active + enabled).
 * This covers Session restore after an admin/president disables the user.
 */
class RequireUserEnabledMiddleware implements MiddlewareInterface
{
    use LocatorAwareTrait;

    /**
     * Paths that must stay reachable without a valid identity check.
     *
     * @var list<string>
     */
    protected const SKIP_PATH_PREFIXES = [
        '/login',
        '/logout',
        '/register',
        '/request-reset-password',
        '/reset-password',
        '/users/login',
        '/users/logout',
        '/users/register',
        '/users/request-reset-password',
        '/users/reset-password',
        '/users/request-login-link',
        '/users/single-token-login',
        '/users/verify',
        '/users/resend-token-validation',
        '/users/social-email',
        '/css/',
        '/js/',
        '/img/',
        '/font/',
        '/plugins/',
        '/favicon',
    ];

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if ($this->shouldSkip($request)) {
            return $handler->handle($request);
        }

        $identity = $request->getAttribute('identity');
        if ($identity === null) {
            return $handler->handle($request);
        }

        $userId = $this->identityId($identity);
        if ($userId === '') {
            return $handler->handle($request);
        }

        /** @var \App\Model\Table\UsersTable $users */
        $users = $this->fetchTable('Users');
        if ($users->isLoginAllowedForId($userId)) {
            return $handler->handle($request);
        }

        return $this->forceLogout($request);
    }

    protected function shouldSkip(ServerRequestInterface $request): bool
    {
        $path = strtolower($request->getUri()->getPath() ?: '/');
        foreach (self::SKIP_PATH_PREFIXES as $prefix) {
            if ($path === $prefix || str_starts_with($path, $prefix)) {
                return true;
            }
        }

        return false;
    }

    protected function identityId(mixed $identity): string
    {
        if (is_object($identity) && method_exists($identity, 'getIdentifier')) {
            $id = $identity->getIdentifier();
            if ($id !== null && $id !== '') {
                return (string)$id;
            }
        }
        if (is_object($identity) && method_exists($identity, 'get')) {
            $id = $identity->get('id');
            if ($id !== null && $id !== '') {
                return (string)$id;
            }
        }
        if (is_array($identity) && isset($identity['id'])) {
            return (string)$identity['id'];
        }

        return '';
    }

    protected function forceLogout(ServerRequestInterface $request): ResponseInterface
    {
        $response = new Response();
        $service = $request->getAttribute('authentication');
        if ($service instanceof AuthenticationServiceInterface) {
            $cleared = $service->clearIdentity($request, $response);
            $request = $cleared['request'];
            $response = $cleared['response'];
        }

        $session = $request->getAttribute('session');
        if ($session !== null && method_exists($session, 'write')) {
            $session->write('Flash.auth', [
                [
                    'message' => __('Your account has been disabled. Please contact an administrator.'),
                    'key' => 'auth',
                    'element' => 'default',
                    'params' => [],
                ],
            ]);
        }

        $loginUrl = Router::url(UsersUrl::actionUrl('login'));

        return $response
            ->withHeader('Location', $loginUrl)
            ->withStatus(302);
    }
}
