<?php

// -----------------------------------------------------------
//  [*] AdminLoginController — the admin panel's sign-in
//
//  The admin login at /admin-panel and the logout. Managers
//  sign in on the man guard, against bei_managers, and only
//  an active one (status 1) gets in. The login records the
//  time and the address of the visit on the manager and
//  copies its main flag and country into the session, where
//  the Perms middleware and the panel's views read them.
//
//  Used by:
//    - routes/web_admin.php — the admin-panel and
//      admin-panel/logout routes
// -----------------------------------------------------------


namespace App\Http\Controllers\Auth;

use Session;
use App\Http\Controllers\Controller;
use App\Http\Controllers\SettingsController;
use App\Providers\RouteServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Models\Managers as Managers;
use Route;
use Carbon\Carbon;








// -----------------------------------------------------------
// AdminLoginController
// -----------------------------------------------------------
//
// Laravel's AuthenticatesUsers with its form, login and
// logout replaced; nothing else of the trait is called, so
// its ThrottlesLogins never runs (pinned as
// admin-login-throttle, see login).
//
//   showLoginForm — the form
//   login         — checks the password, fills the session
//   logout        — signs the manager out
//
// Used by:
//   - routes/web_admin.php — the admin login routes
// -----------------------------------------------------------

class AdminLoginController extends Controller
{

    // Laravel's login plumbing; its form, login and logout
    // are replaced below
    use AuthenticatesUsers;

    // Where the trait sends a manager after signing in.
    // Nothing reads it: login below redirects to the
    // dashboard route itself, the same path
    protected $redirectTo = '/admin-panel/dashboard';






    // -----------------------------------------------------------
    // __construct
    // -----------------------------------------------------------
    //
    // Keeps signed-in users off the login, while the logout
    // stays open to anyone. guest:man sends a signed-in
    // manager to the dashboard and guest:mem a signed-in
    // member to the home page; the bare guest names no guard,
    // and RedirectIfAuthenticated acts only on man and mem, so
    // that one lets everybody through.
    //
    // Used by:
    //   - Laravel's router, for every route of this controller
    // -----------------------------------------------------------

    public function __construct()
    {
        $this->middleware('guest')->except('logout');
        $this->middleware('guest:mem')->except('logout');
        $this->middleware('guest:man')->except('logout');
    }






    // -----------------------------------------------------------
    // showLoginForm
    // -----------------------------------------------------------
    //
    // GET /admin-panel
    //
    // The admin login form, admin-panel/auth/login.blade.php.
    // No page links here; visitors arrive through redirects.
    //
    // Used by:
    //   - AuthenticateAdmin (the auth.admin middleware) — its
    //     redirect for a visitor on any page of the panel
    //   - login (below) — its answer to a failed attempt
    //   - logout (below) — its redirect
    // -----------------------------------------------------------

    public function showLoginForm()
    {
        return view('admin-panel.auth.login');
    }






    // -----------------------------------------------------------
    // login
    // -----------------------------------------------------------
    //
    // POST /admin-panel
    //
    // Checks the form — an address, and a password of at
    // least 6 characters — and tries the man guard with them
    // and status 1, so a switched-off manager is refused like
    // a wrong password. On success it records the time and
    // the address of the visit on the manager, copies the
    // manager's main flag and country into the session and
    // answers a redirect to the dashboard; on failure, back
    // to the form with the auth.failed message. The address
    // is the one SettingsController::getUserIP picks, which a
    // header from the client can choose (pinned there as
    // client-ip-header).
    //
    // Known bug (high): attempts are never limited
    // (AdminLoginController.php:173). The method replaces the
    // trait's login without its ThrottlesLogins calls, and the
    // form has no reCAPTCHA, so the panel that runs the whole
    // site takes any number of password guesses (pinned as
    // admin-login-throttle). Fix: lock the login for a while
    // after a few failures, as ThrottlesLogins does.
    //
    // Known bug (low): both answers go through Laravel's
    // intended redirect (AdminLoginController.php:179, 183),
    // which first takes the page auth.admin parked in the
    // session when it sent the visitor here. A failed attempt
    // goes to that page, which sends the visitor straight
    // back, and the failure message is lost on the way. Fix:
    // answer a failure with a plain redirect to the form.
    // Pinned as admin-login-error-lost.
    //
    // Used by:
    //   - admin-panel/auth/login.blade.php — the form
    // -----------------------------------------------------------

    public function login(Request $request)
    {
        $this->validate($request, [
            'email'   => 'required|email',
            'password' => 'required|min:6'
        ]);

        if (Auth::guard('man')->attempt(['email' => $request->email, 'password' => $request->password, 'status'=>1])) {

            Managers::where(['id' => Auth::guard('man')->id()])->update(['last_login' => Carbon::now(), 'last_ip' => SettingsController::getUserIP()]);
            Session::put('main', Managers::select('main')->where(['id' => Auth::guard('man')->id(), 'status'=>1])->get()->pluck('main')->first());
            Session::put('country', Managers::select('country')->where(['id' => Auth::guard('man')->id(), 'status'=>1])->get()->pluck('country')->first());

            return redirect()->intended(route('admin_dashboard'));

        }

        return redirect()->intended(route('admin_login'))->withErrors([__('auth.failed')]);
    }






    // -----------------------------------------------------------
    // logout
    // -----------------------------------------------------------
    //
    // GET /admin-panel/logout
    //
    // Signs the manager out of the man guard and answers a
    // redirect to the admin login. The session itself lives
    // on: its main and country values stay until the next
    // admin login replaces them.
    //
    // Used by:
    //   - admin-panel/layouts/main.blade.php — Logout, in the
    //     account menu of the top bar
    // -----------------------------------------------------------

    public function logout()
    {
        Auth::guard('man')->logout();
        return redirect('/admin-panel');
    }
}
