<?php

// -----------------------------------------------------------
//  [*] lt/passwords — Laravel's reset messages, in Lithuanian
//
//  Laravel's stock password-reset file, translated into
//  Lithuanian: what the password broker answers to a reset
//  request and to a reset. The site never uses the broker:
//  ForgotPasswordController and ResetPasswordController,
//  which would, have no route, and the site's own reset in
//  LoginController answers with main.php's strings.
//
//  Used by:
//    - nothing at the moment — no route reaches the broker
// -----------------------------------------------------------


return [

    'reset' => 'Slaptažodis pakeistas!',
    'sent' => 'Naujo slaptažodžio nuorodą išsiųsta!',
    'throttled' => 'Palaukite prieš kitą bandymą.',
    'token' => 'Tokenas blogas.',
    'user' => ".",

];
