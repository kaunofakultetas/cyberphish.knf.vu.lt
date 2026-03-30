<?php

namespace App\Models;

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;

class KnowledgeOption extends Model
{
    protected $table = "bei_knowledge_option";
    public $timestamps = false;
    protected $rememberTokenName = false;

    protected $fillable = [
    ];

    protected $hidden = [
    ];

    protected $casts = [
    ];

}