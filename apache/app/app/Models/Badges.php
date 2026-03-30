<?php

namespace App\Models;

use Session;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use App\Models\Langs;

class Badges extends Model
{
    protected $table = "bei_users_badges";
    public $timestamps = false;
    protected $rememberTokenName = false;

    protected $fillable = [
    ];

    protected $hidden = [
    ];

    protected $casts = [
    ];

    public static function save_badge($lang_id, $badge_name){

        $num = Badges::where(['lang_id'=>$lang_id, 'user_id'=>Auth::guard('mem')->id(), 'name'=>$badge_name])->count();

        if($num == 0){

            $add = new Badges;
            $add->lang_id = $lang_id;
            $add->user_id = Auth::guard('mem')->id();
            $add->name = $badge_name;
            $add->save();

            return true;

        } else {

            return false;

        }

    }

    public static function get_badge($name){

        $num = Badges::where(['name'=>$name, 'user_id'=>Auth::guard('mem')->id(), 'lang_id'=>Langs::getLangId(Session::get('lang'))])->count();

        if($num == 1){
            return true;
        } elseif($num == 0){
            return false;
        }

    }

}