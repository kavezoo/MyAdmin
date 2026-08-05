<?php
declare(strict_types=1);

namespace App\Utility;

use App\Auth\CurrentUser;
use Cake\Http\ServerRequest;
use Cake\I18n\DateTime;
use Cake\Log\Log;
use Cake\ORM\Locator\LocatorAwareTrait;

/**
 * Write rows into `event_logs` (never throws to callers).
 */
class EventLogger
{
    use LocatorAwareTrait;

    /** @var list<string> */
    protected const SENSITIVE_KEYS = [
        'password',
        'password_confirm',
        'password_confirmation',
        'current_password',
        'new_password',
        'confirm_password',
        'token',
        'api_token',
        'secret',
        'login_token',
        'csrfToken',
        '_csrfToken',
    ];

    /**
     * Whether a field must never store plaintext in event_logs.
     */
    public static function isSecretField(string $field): bool
    {
        $field = strtolower(trim($field));
        if ($field === '') {
            return false;
        }
        foreach (self::SENSITIVE_KEYS as $key) {
            if (strtolower($key) === $field) {
                return true;
            }
        }

        return str_contains($field, 'password')
            || str_ends_with($field, '_token')
            || $field === 'secret';
    }
    /**
     * @param array{
     *   module: string,
     *   action: string,
     *   entity?: string|null,
     *   entity_id?: int|string|null,
     *   description?: string|null,
     *   url?: string|null,
     *   http_method?: string|null,
     *   request_data?: array<string, mixed>|string|null,
     *   country_id?: int|null,
     *   user_id?: string|null,
     *   actor_role?: string|null,
     *   ip?: string|null,
     *   user_agent?: string|null,
     * } $data
     * @param \Cake\Http\ServerRequest|null $request
     */
    public static function log(array $data, ?ServerRequest $request = null): void
    {
        try {
            $request ??= static::currentRequest();
            $countryHint = isset($data['country_id']) ? (int)$data['country_id'] : 0;
            if ($countryHint < 1 && $request !== null) {
                $countryHint = CurrentUser::countryId($request);
            }
            if (!ActivityLogSetup::isLoggingEnabled($countryHint > 0 ? $countryHint : null, $request)) {
                return;
            }

            $payload = static::normalize($data, $request);
            if ($payload['module'] === '' || $payload['action'] === '') {
                return;
            }

            /** @var \App\Model\Table\EventLogsTable $table */
            $table = (new self())->fetchTable('EventLogs');
            $entity = $table->newEntity($payload, [
                'accessibleFields' => [
                    '*' => true,
                ],
            ]);
            $table->save($entity, ['checkRules' => false, 'atomic' => true]);
        } catch (\Throwable $e) {
            Log::warning('EventLogger failed: ' . $e->getMessage(), ['scope' => ['event_log']]);
        }
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    protected static function normalize(array $data, ?ServerRequest $request): array
    {
        $countryId = isset($data['country_id']) ? (int)$data['country_id'] : 0;
        if ($countryId < 1) {
            $countryId = CurrentUser::countryId($request);
        }
        if ($countryId < 1) {
            $countryId = AdminCountry::id();
        }

        $userId = $data['user_id'] ?? null;
        if (($userId === null || $userId === '') && $request !== null) {
            $identity = $request->getAttribute('identity');
            if (is_object($identity) && method_exists($identity, 'getIdentifier')) {
                $userId = $identity->getIdentifier();
            } elseif (is_object($identity) && method_exists($identity, 'get')) {
                $userId = $identity->get('id');
            }
        }

        $role = $data['actor_role'] ?? null;
        if (($role === null || $role === '') && $request !== null) {
            $role = CurrentUser::role($request);
        }

        $requestData = $data['request_data'] ?? null;
        if (is_array($requestData)) {
            $requestData = static::encodeRequestData($requestData);
        } elseif ($requestData !== null && !is_string($requestData)) {
            $requestData = null;
        }

        $entityId = $data['entity_id'] ?? null;
        if ($entityId !== null && $entityId !== '') {
            $entityId = (string)$entityId;
            if (strlen($entityId) > 64) {
                $entityId = substr($entityId, 0, 64);
            }
        } else {
            $entityId = null;
        }

        $url = $data['url'] ?? null;
        if ($url === null && $request !== null) {
            $url = $request->getRequestTarget();
        }
        if (is_string($url) && strlen($url) > 500) {
            $url = substr($url, 0, 500);
        }

        $ua = $data['user_agent'] ?? null;
        if ($ua === null && $request !== null) {
            $ua = (string)$request->getHeaderLine('User-Agent');
        }
        if (is_string($ua) && strlen($ua) > 500) {
            $ua = substr($ua, 0, 500);
        }

        $ip = $data['ip'] ?? null;
        if ($ip === null && $request !== null) {
            $ip = $request->clientIp();
        }

        $description = isset($data['description']) ? trim((string)$data['description']) : '';
        if (strlen($description) > 500) {
            $description = substr($description, 0, 500);
        }

        return [
            'country_id' => $countryId > 0 ? $countryId : null,
            'user_id' => $userId !== null && $userId !== '' ? (string)$userId : null,
            'actor_role' => $role !== null && $role !== '' ? (string)$role : null,
            'module' => trim((string)($data['module'] ?? '')),
            'action' => trim((string)($data['action'] ?? '')),
            'entity' => isset($data['entity']) && $data['entity'] !== '' ? (string)$data['entity'] : null,
            'entity_id' => $entityId,
            'description' => $description !== '' ? $description : null,
            'url' => is_string($url) && $url !== '' ? $url : null,
            'http_method' => isset($data['http_method'])
                ? strtoupper((string)$data['http_method'])
                : ($request !== null ? $request->getMethod() : null),
            'ip' => is_string($ip) && $ip !== '' ? $ip : null,
            'user_agent' => is_string($ua) && $ua !== '' ? $ua : null,
            'request_data' => $requestData,
            'created' => new DateTime(),
        ];
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function encodeRequestData(array $data): ?string
    {
        $clean = static::sanitize($data);
        if ($clean === []) {
            return null;
        }
        $json = json_encode($clean, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($json === false) {
            return null;
        }
        if (strlen($json) > 65000) {
            $json = substr($json, 0, 65000);
        }

        return $json;
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public static function sanitize(array $data): array
    {
        $out = [];
        foreach ($data as $key => $value) {
            $keyStr = (string)$key;
            if (static::isSecretField($keyStr)) {
                // Keep structured from→to markers from EventLogBehavior.
                if (is_array($value) && (array_key_exists('from', $value) || array_key_exists('to', $value))) {
                    $out[$keyStr] = [
                        'from' => is_string($value['from'] ?? null) && str_starts_with((string)$value['from'], '[')
                            ? $value['from']
                            : '[redacted]',
                        'to' => is_string($value['to'] ?? null) && str_starts_with((string)$value['to'], '[')
                            ? $value['to']
                            : '[changed]',
                    ];
                    continue;
                }
                $out[$keyStr] = '[redacted]';
                continue;
            }
            if (is_array($value)) {
                // Cap nested size
                if (count($value) > 80) {
                    $out[$keyStr] = ['…' => count($value) . ' items'];
                    continue;
                }
                $out[$keyStr] = static::sanitize($value);
                continue;
            }
            if (is_object($value)) {
                continue;
            }
            if (is_string($value) && strlen($value) > 500) {
                $value = substr($value, 0, 500) . '…';
            }
            $out[$keyStr] = $value;
        }

        return $out;
    }
    protected static function currentRequest(): ?ServerRequest
    {
        try {
            /** @var \Cake\Http\ServerRequest|null $request */
            $request = \Cake\Routing\Router::getRequest();

            return $request instanceof ServerRequest ? $request : null;
        } catch (\Throwable) {
            return null;
        }
    }
}
