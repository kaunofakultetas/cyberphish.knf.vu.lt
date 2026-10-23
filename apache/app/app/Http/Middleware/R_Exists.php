<?php

// -----------------------------------------------------------
//  [*] R_Exists — 404 for a missing row, by kind
//
//  One middleware for five existence checks, picked by the
//  parameter after the alias: a learning-material category,
//  a member's self-evaluation attempt, a simulation
//  category, a simulation, a member's simulation run. A
//  failed check is a 404, so the page never runs on
//  nothing.
//
//  Used by:
//    - Kernel::$routeMiddleware (app/Http/Kernel.php) — the
//      alias r_exists
// -----------------------------------------------------------


namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use App\Models\Members;
use App\Models\SelfEvalUserTest;
use App\Models\SelfEval;
use App\Models\SelfEvalOption;
use App\Models\SelfEvalUser;
use App\Models\LMContent;
use App\Models\Langs;
use App\Models\LMCategory;
use App\Models\LMFiles;
use App\Models\LMUsers;
use App\Models\Scenarios;
use App\Models\ScenariosAttributes;
use App\Models\ScenariosCategory;
use App\Models\ScenariosInAttributes;
use App\Models\ScenariosInCategory;
use App\Models\ScenariosOptions;
use App\Models\ScenariosUsers;
use App\Models\ScenariosUsersOptions;








// -----------------------------------------------------------
// R_Exists
// -----------------------------------------------------------
//
// Guards the member pages that take an id from the URL.
// The kind after the alias picks the check, and a failed
// check is a 404:
//
//   lm_cat               — the learning-material category
//                          in cat_id exists
//   se_public_unfinished — the member has an attempt with
//                          that public_id, finished or
//                          not, whatever the name says
//   sim_cat              — the simulation category in uid
//                          exists
//   sim_id               — the simulation in uid exists
//   sim_public_id        — the member has exactly one run
//                          with that public_id
//
// Used by:
//   - Kernel::$routeMiddleware — the alias r_exists
//   - routes/web_user.php — the routes below, by kind
//   - lm_cat — GET /{lang}/self-evaluation-test/{cat_id},
//     GET /cp/se/{public_id}/{cat_id}
//   - se_public_unfinished — GET /cp/se_r/{public_id}
//   - sim_cat — GET /{lang}/simulations/{name}/{uid}
//   - sim_id — GET /{lang}/simulation/{uid},
//     POST /{lang}/simulation_progress/{uid}
//   - sim_public_id — GET and POST
//     /{lang}/simulation/progress/{public_id}
// -----------------------------------------------------------

class R_Exists
{






    // -----------------------------------------------------------
    // handle
    // -----------------------------------------------------------
    //
    // Runs the check the kind names — one count over the
    // table, by the id the URL carries, and for the two
    // public ids by the signed-in member too — and lets the
    // request through when it finds the row, 404 otherwise.
    // A kind outside the five falls through every branch and
    // returns no response at all; every route names one of
    // the five. sim_id finds a simulation of any language;
    // SimulationsController::simulation_start answers one of
    // another language with its own 404.
    //
    // Used by:
    //   - Laravel's router, on the routes in the class banner
    // -----------------------------------------------------------

    public function handle($request, Closure $next, $type)
    {
        if($type =='lm_cat'){

            $uid = $request->route('cat_id');

            if(LMCategory::where(['id'=>$uid])->count() == 1) {

                return $next($request);

            } else {
                abort(404);
            }

        }

        if($type =='se_public_unfinished'){

            $uid = $request->route('public_id');

            if(SelfEvalUserTest::where(['public_id'=>$uid, 'user_id'=>Auth::guard('mem')->id()])->count() > 0) {

                return $next($request);

            } else {
                abort(404);
            }

        }

        if($type =='sim_cat'){

            $uid = $request->route('uid');

            if(ScenariosCategory::where(['id'=>$uid])->count() == 1) {

                return $next($request);

            } else {
                abort(404);
            }

        }

        if($type =='sim_id'){

            $uid = $request->route('uid');

            if(Scenarios::where(['id'=>$uid])->count() == 1) {

                return $next($request);

            } else {
                abort(404);
            }

        }

        if($type =='sim_public_id'){

            $uid = $request->route('public_id');

            if(ScenariosUsers::where(['public_id'=>$uid, 'user_id'=>Auth::guard('mem')->id()])->count() == 1) {

                return $next($request);

            } else {
                abort(404);
            }

        }
    }
}
