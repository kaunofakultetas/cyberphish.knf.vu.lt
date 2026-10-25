<?php

// -----------------------------------------------------------
//  [*] ManagerMainController — member dashboard and account
//
//  The member side's controller of this name; the admin
//  panel has its own, Admin\ManagerMainController. It
//  serves the dashboard a member lands on after signing in
//  and the three account forms: the user name and the
//  password on the dashboard and, on the knowledge-test
//  pages, the full name the certificate prints. Every
//  route sits behind auth:mem, and each form answers with a
//  redirect back to the page it was posted from, carrying a
//  success or an error message.
//
//  Used by:
//    - routes/web_user.php — cp/dashboard, cp/change-pass,
//      cp/change-username, cp/change-fullname
// -----------------------------------------------------------


namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Middleware\SignOutChangedPassword;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use App\Models\LoginLog;
use App\Models\Members;








// -----------------------------------------------------------
// ManagerMainController
// -----------------------------------------------------------
//
// Every action works on the signed-in member's own row of
// bei_users. The forms check lengths with Laravel's
// validator, the same lengths their pages ask for in the
// browser. A name keeps every letter typed, the table
// being utf8mb4.
//
//   dashboard       — the member's own page
//   change_pass     — a new password
//   change_username — a new user name, unique among members
//   change_fullname — the name the certificate prints
//
// Used by:
//   - routes/web_user.php — the four routes under cp/
// -----------------------------------------------------------

class ManagerMainController extends BaseController
{






    // -----------------------------------------------------------
    // __construct
    // -----------------------------------------------------------
    //
    // BaseController's constructor and nothing else: it
    // registers the middleware that sets the language and
    // shares the member's data with every view.
    //
    // Used by:
    //   - Laravel, when it builds the controller to dispatch
    //     one of its routes
    // -----------------------------------------------------------

    public function __construct()
    {
        parent::__construct();
    }






    // -----------------------------------------------------------
    // dashboard
    // -----------------------------------------------------------
    //
    // GET /cp/dashboard
    //
    // The member's own page, behind auth:mem:
    // dashboard.blade.php with the user-name form, filled in
    // with the current name, and the password form.
    //
    // Used by:
    //   - LoginController::login — its redirect after signing
    //     in, unless another page was asked for first
    //   - layouts/main.blade.php — "my info" in the member
    //     menu of the top bar
    //   - parts/left-user-links.blade.php — the "my info"
    //     button on the dashboard, badges and history pages
    // -----------------------------------------------------------

    public function dashboard()
    {
        return view('dashboard', [
            'username'=>Members::select('username')->where(['id'=>Auth::guard('mem')->id()])->get()->pluck('username')->first()
        ]);
    }






    // -----------------------------------------------------------
    // change_pass
    // -----------------------------------------------------------
    //
    // POST /cp/change-pass
    //
    // Changes the member's password, behind auth:mem. The
    // current password must match the stored hash, the new one
    // must differ from it and equal its repetition, and it
    // must be 12 to 50 characters long — the login's rule, so
    // no member can pick a password the login then refuses;
    // each refusal comes back with its own message, and
    // success stores the new hash. A field posted as a list is
    // refused at the check it would have failed —
    // password_verify and strcmp take one string, and a list
    // reaching them was a TypeError, a 500. A new password
    // with a NUL byte gets the list's refusal: bcrypt refuses
    // the byte, and Hash::make failed on it with a 500. The
    // new password ends the member's other sessions at their
    // next request (SignOutChangedPassword). This one is
    // marked with the hash just written, so the member stays
    // signed in here, and moves to a new session id, so a
    // copy of its cookie someone else holds ends too; its
    // CSRF token stays, for the browser's other open tabs.
    //
    // Used by:
    //   - parts/change_password.blade.php — the password form
    //     on the dashboard
    // -----------------------------------------------------------

