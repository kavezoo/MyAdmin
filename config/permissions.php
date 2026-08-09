<?php
declare(strict_types=1);

/**
 * CakeDC Auth RBAC permissions.
 *
 * IMPORTANT: App `Users.controller = Users` → request `plugin` is null.
 * Do NOT set `'plugin' => false` (false !== null in CakeDC Rbac matcher).
 *
 * Role panels: each role may enter its own prefix (Registration `new` → New only).
 * Officers also access Member; president/vp/clubpresident with club → Clubpresident too.
 *
 * @see vendor/cakedc/users/config/permissions.php
 */

return [
    'CakeDC/Auth.permissions' => [
        // Public auth — App UsersController (plugin null)
        [
            'controller' => 'Users',
            'action' => [
                'socialLogin',
                'login',
                'logout',
                'socialEmail',
                'verify',
                'register',
                'validateEmail',
                'changePassword',
                'resetPassword',
                'requestResetPassword',
                'resendTokenValidation',
                'linkSocial',
                'webauthn2fa',
                'webauthn2faRegister',
                'webauthn2faRegisterOptions',
                'webauthn2faAuthenticate',
                'webauthn2faAuthenticateOptions',
                'requestLoginLink',
                'sendLoginLink',
                'singleTokenLogin',
            ],
            'bypassAuth' => true,
        ],
        [
            'plugin' => 'CakeDC/Users',
            'controller' => 'SocialAccounts',
            'action' => [
                'validateAccount',
                'resendValidation',
            ],
            'bypassAuth' => true,
        ],
        [
            'controller' => 'Locales',
            'action' => ['home'],
            'bypassAuth' => true,
        ],
        [
            'controller' => 'Pages',
            'action' => 'display',
            'bypassAuth' => true,
        ],
        [
            'role' => '*',
            'plugin' => 'DebugKit',
            'controller' => '*',
            'action' => '*',
            'bypassAuth' => true,
        ],
        // Logged-in profile / logout (all roles)
        [
            'role' => '*',
            'controller' => 'Users',
            'action' => ['profile', 'edit', 'completeProfile', 'clubsForCountry', 'logout', 'linkSocial', 'callbackLinkSocial', 'changePassword', 'eventLog', 'eventLogView', 'deleteAvatar'],
        ],
        // Admin prefix — only admin / superuser (full panel)
        [
            'role' => ['superuser', 'admin'],
            'prefix' => 'Admin',
            'controller' => '*',
            'action' => '*',
        ],
        [
            'role' => 'new',
            'prefix' => 'New',
            'controller' => '*',
            'action' => '*',
        ],
        // Guests assigned as competition staff
        [
            'role' => 'new',
            'prefix' => 'Checkin',
            'controller' => '*',
            'action' => '*',
        ],
        [
            'role' => 'new',
            'prefix' => 'Judge',
            'controller' => '*',
            'action' => '*',
        ],
        [
            'role' => ['member', 'editor'],
            'prefix' => 'Member',
            'controller' => '*',
            'action' => '*',
        ],
        // Members+ may open staff panels when assigned (AppController enforces assignment)
        [
            'role' => ['member', 'editor', 'clubpresident', 'president', 'vicepresident'],
            'prefix' => 'Checkin',
            'controller' => '*',
            'action' => '*',
        ],
        [
            'role' => ['member', 'editor', 'clubpresident', 'president', 'vicepresident'],
            'prefix' => 'Judge',
            'controller' => '*',
            'action' => '*',
        ],
        // Flutter judge API — controller checks identity + competition_staff + staff day; JSON 401/403.
        [
            'prefix' => 'Api',
            'controller' => 'CompetitionResults',
            'action' => 'submit',
            'bypassAuth' => true,
        ],
        // Public close: obfuscated competition+competitor token + POST email/time (no session).
        [
            'prefix' => 'Judge',
            'controller' => 'Close',
            'action' => 'index',
            'bypassAuth' => true,
        ],
        [
            'role' => ['clubpresident', 'president', 'vicepresident'],
            'prefix' => 'Member',
            'controller' => '*',
            'action' => '*',
        ],
        [
            'role' => ['clubpresident', 'president', 'vicepresident'],
            'prefix' => 'Clubpresident',
            'controller' => '*',
            'action' => '*',
        ],
        [
            'role' => ['president', 'vicepresident'],
            'prefix' => 'Clubpresident',
            'controller' => '*',
            'action' => '*',
        ],
        [
            'role' => ['president', 'vicepresident'],
            'prefix' => 'President',
            'controller' => '*',
            'action' => '*',
        ],
        [
            'role' => ['superuser', 'admin'],
            'prefix' => '*',
            'extension' => '*',
            'plugin' => '*',
            'controller' => '*',
            'action' => '*',
        ],
        [
            'role' => '*',
            'prefix' => 'Api',
            'extension' => '*',
            'plugin' => '*',
            'controller' => '*',
            'action' => '*',
        ],
    ],
];
