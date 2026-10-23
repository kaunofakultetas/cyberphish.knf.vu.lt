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
// Trims every input but the password fields, so that a
// password keeps the blanks it was typed with — the same
// in every form, or one with a blank at either end would
// never match its repeat, or would be saved without the
// blank and refused at the login.
//
// Used by:
//   - Kernel::$middleware — the global stack, every request
// -----------------------------------------------------------

class TrimStrings extends Middleware
{

    // The inputs left as typed: Laravel's password fields and
    // the app's — the sign-up form's repeat, the two
    // change-password forms' new password and its repeat, the
    // manager form's pair
    protected $except = [
        'password',
        'password_confirmation',
        'password2',
        'newpass',
        'renewpass',
        'pass',
        'repass',
    ];
}
