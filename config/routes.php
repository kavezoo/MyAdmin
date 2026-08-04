<?php
/**
 * Routes configuration.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

use Cake\Routing\Route\DashedRoute;
use Cake\Routing\RouteBuilder;

/*
 * Loaded in Application::routes().
 */
return function (RouteBuilder $routes): void {
    $routes->setRouteClass(DashedRoute::class);

    /*
     * Role panels — same chrome as Admin (layout admin + sidebar per prefix).
     * No language segment in the URL (locale = session / user country).
     */
    $panelPrefixes = ['Admin', 'New', 'Member', 'Clubpresident', 'President'];
    foreach ($panelPrefixes as $prefix) {
        $routes->prefix($prefix, function (RouteBuilder $builder): void {
            $builder->connect('/', ['controller' => 'Dashboard', 'action' => 'index']);
            $builder->fallbacks(DashedRoute::class);
        });
    }

    $routes->scope('/', function (RouteBuilder $builder): void {
        // / → login (locale from BrowserLocale / user after auth)
        $builder->connect('/', ['controller' => 'Locales', 'action' => 'home']);
        $builder->connect('/pages/*', 'Pages::display');
        $builder->fallbacks();
    });
};
