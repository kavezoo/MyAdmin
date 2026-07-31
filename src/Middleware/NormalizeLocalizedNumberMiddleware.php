<?php
declare(strict_types=1);

namespace App\Middleware;

use App\Utility\LocaleNumberParser;
use Cake\Http\ServerRequest;
use Cake\I18n\I18n;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Normalizes localized number strings in request body data for DB save.
 *
 * Runs on POST / PUT / PATCH / DELETE bodies. Skips CSRF and technical keys.
 * Depends on current I18n locale (set by LocaleMiddleware / Admin AppController).
 */
class NormalizeLocalizedNumberMiddleware implements MiddlewareInterface
{
    /**
     * Keys that must never be rewritten.
     *
     * @var list<string>
     */
    protected array $skipKeys = [
        '_csrfToken',
        '_Token',
        '_method',
        '_redirect',
        'password',
        'password_confirm',
        'current_password',
    ];

    /**
     * @param list<string> $skipKeys Extra keys to skip
     */
    public function __construct(array $skipKeys = [])
    {
        if ($skipKeys !== []) {
            $this->skipKeys = array_values(array_unique(array_merge($this->skipKeys, $skipKeys)));
        }
    }

    /**
     * @param \Psr\Http\Message\ServerRequestInterface $request
     * @param \Psr\Http\Server\RequestHandlerInterface $handler
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (!$request instanceof ServerRequest) {
            return $handler->handle($request);
        }

        if (!$request->is(['post', 'put', 'patch', 'delete'])) {
            return $handler->handle($request);
        }

        $data = $request->getData();
        if (!is_array($data) || $data === []) {
            return $handler->handle($request);
        }

        $locale = I18n::getLocale();
        $normalized = $this->walk($data, $locale);

        return $handler->handle($request->withParsedBody($normalized));
    }

    /**
     * @param array<string|int, mixed> $data
     * @return array<string|int, mixed>
     */
    protected function walk(array $data, string $locale): array
    {
        $out = [];
        foreach ($data as $key => $value) {
            $keyStr = (string)$key;
            if (in_array($keyStr, $this->skipKeys, true) || str_starts_with($keyStr, '_')) {
                $out[$key] = $value;
                continue;
            }
            if (is_array($value)) {
                $out[$key] = $this->walk($value, $locale);
                continue;
            }
            $out[$key] = LocaleNumberParser::normalize($value, $locale);
        }

        return $out;
    }
}
