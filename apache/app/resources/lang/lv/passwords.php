<?php

// -----------------------------------------------------------
//  [*] lv/passwords — Laravel's reset messages, in Latvian
//
//  Laravel's stock password-reset file, translated into
//  Latvian: what the password broker answers to a reset
//  request and to a reset. The site never uses the broker:
//  ForgotPasswordController and ResetPasswordController,
//  which would, have no route, and the site's own reset in
//  LoginController answers with main.php's strings.
//
//  Used by:
//    - nothing at the moment — no route reaches the broker
// -----------------------------------------------------------


return [

    'reset' => 'Jūsu parole ir atiestatīta!',
    'sent' => 'Mēs nosūtījām e-pastā jūsu paroles atiestatīšanas saiti! ',
    'throttled' => 'Lūdzu, uzgaidiet, pirms mēģināt vēlreiz.',
    'token' => 'Šī paroles atiestatīšanas pilnvara nav derīga. ',
    'user' => "Mēs nevaram atrast lietotāju ar šo e-pasta adresi. ",

];
