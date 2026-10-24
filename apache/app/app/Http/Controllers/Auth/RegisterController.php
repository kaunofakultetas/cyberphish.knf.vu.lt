<?php

// -----------------------------------------------------------
//  [*] RegisterController — member sign-up and verification
//
//  How a visitor becomes a member: the sign-up form and its
//  POST, which creates an inactive account (status 0) under
//  a generated User<number> name and mails a verification
//  link — or, for an address already in use, answers the
//  same and mails the address's owner instead — and the
//  link itself, which switches the account on. Built on
//  BaseController, whose middleware sets the language and
//  shares the member's data with every view.
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
use App\Mail\SendAccountDeactivated;
use App\Mail\SendAccountExists;
use App\Mail\SendActivationLink;
use App\Mail\SendVerification;
use Illuminate\Database\UniqueConstraintViolationException;
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
    // characters, the password twice (12 to 50 characters,
    // each one string, and no NUL byte, which bcrypt refuses —
    // a list once passed the rule's count, and the link was
    // mailed before Hash::make failed on the list or the byte
    // with a 500), one of the country ids the form offers, 0
    // to 5, the privacy policy's box ticked — which once only
    // the browser asked for, so a post without it made an
    // account all the same — and a solved reCAPTCHA — while
    // Google cannot be reached the reCAPTCHA counts as
    // unsolved (AppServiceProvider::boot), after the package's
    // 30-second timeout when the connection hangs. Passwords
    // that differ go back with pass_dont_match, for any
    // address. Then every sign-up gets the same answer,
    // user_registered, so that the form tells no one which
    // addresses have accounts, as forgot-password does not; it
    // once answered email_already_exists. A new address gets
    // its row first — the password's hash, a generated user
    // name, a 50-character verify token from random_string and
    // the country, inactive (status defaults to 0) — and then
    // the token in a SendVerification mail. The row comes
    // before the mail so that a sign-up racing this one meets
    // the email's unique key instead of mailing a link no row
    // holds; it once did, and then failed on the key with a
    // 500. The loser answers as every sign-up does and mails
    // nothing, the winner's mail carrying the account's link.
    // An address in use keeps its row, its password included,
    // and its owner hears of the sign-up by mail — one mail
    // for every account, as for a new address. One still
    // waiting for its verification gets SendActivationLink,
    // the reset page's link (reset_pass), which mails a fresh
    // password to the address and switches the account on, and
    // its verification link is cancelled: that link would
    // switch the account on with the password of whoever
    // signed the address up first, maybe not the address's
    // owner. An active account gets a SendAccountExists note,
    // pointing to the login form, and one an admin switched
    // off a SendAccountDeactivated note; that one once got no
    // mail, and so answered sooner, and with user_registered
    // where the others said mail_failed while mail could not
    // go out. The password is hashed for every address too, so
    // no address in use answers faster than a new one. When a
    // mail cannot be sent, the failure is logged, a row
    // written for it is deleted again and the form says so
    // (mail_failed), where it once answered with a 500; the
    // address can sign up once mail goes out again. Every
    // refusal brings the form back with the email, the country
    // and the terms box as they were sent, as a refused
    // validation does, and never the passwords; these three
    // once came back empty. The email compares without case
    // (the column's case-insensitive collation).
    //
    // Used by:
    //   - auth/register.blade.php — the sign-up form posts here
    // -----------------------------------------------------------

    public function register(Request $request)
    {
        // STEP 1: the form, then the passwords, which must match
        // whatever the address
        // ======================================================
        $this->validate($request, [
            'email'   => 'required|email|min:4|max:254',
            'password'=> 'required|string|min:12|max:50|not_regex:/\x00/',
            'password2'=>'required|string|min:12|max:50',
            'country'=>'required|integer|between:0,5',
            'terms'=>'accepted',
            'g-recaptcha-response' => 'required|captcha',
        ]);

        if($request->get('password') != $request->get('password2')){

            return back()->withInput($request->only('email', 'country', 'terms'))->withErrors(__('main.pass_dont_match'));
        }


        // STEP 2: the account — a new one for a new address,
        // written before its mail; the password is hashed for
        // every address, so that one in use answers no faster
        // ===================================================
        $hash = Hash::make($request->get('password'));
        $member = Members::where(array('email' => $request->get('email')))->first();
        $created = false;
        $raced = false;

        if($member === null){

            try {
                $member = Members::create([
                    'username'=> RegisterController::getUsername(),
                    'email' => $request->get('email'),
                    'password' => $hash,
                    'verify_token'=>SettingsController::random_string(50),
                    'country'=>$request->get('country')
                ]);
                $created = true;
            } catch (UniqueConstraintViolationException $e) {
                $member = Members::where(array('email' => $request->get('email')))->first();

                // Not the address: a user name drawn twice at once
                if($member === null){
                    throw $e;
                }

                // A sign-up racing this one wrote the account first,
                // and its mail carries the account's link
                $raced = true;
            }
        }


        // STEP 3: the mail — one for every account, so that no
        // address answers sooner: the verification link to a new
        // account, the activation link to one still waiting for
        // it, a note to an active one or one an admin switched
        // off; none to a sign-up that lost the race — and one
        // answer for all of them
        // ======================================================
        try {
            if($created){
                Mail::to($member->email)->send(new SendVerification(['uid'=>$member->verify_token]));
            } elseif($raced){
                // The winner mails the link
            } elseif($member->status == 0){
                // Not its verification link, which would switch the
                // account on with the password of whoever signed the
                // address up first: the reset page's link, which
                // mails a new password to the address, and the old
                // link goes
                $reset_token = SettingsController::random_string(50);
                Mail::to($member->email)->send(new SendActivationLink(['uid'=>$reset_token]));
                Members::where(['id'=>$member->id])->update(['reset_token'=>$reset_token, 'verify_token'=>'']);
            } elseif($member->status == 1){
                Mail::to($member->email)->send(new SendAccountExists());
            } else {
                Mail::to($member->email)->send(new SendAccountDeactivated());
            }
        } catch (\Throwable $e) {
            report($e);

            // A new account goes with its mail, so the address can
            // sign up again once mail goes out
            if($created){
                $member->delete();
            }

            return back()->withInput($request->only('email', 'country', 'terms'))->withErrors(__('main.mail_failed'));
        }

        return back()->withSuccess(__('main.user_registered'));
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
