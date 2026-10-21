<?php

// -----------------------------------------------------------
//  [*] ManagersController — the country managers of the panel
//
//  Main admins manage the panel's other accounts here: the
//  country managers, bei_managers rows with main 0, who see
//  only their own country's part of the panel. A new
//  manager starts switched off (status 0) until a main
//  admin switches it on; only status 1 may sign in, and the
//  managers page switches a manager off with status 2. Main
//  admins are never listed, and the status and delete
//  actions pass over them. Every route sits behind
//  auth.admin:man and perms: main admins only.
//
//  Used by:
//    - routes/web_admin.php — the managers page, its dialog
//      and their posts
// -----------------------------------------------------------


namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use App\Http\Controllers\SettingsController;
use Illuminate\Support\Facades\Hash;
use App\Models\Managers as Managers;
use Illuminate\Support\Facades\Validator;








// -----------------------------------------------------------
// ManagersController
// -----------------------------------------------------------
//
// Passwords are stored hashed; an address may belong to one
// manager only, which this class checks — the table does
// not.
//
//   managers       — the managers page
//   manager_form   — the new and edit dialog
//   manager_status — switches a manager on or off
//   manager_create, manager_update
//                  — the dialog's two posts
//   manager_delete — removes a manager
//
// Used by:
//   - routes/web_admin.php — the managers routes
// -----------------------------------------------------------

class ManagersController extends BaseController
{






    // -----------------------------------------------------------
    // __construct
    // -----------------------------------------------------------
    //
    // Nothing of its own: BaseController's constructor adds
    // the auth:man middleware and shares the session's main
    // and country flags with every view of the panel.
    //
    // Used by:
    //   - Laravel's router, for every route of this controller
    // -----------------------------------------------------------

    public function __construct()
    {
        parent::__construct();
    }






    // -----------------------------------------------------------
    // managers
    // -----------------------------------------------------------
    //
    // GET /admin-panel/settings/managers
    //
    // The managers page, admin-panel/manager_settings.blade.php:
    // every country manager (main 0) with its status, last
    // login and last address, and the links that switch,
    // edit and delete it.
    //
    // Used by:
    //   - admin-panel/sidebar.blade.php — Managers, under
    //     Settings
    // -----------------------------------------------------------

    public function managers(Request $request)
    {
        $managerlist = Managers::where(['main'=>0])->get()->toArray();
        return view('admin-panel.manager_settings', ['managerlist'=>$managerlist]);
    }






    // -----------------------------------------------------------
    // manager_form
    // -----------------------------------------------------------
    //
    // GET /admin-panel/dialog/manager/new
    // GET /admin-panel/dialog/manager/edit/{uid?}
    //
    // The dialog of admin-panel/dialog/managers_form.blade.php,
    // loaded into the managers page's modal: empty and
    // posting to manager/create without an id; with one,
    // posting to manager/update and filled with the manager's
    // address — never a main admin's.
    //
    // Used by:
    //   - public/js/window.js — managers_form, called by the
    //     Create Manager button and each row's edit icon on
    //     admin-panel/manager_settings.blade.php
    // -----------------------------------------------------------

    public function manager_form(Request $request, $uid=null)
    {
        if($uid == null){

            return view('admin-panel.dialog.managers_form', ['url'=>env('APP_URL').'/admin-panel/manager/create']);

        } else {

            return view('admin-panel.dialog.managers_form',
                [
                    'url'=>env('APP_URL').'/admin-panel/manager/update',
                    'uid'=>$uid,
                    'email'=>Managers::where(['id'=>$uid])->where('main','!=', 1)->get()->pluck('email')->first()
                ]);

        }
    }






    // -----------------------------------------------------------
    // manager_status
    // -----------------------------------------------------------
    //
    // GET /admin-panel/settings/manager/status/{status_id}/{user_id}
    //
    // Writes the status from the URL, whatever its value, onto
    // the manager unless it is a main admin; the managers page
    // sends 1 to switch a manager on and 2 to switch it off.
    // Answers a redirect back to the page.
    //
    // Known bug (medium): a change on a plain GET
    // (ManagersController.php:182), which the CSRF check never
    // covers, so a link a signed-in admin opens switches a
    // manager on or off (pinned as admin-get-deletes, for
    // every delete and status change of the panel). Fix: a
    // POST route, sent with the CSRF token.
    //
    // Used by:
    //   - admin-panel/manager_settings.blade.php — the Enabled
    //     and Disabled badges of each row
    // -----------------------------------------------------------

    public function manager_status($status_id, $user_id)
    {
        Managers::where(['id' => $user_id])->where('main', '!=', '1')->update(['status'=>$status_id]);

        return back()->withSuccess("Status changed!");
    }






