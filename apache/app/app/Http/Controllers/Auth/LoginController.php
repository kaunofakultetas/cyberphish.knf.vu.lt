<?php

// -----------------------------------------------------------
//  [*] LoginController — members signing in and out
//
//  The member side of signing in: the login form and its
//  POST, signing out, and the forgotten password — the form
//  that mails a reset link, and the link itself, which
//  mails a new random password. Members sign in on the mem
//  guard, whose database provider reads bei_users. Every
//  action but logout is for visitors only: guest:mem sends
//  a signed-in member to the home page.
//
//  Used by:
//    - routes/web_user.php — the login, logout,
//      forgot-password and reset routes
// -----------------------------------------------------------


namespace App\Http\Controllers\Auth;

use Session;
use Mail;
use App\Http\Controllers\BaseController;
use App\Mail\SendRemindLink;
use App\Mail\SendNewPass;
use App\Http\Controllers\Controller;
use App\Http\Controllers\SettingsController;
use App\Providers\RouteServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\LoginLog;
use App\Models\Members;
use Illuminate\Support\Facades\Validator;
use Route;
use Carbon\Carbon;








// -----------------------------------------------------------
// LoginController
// -----------------------------------------------------------
//
// Built on BaseController, whose middleware sets the
// language and shares the member's data with every view.
// AuthenticatesUsers is mixed in, but showLoginForm, login
// and logout replace the trait's own; login keeps the
// throttling of the trait's login (ThrottlesLogins).
//
//   showLoginForm, login, logout — signing in and out
//   ForgotPasswordForm,
//   forgotpassword               — asking for a reset link
//   reset_pass                   — the link: a new password
//
// Used by:
//   - routes/web_user.php — the routes in the actions'
//     banners
// -----------------------------------------------------------

class LoginController extends BaseController
{

    // Laravel's login trait; only its helpers are left, the
    // methods below replace its actions
    use AuthenticatesUsers;

    // Where the trait's own login would send a member after
    // signing in; nothing reads it, login names the dashboard
    // itself
    protected $redirectTo = "/cp/dashboard";

    // ThrottlesLogins reads these: ten failed sign-ins for one
    // email from one address lock that pair out until fifteen
    // minutes after the first of them
    protected $maxAttempts = 10;
    protected $decayMinutes = 15;






    // -----------------------------------------------------------
    // __construct
    // -----------------------------------------------------------
    //
    // BaseController's middleware first, then guest:mem on
    // every action but logout, which sends a signed-in member
    // to the home page. The bare guest line checks nothing:
    // RedirectIfAuthenticated acts only when it is given the
    // man or the mem guard.
    //
    // Used by:
    //   - Laravel's router, before any action of this
    //     controller
    // -----------------------------------------------------------

    public function __construct()
    {
        parent::__construct();
        $this->middleware('guest')->except('logout');
        $this->middleware('guest:mem')->except('logout');
    }






    // -----------------------------------------------------------
    // showLoginForm
    // -----------------------------------------------------------
    //
    // GET /login
    //
    // The login form, auth/login.blade.php. The route is named
    // login, so this is also where the Authenticate middleware
    // sends a visitor who opens a member page.
    //
    // Used by:
    //   - layouts/main.blade.php — the sign-in entry of the
    //     menu, for visitors
    //   - auth/register.blade.php and
    //     auth/forgot-password.blade.php — their "already have
    //     an account" links
    //   - Authenticate (middleware) — a visitor on a member page
    //   - login, reset_pass (below), RegisterController::verify
    //     — their redirects back to the form
    // -----------------------------------------------------------

    public function showLoginForm()
    {
        return view('auth.login');
    }






    // -----------------------------------------------------------
    // login
    // -----------------------------------------------------------
    //
    // POST /login
    //
    // Signs a member in on the mem guard. The form needs an
    // email and a password of 12 to 50 characters; the guard
    // then looks for an active account (status 1) with that
    // email and checks the password against its hash, so a
    // wrong password, an unknown address and an account not
    // yet verified all get the same auth_failed refusal. On
    // success it records the visit — last_login, last_ip
    // (SettingsController::getUserIP), a bei_users_loginlog
    // row, badge_login_10 after ten days in a row — empties
    // reset_token, and sends the member to the page that
    // asked for a sign-in, or to the dashboard. The form has
    // no reCAPTCHA, so the guesses are limited the way the
    // trait's login limits them: every refusal counts against
    // the email and the address it came from, and once the
    // pair has ten (the properties above), every post of it —
    // the right password included — goes back to the form
    // with auth.throttle in the session's language until
    // fifteen minutes after the first refusal. A sign-in
    // clears the count. A refusal goes straight back to the
    // form with its error; the page that asked for a sign-in
    // stays parked in the session for the next try.
    //
    // Used by:
    //   - auth/login.blade.php — the login form posts here
    // -----------------------------------------------------------

