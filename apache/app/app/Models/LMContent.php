<?php

// -----------------------------------------------------------
//  [*] LMContent — the lessons of the learning material
//
//  One bei_lm_content row per lesson: a title and an HTML
//  body in a category of one language, plus the hours the
//  lesson counts for in the course progress. A member marks
//  a lesson completed from its page (LMUsers keeps that);
//  the files attached to it live in LMFiles.
//
//  Used by:
//    - LMController, Admin\LMController (Http/Controllers/)
//    - LMExists (Http/Middleware/)
// -----------------------------------------------------------


namespace App\Models;

use Illuminate\Database\Eloquent\Model;








// -----------------------------------------------------------
// LMContent
// -----------------------------------------------------------
//
// Columns: cat_id (bei_lm_category; NULL once the category
// is deleted), title, content (the HTML body), hours and
// lang_id. The course progress weighs a member's completed
// lessons by their hours against all hours of the language.
//
//   get_id — a lesson's id from the hash its page posts
//
// Used by:
//   - LMController — content (the lesson page),
//     category_content_list (the lesson menu),
//     mark_completed, check_if_all_presentations_finished,
//     show_self_eval
//   - LMExists::handle — the 404 for an unknown lesson
//   - Admin\LMController — index, form, create, update,
//     delete, additional_files
// -----------------------------------------------------------

class LMContent extends Model
{

    // The table, which has no created_at / updated_at
    // columns; rememberTokenName belongs to the account
    // models, a plain model never reads it
    protected $table = "bei_lm_content";
    public $timestamps = false;
    protected $rememberTokenName = false;

    // The columns create() and fill() would accept; nothing
    // calls either on this model
    protected $fillable = [
        'id', 'cat_id', 'title', 'content', 'hours', 'lang_id'
    ];

    // Empty: toArray hands out every column
    protected $hidden = [
    ];

    // How the columns read back through the model. hours goes
    // uncast, as the driver returns it, as it always did: its
    // entry named the cast type 'number', which no cast is —
    // Laravel 7 ignored it, Laravel 13 throws on it — so the
    // migration dropped it
    protected $casts = [
        'id'=>'integer',
        'cat_id'=>'integer',
        'title'=>'string',
        'content'=>'string',
        'lang_id'=>'integer'
    ];






    // -----------------------------------------------------------
    // get_id
    // -----------------------------------------------------------
    //
    // The id of the lesson a hash stands for. The lesson
    // page's completed button posts the MD5 of the MD5 of
    // APP_SALT followed by the id (lm-content.blade.php
    // builds it), so the form never shows the id; MySQL
    // hashes every row of the table to find the match.
    // Answers false when no lesson matches — and for every
    // lesson while APP_SALT is unset, since CONCAT with NULL
    // gives NULL.
    //
    // Used by:
    //   - LMController::mark_completed — a lesson marked
    //     completed
    // -----------------------------------------------------------

    public static function get_id($hash)
    {
        $data = LMContent::select('id')->whereRaw("MD5(MD5(CONCAT(?, id))) = ?", [env('APP_SALT'), $hash])->get()->pluck('id')->first();

        if(is_numeric($data) && $data > 0){
            return $data;
        } else {
            return false;
        }
    }
}
