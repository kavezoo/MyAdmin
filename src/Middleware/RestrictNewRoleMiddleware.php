<?php
declare(strict_types=1);

namespace App\Middleware;

use App\Auth\AppRoles;
use Cake\Http\Response;
use Cake\Routing\Router;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Role `new` may only use the `/new` panel and own profile / auth actions.
 *
 * Used after club switch (session role reset to `new`) until president approves
 * membership and the user logs in again with role `member`.
 */
class RestrictNewRoleMiddleware implements MiddlewareInterface
{
    /**
     * @var list<string>
     */
    protected const SKIP_PATH_PREFIXES = [
        '/css/',
        '/js/',
        '/img/',
        '/font/',
        '/plugins/',
        '/favicon',
    ];

    /**
     * Users controller actions reachable without the New prefix.
     *
     * @var list<string>
     */
    protected const ALLOWED_USER_ACTIONS = [
        'profile',
        'edit',
        'completeprofile',
        'clubsforcountry',
        'deleteavatar',
        'logout',
        'linksocial',
        'callbacklinksocial',
        'changepassword',
        'eventlog',
        'eventlogview',
        'resendtokenvalidation',
        'validateemail',
        'validate',
        'resetpassword',
        'requestresetpassword',
        'requestloginlink',
        'singletokenlogin',
        'verify',
        'social',
        'socialdel',
        'u2f',
        'u2fverify',
        'u2fregister',
        'u2fstart',
        'u2fcomplete',
    ];

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if ($this->shouldSkipPath($request)) {
            return $handler->handle($request);
        }

        $identity = $request->getAttribute('identity');
        if ($identity === null) {
            return $handler->handle($request);
        }

        $role = '';
        if (method_exists($identity, 'get')) {
            $role = strtolower(trim((string)($identity->get('role') ?? '')));
        }
        if ($role !== AppRoles::NEW) {
            return $handler->handle($request);
        }

        $prefix = strtolower((string)$request->getParam('prefix'));
        if ($prefix === 'new') {
            return $handler->handle($request);
        }

        $plugin = (string)($request->getParam('plugin') ?? '');
        $controller = strtolower((string)$request->getParam('controller'));
        $action = strtolower((string)$request->getParam('action'));

        if ($plugin === '' && $controller === 'users' && in_array($action, self::ALLOWED_USER_ACTIONS, true)) {
            return $handler->handle($request);
        }

        $location = Router::url([
            'prefix' => 'New',
            'controller' => 'Dashboard',
            'action' => 'index',
        ]);

        return (new Response())
            ->withHeader('Location', $location)
            ->withStatus(302);
    }

    protected function shouldSkipPath(ServerRequestInterface $request): bool
    {
        $path = strtolower($request->getUri()->getPath() ?: '/');
        foreach (self::SKIP_PATH_PREFIXES as $prefix) {
            if (str_starts_with($path, $prefix)) {
                return true;
            }
        }

        return false;
    }
}
