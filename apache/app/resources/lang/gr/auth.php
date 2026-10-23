<?php

// -----------------------------------------------------------
//  [*] gr/auth — Laravel's sign-in messages, in Greek
//
//  Laravel's stock auth file, translated into Greek (gr is
//  the site's code for it): failed for credentials that match
//  no account, throttle for too many attempts. failed never
//  shows — its one reader, the panel's sign-in, runs in
//  English whatever the session's language — but throttle
//  does, once ThrottlesLogins locks the member login of a
//  Greek session.
//
//  Used by:
//    - LoginController::login — its lockout
// -----------------------------------------------------------


return [

    'failed' => 'Τα διαπιστευτήρια δεν ταιριάζουν με τα αρχεία μας. ',
    'throttle' => 'Πάρα πολλές προσπάθειες σύνδεσης. Παρακαλώ δοκιμάστε ξανά αργότερα. ',

];
