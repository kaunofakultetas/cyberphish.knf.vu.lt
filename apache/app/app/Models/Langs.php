<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Langs extends Model
{
    protected $table = "bei_lang";
    public $timestamps = false;
    protected $rememberTokenName = false;

    protected $fillable = [
        'id', 'name', 'locale'
    ];

    protected $hidden = [
    ];

    protected $casts = [
        'id'=>'integer',
        'name'=>'string',
        'locale'=>'string'
    ];

    public static function getLangId($locale){

        return Langs::select('id')->where(['locale'=>$locale])->get()->pluck('id')->first();


    }


}