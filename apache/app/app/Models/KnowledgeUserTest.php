<?php

namespace App\Models;

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;

class KnowledgeUserTest extends Model
{
    protected $table = "bei_users_knowledge_test";
    public $timestamps = false;
    protected $rememberTokenName = false;

    protected $fillable = [
    ];

    protected $hidden = [
    ];

    protected $casts = [
    ];

    public static function getPrivateId($public_id){

        return KnowledgeUserTest::where(['public_id'=>$public_id])->get()->pluck('id')->first();

    }

    public static function getQIds($id){

        return json_decode(KnowledgeUserTest::where(['id'=>$id])->get()->pluck('question_set')->first(), 1);

    }

}