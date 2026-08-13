<?php
declare(strict_types=1);

namespace App\Http\Exception;

use Authorization\Exception\Exception;
use Authorization\Middleware\UnauthorizedHandler\HandlerInterface;
use Cake\Http\Response;
use Cake\Http\ServerRequest;
use CakeDC\Users\Middleware\UnauthorizedHandler\DefaultRedirectHandler;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * API (/api/*): JSON 401 — never redirect to HTML login (Flutter would think “token required”).
 * Other paths: CakeDC default redirect to login.
 */
class ApiAwareUnauthorizedHandler implements HandlerInterface
{
    public function handle(
        Exception $exception,
        ServerRequestInterface $request,
        array $options = [],
    ): ResponseInterface {
        $path = $request->getUri()->getPath();
        $prefix = $request instanceof ServerRequest ? (string)$request->getParam('prefix') : '';
        if (str_starts_with($path, '/api/') || strcasecmp($prefix, 'Api') === 0) {
            $body = (string)json_encode([
                'success' => false,
                'message' => 'Unauthorized (RBAC). Api.openAccess should allow this in development.',
                'error' => $exception->getMessage(),
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

            return (new Response())
                ->withStatus(401)
                ->withType('application/json')
                ->withHeader('Access-Control-Allow-Origin', '*')
                ->withStringBody($body);
        }

        return (new DefaultRedirectHandler())->handle($exception, $request, $options);
    }
}
