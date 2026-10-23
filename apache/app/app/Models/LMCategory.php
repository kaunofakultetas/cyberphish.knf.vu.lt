<?php

// -----------------------------------------------------------
//  [*] LMCategory — the categories of the learning material
//
//  One bei_lm_category row per category, in one language.
//  More than the lessons hang off it: the self-evaluation
//  questions and the knowledge-test questions are filed
//  under these categories too, and settings row 3 sets how
//  many knowledge questions each category gives.
//
//  Used by:
//    - LMController, KnowledgeTestController
//      (Http/Controllers/)
//    - Admin\LMController, Admin\KnowledgeController,
//      Admin\SelfevalController, Admin\SiteSettingsController,
//      Admin\LanguagesController (Http/Controllers/Admin/)
//    - R_Exists (Http/Middleware/)
// -----------------------------------------------------------


namespace App\Models;

use Illuminate\Database\Eloquent\Model;








// -----------------------------------------------------------
// LMCategory
// -----------------------------------------------------------
//
// Columns: name and lang_id (bei_lang). The table is
// utf8mb4 (mysql/init.sql:105), so category names keep
// every letter. A lesson whose category is deleted keeps
// no category (the foreign key sets its cat_id to NULL).
//
// Used by:
//   - LMController — category_content_list (the lesson
//     menu), check_if_all_presentations_finished,
//     self_eval_test, self_eval_test_results
//   - KnowledgeTestController::get_lang_cats — the
//     categories of a language
//   - R_Exists::handle — the lm_cat check
//   - Admin\LMController — form, categories,
//     categories_form, categories_create, categories_update,
//     categories_delete
//   - Admin\KnowledgeController::index,
//     Admin\SelfevalController::index,
//     Admin\SiteSettingsController::other — the category
//     lists of those pages
//   - Admin\LanguagesController::delete — a language that
//     still has categories is not deleted
// -----------------------------------------------------------

class LMCategory extends Model
{

    // The table, which has no created_at / updated_at
    // columns; rememberTokenName belongs to the account
    // models, a plain model never reads it
    protected $table = "bei_lm_category";
    public $timestamps = false;
    protected $rememberTokenName = false;

    // The columns create() and fill() would accept; nothing
    // calls either on this model
    protected $fillable = [
        'id', 'name'
    ];

    // Empty: toArray hands out every column
    protected $hidden = [
    ];

    // How the columns read back through the model; lang_id
    // stays uncast
    protected $casts = [
        'id'=>'integer',
        'name'=>'string',
    ];
}
