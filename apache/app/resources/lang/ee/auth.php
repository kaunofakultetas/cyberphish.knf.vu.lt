<?php

// -----------------------------------------------------------
//  [*] ee/auth — Laravel's sign-in messages, in Estonian
//
//  Laravel's stock auth file, translated into Estonian (ee is
//  the site's code for it): failed for credentials that match
//  no account, throttle for too many attempts. failed never
//  shows — its one reader, the panel's sign-in, runs in
//  English whatever the session's language — but throttle
//  does, once ThrottlesLogins locks the member login of an
//  Estonian session.
//
//  Used by:
//    - LoginController::login — its lockout
// -----------------------------------------------------------


return [

    'failed' => 'Mandaat ei ühti meie andmetega.',
    'throttle' => 'Liiga palju sisselogimiskatseid. Proovige uuesti :seconds sekundi pärast.',

];
