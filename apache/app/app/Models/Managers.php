<?php

// -----------------------------------------------------------
//  [*] Managers — the admin panel accounts
//
//  One bei_managers row per account of the admin panel: the
//  main admin, who may do everything, and the country
//  managers, who see the members of their own country.
//  Managers sign in through the man guard on Laravel's
//  database provider, so the signed-in manager is a
//  GenericUser, never this model.
//
//  Used by:
//    - Auth\AdminLoginController (Http/Controllers/)
//    - Admin\ManagersController, Admin\ManagerMainController
//      (Http/Controllers/Admin/)
//    - World, TestCase and the admin tests (tests/)
// -----------------------------------------------------------


namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;








// -----------------------------------------------------------
// Managers
// -----------------------------------------------------------
//
// Columns: email, password (a bcrypt hash), main (1 the
// main admin, 0 a country manager), status ('1' active, '0'
// switched off — text, not a number; only 1 signs in),
// country (the country a manager looks after, 0 for the
// main admin), remember_token, last_login and last_ip. The
// admin login copies main and country into the session,
// where the perms middleware checks main. mysql/init.sql
// seeds the main admin as manager 1 with the hash of a
// password nobody kept, so a fresh install sets one before
// the first sign-in.
//
// Used by:
//   - Auth\AdminLoginController::login — the last login and
//     address, and main and country for the session
//   - Admin\ManagersController — managers, manager_form,
//     manager_status, manager_create, manager_update,
//     manager_delete
//   - Admin\ManagerMainController — dashboard (the
//     manager's country), change_pass, member_in_scope
//     (main and country, which decide whose reports the
//     manager may read)
//   - World::manager and TestCase::signInManager (tests/),
//     AdminLoginTest and AdminManagersTest
//     (tests/Feature/Admin/)
// -----------------------------------------------------------

class Managers extends Authenticatable
{

    // Laravel's notification trait, from the User stub;
    // nothing notifies a manager
    use Notifiable;

    // The table, which has no created_at / updated_at
    // columns. rememberTokenName false switches the model's
    // own remember-token methods off, and nothing reads
    // guard (the manager guard is man); neither matters, as
    // the guards never build this model
    protected $table = "bei_managers";
    public $timestamps = false;
    protected $rememberTokenName = false;
    protected $guard = 'managers';

    // The columns create() and fill() would accept; nothing
    // calls either on this model — manager_create builds the
    // row column by column
    protected $fillable = [
        'id', 'email', 'last_login', 'last_ip', 'main', 'country'
    ];

    // Kept out of toArray, which the panel's manager list
    // goes through
    protected $hidden = [
        'password',
    ];

    // How the columns read back through the model; status
    // stays text, and last_login becomes a date, which
    // toArray writes as ISO-8601 text in UTC
    protected $casts = [
        'id'=>'integer',
        'email'=>'string',
        'password'=>'string',
        'last_login'=>'datetime',
        'last_ip'=>'string',
        'main'=>'integer',
        'country'=>'integer'
    ];
}
