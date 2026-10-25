<?php

// -----------------------------------------------------------
//  [*] SignOutSwitchedOff — a switched-off account loses its
//      session
//
//  The logins check an account's status once, at sign-in,
//  and the guards load a signed-in user by id alone, so on
//  their own a member or manager switched off in the panel
//  would stay signed in until the session ran out. This
//  runs on every web request and puts the rows' current
//  state in force: an account switched off since its
//  sign-in is signed out, a deleted one's id leaves the
//  session, and a manager's main flag and country — which
//  Perms and the panel's pages read from the session —
//  follow the manager's row, so a main admin demoted to
//  country manager loses the main pages at the next
//  request.
//
//  Used by:
//    - Kernel::$middlewareGroups (app/Http/Kernel.php) — the
//      web group, so every page of the site and the panel
// -----------------------------------------------------------


namespace App\Http\Middleware;

use Closure;
use Session;
use Illuminate\Support\Facades\Auth;
use App\Models\Managers;
use App\Models\Members;








// -----------------------------------------------------------
// SignOutSwitchedOff
// -----------------------------------------------------------
//
// Reads the row of each signed-in guard afresh, one query
// each, rather than the user the guard keeps, so a change
// made in the panel a moment ago counts. Only status 1
// keeps a session, as only status 1 signs in — a number on
// the member side, the text '1' in bei_managers.
//
// Used by:
//   - Kernel::$middlewareGroups — the web group
// -----------------------------------------------------------

class SignOutSwitchedOff
{






    // -----------------------------------------------------------
    // handle
    // -----------------------------------------------------------
    //
    // Signs out a member whose bei_users row no longer has
    // status 1, or no longer exists, and a manager whose
    // bei_managers row is in the same state, the session's
    // main and country going with the manager. A row that is
    // gone leaves the guard loading no one, and its id is
    // dropped from the session: it once stayed there, and
    // would have signed in whoever was given that id next —
    // MySQL 5.7 hands the highest id out again after a
    // restart. An active manager's main and country are
    // copied into the session again from the row. Then the
    // request goes on: a page behind a guard sends the visitor
    // to its login form, any other page opens as for a
    // visitor.
    //
    // Used by:
    //   - Laravel's router, on every request of the web group
    // -----------------------------------------------------------

    public function handle($request, Closure $next)
    {
        // STEP 1: the member side
        // =======================
        if(Auth::guard('mem')->check()){

            $member = Members::select('status')->where(['id'=>Auth::guard('mem')->id()])->first();

            if($member == null || $member->status != 1){
                Auth::guard('mem')->logout();
            }

        } elseif(Session::has(Auth::guard('mem')->getName())){

            // The id of a member whose row is gone, which the
            // guard can no longer load
            Session::forget(Auth::guard('mem')->getName());
        }


        // STEP 2: the panel
        // =================
        if(Auth::guard('man')->check()){

            $manager = Managers::select('status', 'main', 'country')->where(['id'=>Auth::guard('man')->id()])->first();

            if($manager == null || $manager->status != 1){
                Auth::guard('man')->logout();
                Session::forget(['main', 'country']);
            } else {
                Session::put('main', $manager->main);
                Session::put('country', $manager->country);
            }

        } elseif(Session::has(Auth::guard('man')->getName())){

            // The id of a manager whose row is gone
            Session::forget([Auth::guard('man')->getName(), 'main', 'country']);
        }

        return $next($request);
    }
}
