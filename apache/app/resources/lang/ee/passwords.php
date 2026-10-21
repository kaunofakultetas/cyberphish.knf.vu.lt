<?php

// -----------------------------------------------------------
//  [*] ee/passwords — Laravel's reset messages, in Estonian
//
//  Laravel's stock password-reset file, translated into
//  Estonian (ee is the site's code for it): what the password
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

    'reset' => 'Teie parool on lähtestatud',
    'sent' => 'Saatsime teie parooli lähtestamise lingi meili teel',
    'throttled' => 'Oodake enne uuesti proovimist!',
    'token' => 'See parooli lähtestamise luba on kehtetu.',
    'user' => 'Me ei leia selle e-posti aadressiga kasutajat.',

];
