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
    // BaseController's middleware, then the visitors-only
    // checks. The bare guest checks nothing —
    // RedirectIfAuthenticated acts only when it is given the
    // man or the mem guard — so guest:mem does the work: it
    // sends a signed-in member from the sign-up form and its
    // POST to the home page. The verification link is left
    // out, so a link opened while signed in still switches its
    // account on.
    //
    // Used by:
    //   - Laravel's router, before any action of this
    //     controller
    // -----------------------------------------------------------

    public function __construct()
    {
        parent::__construct();
        $this->middleware('guest');
        $this->middleware('guest:mem')->except('verify');
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
    // characters, the password twice (12 to 50 characters),
    // one of the country ids the form offers, 0 to 5, and a
    // solved reCAPTCHA — while Google cannot be reached the
    // reCAPTCHA counts as unsolved (AppServiceProvider::boot),
    // after the package's 30-second timeout when the
    // connection hangs. A new address with matching passwords
    // gets a 50-character verify token from random_string,
    // mailed in a SendVerification mail before the row is
    // written; the row holds the password's hash, a generated
    // user name, the token and the country, and stays inactive
    // (status defaults to 0) until the link is opened. When
    // the mail cannot be sent, the failure is logged, no row
    // is written and the form says so (mail_failed), where it
    // once answered with a 500; the address can sign up once
    // mail goes out again. Mismatched passwords and an address
    // already registered go back with their message — so
    // unlike forgot-password, this form tells whether an
    // address has an account. The email compares without case
    // (the column's case-insensitive collation).
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
            'country'=>'required|integer|between:0,5',
            'g-recaptcha-response' => 'required|captcha',
        ]);

        if (Members::where(array('email' => $request->get('email')))->count() == 0) {

            if($request->get('password') == $request->get('password2')){

                $uid = SettingsController::random_string(50);

                try {
                    Mail::to($request->get('email'))->send(new SendVerification(['uid'=>$uid]));
                } catch (\Throwable $e) {
                    report($e);

                    return back()->withErrors(__('main.mail_failed'));
                }

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
    // The link of the verification mail. The one member
    // holding the token from the URL is switched on (status 1)
    // and the token is emptied, then the login form greets
    // them with user_verified — the form itself, even when a
    // member page opened earlier parked another destination in
    // the session. An unknown token changes nothing and ends
    // on the login form as well, and so does a token of
    // blanks: MySQL ignores trailing spaces when it compares
    // strings, so it would match every emptied token — every
    // verified account, those an admin switched off included.
    // A token more than one member holds changes nothing
    // either, the guard ValidateReset gives the reset link. An
    // account an admin switched off (status 2) stays off: an
    // admin who switches an unverified member on leaves the
    // token in place, so the old mail would switch the account
    // back on once it was switched off again. Its token is
    // used up all the same, and the login form opens without
    // the greeting.
    //
    // Used by:
    //   - SendVerification — the link in the verification mail
    //     (emails/userverification.blade.php)
    // -----------------------------------------------------------

    public function verify(Request $request, $verify_token)
    {
        if(trim($verify_token) <> ''){

            if(Members::where(array('verify_token' => $verify_token))->count() <> 1) {
                // An unknown token, or one of several members:
                // nothing to switch on, the redirect at the end
                // answers it
            } else {

                // Only an account waiting for its link (0), or one an
                // admin switched on before it came (1), is on after it
                if(!in_array(Members::where(['verify_token' => $verify_token])->value('status'), [0, 1])){
                    Members::where(['verify_token' => $verify_token])->update(['verify_token'=>'']);

                    return redirect(route('login'));
                }

                Members::where(['verify_token' => $verify_token])->update(['status'=>1,'verify_token'=>'']);

                return redirect(route('login'))->withSuccess(__('main.user_verified'));
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
    //   - RegistrationTest (tests/Feature/Auth/) — measures
    //     the memory one draw takes
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
    // to $max — never more than the range holds — drawn one
    // by one with random_int until enough distinct ones have
    // come up, so asking for one number from a range of a
    // million costs one draw, not the range.
    //
    // Used by:
    //   - getUsername (above)
    // -----------------------------------------------------------

    public static function randomGen($min, $max, $quantity)
    {
        $numbers = [];
        while (count($numbers) < min($quantity, $max - $min + 1)) {
            $numbers[random_int($min, $max)] = true;
        }
        return array_keys($numbers);
    }
}
