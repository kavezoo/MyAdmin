<?php
declare(strict_types=1);

namespace App\Middleware;

use Cake\Http\Response;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Break /login?redirect=/login?redirect=… loops (URI Too Long).
 *
 * If the target is already an auth page, drop the nested redirect and
 * send the browser to a clean /login.
 */
class SanitizeAuthRedirectMiddleware implements MiddlewareInterface
{
    /**
     * Auth paths that must never appear as redirect targets.
     *
     * @var list<string>
     */
    protected const AUTH_PATHS = [
        '/login',
        '/register',
        '/logout',
        '/users/login',
        '/users/register',
        '/users/logout',
        '/users/request-reset-password',
        '/users/requestresetpassword',
        '/request-reset-password',
    ];

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $path = strtolower(rtrim($request->getUri()->getPath(), '/') ?: '/');
        $redirect = $request->getQueryParams()['redirect'] ?? null;

        if (!is_string($redirect) || $redirect === '') {
            return $handler->handle($request);
        }

        // Extremely nested / encoded redirect → hard reset
        if (strlen($redirect) > 200 || substr_count(strtolower($redirect), 'login') > 1) {
            return $this->cleanLoginRedirect($request);
        }

        if ($this->isAuthPath($path) && $this->redirectTargetsAuth($redirect)) {
            return $this->cleanLoginRedirect($request);
        }

        return $handler->handle($request);
    }

    protected function redirectTargetsAuth(string $redirect): bool
    {
        $decoded = $redirect;
        for ($i = 0; $i < 8; $i++) {
            $next = rawurldecode($decoded);
            if ($next === $decoded) {
                break;
            }
            $decoded = $next;
        }

        $targetPath = parse_url($decoded, PHP_URL_PATH);
        if (!is_string($targetPath) || $targetPath === '') {
            // Relative query-only or path without scheme
            $targetPath = explode('?', $decoded, 2)[0];
        }
        $targetPath = strtolower(rtrim($targetPath, '/') ?: '/');

        return $this->isAuthPath($targetPath)
            || str_contains($targetPath, '/login');
    }

    protected function isAuthPath(string $path): bool
    {
        return in_array($path, self::AUTH_PATHS, true);
    }

    protected function cleanLoginRedirect(ServerRequestInterface $request): ResponseInterface
    {
        return (new Response())
            ->withStatus(302)
            ->withHeader('Location', '/login');
    }
}
