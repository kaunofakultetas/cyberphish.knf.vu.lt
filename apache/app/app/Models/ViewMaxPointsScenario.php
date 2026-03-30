<?php

namespace App\Models;

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;

class ViewMaxPointsScenario extends Model
{
    protected $table = "view_max_points_per_scenario";
    public $timestamps = false;
    protected $rememberTokenName = false;

    protected $fillable = [
    ];

    protected $hidden = [
    ];

    protected $casts = [
    ];

}