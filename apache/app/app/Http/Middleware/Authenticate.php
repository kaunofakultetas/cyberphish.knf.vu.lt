<?php

// -----------------------------------------------------------
//  [*] Authenticate — the sign-in check of the auth alias
//
//  Laravel's Authenticate middleware, told where to send a
//  visitor who is not signed in: the member login page.
//  Routes name it with the guard to check — auth:mem on
//  the member pages.
//
//  Used by:
//    - Kernel::$routeMiddleware (app/Http/Kernel.php) — the
//      alias auth
// -----------------------------------------------------------


namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;








// -----------------------------------------------------------
// Authenticate
// -----------------------------------------------------------
//
// Guards the pages of a signed-in user: a request passes
// when the guard the route names has a user signed in.
// Otherwise Laravel redirects a browser to the member login
// page, remembering the URL it asked for so the login can
// return there, and answers a JSON request with 401.
//
// Used by:
//   - Kernel::$routeMiddleware — the alias auth
//   - routes/web_user.php — auth:mem on every member page:
//     the dashboard and the account changes, the learning
//     material list and lesson completion, self-evaluations,
//     simulations, badges, the knowledge test and the
//     certificate
//   - Admin\BaseController — auth:man on every action of
//     the panel; the routes' auth.admin:man answers first,
//     so it never refuses anyone today
//   - Auth\ConfirmPasswordController,
//     Auth\VerificationController — a bare auth, on
//     scaffold controllers no route reaches
// -----------------------------------------------------------

class Authenticate extends Middleware
{






    // -----------------------------------------------------------
    // redirectTo
    // -----------------------------------------------------------
    //
    // Where a refused request goes: the member login page
    // (the route named login) for a browser, nowhere for a
    // JSON request, which Laravel then answers with 401.
    //
    // Used by:
    //   - Laravel's Authenticate middleware, when it refuses a
    //     request
    // -----------------------------------------------------------

    protected function redirectTo($request)
    {
        if (! $request->expectsJson()) {
            return route('login');
        }
    }
}
