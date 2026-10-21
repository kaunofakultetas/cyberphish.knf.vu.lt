<?php

// -----------------------------------------------------------
//  [*] lv/auth — Laravel's sign-in messages, in Latvian
//
//  Laravel's stock auth file, translated into Latvian: failed
//  for credentials that match no account, throttle for too
//  many attempts. Neither ever shows: the one reader of
//  failed, the panel's sign-in, runs in English whatever the
//  session's language, and throttle belongs to
//  ThrottlesLogins, which neither login calls.
//
//  Used by:
//    - nothing at the moment — the panel reads en/auth.php
// -----------------------------------------------------------


return [

    'failed' => 'Akreditācijas dati neatbilst mūsu ierakstiem.',
    'throttle' => 'Pārāk daudz pieteikšanās mēģinājumu. Lūdzu, mēģiniet vēlreiz pēc :seconds sekundēm.',

];
