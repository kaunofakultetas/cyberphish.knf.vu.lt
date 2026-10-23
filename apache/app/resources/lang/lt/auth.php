<?php

// -----------------------------------------------------------
//  [*] lt/auth — Laravel's sign-in messages, in Lithuanian
//
//  Laravel's stock auth file, translated into Lithuanian:
//  failed for credentials that match no account, throttle for
//  too many attempts. failed never shows — its one reader,
//  the panel's sign-in, runs in English whatever the
//  session's language — but throttle does, once
//  ThrottlesLogins locks the member login of a Lithuanian
//  session.
//
//  Used by:
//    - LoginController::login — its lockout
// -----------------------------------------------------------


return [

    'failed' => 'Neteisingi prisijungimo duomenys',
    'throttle' => 'Palaukite :seconds sekundžių prieš kitą bandymą.',

];
