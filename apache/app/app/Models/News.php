<?php

// -----------------------------------------------------------
//  [*] News — the news articles of the site
//
//  One bei_news row per article, written in the admin panel
//  for one language, with an optional featured picture. The
//  home page shows the newest three of its language, the
//  news page lists them all; an article's URL carries its
//  alias and its id, and the article is found by the id
//  alone.
//
//  Used by:
//    - HomeController, NewsController, Admin\NewsController
//      (Http/Controllers/)
//    - NewsExists (Http/Middleware/)
// -----------------------------------------------------------


namespace App\Models;

use Illuminate\Database\Eloquent\Model;








// -----------------------------------------------------------
// News
// -----------------------------------------------------------
//
// Columns: title, content (the HTML body), feat_img (the
// picture's path under storage/app/public, empty without
// one), alias (a slug made from the title), created (when
// it was published), created_by (bei_managers) and lang_id.
//
//   scopeLatestr — the newest articles of a language
//   latest_news  — the same, as plain arrays
//
// Used by:
//   - HomeController::index — the newest three, through
//     latest_news
//   - NewsController::index, content — the news page and
//     an article
//   - NewsExists::handle — the 404 for an unknown article
//   - Admin\NewsController — index, form, create, update,
//     delete, delete_picture
// -----------------------------------------------------------

class News extends Model
{

    // The table, which has no created_at / updated_at
    // columns (created is set by the panel); rememberTokenName
    // belongs to the account models, a plain model never
    // reads it
    protected $table = "bei_news";
    public $timestamps = false;
    protected $rememberTokenName = false;

    // The columns create() and fill() would accept; nothing
    // calls either on this model
    protected $fillable = [
        'id', 'title', 'content', 'created', 'created_by', 'lang_id', 'feat_img', 'alias'
    ];

    // Empty: toArray hands out every column
    protected $hidden = [
    ];

    // How the columns read back through the model; created
    // becomes a date, which toArray writes as ISO-8601 text
    // in UTC — the pages read it back through strtotime
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






    // -----------------------------------------------------------
    // scopeLatestr
    // -----------------------------------------------------------
    //
    // The query scope latestr: the articles of the language,
    // newest id first, at most limit of them. The trailing r
    // keeps the name clear of the query builder's own
    // latest(), which a scope called latest could never
    // override.
    //
    // Used by:
    //   - latest_news (below)
    // -----------------------------------------------------------

    public function scopeLatestr($query, $limit, $lang_id)
    {
        return $query->where(['lang_id'=>$lang_id])->take($limit)->orderByDesc('id');
    }






    // -----------------------------------------------------------
    // latest_news
    // -----------------------------------------------------------
    //
    // The newest limit articles of the language as plain
    // arrays — the columns a teaser needs (id, title,
    // feat_img, lang_id, created and alias), not the body.
    //
    // Used by:
    //   - HomeController::index — the home page's news
    // -----------------------------------------------------------

    public static function latest_news($limit, $lang_id)
    {
        return News::select("id", "title", "feat_img", "lang_id", "created", "alias")->latestr($limit, $lang_id)->get()->toArray();
    }
}
