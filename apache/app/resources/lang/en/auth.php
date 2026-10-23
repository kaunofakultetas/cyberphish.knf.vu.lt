<?php

// -----------------------------------------------------------
//  [*] en/auth — Laravel's sign-in messages, in English
//
//  Laravel's stock auth file: failed for credentials that
//  match no account, throttle for too many attempts. failed
//  shows as the error of a failed sign-in to the panel — the
//  member login answers with main.auth_failed instead — and
//  throttle once ThrottlesLogins locks either login: always
//  on the panel, which runs in English whatever the
//  session's language, and on the member login of an
//  English session.
//
//  Used by:
//    - AdminLoginController::login — the panel's sign-in
//      error and its lockout
//    - LoginController::login — its lockout
//    - AdminLoginTest (tests/Feature/Admin/) — expects failed
// -----------------------------------------------------------


return [

    'failed' => 'Credentials do not match our records.',
    'throttle' => 'Too many login attempts. Please try again in :seconds seconds.',

];