    public function login(Request $request)
    {
        $this->validate($request, [
            'email'   => 'required|email',
            'password' => 'required|min:12|max:50'
        ]);

        if ($this->hasTooManyLoginAttempts($request)) {
            $this->fireLockoutEvent($request);
            return $this->sendLockoutResponse($request);
        }

        if (Auth::guard('mem')->attempt(['email' => $request->email, 'password' => $request->password, 'status'=>1])) {

            $this->clearLoginAttempts($request);

            // Emptying reset_token cancels a pending password
            // reset (see forgotpassword)
            Members::where(['id' => Auth::guard('mem')->id()])->update(['last_login' => Carbon::now(), 'last_ip' => SettingsController::getUserIP(), 'reset_token'=>'']);

            LoginLog::save_login(Auth::guard('mem')->id());
            LoginLog::if_login_10(Auth::guard('mem')->id());

            return redirect()->intended(route('dashboard'));

        }

        $this->incrementLoginAttempts($request);

        return redirect(route('login'))->withErrors([__('main.auth_failed')]);
    }






    // -----------------------------------------------------------
    // logout
    // -----------------------------------------------------------
    //
    // GET /cp/logout
    //
    // Signs the member out of the mem guard and goes to the
    // home page. Unlike the trait's logout it leaves the
    // session alive, so the language choice survives — and so
    // does a manager signed in on the same browser. On the way
    // out the guard replaces a non-empty remember_token with a
    // random one; a pending password reset waits in
    // reset_token, out of its reach (see forgotpassword).
    //
    // Used by:
    //   - layouts/main.blade.php — the logout entry of the
    //     member menu
    // -----------------------------------------------------------

    public function logout()
    {
        Auth::guard('mem')->logout();
        return redirect('/');
    }






    // -----------------------------------------------------------
    // ForgotPasswordForm
    // -----------------------------------------------------------
    //
    // GET /forgot-password
    //
    // The forgotten-password form,
    // auth/forgot-password.blade.php: an email and the
    // reCAPTCHA.
    //
    // Used by:
    //   - auth/login.blade.php and auth/register.blade.php —
    //     their "forgot password" links
    // -----------------------------------------------------------

    public function ForgotPasswordForm()
    {
        return view('auth.forgot-password');
    }






    // -----------------------------------------------------------
    // forgotpassword
    // -----------------------------------------------------------
    //
    // POST /forgot-password
    //
    // Mails a password-reset link. The form needs an email and
    // a solved reCAPTCHA — while Google cannot be reached the
    // reCAPTCHA counts as unsolved (AppServiceProvider::boot).
    // When exactly one member has that email, a 50-character
    // token from random_string goes out in a SendRemindLink
    // mail and is then stored in the member's reset_token — a
    // column of its own, as remember_token belongs to
    // Laravel's session guard, whose logout replaces a
    // non-empty one: a member still signed in elsewhere who
    // signs out there keeps the link. Every address gets the
    // same forgot_pass_msg answer, mail or no mail, so the
    // form does not tell who has an account.
    //
    // Used by:
    //   - auth/forgot-password.blade.php — the form posts here
    // -----------------------------------------------------------

    public function forgotpassword(Request $request)
    {
        $this->validate($request, [
            'email'   => 'required|email',
            'g-recaptcha-response' => 'required|captcha',
        ]);

        if (Members::where(array('email' => $request->get('email')))->count() == 1) {

            $uid = SettingsController::random_string(50);

            Mail::to($request->get('email'))->send(new SendRemindLink(['uid'=>$uid]));

            Members::where(['email'=>$request->get('email')])->update(['reset_token'=>$uid]);

        }

        return back()->withSuccess(__('main.forgot_pass_msg'));
    }






    // -----------------------------------------------------------
    // reset_pass
    // -----------------------------------------------------------
    //
    // GET /reset/{reset_token}
    //
    // The link of the reset mail. ValidateReset
    // (validate.reset) lets it through only when exactly one
    // member holds that non-empty token, and answers 404
    // otherwise. A new 12-character password from
    // random_string goes to the member in a SendNewPass mail
    // and replaces the password hash, the token is used up,
    // and the login form shows new_pass_emailed — the form
    // itself, even when a member page parked another
    // destination in the session. Opening the link is all it
    // takes, there is no form. A signed-in member is sent
    // home by guest:mem and the token stays.
    //
    // Used by:
    //   - SendRemindLink — the link in the password-reset mail
    //     (emails/userpassreset.blade.php)
    // -----------------------------------------------------------

    public function reset_pass($reset_token)
    {
        $new_pass = SettingsController::random_string(12);

        $user_email = Members::select('email')->where('reset_token', '=', $reset_token)->get()->pluck('email')->first();

        Mail::to($user_email)->send(new SendNewPass(['new_pass'=>$new_pass]));

        Members::where('reset_token', '=', $reset_token)->update(['password'=>Hash::make($new_pass), 'reset_token'=>'']);

        return redirect(route('login'))->withSuccess(__('main.new_pass_emailed'));
    }
}
