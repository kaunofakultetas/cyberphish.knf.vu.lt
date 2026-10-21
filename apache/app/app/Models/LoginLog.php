<?php

// -----------------------------------------------------------
//  [*] LoginLog — the member login log
//
//  One bei_users_loginlog row per successful member login.
//  Besides keeping the record, the log earns the badge for
//  logging in on ten days in a row.
//
//  Used by:
//    - Auth\LoginController (Http/Controllers/)
// -----------------------------------------------------------


namespace App\Models;

use Session;
use DatePeriod;
use DateTime;
use DateInterval;
use App\Models\Badges;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;








// -----------------------------------------------------------
// LoginLog
// -----------------------------------------------------------
//
// Columns: user_id (bei_users) and login_date, in UTC — the
// app's time zone.
//
//   save_login  — records a login
//   if_login_10 — the ten-days-in-a-row badge
//
// Used by:
//   - Auth\LoginController::login — every successful member
//     login, both helpers in turn
// -----------------------------------------------------------

class LoginLog extends Model
{

    // The table, which has no created_at / updated_at
    // columns; rememberTokenName belongs to the account
    // models, a plain model never reads it
    protected $table = "bei_users_loginlog";
    public $timestamps = false;
    protected $rememberTokenName = false;

    // Empty: nothing mass-assigns this model
    protected $fillable = [
    ];

    // Empty: toArray hands out every column
    protected $hidden = [
    ];

    // Empty: every column comes back uncast
    protected $casts = [
    ];






    // -----------------------------------------------------------
    // save_login
    // -----------------------------------------------------------
    //
    // Records one login of the member at the current time.
    //
    // Used by:
    //   - Auth\LoginController::login — every successful member
    //     login
    // -----------------------------------------------------------

    public static function save_login($user_id)
    {
        $a = new LoginLog;
        $a->user_id = $user_id;
        $a->login_date = date("Y-m-d H:i:s");
        $a->save();
    }






    // -----------------------------------------------------------
    // if_login_10
    // -----------------------------------------------------------
    //
    // Awards badge_login_10 when the member has logged in on
    // each of the last ten days, today included — one count
    // query per day, ten on every login. The badge goes to
    // the language the session shows.
    //
    // Known bug (low): the message condition is inverted
    // (LoginLog.php:144). save_badge answers true for a NEW
    // badge, so the new-badge message is set on every login
    // after the badge was earned and never on the login that
    // earns it — and session()->now() keeps it for this
    // request only, which ends in the login's redirect, so no
    // page shows it either way. Fix: flash the message when
    // save_badge answers true.
    // Pinned as login-badge-message.
    //
    // Used by:
    //   - Auth\LoginController::login — every successful member
    //     login, right after save_login
    // -----------------------------------------------------------

    public static function if_login_10($user_id)
    {
        // The number of logins on each of the ten days that end
        // today, by date — DatePeriod leaves out its end date,
        // tomorrow
        $period = new DatePeriod(
            new DateTime(date("Y-m-d", strtotime("-9 days", strtotime(date("Y-m-d"))))),
            new DateInterval('P1D'),
            new DateTime(date("Y-m-d", strtotime("+1 day", strtotime(date("Y-m-d")))))
            );
        $gavno = [];
        foreach ($period as $key => $value) {
            $gavno[$value->format('Y-m-d')] = LoginLog::whereraw("user_id = ? AND DATE(login_date) = ?", [$user_id, $value->format('Y-m-d')])->count();
        }

        if(!in_array(0, $gavno)){

            $ba = Badges::save_badge(Langs::getLangId(Session::get('lang')), 'badge_login_10');

            // Inverted: true means the badge is new — see the
            // Known bug above
            if($ba == true){

            } else {
                session()->now('success', __('main.new_badge'));
            }

        }
    }
}
