<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Settings extends Model
{
    protected $table = "bei_settings";
    public $timestamps = false;
    protected $rememberTokenName = false;

    protected $fillable = [
        'id', 'name', 'val1', 'val2', 'val3'
    ];

    protected $hidden = [
    ];

    protected $casts = [
        'id'=>'integer',
        'name'=>'string',
        'val1'=>'string',
        'val2'=>'string',
        'val3'=>'integer'
    ];




}