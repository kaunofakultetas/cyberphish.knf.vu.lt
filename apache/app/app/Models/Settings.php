<?php

// -----------------------------------------------------------
//  [*] Settings — the site settings, one row per setting
//
//  bei_settings keeps each setting in a row with a fixed id,
//  and the code reads the rows by those ids: 1 is the about
//  text of the home page, 2 the number of questions a
//  self-evaluation asks, 3 the number of questions the
//  knowledge test draws per category. The admin panel's
//  settings page edits all three.
//
//  Used by:
//    - HomeController, KnowledgeTestController
//      (Http/Controllers/)
//    - Admin\SiteSettingsController (Http/Controllers/Admin/)
//    - SelfEval (this folder)
// -----------------------------------------------------------


namespace App\Models;

use Illuminate\Database\Eloquent\Model;








// -----------------------------------------------------------
// Settings
// -----------------------------------------------------------
//
// Columns: name and three value slots. Row 1 keeps a JSON
// object in val1 — per language id, a description and a
// link; row 2 keeps its number in val3; row 3 keeps a JSON
// object in val1 — per language id, per category id, the
// number of questions. The settings page saves with an
// UPDATE, so mysql/init.sql seeds all three rows — 1 and 3
// empty until the page fills them in.
//
// Used by:
//   - HomeController::index — row 1
//   - SelfEval::attempt_length — row 2, for FinishedSe
//     and LMController::self_eval_test
//   - KnowledgeTestController::draw_or_resume — row 3
//   - Admin\SiteSettingsController — other, self_eval_save,
//     about_us_save, knowledge_test_settings_save
// -----------------------------------------------------------

class Settings extends Model
{

    // The table, which has no created_at / updated_at
    // columns; rememberTokenName belongs to the account
    // models, a plain model never reads it
    protected $table = "bei_settings";
    public $timestamps = false;
    protected $rememberTokenName = false;

    // The columns create() and fill() would accept; nothing
    // calls either on this model
    protected $fillable = [
        'id', 'name', 'val1', 'val2', 'val3'
    ];

    // Empty: toArray hands out every column
    protected $hidden = [
    ];

    // How the columns read back through the model
    protected $casts = [
        'id'=>'integer',
        'name'=>'string',
        'val1'=>'string',
        'val2'=>'string',
        'val3'=>'integer'
    ];
}
