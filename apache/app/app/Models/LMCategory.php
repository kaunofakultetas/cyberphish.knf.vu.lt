<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LMCategory extends Model
{
    protected $table = "bei_lm_category";
    public $timestamps = false;
    protected $rememberTokenName = false;

    protected $fillable = [
        'id', 'name'
    ];

    protected $hidden = [
    ];

    protected $casts = [
        'id'=>'integer',
        'name'=>'string',
    ];




}