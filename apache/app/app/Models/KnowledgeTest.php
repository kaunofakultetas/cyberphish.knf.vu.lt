<?php

namespace App\Models;

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;

class KnowledgeTest extends Model
{
    protected $table = "bei_knowledge_test";
    public $timestamps = false;
    protected $rememberTokenName = false;

    protected $fillable = [
    ];

    protected $hidden = [
    ];

    protected $casts = [
    ];

    public static function getQuestion($id){

        return KnowledgeTest::where(['id'=>$id])->get()->pluck('question')->first();

    }

    public static function getDecryptedId($hashed_id){

        return KnowledgeTest::whereraw("MD5(MD5(id))=?", [$hashed_id])->get()->pluck('id')->first();

    }

    public static function getCatId($id){

        return KnowledgeTest::where(['id'=>$id])->get()->pluck('cat_id')->first();

    }

}