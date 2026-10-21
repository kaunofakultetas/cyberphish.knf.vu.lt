<?php

// -----------------------------------------------------------
//  [*] config/hashing.php — how passwords are hashed
//
//  Laravel 7's stock hashing file, unchanged: bcrypt at a
//  cost of 10, since BCRYPT_ROUNDS is not set in
//  docker-compose.yml (the in-process tests lower it to 4
//  through phpunit.xml). A stored hash carries its own
//  algorithm and cost, so a change here applies to new
//  hashes only and existing passwords keep working.
//
//  Used by:
//    - Laravel's HashManager — the password check of both
//      logins (the guards of config/auth.php), and Hash in
//      RegisterController, LoginController,
//      ManagerMainController and the admin panel's
//      ManagerMainController and ManagersController
// -----------------------------------------------------------


return [

    // The algorithm of new hashes; Laravel supports bcrypt,
    // argon and argon2id
    'driver' => 'bcrypt',

    // The bcrypt cost, from BCRYPT_ROUNDS — not set, so 10;
    // each step up doubles the time a hash takes
    'bcrypt' => [
        'rounds' => env('BCRYPT_ROUNDS', 10),
    ],

    // The Argon settings — memory in kibibytes — used only
    // if the driver were argon
    'argon' => [
        'memory' => 1024,
        'threads' => 2,
        'time' => 2,
    ],

];
