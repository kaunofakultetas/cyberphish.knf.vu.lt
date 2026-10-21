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
// validator, and those differ from what the pages ask for
// in the browser; what a name may hold is decided by the
// table's latin1 character set (see the method banners).
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
    // current password must match the stored hash, the new
    // one must differ from it and equal its repetition, and
    // both new fields need 6 characters; each refusal comes
    // back with its own message, and success stores the new
    // hash.
    //
    // Known bug (medium): the new password needs only 6
    // characters and has no upper limit
    // (ManagerMainController.php:167), while registration
    // takes 12 to 50 and the login refuses anything else — a
    // member who picks a shorter or a longer one cannot sign
    // in again until a password reset. The form asks for 12
    // to 50 in the browser only. Pinned as password-policy
    // (the short side). Fix: the login's rule, 12 to 50, here
    // too.
    //
    // Used by:
    //   - parts/change_password.blade.php — the password form
    //     on the dashboard
    // -----------------------------------------------------------

    public function change_pass(Request $request)
    {
        if (!(Hash::check($request->get('password'), Auth::guard('mem')->user()->password))) {

            return redirect()->back()->withErrors(__('main.curr_pass_wrong'));
        }

        if(strcmp($request->get('password'), $request->get('newpass')) == 0){

            return redirect()->back()->withErrors(__('main.curr_new_same'));
        }

        if(strcmp($request->get('newpass'), $request->get('renewpass')) <> 0){

            return redirect()->back()->withErrors(__('main.new_pass_not_match'));
        }

        $validator = Validator::make($request->all(), [
            'newpass' => 'required|min:6',
            'renewpass' => 'required|min:6'
        ]);

        if($validator->fails()){

            return redirect()->back()->withErrors(__('main.pass_too_short', ['length'=>6]));

        }

        Members::where('id', Auth::guard('mem')->id())->update(['password' => Hash::make($request->get('newpass'))]);

        return redirect()->back()->withSuccess(__('main.pass_changed'));
    }






    // -----------------------------------------------------------
    // change_username
    // -----------------------------------------------------------
    //
    // POST /cp/change-username
    //
    // Renames the member, behind auth:mem: 6 to 100
    // characters (the form asks for 3 to 20), and no other
    // member may hold the name already — submitting one's own
    // name again is fine. A failed validation comes back as a
    // bare "Error!", untranslated; success and a taken name
    // answer with the translation keys pass_updated and
    // pass_unavailable, which hold the user-name texts
    // despite their names.
    //
    // Known bug (medium): bei_users is latin1 while the
    // connection speaks utf8mb4, so the uniqueness lookup
    // (ManagerMainController.php:234) fails on a name with
    // Lithuanian letters with an illegal mix of collations —
    // a 500, which production logged for students' real
    // names. Pinned as username-lithuanian. Fix: convert the
    // table to utf8mb4.
    //
    // Known bug (low): the validator allows 100 characters
    // for a column of 45 (ManagerMainController.php:225), and
    // the non-strict SQL mode cuts the rest off without a
    // word — a cut name can even collide with an existing one
    // on the unique key, a 500. Pinned as username-length.
    // Fix: a limit of 45.
    //
    // Used by:
    //   - parts/change_username.blade.php — the user-name form
    //     on the dashboard
    // -----------------------------------------------------------

    public function change_username(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'username' => 'required|min:6|max:100'
        ]);

        if($validator->fails()){

            return redirect()->back()->withErrors(__('Error!'));

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
    // auth:mem: 6 to 20 characters (the form allows up to
    // 100), a bare "Error!" otherwise. Unlike the user name
    // it need not be unique.
    //
    // Known bug (medium): bei_users is a latin1 table, so the
    // letters latin1 lacks — ą, č, ę, ė, į, ų, ū — are stored
    // as question marks without an error
    // (ManagerMainController.php:288), and the certificate
    // prints them so. Pinned as fullname-latin1. Fix: convert
    // the table to utf8mb4.
    //
    // Used by:
    //   - parts/change_fullname.blade.php — the full-name form
    //     on the knowledge-test page and on the result page of
    //     a passed attempt
    // -----------------------------------------------------------

    public function change_fullname(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'fullname' => 'required|min:6|max:20'
        ]);

        if($validator->fails()){

            return redirect()->back()->withErrors(__('Error!'));

        }

        Members::where(['id'=>Auth::guard('mem')->id()])->update(['fullname'=>$request->get('fullname')]);

        return back()->withSuccess(__('main.updated'));
    }
}
