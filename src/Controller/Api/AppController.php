<?php
declare(strict_types=1);

namespace App\Controller\Api;

use App\Controller\AppController as BaseController;
use Cake\Event\EventInterface;
use Cake\Http\Response;

/**
 * JSON API base (Flutter / mobile). No admin layout.
 */
class AppController extends BaseController
{
    public function initialize(): void
    {
        parent::initialize();
        $this->viewBuilder()->disableAutoLayout();
    }

    public function beforeFilter(EventInterface $event)
    {
        parent::beforeFilter($event);
        $this->response = $this->response->withType('application/json');
    }

    /**
     * @param array<string, mixed> $payload
     */
    protected function jsonResponse(array $payload, int $status = 200): Response
    {
        return $this->response
            ->withStatus($status)
            ->withType('application/json')
            ->withStringBody((string)json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }
}
