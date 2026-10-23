<?php

// -----------------------------------------------------------
//  [*] ValidateReset — 404 for a dead password-reset link
//
//  Stands in front of the reset link the forgot-password
//  mail carries. Only a token that belongs to exactly one
//  member passes; a used, unknown or blank one is a 404, so
//  the reset never runs on nothing.
//
//  Used by:
//    - Kernel::$routeMiddleware (app/Http/Kernel.php) — the
//      alias validate.reset
// -----------------------------------------------------------


namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use App\Models\Members as Members;








// -----------------------------------------------------------
// ValidateReset
// -----------------------------------------------------------
//
// Guards the reset link: its token must be the
// reset_token of exactly one member, and not an empty
// one. The reset empties the column, and so does every
// sign-in of the member, so a link works once and dies at
// the next login. Refuses with a 404.
//
// Used by:
//   - Kernel::$routeMiddleware — the alias validate.reset
//   - routes/web_user.php — GET /reset/{reset_token}
// -----------------------------------------------------------

class ValidateReset
{






    // -----------------------------------------------------------
    // handle
    // -----------------------------------------------------------
    //
    // Checks the token of the URL: exactly one bei_users row
    // whose reset_token equals it, the empty tokens of the
    // members with no reset pending left out. Leaving them
    // out also stops a token of blanks, which MySQL would
    // find equal to an empty one, since it ignores trailing
    // spaces when it compares.
    //
    // Used by:
    //   - Laravel's router, on the route in the class banner
    // -----------------------------------------------------------

    public function handle($request, Closure $next)
    {
        $reset_token = $request->route('reset_token');

        if(Members::where(['reset_token'=>$reset_token])->where('reset_token', '<>', '')->count() == 1) {

            return $next($request);

        } else {
            abort(404);
        }
    }
}
