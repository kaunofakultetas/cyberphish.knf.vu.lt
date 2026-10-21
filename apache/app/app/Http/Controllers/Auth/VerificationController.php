<?php

// -----------------------------------------------------------
//  [*] VerificationController — Laravel's stub, unused
//
//  The email-verification controller Laravel's auth
//  scaffolding generates: the "check your mail" page, the
//  signed link that marks an address verified, and a resend
//  button. This app verifies members its own way —
//  RegisterController::verify, with the token mailed at
//  sign-up — and never routed this one: neither routes file
//  calls Auth::routes or names its actions.
//
//  Used by:
//    - nothing — no route reaches it and nothing calls it
//      at the moment
// -----------------------------------------------------------


namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Providers\RouteServiceProvider;
use Illuminate\Foundation\Auth\VerifiesEmails;








// -----------------------------------------------------------
// VerificationController
// -----------------------------------------------------------
//
// The VerifiesEmails trait holds every action — the notice
// page (auth/verify.blade.php), the verification, the
// resend; the class says where to go afterwards and puts
// the middleware in front.
//
// Used by:
//   - nothing — no route reaches it and nothing calls it at
//     the moment
// -----------------------------------------------------------

class VerificationController extends Controller
{

    // Laravel's email-verification actions
    use VerifiesEmails;

    // Where the trait sends the user after verifying:
    // RouteServiceProvider::HOME, the home page
    protected $redirectTo = RouteServiceProvider::HOME;






    // -----------------------------------------------------------
    // __construct
    // -----------------------------------------------------------
    //
    // The auth middleware on every action — with no guard
    // named it checks the default guard, man — a valid
    // signature on the verification link, and at most six
    // verify or resend requests a minute.
    //
    // Used by:
    //   - nothing — no route reaches it and nothing calls it at
    //     the moment
    // -----------------------------------------------------------

    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('signed')->only('verify');
        $this->middleware('throttle:6,1')->only('verify', 'resend');
    }
}
