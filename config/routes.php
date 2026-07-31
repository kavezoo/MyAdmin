<?php
/**
 * Routes configuration.
 *
 * In this file, you set up routes to your controllers and their actions.
 * Routes are very important mechanism that allows you to freely connect
 * different URLs to chosen controllers and their actions (functions).
 *
 * It's loaded within the context of `Application::routes()` method which
 * receives a `RouteBuilder` instance `$routes` as method argument.
 *
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

use Cake\Core\Configure;
use Cake\Routing\Route\DashedRoute;
use Cake\Routing\RouteBuilder;

/*
 * This file is loaded in the context of the `Application` class.
 * So you can use `$this` to reference the application class instance
 * if required.
 */
return function (RouteBuilder $routes): void {
    /*
     * The default class to use for all routes
     *
     * The following route classes are supplied with CakePHP and are appropriate
     * to set as the default:
     *
     * - Route
     * - InflectedRoute
     * - DashedRoute
     *
     * If no call is made to `Router::defaultRouteClass()`, the class used is
     * `Route` (`Cake\Routing\Route\Route`)
     *
     * Note that `Route` does not do any inflections on URLs which will result in
     * inconsistently cased URLs when used with `{plugin}`, `{controller}` and
     * `{action}` markers.
     */
    $routes->setRouteClass(DashedRoute::class);

    /*
     * Admin prefix routes.
     * Controllers live in src/Controller/Admin/, templates in templates/Admin/.
     * URLs are under /admin/...
     */
    $routes->prefix('Admin', function (RouteBuilder $builder): void {
        $builder->connect('/', ['controller' => 'Dashboard', 'action' => 'index']);
        $builder->fallbacks(DashedRoute::class);
    });

    /*
     * Member prefix with language segment.
     * Controllers live in src/Controller/Member/, templates in templates/Member/.
     * URLs are under /{lang}/member/... e.g. /hu/member
     *
     * Must be registered before the root catch-all fallbacks, otherwise
     * /hu/... is matched as HuController.
     */
    $routes->scope('/{lang}', function (RouteBuilder $builder): void {
        $builder->setOptions([
            'lang' => implode('|', array_keys(Configure::read('App.languages', []))),
            'persist' => ['lang'],
        ]);

        // /hu → /hu/member
        $builder->redirect('/', ['prefix' => 'Member', 'controller' => 'Dashboard', 'action' => 'index'], [
            'persist' => ['lang'],
            'status' => 302,
        ]);

        $builder->prefix('Member', function (RouteBuilder $builder): void {
            $builder->connect('/', ['controller' => 'Dashboard', 'action' => 'index']);
            $builder->fallbacks(DashedRoute::class);
        });
    });

    $routes->scope('/', function (RouteBuilder $builder): void {
        // / → /{browser-lang}/member
        $builder->connect('/', ['controller' => 'Locales', 'action' => 'home']);

        /*
         * ...and connect the rest of 'Pages' controller's URLs.
         */
        $builder->connect('/pages/*', 'Pages::display');

        /*
         * Connect catchall routes for all controllers.
         *
         * The `fallbacks` method is a shortcut for
         *
         * ```
         * $builder->connect('/{controller}', ['action' => 'index']);
         * $builder->connect('/{controller}/{action}/*', []);
         * ```
         *
         * It is NOT recommended to use fallback routes after your initial prototyping phase!
         * See https://book.cakephp.org/5/en/development/routing.html#fallbacks-method for more information
         */
        $builder->fallbacks();
    });
};
