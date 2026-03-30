<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LMFiles extends Model
{
    protected $table = "bei_lm_content_files";
    public $timestamps = false;
    protected $rememberTokenName = false;

    protected $fillable = [
        'id', 'content_id', 'name', 'file_name', 'mime_type', 'file_size', 'embed'
    ];

    protected $hidden = [
    ];

    protected $casts = [
        'id'=>'integer',
        'content_id'=>'integer',
        'name'=>'string',
        'file_name'=>'string',
        'mime_type'=>'string',
        'file_size'=>'integer',
        'embed'=>'integer'
    ];




}