<?php
declare(strict_types=1);

namespace App\Middleware;

use App\Utility\AdminCountry;
use App\Utility\BrowserLocale;
use Cake\Core\Configure;
use Cake\Http\Response;
use Cake\Http\ServerRequest;
use Cake\I18n\I18n;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Sets the application locale from session / cookie / Accept-Language
 * (matched to visible Countries.locale). No URL language prefix.
 *
 * Panel AppControllers may refine from Users.country_id after Auth.
 * Responses refresh the locale cookie from session (≥1 year).
 */
class LocaleMiddleware implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if ($request instanceof ServerRequest) {
            static::apply(BrowserLocale::resolve($request));
        }

        $response = $handler->handle($request);

        if ($request instanceof ServerRequest && $response instanceof Response) {
            $sessionLocale = $request->getSession()->read(BrowserLocale::SESSION_KEY);
            if (is_string($sessionLocale) && BrowserLocale::isKnownLocale($sessionLocale)) {
                return BrowserLocale::persist($request, $response, $sessionLocale);
            }
        }

        return $response;
    }

    protected static function apply(string $locale): void
    {
        I18n::setLocale($locale);
        Configure::write('App.defaultLocale', $locale);
        AdminCountry::applyTranslateLocale($locale);
    }
}
