<?php

namespace App\Models;

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;

class ScenariosAttributes extends Model
{
    protected $table = "bei_scenarios_attributes";
    public $timestamps = false;
    protected $rememberTokenName = false;

    protected $fillable = [
    ];

    protected $hidden = [
    ];

    protected $casts = [
    ];

    public static function IdByAttr($name){

        $id = ScenariosAttributes::where(['name'=>trim($name)])->pluck('id')->first();

        return $id;

    }

}