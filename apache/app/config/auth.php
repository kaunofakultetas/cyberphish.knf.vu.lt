<?php

// -----------------------------------------------------------
//  [*] config/auth.php — the two sign-in guards
//
//  Who can sign in, and from which table. Laravel's stock
//  web and api guards over an Eloquent User model are
//  replaced by two session guards on the database
//  provider: man for the managers of the admin panel
//  (bei_managers) and mem for the members (bei_users).
//  With the database provider a signed-in user is a
//  GenericUser holding the table row, never a Managers or
//  Members model. The stock passwords section and
//  password_timeout are gone too. No environment variable
//  feeds this file.
//
//  Used by:
//    - Laravel's AuthManager — the Auth facade in the
//      controllers and views, the logins of LoginController
//      and AdminLoginController, and the auth, auth.admin
//      and guest middleware (auth:mem on the member pages,
//      auth.admin:man on the admin panel)
// -----------------------------------------------------------


return [

    // man is the default guard, so a bare Auth call — the
    // admin panel's code — means the managers, while the
    // member side names the mem guard. The managers password
    // broker is defined nowhere, as the passwords section is
    // gone; only Laravel's own reset controllers would ask
    // for it, and no route reaches them — the app resets
    // passwords its own way (LoginController)
    'defaults' => [
        'guard' => 'man',
        'passwords' => 'managers',
    ],

    // Both guards keep the signed-in user's id in the
    // session: man for the managers, mem for the members.
    // Laravel supports the session and token drivers
    'guards' => [
        'man' => [
            'driver' => 'session',
            'provider' => 'managers',
        ],
        'mem' => [
            'driver' => 'session',
            'provider' => 'members',
        ]
    ],

    // Both providers query their table directly, with no
    // model, and check passwords with the hasher
    // (config/hashing.php)
    'providers' => [
        'managers' => [
            'driver' => 'database',
            'table' => 'bei_managers',
        ],
        'members' => [
            'driver' => 'database',
            'table' => 'bei_users',
        ]
    ],

];
