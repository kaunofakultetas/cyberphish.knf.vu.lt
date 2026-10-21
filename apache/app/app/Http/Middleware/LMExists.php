<?php

// -----------------------------------------------------------
//  [*] LMExists — 404 for a lesson that does not exist
//
//  Stands in front of the lesson page and turns a lesson id
//  that names no row of bei_lm_content into a 404, so the
//  page never runs on nothing.
//
//  Used by:
//    - Kernel::$routeMiddleware (app/Http/Kernel.php) — the
//      alias lm.exists
// -----------------------------------------------------------


namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use App\Models\LMContent as LMContent;








// -----------------------------------------------------------
// LMExists
// -----------------------------------------------------------
//
// Guards the lesson page: an id that is not a number, or
// names no lesson, is a 404. The slug is not looked at.
//
// Used by:
//   - Kernel::$routeMiddleware — the alias lm.exists
//   - routes/web_user.php — GET /{lang}/learn/{slug}/{uid}
// -----------------------------------------------------------

class LMExists
{






    // -----------------------------------------------------------
    // handle
    // -----------------------------------------------------------
    //
    // Checks the uid of the URL: a number, and exactly one
    // bei_lm_content row with that id; a 404 otherwise.
    //
    // Used by:
    //   - Laravel's router, on the route in the class banner
    // -----------------------------------------------------------

    public function handle($request, Closure $next)
    {
        $uid = $request->route('uid');

        if(is_numeric($uid) && LMContent::where(['id'=>$uid])->count() == 1) {

            return $next($request);

        } else {

            abort(404);

        }
    }
}
