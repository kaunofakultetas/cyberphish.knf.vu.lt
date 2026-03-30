<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class News extends Model
{
    protected $table = "bei_news";
    public $timestamps = false;
    protected $rememberTokenName = false;

    protected $fillable = [
        'id', 'title', 'content', 'created', 'created_by', 'lang_id', 'feat_img', 'alias'
    ];

    protected $hidden = [
    ];

    protected $casts = [
        'id'=>'integer',
        'title'=>'string',
        'content'=>'string',
        'created'=>'datetime',
        'created_by'=>'integer',
        'lang_id'=>'integer',
        'feat_img'=>'string',
        'alias'=>'string'
    ];

    public function scopeLatestr($query, $limit, $lang_id){

        return $query->where(['lang_id'=>$lang_id])->take($limit)->orderByDesc('id');

    }

    public static function latest_news($limit, $lang_id){

        return News::select("id", "title", "feat_img", "lang_id", "created", "alias")->latestr($limit, $lang_id)->get()->toArray();

    }


}