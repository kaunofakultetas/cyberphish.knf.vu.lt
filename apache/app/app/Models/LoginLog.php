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
use Carbon\Carbon;
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
    // each of the last ten days, today included, the days
    // counted on the members' clock (config
    // app.display_timezone), from one query of the member's
    // logins. The days once ended at UTC midnight — two or
    // three at night for the members — so half-past-midnight
    // logins on ten days in a row could leave a day empty and
    // earn nothing. The badge is the member's, whatever the
    // language: it is filed under the language the session
    // shows when it is first earned, another streak earns no
    // second one, and the badges page shows it in every
    // language (BadgesController::index). It was once earned
    // per language — a fresh session, English, took most of
    // them — and showed only in the language it was filed
    // under. The login that earns it flashes the new-badge
    // message for the page the login redirects to.
    //
    // Used by:
    //   - Auth\LoginController::login — every successful member
    //     login, right after save_login
    // -----------------------------------------------------------

    public static function if_login_10($user_id)
    {
        // The ten days that end today on the members' clock, and
        // the dates of the member's logins since the first of
        // them began, read on that clock from the UTC they are
        // stored in
        $zone = config('app.display_timezone');
        $today = Carbon::now($zone)->startOfDay();
        $first = $today->copy()->subDays(9);

        $dates = LoginLog::where('user_id', $user_id)
            ->where('login_date', '>=', $first->copy()->setTimezone('UTC')->format('Y-m-d H:i:s'))
            ->pluck('login_date')
            ->map(function ($at) use ($zone) {
                return Carbon::parse($at, 'UTC')->setTimezone($zone)->format('Y-m-d');
            })
            ->all();

        $gavno = [];
        for ($day = $first->copy(); $day->lte($today); $day->addDay()) {
            $gavno[$day->format('Y-m-d')] = in_array($day->format('Y-m-d'), $dates) ? 1 : 0;
        }

        if(!in_array(0, $gavno)){

            // The member's badge, held under whichever language it
            // was first earned in: another streak earns no second
            if(Badges::where(['user_id'=>$user_id, 'name'=>'badge_login_10'])->exists()){
                return;
            }

            $ba = Badges::save_badge(Langs::getLangId(Session::get('lang')), 'badge_login_10');

            // save_badge answers true for a new badge only
            if($ba == true){
                session()->flash('success', __('main.new_badge'));
            }

        }
    }
}