    public function change_pass(Request $request)
    {
        if (!is_string($request->get('password')) || !(Hash::check($request->get('password'), Auth::guard('mem')->user()->password))) {

            return redirect()->back()->withErrors(__('main.curr_pass_wrong'));
        }

        if(is_array($request->get('newpass')) || is_array($request->get('renewpass')) || strpos((string) $request->get('newpass'), "\0") !== false){

            return redirect()->back()->withErrors(__('main.new_pass_not_match'));
        }

        if(strcmp($request->get('password'), $request->get('newpass')) == 0){

            return redirect()->back()->withErrors(__('main.curr_new_same'));
        }

        if(strcmp($request->get('newpass'), $request->get('renewpass')) <> 0){

            return redirect()->back()->withErrors(__('main.new_pass_not_match'));
        }

        $validator = Validator::make($request->all(), [
            'newpass' => 'required|min:12',
            'renewpass' => 'required|min:12'
        ]);

        if($validator->fails()){

            return redirect()->back()->withErrors(__('main.pass_too_short', ['length'=>12]));

        }

        if(Validator::make($request->all(), ['newpass' => 'max:50'])->fails()){

            return redirect()->back()->withErrors(__('main.pass_too_long'));

        }

        $hash = Hash::make($request->get('newpass'));
        Members::where('id', Auth::guard('mem')->id())->update(['password' => $hash]);

        // This browser goes on under the new password, its
        // session under a new id: the old id, and any copy of
        // its cookie, ends with the member's other sessions.
        // The CSRF token stays, so the browser's other open
        // tabs can still post
        SignOutChangedPassword::mark('mem', null, $hash);
        $request->session()->migrate(true);

        return redirect()->back()->withSuccess(__('main.pass_changed'));
    }






    // -----------------------------------------------------------
    // change_username
    // -----------------------------------------------------------
    //
    // POST /cp/change-username
    //
    // Renames the member, behind auth:mem: a string of 6 to 45
    // characters, the column's size and what the form asks
    // for — a list of six to 45 values once passed the counts
    // and was stored as its JSON text — and no other member
    // may hold the name already — submitting one's own name
    // again is fine. A name of the wrong length comes back
    // with username_length, which gives both limits in the
    // member's language; success and a taken name answer with
    // the translation keys pass_updated and pass_unavailable,
    // which hold the user-name texts despite their names.
    //
    // Used by:
    //   - parts/change_username.blade.php — the user-name form
    //     on the dashboard
    // -----------------------------------------------------------

    public function change_username(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'username' => 'required|string|min:6|max:45'
        ]);

        if($validator->fails()){

            return redirect()->back()->withErrors(__('main.username_length', ['min'=>6, 'max'=>45]));

        }

        $usr = Members::where('username', '=', $request->get('username'))->where('id', '<>', Auth::guard('mem')->id())->count();

        if($usr == 0){

            Members::where(['id'=>Auth::guard('mem')->id()])->update(['username'=>$request->get('username')]);

            return back()->withSuccess(__('main.pass_updated'));

        } else {
            return back()->withErrors(__('main.pass_unavailable'));
        }
    }






    // -----------------------------------------------------------
    // change_fullname
    // -----------------------------------------------------------
    //
    // POST /cp/change-fullname
    //
    // Stores the full name the certificate prints, behind
    // auth:mem: a string of 6 to 100 characters, as the form
    // asks, never a list stored as its JSON text — the
    // certificate shrinks a name too wide for its line
    // (CertController::name_style) — or else fullname_length,
    // which gives both limits in the member's language.
    // Unlike the user name it need not be unique.
    //
    // Used by:
    //   - parts/change_fullname.blade.php — the full-name form
    //     on the knowledge-test page and on the result page of
    //     a passed attempt
    // -----------------------------------------------------------

    public function change_fullname(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'fullname' => 'required|string|min:6|max:100'
        ]);

        if($validator->fails()){

            return redirect()->back()->withErrors(__('main.fullname_length', ['min'=>6, 'max'=>100]));

        }

        Members::where(['id'=>Auth::guard('mem')->id()])->update(['fullname'=>$request->get('fullname')]);

        return back()->withSuccess(__('main.updated'));
    }
}
