<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Information extends Model
{
    protected $table = "bei_information";
    public $timestamps = false;
    protected $rememberTokenName = false;

    protected $fillable = [
        'id', 'title', 'content', 'lang_id', 'alias'
    ];

    protected $hidden = [
    ];

    protected $casts = [
        'id'=>'integer',
        'title'=>'string',
        'content'=>'string',
        'lang_id'=>'integer',
        'alias'=>'alias'
    ];




}