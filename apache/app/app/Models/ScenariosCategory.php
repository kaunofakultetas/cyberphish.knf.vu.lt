<?php

namespace App\Models;

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;

class ScenariosCategory extends Model
{
    protected $table = "bei_scenarios_category";
    public $timestamps = false;
    protected $rememberTokenName = false;

    protected $fillable = [
    ];

    protected $hidden = [
    ];

    protected $casts = [
    ];

    public static function IdByCategory($name){

        $id = ScenariosCategory::where(['name'=>trim($name)])->pluck('id')->first();

        return $id;

    }

}