<?php

// -----------------------------------------------------------
//  [*] ForgotPasswordController — Laravel's stub, unused
//
//  The reset-link controller Laravel's auth scaffolding
//  generates, which mails a reset link through Laravel's
//  password broker. This app does that its own way, in
//  LoginController (forgotpassword and reset_pass), and
//  never routed this one — neither routes file calls
//  Auth::routes or names its actions. Were it routed, it
//  would fail anyway: config/auth.php names a managers
//  password broker but defines none.
//
//  Used by:
//    - nothing — no route reaches it and nothing calls it
//      at the moment
// -----------------------------------------------------------


namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\SendsPasswordResetEmails;








// -----------------------------------------------------------
// ForgotPasswordController
// -----------------------------------------------------------
//
// The SendsPasswordResetEmails trait holds every action —
// the form (auth/passwords/email.blade.php) and the mail;
// the class adds nothing to it.
//
// Used by:
//   - nothing — no route reaches it and nothing calls it at
//     the moment
// -----------------------------------------------------------

class ForgotPasswordController extends Controller
{

    // Laravel's reset-link actions
    use SendsPasswordResetEmails;
}
