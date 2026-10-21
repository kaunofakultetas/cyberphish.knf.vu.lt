<?php

// -----------------------------------------------------------
//  [*] en/passwords — Laravel's reset messages, in English
//
//  Laravel's stock password-reset file: what the password
//  broker answers to a reset request and to a reset. The site
//  never uses the broker: ForgotPasswordController and
//  ResetPasswordController, which would, have no route, and
//  the site's own reset in LoginController answers with
//  main.php's strings.
//
//  Used by:
//    - nothing at the moment — no route reaches the broker
// -----------------------------------------------------------


return [

    'reset' => 'Your password has been reset!',
    'sent' => 'We have emailed your password reset link!',
    'throttled' => 'Please wait before retrying.',
    'token' => 'This password reset token is invalid.',
    'user' => "We can't find a user with that email address.",

];
