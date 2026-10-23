<?php

// -----------------------------------------------------------
//  [*] UsersController — the members page of the panel
//
//  admin-panel/users lists every member account, whatever
//  its country, on one page with a quick search, and a
//  status switch and a delete icon on each row. Its routes
//  carry auth.admin:man and perms, so only main admins
//  reach them; a country manager gets a 404. Both row
//  actions are POST routes, behind the CSRF check, and the
//  page comes back to where it stood after either.
//
//  Used by:
//    - routes/web_admin.php — admin-panel/users and the
//      user/delete and user/status routes
// -----------------------------------------------------------


namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Members as Members;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;








// -----------------------------------------------------------
// UsersController
// -----------------------------------------------------------
//
// The list and its two row actions, which both redirect
// back to the list.
//
//   index         — the list of members
//   delete        — removes an account
//   change_status — switches an account on or off
//
// Used by:
//   - routes/web_admin.php
// -----------------------------------------------------------

class UsersController extends BaseController
{






    // -----------------------------------------------------------
    // MEMBER_TABLES
    // -----------------------------------------------------------
    //
    // Every table that holds rows of a member, by user_id —
    // the logins, the badges, the completed lessons and each
    // kind of attempt with its answers. delete empties them
    // of the member, answers before their attempts, before it
    // removes the account.
    //
    // Used by:
    //   - delete (below)
    // -----------------------------------------------------------

    private const MEMBER_TABLES = [
        'bei_users_loginlog',
        'bei_users_badges',
        'bei_lm_users',
        'bei_users_selfeval',
        'bei_users_selfeval_test',
        'bei_users_knowledge_options',
        'bei_users_knowledge_test',
        'bei_users_scenarios_options',
        'bei_users_scenarios',
    ];






    // -----------------------------------------------------------
    // __construct
    // -----------------------------------------------------------
    //
    // Only runs the admin BaseController's constructor: the
    // man-guard check and the session flags shared with the
    // views.
    //
    // Used by:
    //   - Laravel, when it dispatches a route of this
    //     controller
    // -----------------------------------------------------------

    public function __construct()
    {
        parent::__construct();
    }






    // -----------------------------------------------------------
    // index
    // -----------------------------------------------------------
    //
    // GET /admin-panel/users
    //
    // Every member account, whatever its country, newest
    // first, on one page as on the dashboard — the page's
    // quick search (User_Search of admn.js) filters the rows
    // as the admin types, so the list is not cut into pages,
    // and a page parameter is ignored. Each row carries the
    // user name and email the search matches, and the country
    // by its name (Members::COUNTRIES). Main admins only
    // (auth.admin:man, perms). Answers with
    // admin-panel/users.blade.php.
    //
    // Used by:
    //   - admin-panel/sidebar.blade.php — the Users entry of
    //     the menu, shown to main admins
    //   - delete, change_status (below) — they redirect here
    // -----------------------------------------------------------

    public function index()
    {
        $userlist = Members::select(['id', 'username', 'email', 'country', 'last_login', 'last_ip', 'status'])->orderByDesc('id')->get()->toArray();

        return view('admin-panel.users', ['userlist'=>$userlist, 'countries'=>Members::COUNTRIES]);
    }






    // -----------------------------------------------------------
    // delete
    // -----------------------------------------------------------
    //
    // POST /admin-panel/user/delete/{user_id}
    //
    // Removes every row of the member — the tables of
    // MEMBER_TABLES — and then the account, in one
    // transaction, when an account has that id, and redirects
    // to the members page — that page, whatever an earlier
    // visit left in the session — with "User deleted!", also
    // when none had it. Nothing of the member stays behind:
    // MySQL 5.7 gives the highest id out again after a
    // restart, and a row left behind would pass to the next
    // member to sign up with it — a passed knowledge test, and
    // with it the certificate. Main admins only
    // (auth.admin:man, perms).
    //
    // Used by:
    //   - admin-panel/users.blade.php — the delete icon of
    //     each row, through Confirm_Dialog (public/js/admn.js)
    // -----------------------------------------------------------

    public function delete($user_id)
    {
        // Built and never asked to validate anything, so the id
        // goes unchecked
        $validator = Validator::make(['user_id'=>$user_id], [
            'user_id' => 'integer|required'
        ]);

        if(Members::where(array('id' => $user_id))->count() > 0) {

            DB::transaction(function () use ($user_id) {
                foreach(self::MEMBER_TABLES as $table){
                    DB::table($table)->where('user_id', $user_id)->delete();
                }

                Members::destroy($user_id);
            });

        }

        return redirect(route('users'))->withSuccess("User deleted!");
    }






    // -----------------------------------------------------------
    // change_status
    // -----------------------------------------------------------
    //
    // POST /admin-panel/user/status/{status_id}/{user_id}
    //
    // Writes {status_id} from the URL, unchecked, into the
    // member's status and redirects to the members page —
    // that page, whatever an earlier visit left in the
    // session; an
    // unknown member id gets "User do not exist!" as an error
    // instead. Only status 1 signs in (LoginController::
    // login): the list links 2 to switch an active member off
    // and 1 to switch an inactive one back on. Main admins
    // only (auth.admin:man, perms).
    //
    // Used by:
    //   - admin-panel/users.blade.php — the Active / Not
    //     active badge of each row
    // -----------------------------------------------------------

    public function change_status($status_id, $user_id)
    {
        if (Members::where(array('id' => $user_id))->count() > 0) {

            $update = array('status'=> $status_id);

            Members::where(['id' => $user_id])->update($update);

            return redirect(route('users'));

        }

        return redirect(route('users'))->withErrors("User do not exist!");
    }
}
