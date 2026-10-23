<?php

// -----------------------------------------------------------
//  [*] ValidateVerify — the verification-link check, unused
//
//  Built to stand in front of the account verification
//  link: only a token that belongs to exactly one member
//  would pass, anything else would go to the login page.
//  It is dead code — no alias in app/Http/Kernel.php names
//  it and no route uses it.
//
//  Used by:
//    - nothing at the moment
// -----------------------------------------------------------


namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use App\Models\Members as Members;








// -----------------------------------------------------------
// ValidateVerify
// -----------------------------------------------------------
//
// Would guard GET /verify/{verify_token}, which goes
// straight to RegisterController::verify. That action makes
// the same checks itself — a blank token, or one more than
// one member holds, switches nothing on and ends on the
// login page — so registering this would change nothing a
// visitor sees. Would refuse with a redirect to the login
// page.
//
// Used by:
//   - nothing calls this at the moment — never registered
// -----------------------------------------------------------

class ValidateVerify
{






    // -----------------------------------------------------------
    // handle
    // -----------------------------------------------------------
    //
    // Checks the token of the URL: exactly one bei_users row
    // whose verify_token equals it, the empty and NULL tokens
    // left out; anything else is sent to the login page.
    //
    // Used by:
    //   - nothing calls this at the moment
    // -----------------------------------------------------------

    public function handle($request, Closure $next)
    {
        $verify_token = $request->route('verify_token');

        if(Members::where(['verify_token'=>$verify_token])->where('verify_token', '<>', '')->whereNotNull('verify_token')->count() == 1) {

            return $next($request);

        } else {

            return redirect(route('login'));

        }
    }
}