    // -----------------------------------------------------------
    // manager_create
    // -----------------------------------------------------------
    //
    // POST /admin-panel/manager/create
    //
    // Creates a country manager: a valid address no manager
    // has yet, a password of 6 to 50 characters typed twice
    // and stored hashed, and the country as posted — the
    // dialog offers 1 to 5, and 0 for any other. The manager
    // starts switched off (status 0). Answers a redirect back
    // with the result; the success message asks to add
    // permissions, though the panel has no page for them.
    //
    // Used by:
    //   - admin-panel/dialog/managers_form.blade.php — the
    //     dialog, opened without an id
    // -----------------------------------------------------------

    public function manager_create(Request $request)
    {
        $validatedData = $request->validate([
            'email' => [ 'required', 'email' ],
            'pass' => [ 'required', 'min:6', 'max:50' ],
            'repass' => [ 'required', 'min:6', 'max:50' ],
        ]);

        if($validatedData['pass'] == $validatedData['repass']){

            if(Managers::where(['email'=>$validatedData['email']])->count() == 0){

                $addAdmin = new Managers;
                $addAdmin->email = $validatedData['email'];
                $addAdmin->password = Hash::make($validatedData['pass']);
                $addAdmin->main = 0;
                $addAdmin->status = 0;
                $addAdmin->country = $request->get('country');
                $addAdmin->save();

                return back()->withSuccess("Manager created! Don't forget to add permissions.");

            } else {

                return back()->withErrors("Manager with this email already exist!");

            }

        } else {

            return back()->withErrors("Passwords don't match!");

        }
    }






    // -----------------------------------------------------------
    // manager_update
    // -----------------------------------------------------------
    //
    // POST /admin-panel/manager/update
    //
    // Gives the manager named by uid a new address, one no
    // other manager has, and a new password typed twice; both
    // are required on every save. Answers a redirect back
    // with the result.
    //
    // Known bug (low): the dialog asks for the country again,
    // but only the address and the password are saved
    // (ManagersController.php:294), so a manager's country
    // cannot change once it is created. Fix: save the country
    // too, or drop the field from the edit dialog.
    // Pinned as manager-country-update.
    //
    // Known bug (low): unlike manager_status and
    // manager_delete, the update does not pass over main
    // admins (ManagersController.php:294): a post with a main
    // admin's id rewrites that admin's address and password.
    // Fix: the same main-is-not-1 condition.
    // Pinned as manager-update-main.
    //
    // Used by:
    //   - admin-panel/dialog/managers_form.blade.php — the
    //     dialog, opened with an id
    // -----------------------------------------------------------

    public function manager_update(Request $request)
    {
        $validatedData = $request->validate([
            'uid' => [ 'required', 'integer' ],
            'email' => [ 'required', 'email' ],
            'pass' => [ 'required', 'min:6', 'max:50' ],
            'repass' => [ 'required', 'min:6', 'max:50' ],
        ]);

        if($validatedData['pass'] == $validatedData['repass']){

            if(Managers::where(['email'=>$validatedData['email']])->where('id','!=', $validatedData['uid'])->count() == 0){

                Managers::where(['id' => $validatedData['uid']])->update([
                    'email'=>$validatedData['email'],
                    'password'=>Hash::make($validatedData['pass'])
                ]);

                return back()->withSuccess("Manager updated!");

            } else {

                return back()->withErrors("Manager with this email already exist!");

            }

        } else {

            return back()->withErrors("Passwords don't match!");

        }

        // Dead code: every branch above returns
        return back()->withSuccess("Updated!");
    }






    // -----------------------------------------------------------
    // manager_delete
    // -----------------------------------------------------------
    //
    // GET /admin-panel/settings/manager/delete/{uid}
    //
    // Deletes the manager unless it is a main admin; answers
    // a redirect back to the managers page.
    //
    // Known bug (medium): a delete on a plain GET
    // (ManagersController.php:346), which the CSRF check never
    // covers, so a link a signed-in admin opens deletes a
    // manager (pinned as admin-get-deletes, for every delete
    // of the panel). Fix: a POST route, sent with the CSRF
    // token.
    //
    // Used by:
    //   - admin-panel/manager_settings.blade.php — each row's
    //     delete icon, through Confirm_Dialog
    //     (public/js/admn.js)
    // -----------------------------------------------------------

    public function manager_delete(Request $request, $uid)
    {
        Managers::where(['id'=>$uid])->where('main', '!=', '1')->delete();

        return back()->withSuccess("Manager deleted!");
    }
}
