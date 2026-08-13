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
     * Controllers: App\Controller\Api\*
     * Dev: no JWT middleware yet — do NOT applyMiddleware('auth') until registered.
     */
    $routes->prefix('Api', function (RouteBuilder $builder): void {
        $builder->setExtensions(['json']);

        // Judge result submit (obfuscated UUID tokens)
        $builder->connect(
            '/competitions/results/{competitionToken}/{userToken}',
            ['controller' => 'CompetitionResults', 'action' => 'submit'],
        )
            ->setPass(['competitionToken', 'userToken'])
            ->setMethods(['POST']);

        // Flutter v1 → /api/v1/...
        $builder->scope('/v1', function (RouteBuilder $v1): void {
            $v1->connect('/profile', ['controller' => 'Profile', 'action' => 'index'])
                ->setMethods(['GET']);
            $v1->connect('/profile', ['controller' => 'Profile', 'action' => 'update'])
                ->setMethods(['PUT']);

            $v1->connect('/competitions', ['controller' => 'Competitions', 'action' => 'index'])
                ->setMethods(['GET']);
            $v1->connect('/competitions/{id}/apply', ['controller' => 'Competitions', 'action' => 'apply'])
                ->setPass(['id'])
                ->setMethods(['POST']);

            $v1->connect('/results/my', ['controller' => 'Results', 'action' => 'myResults'])
                ->setMethods(['GET']);
            $v1->connect('/results/all', ['controller' => 'Results', 'action' => 'allResults'])
                ->setMethods(['GET']);

            $v1->scope('/president', function (RouteBuilder $p): void {
                $p->connect('/pending-members', ['controller' => 'President', 'action' => 'pendingMembers'])
                    ->setMethods(['GET']);
                $p->connect('/approve-member/{id}', ['controller' => 'President', 'action' => 'approveMember'])
                    ->setPass(['id'])
                    ->setMethods(['POST']);
                $p->connect('/competitions/{id}/applicants', ['controller' => 'President', 'action' => 'competitionApplicants'])
                    ->setPass(['id'])
                    ->setMethods(['GET']);
                $p->connect('/competitions/{id}/subclubs', ['controller' => 'President', 'action' => 'createSubclub'])
                    ->setPass(['id'])
                    ->setMethods(['POST']);
                $p->connect('/assign-member', ['controller' => 'President', 'action' => 'assignMemberToSubclub'])
                    ->setMethods(['POST']);
            });
        });

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
