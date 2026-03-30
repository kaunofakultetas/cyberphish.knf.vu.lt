<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LMContent extends Model
{
    protected $table = "bei_lm_content";
    public $timestamps = false;
    protected $rememberTokenName = false;

    protected $fillable = [
        'id', 'cat_id', 'title', 'content', 'hours', 'lang_id'
    ];

    protected $hidden = [
    ];

    protected $casts = [
        'id'=>'integer',
        'cat_id'=>'integer',
        'title'=>'string',
        'content'=>'string',
        'hours'=>'number',
        'lang_id'=>'integer'
    ];

    public static function get_id($hash){

        $data = LMContent::select('id')->whereRaw("MD5(MD5(CONCAT(?, id))) = ?", [env('APP_SALT'), $hash])->get()->pluck('id')->first();

        if(is_numeric($data) && $data > 0){
            return $data;
        } else {
            return false;
        }

    }


}