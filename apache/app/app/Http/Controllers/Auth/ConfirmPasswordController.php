<?php

// -----------------------------------------------------------
//  [*] ConfirmPasswordController — Laravel's stub, unused
//
//  The password-confirmation controller Laravel's auth
//  scaffolding generates: a form that asks a signed-in user
//  for the password again before a sensitive page. This app
//  never wired it up — neither routes file calls
//  Auth::routes or names its actions — so no route reaches
//  it. Were it routed, its auth middleware would check the
//  default guard, man (the managers), and its view,
//  auth/passwords/confirm.blade.php, extends a layouts.app
//  that does not exist.
//
//  Used by:
//    - nothing — no route reaches it and nothing calls it
//      at the moment
// -----------------------------------------------------------


namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Providers\RouteServiceProvider;
use Illuminate\Foundation\Auth\ConfirmsPasswords;








// -----------------------------------------------------------
// ConfirmPasswordController
// -----------------------------------------------------------
//
// The ConfirmsPasswords trait does the work — the form, the
// password check, the redirect afterwards; the class only
// says where to go and puts the auth middleware in front.
//
// Used by:
//   - nothing — no route reaches it and nothing calls it at
//     the moment
// -----------------------------------------------------------

class ConfirmPasswordController extends Controller
{

    // Laravel's password-confirmation actions
    use ConfirmsPasswords;

    // Where the trait sends the user after the check when no
    // intended page waits: RouteServiceProvider::HOME, the
    // home page
    protected $redirectTo = RouteServiceProvider::HOME;






    // -----------------------------------------------------------
    // __construct
    // -----------------------------------------------------------
    //
    // Puts the auth middleware in front of every action; with
    // no guard named it checks the default guard, man.
    //
    // Used by:
    //   - nothing — no route reaches it and nothing calls it at
    //     the moment
    // -----------------------------------------------------------

    public function __construct()
    {
        $this->middleware('auth');
    }
}
