<?php

// -----------------------------------------------------------
//  [*] RegisterController — member sign-up and verification
//
//  How a visitor becomes a member: the sign-up form and its
//  POST, which creates an inactive account (status 0) under
//  a generated User<number> name and mails a verification
//  link, and the link itself, which switches the account
//  on. Built on BaseController, whose middleware sets the
//  language and shares the member's data with every view.
//
//  Used by:
//    - routes/web_user.php — the register and verify routes
// -----------------------------------------------------------


namespace App\Http\Controllers\Auth;

use Mail;
use App\Http\Controllers\BaseController;
use App\Http\Controllers\Controller;
use App\Providers\RouteServiceProvider;
use App\Http\Controllers\SettingsController;
use App\Mail\SendVerification;
use Illuminate\Http\Request;
use App\Models\Members;
use Illuminate\Foundation\Auth\RegistersUsers;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;








// -----------------------------------------------------------
// RegisterController
// -----------------------------------------------------------
//
// RegistersUsers is mixed in, but register overrides the
// trait's own and no route reaches its form action, so
// Laravel's sign-up — which would sign the new user in at
// once — never runs.
//
//   register_form, register — signing up
//   verify                  — the link of the verification
//                             mail
//   getUsername, randomGen  — the generated user name
//
// Used by:
//   - routes/web_user.php — the routes in the actions'
//     banners
// -----------------------------------------------------------

class RegisterController extends BaseController
{

    // Laravel's sign-up trait; register below overrides its
    // register, and no route reaches the rest
    use RegistersUsers;

    // Where the trait's own sign-up would go afterwards;
    // nothing reads it
    protected $redirectTo = '/register';






    // -----------------------------------------------------------
    // __construct
    // -----------------------------------------------------------
    //
    // BaseController's middleware, then guest on every action.
    // The bare guest checks nothing — RedirectIfAuthenticated
    // acts only when it is given the man or the mem guard — so
    // a signed-in member can open the sign-up form and the
    // verification link too.
    //
    // Used by:
    //   - Laravel's router, before any action of this
    //     controller
    // -----------------------------------------------------------

    public function __construct()
    {
        parent::__construct();
        $this->middleware('guest');
    }






    // -----------------------------------------------------------
    // register_form
    // -----------------------------------------------------------
    //
    // GET /register
    //
    // The sign-up form, auth/register.blade.php: the email,
    // the password twice, the country and the reCAPTCHA.
    //
    // Used by:
    //   - layouts/main.blade.php — the sign-up entry of the
    //     menu, for visitors
    //   - auth/login.blade.php and
    //     auth/forgot-password.blade.php — their "create an
    //     account" links
    // -----------------------------------------------------------

    public function register_form()
    {
        return view('auth.register');
    }






    // -----------------------------------------------------------
    // register
    // -----------------------------------------------------------
    //
    // POST /register
    //
    // Creates a member. The form needs an email of 4 to 254
    // characters, the password twice (12 to 50 characters), a
    // country id and a solved reCAPTCHA. A new address with
    // matching passwords gets a 50-character verify token from
    // random_string, mailed in a SendVerification mail before
    // the row is written; the row holds the password's hash,
    // a generated user name, the token and the country, and
    // stays inactive (status defaults to 0) until the link is
    // opened. Mismatched passwords and an address already
    // registered go back with their message — so unlike
    // forgot-password, this form tells whether an address has
    // an account. The email compares without case (latin1's
    // case-insensitive collation).
    //
    // Known bug (low): the reCAPTCHA rule
    // (RegisterController.php:178) has anhskohbo/no-captcha
    // post the token to Google with no error handling, so
    // while Google cannot be reached the connection error
    // escapes and the form answers with the 500 page — after
    // the package's 30-second timeout when the connection
    // hangs. It fails closed, nobody is registered, but the
    // visitor is told nothing; pinned as captcha-outage-500.
    // Fix: catch the connection error and send the form back
    // with a message.
    //
    // Known bug (medium): the country is checked only for
    // being an integer (RegisterController.php:177), but
    // LMController::pc_questionare_link knows countries 0 to 5
    // only, and BaseController calls it on every page of a
    // signed-in member — a member registered with any other
    // number gets a 500 on every page; pinned as
    // country-out-of-range. Fix: accept only the country ids
    // the form offers, 0 to 5.
    //
    // Used by:
    //   - auth/register.blade.php — the sign-up form posts here
    // -----------------------------------------------------------

