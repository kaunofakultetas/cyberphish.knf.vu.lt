<?php

// -----------------------------------------------------------
//  [*] TrimStrings — trims the blanks off every input
//
//  Laravel's input trimming: every string a request carries
//  loses its leading and trailing blanks before a
//  controller sees it, except the fields listed here.
//
//  Used by:
//    - Kernel::$middleware (app/Http/Kernel.php) — every
//      request
// -----------------------------------------------------------


namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\TrimStrings as Middleware;








// -----------------------------------------------------------
// TrimStrings
// -----------------------------------------------------------
//
// Trims every input but password and password_confirmation,
// so that a password keeps the blanks it was typed with.
//
// Known bug (low): the list holds Laravel's field names
// (TrimStrings.php:53), but the app's forms also post
// passwords as password2, newpass, renewpass, pass and
// repass. Those are trimmed while password is not, so a
// password with a blank at either end never matches its
// repeat on the registration form, and one set on a
// change-password form or the manager form is saved
// without the blank and then refused at the login, where
// password arrives as typed. Fix: add those names.
// Pinned as password-trim.
//
// Used by:
//   - Kernel::$middleware — the global stack, every request
// -----------------------------------------------------------

class TrimStrings extends Middleware
{

    // The inputs left as typed
    protected $except = [
        'password',
        'password_confirmation',
    ];
}
