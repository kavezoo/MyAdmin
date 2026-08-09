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
    $panelPrefixes = ['Admin', 'New', 'Member', 'Clubpresident', 'President', 'Checkin', 'Judge'];
    foreach ($panelPrefixes as $prefix) {
        $routes->prefix($prefix, function (RouteBuilder $builder) use ($prefix): void {
            $builder->connect('/', ['controller' => 'Dashboard', 'action' => 'index']);
            // Table-judge close API: POST /judge/close/{128-char pair token} (no session)
            if ($prefix === 'Judge') {
                $builder->connect(
                    '/close/{token}',
                    ['controller' => 'Close', 'action' => 'index'],
                )
                    ->setPass(['token'])
                    ->setPatterns(['token' => '[A-Za-z0-9]{128}'])
                    ->setMethods(['POST']);
            }
            $builder->fallbacks(DashedRoute::class);
        });
    }

    /*
     * Mobile / Flutter JSON API (CSRF skipped in Application middleware).
     * POST /api/competitions/results/{competitionToken}/{userToken}
     */
    $routes->prefix('Api', function (RouteBuilder $builder): void {
        $builder->connect(
            '/competitions/results/{competitionToken}/{userToken}',
            ['controller' => 'CompetitionResults', 'action' => 'submit'],
        )
            ->setPass(['competitionToken', 'userToken'])
            ->setMethods(['POST']);
        $builder->fallbacks(DashedRoute::class);
    });

    $routes->scope('/', function (RouteBuilder $builder): void {
        // / → login (locale from BrowserLocale / user after auth)
        $builder->connect('/', ['controller' => 'Locales', 'action' => 'home']);
        $builder->connect('/pages/*', 'Pages::display');
        // Own profile edit (parallel to CakeDC `/profile`)
        $builder->connect('/edit/*', ['controller' => 'Users', 'action' => 'edit'])
            ->setPass(['id']);
        $builder->connect('/complete-profile', ['controller' => 'Users', 'action' => 'completeProfile']);
        // Profile / complete-profile: AJAX club list by country
        $builder->connect('/clubs-for-country', ['controller' => 'Users', 'action' => 'clubsForCountry']);
        $builder->fallbacks();
    });
};
