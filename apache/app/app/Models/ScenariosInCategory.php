<?php

namespace App\Models;

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;

class ScenariosInCategory extends Model
{
    protected $table = "bei_scenarios_in_category";
    public $timestamps = false;
    protected $rememberTokenName = false;

    protected $fillable = [
    ];

    protected $hidden = [
    ];

    protected $casts = [
    ];

}