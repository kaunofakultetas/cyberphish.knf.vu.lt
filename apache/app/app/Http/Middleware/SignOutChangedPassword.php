<?php

// -----------------------------------------------------------
//  [*] SignOutChangedPassword — a new password ends the
//      other sessions
//
//  Laravel's sessions on their own carry on with the old
//  password until they run out. This signs a member or
//  manager out of every other session at its next request
//  once the password changes: in the account settings or the
//  panel's password dialog, through the reset link, or in a
//  main admin's manager dialog. Each session keeps a mark of
//  the password its guard signed in with, and every web
//  request holds the mark against the password the row has
//  now. The mark is set as the guard signs in, from the hash
//  the login checked the password against, and moves on only
//  when this session changes its own password, to the hash it
//  wrote, so the person who made the change stays signed in.
//  It is never read back from the row after a request, a
//  login or a change: a session with a request or a login
//  under way as the password changed elsewhere would adopt
//  the new one, and an intruder who kept requesting pages or
//  signing in would survive the change. A login that rewrites
//  a hash of an older cost is the one exception, and takes
//  the hash it wrote; other sessions holding the old-cost
//  hash are then signed out, once.
//
//  It stands in for Laravel's AuthenticateSession, which
//  checks only the request's default guard and flushes the
//  whole session, so a member's sign-out would take a
//  manager signed in on the same browser along. That one
//  also reads the password from the user the guard keeps,
//  which the app's updates and the login's rehash leave
//  stale.
//
//  Used by:
//    - Kernel::$middlewareGroups (app/Http/Kernel.php) — the
//      web group, so every page of the site and the panel
//    - EventServiceProvider::boot — mark, at every sign-in
//    - ManagerMainController::change_pass,
//      Admin\ManagerMainController::change_pass — mark, after
//      a password changed in the session itself
// -----------------------------------------------------------


namespace App\Http\Middleware;

use Closure;
use Session;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;








// -----------------------------------------------------------
// SignOutChangedPassword
// -----------------------------------------------------------
//
// The mark is an HMAC of the stored password hash under the
// app key, kept in the session as password_mark_mem or
// password_mark_man with the id of the account it was made
// for, so the session files hold no password hash. A
// session without a mark of the account signed in — one
// that signed in before this check came, or one whose mark
// was made for another account — takes the password it
// finds at its next request. Each signed-in guard costs one
// lookup by id a request, on the row itself rather than on
// the user the guard keeps.
//
//   handle        — signs out a guard whose password changed
//   mark          — marks a guard with its password
//   password_mark — the mark of a row's password
//   hash_mark     — the mark of one hash
//
// Used by:
//   - Kernel::$middlewareGroups — the web group
//   - EventServiceProvider::boot — every sign-in
//   - ManagerMainController::change_pass,
//     Admin\ManagerMainController::change_pass
// -----------------------------------------------------------

class SignOutChangedPassword
{

    // The guards a session can hold, each with the table of
    // its accounts
    private const GUARDS = ['mem' => 'bei_users', 'man' => 'bei_managers'];






    // -----------------------------------------------------------
    // handle
    // -----------------------------------------------------------
    //
    // Signs a guard out of this session when its mark no
    // longer matches the password of its row; a manager's
    // main and country go with the manager, as in
    // SignOutSwitchedOff. The other guard of a browser signed
    // in on both sides stays. A guard without a mark of its
    // account gets one, from the row as the request begins.
    // Then the request goes on, a page behind the guard
    // sending the visitor to its login form, and a guard
    // signed out by then loses its mark.
    //
    // Used by:
    //   - Laravel's router, on every request of the web group
    // -----------------------------------------------------------

    public function handle($request, Closure $next)
    {
        // STEP 1: a guard whose password has changed since its
        // mark was set is signed out of the session
        // ====================================================
        foreach(SignOutChangedPassword::GUARDS as $guard => $table){

            if(!Auth::guard($guard)->check()){
                continue;
            }

            $mark = Session::get('password_mark_'.$guard);
            $current = SignOutChangedPassword::password_mark($table, Auth::guard($guard)->id());

            // A mark made for another account says nothing of
            // this one's password
            if(!is_array($mark) || (int) $mark['id'] !== (int) Auth::guard($guard)->id()){
                Session::put('password_mark_'.$guard, ['id'=>Auth::guard($guard)->id(), 'mark'=>$current]);
            } elseif(!hash_equals($mark['mark'], $current)){

                // This browser's sign-in alone ends; the row and
                // its remember token stay as they are
                Auth::guard($guard)->logoutCurrentDevice();

                if($guard == 'man'){
                    Session::forget(['main', 'country']);
                }
            }
        }

        $response = $next($request);


        // STEP 2: a guard signed out by the request — or above
        // — loses its mark
        // ====================================================
        foreach(SignOutChangedPassword::GUARDS as $guard => $table){

            if(!Auth::guard($guard)->check()){
                Session::forget('password_mark_'.$guard);
            }
        }

        return $response;
    }






    // -----------------------------------------------------------
    // mark
    // -----------------------------------------------------------
    //
    // Marks the session with a password of the guard's
    // account: the hash given — the one a login checked, or
    // the one this session has just written — or else the one
    // the row has now. The account is the one given — the
    // Login event's, which comes before the guard holds it —
    // or else the guard's. A guard that is not one of the two,
    // or holds no one, is left alone.
    //
    // Used by:
    //   - EventServiceProvider::boot — on Laravel's Login event
    //   - ManagerMainController::change_pass — the member's own
    //     new password
    //   - Admin\ManagerMainController::change_pass — the
    //     manager's own new password
    // -----------------------------------------------------------

    public static function mark($guard, $id = null, $hash = null)
    {
        if(!isset(SignOutChangedPassword::GUARDS[$guard])){
            return;
        }

        $id = $id ?? Auth::guard($guard)->id();

        if($id !== null){
            Session::put('password_mark_'.$guard, ['id'=>$id, 'mark'=>$hash !== null ? SignOutChangedPassword::hash_mark($hash) : SignOutChangedPassword::password_mark(SignOutChangedPassword::GUARDS[$guard], $id)]);
        }
    }






    // -----------------------------------------------------------
    // password_mark
    // -----------------------------------------------------------
    //
    // The mark of the password a row has now, read from the
    // table itself. A row that is gone gives the mark of an
    // empty hash, which matches no session's.
    //
    // Used by:
    //   - handle, mark (above)
    // -----------------------------------------------------------

    private static function password_mark($table, $id)
    {
        return SignOutChangedPassword::hash_mark((string) DB::table($table)->where('id', $id)->value('password'));
    }






    // -----------------------------------------------------------
    // hash_mark
    // -----------------------------------------------------------
    //
    // The mark of one password hash: its HMAC under the app
    // key.
    //
    // Used by:
    //   - mark, password_mark (above)
    // -----------------------------------------------------------

    private static function hash_mark($hash)
    {
        return hash_hmac('sha256', $hash, config('app.key'));
    }
}
