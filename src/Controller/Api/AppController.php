<?php
declare(strict_types=1);

namespace App\Controller\Api;

use App\Controller\AppController as BaseController;
use Cake\Core\Configure;
use Cake\Event\EventInterface;
use Cake\Http\Response;
use Cake\ORM\Locator\LocatorAwareTrait;

/**
 * JSON API base (Flutter / mobile). No admin layout.
 *
 * While `Api.openAccess` is true (default for now): no login, no Bearer token.
 * Optional injected user (`Api.devUserId` / first active) so actions can read identity.
 */
class AppController extends BaseController
{
    use LocatorAwareTrait;

    public function initialize(): void
    {
        parent::initialize();
        $this->viewBuilder()->disableAutoLayout();
        $this->viewBuilder()->setClassName('Json');

        // Never require session/JWT identity on Api controllers while openAccess is on.
        if (!$this->components()->has('Authentication')) {
            $this->loadComponent('Authentication.Authentication', [
                'requireIdentity' => false,
            ]);
        } else {
            $this->Authentication->setConfig('requireIdentity', false);
        }
    }

    public function beforeFilter(EventInterface $event)
    {
        parent::beforeFilter($event);
        $this->response = $this->response->withType('application/json');

        if (!$this->components()->has('Authentication')) {
            return;
        }

        // Allow every action without credentials.
        $action = (string)$this->request->getParam('action');
        if ($action !== '') {
            $this->Authentication->addUnauthenticatedActions([$action]);
        }

        if ($this->isApiOpenAccess()) {
            $this->ensureDevIdentity();
        }
    }

    /**
     * Free API access (no password / token). Forced on until Flutter auth is ready.
     */
    protected function isApiOpenAccess(): bool
    {
        $configured = Configure::read('Api.openAccess');
        if ($configured === false) {
            return false;
        }

        // Default: open (true). Only explicit false locks it down.
        return true;
    }

    /**
     * Inject a user identity when openAccess and nobody is logged in.
     */
    protected function ensureDevIdentity(): void
    {
        if (!$this->components()->has('Authentication')) {
            return;
        }
        if ($this->Authentication->getIdentity() !== null) {
            return;
        }

        $users = $this->fetchTable('Users');
        $userId = Configure::read('Api.devUserId');
        $user = null;
        try {
            if (is_string($userId) && $userId !== '') {
                $user = $users->find()->where(['Users.id' => $userId])->first();
            }
            if ($user === null) {
                $user = $users->find()
                    ->where(['Users.active' => 1])
                    ->orderBy(['Users.created' => 'ASC'])
                    ->first();
            }
        } catch (\Throwable) {
            $user = null;
        }

        if ($user === null) {
            return;
        }

        $this->Authentication->setIdentity($user);
    }

    /**
     * @param array<string, mixed> $payload
     */
    protected function jsonResponse(array $payload, int $status = 200): Response
    {
        return $this->response
            ->withStatus($status)
            ->withType('application/json')
            ->withHeader('Access-Control-Allow-Origin', '*')
            ->withStringBody((string)json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }
}