    public function register(Request $request)
    {
        $this->validate($request, [
            'email'   => 'required|email|min:4|max:254',
            'password'=> 'required|min:12|max:50',
            'password2'=>'required|min:12|max:50',
            'country'=>'required|integer',
            'g-recaptcha-response' => 'required|captcha',
        ]);

        if (Members::where(array('email' => $request->get('email')))->count() == 0) {

            if($request->get('password') == $request->get('password2')){

                $uid = SettingsController::random_string(50);

                Mail::to($request->get('email'))->send(new SendVerification(['uid'=>$uid]));

                Members::create([
                    'username'=> RegisterController::getUsername(),
                    'email' => $request->get('email'),
                    'password' => Hash::make($request->get('password')),
                    'verify_token'=>$uid,
                    'country'=>$request->get('country')
                ]);

                return back()->withSuccess(__('main.user_registered'));

            } else {

                return back()->withErrors(__('main.pass_dont_match'));
            }

        } else {

            return back()->withErrors(__('main.email_already_exists'));
        }
    }






    // -----------------------------------------------------------
    // verify
    // -----------------------------------------------------------
    //
    // GET /verify/{verify_token}
    //
    // The link of the verification mail. Every member holding
    // the token from the URL is switched on (status 1) and the
    // token is emptied, then the login form greets them with
    // user_verified — through intended(), so a visitor who
    // opened a member page earlier is sent there instead,
    // bounced back to the form, and misses the greeting. An
    // unknown token changes nothing and ends on the login form
    // as well. The branch for an empty token never runs: the
    // route needs a non-empty segment.
    //
    // Known bug (medium): a token of spaces passes the check
    // against the empty string (RegisterController.php:250),
    // and MySQL ignores trailing spaces when it compares
    // strings, so it matches every member whose token is
    // already empty — every verified account, those an admin
    // switched off included — and the update has no "exactly
    // one" guard (RegisterController.php:257): a link whose
    // token is one space switches every deactivated account
    // back on; pinned as verify-blank-token. Fix: refuse a
    // blank token and demand exactly one match, as
    // ValidateReset does for the reset link.
    //
    // Used by:
    //   - SendVerification — the link in the verification mail
    //     (emails/userverification.blade.php)
    // -----------------------------------------------------------

    public function verify(Request $request, $verify_token)
    {
        if($verify_token <> ''){

            if(Members::where(array('verify_token' => $verify_token))->count() == 0) {
                // An unknown token: nothing to switch on, the
                // redirect at the end answers it
            } else {

                Members::where(['verify_token' => $verify_token])->update(['status'=>1,'verify_token'=>'']);

                return redirect()->intended(route('login'))->withSuccess(__('main.user_verified'));
            }

        } else {

            return redirect(route('login'));

        }

        return redirect(route('login'));
    }






    // -----------------------------------------------------------
    // getUsername
    // -----------------------------------------------------------
    //
    // A user name no member has yet: "User" and a random
    // number from 99 to 999999, drawn again — recursively —
    // while the name is taken (the column is unique).
    //
    // Used by:
    //   - register (above)
    // -----------------------------------------------------------

    public static function getUsername()
    {
        $username = "User".RegisterController::randomGen(99, 999999, 1)[0];

        if(Members::where(['username'=>$username])->count() == 0){
            return $username;
        } else {
            return RegisterController::getUsername();
        }
    }






    // -----------------------------------------------------------
    // randomGen
    // -----------------------------------------------------------
    //
    // A list of $quantity distinct random integers from $min
    // to $max: the whole range is built, shuffled, and its
    // head returned.
    //
    // Known bug (low): getUsername wants a single number, yet
    // for it this builds and shuffles a range of almost a
    // million integers (RegisterController.php:325) — some tens
    // of megabytes and a full shuffle on every sign-up and
    // every retry. Not pinned. Fix: draw the number with
    // random_int.
    //
    // Used by:
    //   - getUsername (above)
    // -----------------------------------------------------------

    public static function randomGen($min, $max, $quantity)
    {
        $numbers = range($min, $max);
        shuffle($numbers);
        return array_slice($numbers, 0, $quantity);
    }
}
