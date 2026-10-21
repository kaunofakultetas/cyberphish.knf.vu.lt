<?php

// -----------------------------------------------------------
//  [*] SelfEvalOption — the options of a self-evaluation
//
//  One bei_selfeval_option row per answer option of a
//  self-evaluation question (SelfEval): the text, whether it
//  is right, and the points it is worth. The panel's
//  spreadsheet import writes them with their questions.
//
//  Used by:
//    - LMController (Http/Controllers/)
//    - FinishedSe (Http/Middleware/)
//    - Admin\SelfevalController, Admin\ManagerMainController
//      (Http/Controllers/Admin/)
// -----------------------------------------------------------


namespace App\Models;

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;








// -----------------------------------------------------------
// SelfEvalOption
// -----------------------------------------------------------
//
// Columns: question_id (bei_selfeval_question), option (the
// text), correct (1 right, 0 wrong) and points. Scoring
// takes the question's highest points: each right choice
// adds them, each wrong one subtracts them
// (FinishedSe::calucate_points).
//
// Used by:
//   - LMController — self_eval_test (the options, shuffled),
//     self_eval_test_save (each choice's correct flag and
//     points), self_eval_test_results
//   - FinishedSe::calucate_points — the score
//   - Admin\SelfevalController — import_questions,
//     question_edit, question_update, question_delete
//   - Admin\ManagerMainController::question_correct — whether
//     a member chose exactly the right options
// -----------------------------------------------------------

class SelfEvalOption extends Model
{

    // The table, which has no created_at / updated_at
    // columns; rememberTokenName belongs to the account
    // models, a plain model never reads it
    protected $table = "bei_selfeval_option";
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
