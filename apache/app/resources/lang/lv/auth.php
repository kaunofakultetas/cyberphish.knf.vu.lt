<?php

// -----------------------------------------------------------
//  [*] lv/auth — Laravel's sign-in messages, in Latvian
//
//  Laravel's stock auth file, translated into Latvian: failed
//  for credentials that match no account, throttle for too
//  many attempts. failed never shows — its one reader, the
//  panel's sign-in, runs in English whatever the session's
//  language — but throttle does, once ThrottlesLogins locks
//  the member login of a Latvian session.
//
//  Used by:
//    - LoginController::login — its lockout
// -----------------------------------------------------------


return [

    'failed' => 'Akreditācijas dati neatbilst mūsu ierakstiem.',
    'throttle' => 'Pārāk daudz pieteikšanās mēģinājumu. Lūdzu, mēģiniet vēlreiz pēc :seconds sekundēm.',

];
