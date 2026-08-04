<?php
declare(strict_types=1);

/**
 * Application roles (CakeDC Users later — same string keys).
 *
 * Labels: App\Auth\AppRoles::label() → __() msgid (see default.po / .pot).
 */
return [
    'AppRoles' => [
        /**
         * Fallback role when Authentication / CakeDC is not installed.
         * Debug: typically superuser so Admin Setups stays usable.
         * Non-debug: guest-like role with no Setups access (`new`).
         */
        'devRole' => env('APP_DEV_ROLE', null),
    ],
];
