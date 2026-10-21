<?php

// -----------------------------------------------------------
//  [*] SelfEval — the self-evaluation questions
//
//  One bei_selfeval_question row per self-evaluation
//  question. Questions are filed under the learning-material
//  categories (LMCategory), and a self-evaluation asks a
//  category's questions in random order, as many as
//  settings row 2 says. The options live in SelfEvalOption.
//
//  Used by:
//    - LMController (Http/Controllers/)
//    - Admin\SelfevalController (Http/Controllers/Admin/)
// -----------------------------------------------------------


namespace App\Models;

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;








// -----------------------------------------------------------
// SelfEval
// -----------------------------------------------------------
//
// Columns: cat_id (bei_lm_category), question (the text)
// and q_type — 1 for a single-choice question (radio
// buttons), 2 for a multiple-choice one (check boxes).
//
// Used by:
//   - LMController — self_eval_test (the next question),
//     self_eval_test_save (the answered question, found by
//     its hashed id), self_eval_test_results
//   - Admin\SelfevalController — index, import_questions,
//     question_edit, question_update, question_delete
// -----------------------------------------------------------

class SelfEval extends Model
{

    // The table, which has no created_at / updated_at
    // columns; rememberTokenName belongs to the account
    // models, a plain model never reads it
    protected $table = "bei_selfeval_question";
    public $timestamps = false;
    protected $rememberTokenName = false;

    // Empty: nothing mass-assigns this model
    protected $fillable = [
    ];

    // Empty: toArray hands out every column
    protected $hidden = [
    ];

    // Empty: every column comes back uncast
    protected $casts = [
    ];
}
