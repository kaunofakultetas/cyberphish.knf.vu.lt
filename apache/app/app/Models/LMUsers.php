<?php

namespace App\Models;

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;

class LMUsers extends Model
{
    protected $table = "bei_lm_users";
    public $timestamps = false;
    protected $rememberTokenName = false;

    protected $fillable = [
        'id', 'content_id', 'user_id'
    ];

    protected $hidden = [
    ];

    protected $casts = [
        'id'=>'integer',
        'content_id'=>'integer',
        'user_id'=>'integer'
    ];

    public static function get_users_progress($user_id){

        $data = LMUsers::select("content_id")->where(['user_id'=>$user_id]);

        if($data->count() > 0){

            return $data->get()->pluck('content_id')->toArray();

        } else {
            return array();
        }

    }

    public static function course_progress($user_id, $lang_id){

        $data = DB::select("SELECT bei_user_course_progress(?, ?) AS progress", [$user_id, $lang_id]);

        if(is_array($data) && isset($data[0]->progress)){
            return $data[0]->progress;
        } else {
            return 0;
        }


    }
}