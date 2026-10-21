<?php

// -----------------------------------------------------------
//  [*] BaseController — the base of the panel's controllers
//
//  Every controller of the admin panel extends this class
//  and calls its constructor, which puts two middleware in
//  front of each of their actions: the man guard's sign-in
//  check, and a closure that hands the session's main and
//  country flags to every view. Not to be confused with
//  App\Http\Controllers\BaseController, the base of the
//  member pages.
//
//  Used by:
//    - the eleven controllers of App\Http\Controllers\Admin,
//      which all extend it
// -----------------------------------------------------------


namespace App\Http\Controllers\Admin;

use Session;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use View;








// -----------------------------------------------------------
// BaseController
// -----------------------------------------------------------
//
// Holds only the shared constructor. Each admin controller
// calls it from its own constructor with
// parent::__construct(), so the two middleware run in front
// of every action of the panel; Controller brings Laravel's
// validation and authorization helpers.
//
// Used by:
//   - InfoController, KnowledgeController,
//     LanguagesController, LMController,
//     ManagerMainController, ManagersController,
//     NewsController, SelfevalController,
//     SimulationsController, SiteSettingsController,
//     UsersController (Admin/)
// -----------------------------------------------------------

class BaseController extends Controller
{






    // -----------------------------------------------------------
    // __construct
    // -----------------------------------------------------------
    //
    // Registers two middleware for every action of the
    // controller being built. auth:man repeats the man-guard
    // check every admin route already makes with
    // auth.admin:man; the route's middleware answers first —
    // a visitor lands on the admin login — so this one never
    // refuses anyone today (it would send a visitor to the
    // member login instead). The closure shares the session's
    // main flag (1 for a main admin) and country id with
    // every view as $main and $country. AdminLoginController::
    // login writes both at sign-in, so a change to the
    // manager's row shows only after the next sign-in. The
    // sidebar and the managers pages read $main; no view
    // reads $country at the moment.
    //
    // Used by:
    //   - the constructors of the Admin controllers, through
    //     parent::__construct()
    // -----------------------------------------------------------

    public function __construct()
    {
        $this->middleware('auth:man');

        $this->middleware(function ($request, $next) {
            View::share('main', Session::get('main'));
            View::share('country', Session::get('country'));

            return $next($request);
        });
    }
}
