<?php

// -----------------------------------------------------------
//  [*] gr/auth — Laravel's sign-in messages, in Greek
//
//  Laravel's stock auth file, translated into Greek (gr is
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

    'failed' => 'Τα διαπιστευτήρια δεν ταιριάζουν με τα αρχεία μας. ',
    'throttle' => 'Πάρα πολλές προσπάθειες σύνδεσης. Παρακαλώ δοκιμάστε ξανά αργότερα. ',

];
