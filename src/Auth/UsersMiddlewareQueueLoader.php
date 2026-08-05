<?php
declare(strict_types=1);

namespace App\Auth;

use App\Middleware\RestrictNewRoleMiddleware;
use App\Middleware\RequireUserEnabledMiddleware;
use Authentication\AuthenticationServiceProviderInterface;
use Authorization\AuthorizationServiceProviderInterface;
use Cake\Http\MiddlewareQueue;
use CakeDC\Users\Loader\MiddlewareQueueLoader as CakeDCMiddlewareQueueLoader;

/**
 * CakeDC auth middleware + enabled gate.
 *
 * Page views are not logged — entity CRUD via EventLogBehavior; login/logout via Application events.
 */
class UsersMiddlewareQueueLoader extends CakeDCMiddlewareQueueLoader
{
    /**
     * @inheritDoc
     */
    public function __invoke(
        MiddlewareQueue $middlewareQueue,
        AuthenticationServiceProviderInterface $authenticationServiceProvider,
        AuthorizationServiceProviderInterface $authorizationServiceProvider,
    ): MiddlewareQueue {
        $middlewareQueue = parent::__invoke(
            $middlewareQueue,
            $authenticationServiceProvider,
            $authorizationServiceProvider,
        );
        $middlewareQueue->add(new RequireUserEnabledMiddleware());
        $middlewareQueue->add(new RestrictNewRoleMiddleware());

        return $middlewareQueue;
    }
}
