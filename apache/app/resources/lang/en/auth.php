<?php

// -----------------------------------------------------------
//  [*] en/auth — Laravel's sign-in messages, in English
//
//  Laravel's stock auth file: failed for credentials that
//  match no account, throttle for too many attempts. Only
//  failed ever shows, as the error of a failed sign-in to the
//  panel — the member login answers with main.auth_failed
//  instead, and throttle belongs to ThrottlesLogins, which
//  neither login calls.
//
//  Used by:
//    - AdminLoginController::login — the panel's sign-in
//      error
//    - AdminLoginTest (tests/Feature/Admin/) — expects it
// -----------------------------------------------------------


return [

    'failed' => 'Credentials do not match our records.',
    'throttle' => 'Too many login attempts. Please try again in :seconds seconds.',

];
