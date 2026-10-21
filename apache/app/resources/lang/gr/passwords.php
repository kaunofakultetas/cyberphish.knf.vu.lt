<?php

// -----------------------------------------------------------
//  [*] gr/passwords — Laravel's reset messages, in Greek
//
//  Laravel's stock password-reset file, translated into Greek
//  (gr is the site's code for it): what the password broker
//  answers to a reset request and to a reset. The site never
//  uses the broker: ForgotPasswordController and
//  ResetPasswordController, which would, have no route, and
//  the site's own reset in LoginController answers with
//  main.php's strings.
//
//  Used by:
//    - nothing at the moment — no route reaches the broker
// -----------------------------------------------------------


return [

    'reset' => 'Ο κωδικός πρόσβασής σας επαναφέρθηκε! ',
    'sent' => 'Στείλαμε με email τον σύνδεσμο επαναφοράς του κωδικού πρόσβασής σας!',
    'throttled' => 'Περιμένετε πριν προσπαθήσετε ξανά.',
    'token' => 'Αυτό το διακριτικό επαναφοράς κωδικού πρόσβασης δεν είναι έγκυρο.',
    'user' => "Δεν μπορούμε να βρούμε χρήστη με αυτήν τη διεύθυνση ηλεκτρονικού ταχυδρομείου.",

];
