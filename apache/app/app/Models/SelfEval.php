<?php

// -----------------------------------------------------------
//  [*] SelfEval — the self-evaluation questions
//
//  One bei_selfeval_question row per self-evaluation
//  question. Questions are filed under the learning-material
//  categories (LMCategory), and a self-evaluation asks a
//  category's questions in random order, as many as
//  settings row 2 says, or all of them in a category that
//  has fewer. The options live in SelfEvalOption.
//
//  Used by:
//    - LMController (Http/Controllers/)
//    - FinishedSe (Http/Middleware/)
//    - Admin\SelfevalController, Admin\LMController
//      (Http/Controllers/Admin/)
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
//   scopeUsable    — the questions a member can answer
//   attempt_length — how many questions an attempt asks
//
// Used by:
//   - LMController — self_eval_test (the next question),
//     self_eval_test_save (the answered question, found by
//     its hashed id), self_eval_test_results
//   - FinishedSe::handle, LMController::self_eval_test —
//     attempt_length, the number an attempt closes at and
//     its progress bar counts towards
//   - Admin\SelfevalController — index, import_questions,
//     question_edit, question_update, question_delete
//   - Admin\LMController::categories_delete — a category
//     that still holds questions is not deleted
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






    // -----------------------------------------------------------
    // scopeUsable
    // -----------------------------------------------------------
    //
    // Narrows a query to the questions a member can answer:
    // a known type, 1 or 2, and at least one option. A
    // question saved without them would come up with no
    // answer button and hold its attempt open for good, so
    // the test page draws only these, and an attempt's length
    // counts only these when a category has fewer than the
    // setting.
    //
    // Used by:
    //   - LMController::self_eval_test — the next question
    //   - attempt_length (below) — the category's number of
    //     answerable questions
    // -----------------------------------------------------------

    public function scopeUsable($query)
    {
        return $query->whereIn('q_type', [1, 2])->whereExists(function ($options) {
            $options->select(DB::raw(1))->from('bei_selfeval_option')->whereColumn('bei_selfeval_option.question_id', 'bei_selfeval_question.id');
        });
    }






    // -----------------------------------------------------------
    // attempt_length
    // -----------------------------------------------------------
    //
    // How many questions a self-evaluation of the category
    // asks: val3 of settings row 2, or the category's
    // answerable questions (scopeUsable) when it has fewer.
    // One number for the close and the page, so the progress
    // bar is full when the attempt closes, also in a category
    // shorter than the setting.
    //
    // Used by:
    //   - FinishedSe::handle — when to close an attempt
    //   - LMController::self_eval_test — the progress bar
    // -----------------------------------------------------------

    public static function attempt_length($cat_id)
    {
        $length = (int)Settings::where(['id'=>2])->pluck('val3')->first();

        // A category with fewer answerable questions runs out
        // first: its last one ends the attempt
        $in_category = SelfEval::where(['cat_id'=>$cat_id])->usable()->count();

        if($in_category > 0 && $in_category < $length){
            $length = $in_category;
        }

        return $length;
    }
}
