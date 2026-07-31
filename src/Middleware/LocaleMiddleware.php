<?php
declare(strict_types=1);

namespace App\Middleware;

use Cake\Core\Configure;
use Cake\Http\ServerRequest;
use Cake\I18n\I18n;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Sets the application locale from the `{lang}` route parameter.
 */
class LocaleMiddleware implements MiddlewareInterface
{
    /**
     * @param \Psr\Http\Message\ServerRequestInterface $request The request.
     * @param \Psr\Http\Server\RequestHandlerInterface $handler The request handler.
     * @return \Psr\Http\Message\ResponseInterface A response.
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if ($request instanceof ServerRequest) {
            $languages = Configure::read('App.languages', []);
            $lang = $request->getParam('lang');
            $prefix = $request->getParam('prefix');

            // Admin has no URL language segment — always Hungarian.
            if ($prefix === 'Admin') {
                I18n::setLocale('hu_HU');
                Configure::write('App.defaultLocale', 'hu_HU');
            } elseif (is_string($lang) && isset($languages[$lang])) {
                I18n::setLocale($languages[$lang]);
                Configure::write('App.defaultLocale', $languages[$lang]);
            }
        }

        return $handler->handle($request);
    }
}
