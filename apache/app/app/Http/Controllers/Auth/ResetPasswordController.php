<?php

// -----------------------------------------------------------
//  [*] ResetPasswordController — Laravel's stub, unused
//
//  The password-reset controller Laravel's auth scaffolding
//  generates: the form behind a reset link, where the user
//  types a new password. This app resets passwords its own
//  way — LoginController::reset_pass mails a new random one
//  — and never routed this one: neither routes file calls
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
use App\Providers\RouteServiceProvider;
use Illuminate\Foundation\Auth\ResetsPasswords;








// -----------------------------------------------------------
// ResetPasswordController
// -----------------------------------------------------------
//
// The ResetsPasswords trait holds every action — the form
// (auth/passwords/reset.blade.php), the reset, signing the
// user in; the class only says where to go afterwards.
//
// Used by:
//   - nothing — no route reaches it and nothing calls it at
//     the moment
// -----------------------------------------------------------

class ResetPasswordController extends Controller
{

    // Laravel's password-reset actions
    use ResetsPasswords;

    // Where the trait sends the user after a reset:
    // RouteServiceProvider::HOME, the home page
    protected $redirectTo = RouteServiceProvider::HOME;
}
