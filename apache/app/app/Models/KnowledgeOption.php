<?php

// -----------------------------------------------------------
//  [*] KnowledgeOption — the options of a knowledge question
//
//  The answer options of the knowledge-test questions
//  (KnowledgeTest), one bei_knowledge_option row each: the
//  text the member picks and whether it is a right answer.
//  The panel's plain-text import writes them together with
//  their questions.
//
//  Used by:
//    - KnowledgeTestController (Http/Controllers/)
//    - Admin\KnowledgeController, Admin\ManagerMainController
//      (Http/Controllers/Admin/)
// -----------------------------------------------------------


namespace App\Models;

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;








// -----------------------------------------------------------
// KnowledgeOption
// -----------------------------------------------------------
//
// Columns: question_id (bei_knowledge_test), option — the
// text shown — and correct, 1 for a right answer and 0
// otherwise. When a member answers, KnowledgeUserOptions
// keeps each chosen option's correct flag, not the option
// itself.
//
// Used by:
//   - KnowledgeTestController::progress — the options of the
//     next question, shuffled
//   - KnowledgeTestController::progress_save — whether each
//     chosen option is correct
//   - Admin\KnowledgeController — import_questions,
//     question_edit, question_update, question_delete
//   - Admin\ManagerMainController::knowledge_report — a
//     member's attempt, question by question
// -----------------------------------------------------------

class KnowledgeOption extends Model
{

    // The table, which has no created_at / updated_at
    // columns; rememberTokenName belongs to the account
    // models, a plain model never reads it
    protected $table = "bei_knowledge_option";
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
