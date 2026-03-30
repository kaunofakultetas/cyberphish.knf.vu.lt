<?php

namespace App\Models;

use Session;
use DatePeriod;
use DateTime;
use DateInterval;
use App\Models\Badges;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class LoginLog extends Model
{
    protected $table = "bei_users_loginlog";
    public $timestamps = false;
    protected $rememberTokenName = false;

    protected $fillable = [
    ];

    protected $hidden = [
    ];

    protected $casts = [
    ];

    public static function save_login($user_id){

         $a = new LoginLog;
         $a->user_id = $user_id;
         $a->login_date = date("Y-m-d H:i:s");
         $a->save();

    }

    public static function if_login_10($user_id){

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

            if($ba == true){

            } else {
                session()->now('success', __('main.new_badge'));
            }

        }


    }

}