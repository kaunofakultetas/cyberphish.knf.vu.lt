<?php

// -----------------------------------------------------------
//  [*] Members — the member accounts
//
//  One bei_users row per member of the course. Members sign
//  in through the mem guard, which reads bei_users through
//  Laravel's database provider, so the signed-in member is
//  a GenericUser, never this model. The model serves
//  registration, the account forms, the admin panel's
//  member pages and the tests.
//
//  Used by:
//    - Auth\LoginController, Auth\RegisterController,
//      ManagerMainController, BaseController, CertController,
//      KnowledgeTestController, SimulationsController
//      (Http/Controllers/)
//    - Admin\UsersController, Admin\ManagerMainController
//      (Http/Controllers/Admin/)
//    - ValidateReset, ValidateVerify (Http/Middleware/)
//    - the Feature and Apache suites (tests/)
// -----------------------------------------------------------


namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;








// -----------------------------------------------------------
// Members
// -----------------------------------------------------------
//
// Columns: email (unique, the login), password (a bcrypt
// hash), username (unique, at most 45 characters; sign-up
// makes one up from "User" and a number), fullname (the
// name the certificate prints), country (registration
// takes the form's ids 0 to 5; the pages treat any other
// number like 0, with no questionnaire link), status (0
// until the e-mailed link is followed or once an admin
// switches the account off, 1 active; only 1 signs in),
// verify_token (emptied once used), reset_token (the
// pending password-reset token, emptied once used and at
// every sign-in), remember_token (Laravel's remember-me
// column: members are never remembered, but the session
// guard reads it at every sign-out, so it must stay) and
// last_login / last_ip.
//
// The table is utf8mb4 (mysql/init.sql:57), like the
// connection, so names keep every letter. It was latin1
// until 2026-10-23, and a full name saved before then may
// hold a question mark for a Lithuanian letter, which the
// certificate prints until the member saves the name
// again.
//
// Used by:
//   - Auth\RegisterController — register, verify,
//     getUsername
//   - Auth\LoginController — login, forgotpassword,
//     reset_pass
//   - ManagerMainController — dashboard, change_pass,
//     change_username, change_fullname (the account forms)
//   - BaseController::__construct, CertController::download,
//     KnowledgeTestController::index, progress,
//     SimulationsController::simulation_do_stuff — the
//     member's address, country or full name
//   - ValidateReset::handle, and ValidateVerify::handle —
//     a middleware never registered, so it never runs
//   - Admin\UsersController — index, delete, change_status
//   - Admin\ManagerMainController — dashboard,
//     self_evaluation_history, simulations_history,
//     simulation_report, knowledge_report, member_in_scope
//     (whether a country manager may read the member)
//   - World::member, TestCase::signInMember,
//     ApacheTestCase::signInOverHttp and Flows (tests/), and
//     the tests that read a member back
// -----------------------------------------------------------

class Members extends Authenticatable
{

    // Laravel's notification trait, from the User stub;
    // nothing notifies a member, the mails go out through
    // the Mail facade
    use Notifiable;

    // The table, which has no created_at / updated_at
    // columns. rememberTokenName false switches the model's
    // own remember-token methods off, and nothing reads
    // guard (the member guard is mem); neither matters, as
    // the guards never build this model
    protected $table = "bei_users";
    public $timestamps = false;
    protected $rememberTokenName = false;
    protected $guard = 'members';

    // The columns create() accepts — registration creates
    // the account through it (RegisterController::register);
    // fullname is not among them, change_fullname writes it
    // with a query-builder update
    protected $fillable = [
        'email', 'username', 'last_login', 'last_ip', 'password', 'status', 'verify_token', 'country'
    ];

    // Kept out of toArray, which the admin dashboard's
    // member list goes through
    protected $hidden = [
        'password',
    ];

    // How the columns read back through the model;
    // last_login becomes a date, which toArray writes as
    // ISO-8601 text in UTC
    protected $casts = [
        'email'=>'string',
        'username'=>'string',
        'password'=>'string',
        'last_login'=>'datetime',
        'last_ip'=>'string',
        'country'=>'integer',
        'status'=>'integer',
        'verify_token'=>'string'
    ];
}
