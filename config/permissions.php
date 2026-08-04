<?php
declare(strict_types=1);

/**
 * CakeDC Auth RBAC permissions.
 *
 * IMPORTANT: App `Users.controller = Users` → request `plugin` is null.
 * Do NOT set `'plugin' => false` (false !== null in CakeDC Rbac matcher).
 *
 * Role panels: each role may enter only its own prefix (Registration `new` → New only).
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
            'action' => ['profile', 'logout', 'linkSocial', 'callbackLinkSocial', 'changePassword'],
        ],
        // Role → own panel only
        [
            'role' => 'new',
            'prefix' => 'New',
            'controller' => '*',
            'action' => '*',
        ],
        [
            'role' => ['member', 'editor'],
            'prefix' => 'Member',
            'controller' => '*',
            'action' => '*',
        ],
        [
            'role' => 'clubpresident',
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
        // Admin panel + Setups module access still gated by SetupAccess
        [
            'role' => ['superuser', 'admin'],
            'prefix' => 'Admin',
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
    ],
];
