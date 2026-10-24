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
use Illuminate\Support\Str;
use App\Models\Managers as Managers;
use Route;
use Carbon\Carbon;








// -----------------------------------------------------------
// AdminLoginController
// -----------------------------------------------------------
//
// Laravel's AuthenticatesUsers with its form, login and
// logout replaced; login keeps the throttling of the
// trait's login (ThrottlesLogins).
//
//   showLoginForm — the form
//   login         — checks the password, fills the session
//   throttleKey   — where its failures are counted
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

    // ThrottlesLogins reads these: ten failed sign-ins for one
    // email from one address lock that pair out until fifteen
    // minutes after the first of them
    protected $maxAttempts = 10;
    protected $decayMinutes = 15;






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
    // Checks the form — an address, and a password of at least
    // 6 characters — and tries the man guard with them and
    // status 1, so a switched-off manager is refused like a
    // wrong password. On success it records the time and the
    // address of the visit on the manager, copies the
    // manager's main flag and country into the session and
    // answers a redirect to the dashboard; on failure, back to
    // the form with the auth.failed message. The address is
    // the one SettingsController::getUserIP picks. The form
    // has no reCAPTCHA, so the guesses are limited the way the
    // trait's login limits them: every failure counts against
    // the email and the address it came from, and once the
    // pair has ten (the properties above), every post of it —
    // the right password included — goes back to the form with
    // auth.throttle, in English like the rest of the panel,
    // until fifteen minutes after the first failure. A sign-in
    // clears the count — a count of its own, apart from the
    // member login's (throttleKey). A success goes to the
    // panel page auth.admin parked in the session, if any, and
    // to the dashboard when the page parked is a member page,
    // which the member side's sign-in redirect parks under the
    // same key (SettingsController:: is_panel_url); a failure
    // goes straight back to the form, which keeps the parked
    // page for the next try and gets the email typed back, so
    // only the password needs typing again. The password
    // itself never goes into the session — not here, where
    // only the email is passed back, nor in the validation and
    // lockout refusals Laravel answers itself, which keep the
    // email too and leave out every field of
    // Handler::$dontFlash.
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

        if ($this->hasTooManyLoginAttempts($request)) {
            $this->fireLockoutEvent($request);
            return $this->sendLockoutResponse($request);
        }

        if (Auth::guard('man')->attempt(['email' => $request->email, 'password' => $request->password, 'status'=>1])) {

            $this->clearLoginAttempts($request);

            Managers::where(['id' => Auth::guard('man')->id()])->update(['last_login' => Carbon::now(), 'last_ip' => SettingsController::getUserIP()]);
            Session::put('main', Managers::select('main')->where(['id' => Auth::guard('man')->id(), 'status'=>1])->get()->pluck('main')->first());
            Session::put('country', Managers::select('country')->where(['id' => Auth::guard('man')->id(), 'status'=>1])->get()->pluck('country')->first());

            $intended = $request->session()->pull('url.intended');

            return redirect($intended !== null && SettingsController::is_panel_url($intended) ? $intended : route('admin_dashboard'));

        }

        $this->incrementLoginAttempts($request);

        return redirect(route('admin_login'))->withInput($request->only('email'))->withErrors([__('auth.failed')]);
    }






    // -----------------------------------------------------------
    // throttleKey
    // -----------------------------------------------------------
    //
    // The key ThrottlesLogins counts this login's failures
    // under: the trait's own, the email and the address,
    // marked as the panel's. The member login counts under the
    // bare key in the same cache, so ten wrong member
    // passwords for a manager's address once locked the
    // panel's sign-in of that address too, and the panel's
    // failures the member login's.
    //
    // Used by:
    //   - ThrottlesLogins, through login (above)
    // -----------------------------------------------------------

    protected function throttleKey(Request $request)
    {
        return 'admin-panel|'.Str::transliterate(Str::lower($request->input($this->username())).'|'.$request->ip());
    }






    // -----------------------------------------------------------
    // logout
    // -----------------------------------------------------------
    //
    // POST /admin-panel/logout
    //
    // Signs the manager out of the man guard and answers a
    // redirect to the admin login. A POST with the session's
    // token, so no link or image of another site can sign a
    // manager out. The session itself lives
    // on: its main and country values stay until the next
    // admin login replaces them.
    //
    // Used by:
    //   - admin-panel/layouts/main.blade.php — Logout, a form
    //     button in the account menu of the top bar
    // -----------------------------------------------------------

    public function logout()
    {
        Auth::guard('man')->logout();
        return redirect('/admin-panel');
    }
}
