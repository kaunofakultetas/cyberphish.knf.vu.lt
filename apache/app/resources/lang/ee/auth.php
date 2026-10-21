<?php

// -----------------------------------------------------------
//  [*] ee/auth — Laravel's sign-in messages, in Estonian
//
//  Laravel's stock auth file, translated into Estonian (ee is
//  the site's code for it): failed for credentials that match
//  no account, throttle for too many attempts. Neither ever
//  shows: the one reader of failed, the panel's sign-in, runs
//  in English whatever the session's language, and throttle
//  belongs to ThrottlesLogins, which neither login calls.
//
//  Used by:
//    - nothing at the moment — the panel reads en/auth.php
// -----------------------------------------------------------


return [

    'failed' => 'Mandaat ei ühti meie andmetega.',
    'throttle' => 'Liiga palju sisselogimiskatseid. Proovige uuesti :seconds sekundi pärast.',

];
