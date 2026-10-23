<?php

// -----------------------------------------------------------
//  [*] NewsExists — 404 for an article that does not exist
//
//  Stands in front of the public news article page and
//  turns an article id that names no row of bei_news into a
//  404, so the page never runs on nothing.
//
//  Used by:
//    - Kernel::$routeMiddleware (app/Http/Kernel.php) — the
//      alias news.exists
// -----------------------------------------------------------


namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use App\Models\News as News;








// -----------------------------------------------------------
// NewsExists
// -----------------------------------------------------------
//
// Guards the article page: an id that is not a number, or
// names no article, is a 404. The slug is not looked at.
//
// Used by:
//   - Kernel::$routeMiddleware — the alias news.exists
//   - routes/web_user.php — GET /{lang}/news/{slug}/{uid}
// -----------------------------------------------------------

class NewsExists
{






    // -----------------------------------------------------------
    // handle
    // -----------------------------------------------------------
    //
    // Checks the uid of the URL: a number, and exactly one
    // bei_news row with that id; a 404 otherwise.
    //
    // Used by:
    //   - Laravel's router, on the route in the class banner
    // -----------------------------------------------------------

    public function handle($request, Closure $next)
    {
        $uid = $request->route('uid');

        if(is_numeric($uid) && News::where(['id'=>$uid])->count() == 1) {

            return $next($request);

        } else {

            abort(404);

        }
    }
}
