<?php
declare(strict_types=1);

/**
 * CakeDC Users overrides (loaded via Users.config after plugin defaults).
 *
 * @see vendor/cakedc/users/Docs/Documentation/Extending-the-Plugin.md
 */
return [
    'Users' => [
        // App table (country_id + OneTimeLoginLink CakePHP 5.3 wrappers).
        'table' => 'Users',
        // App controller (login layout, registration country / locale).
        'controller' => 'Users',
        'Registration' => [
            'active' => true,
            'defaultRole' => 'new',
            'reCaptcha' => false,
        ],
        'Tos' => [
            'required' => false,
        ],
        'Email' => [
            'validate' => false,
        ],
        'passwordMeter' => [
            'enabled' => false,
        ],
        'RememberMe' => [
            'active' => true,
        ],
    ],
    'Auth' => [
        'Authenticators' => [
            'Form' => [
                // Login with email (POST field + DB column), not username.
                // @see vendor/cakedc/users/Docs/Documentation/Configuration.md
                'fields' => [
                    'username' => 'email',
                    'password' => 'password',
                ],
                'identifier' => [
                    'Authentication.Password' => [
                        'fields' => [
                            'username' => 'email',
                            'password' => 'password',
                        ],
                    ],
                ],
            ],
        ],
        'AuthenticationComponent' => [
            // Overridden after login by Users.Authentication.afterLogin → RoleHome.
            'loginRedirect' => '/login',
            'logoutRedirect' => '/login',
        ],
        'AuthorizationMiddleware' => [
            'unauthorizedHandler' => [
                'className' => 'CakeDC/Users.DefaultRedirect',
                // App UsersController (plugin null), not CakeDC/Users plugin route.
                'url' => [
                    'plugin' => null,
                    'controller' => 'Users',
                    'action' => 'login',
                ],
            ],
        ],
    ],
];
