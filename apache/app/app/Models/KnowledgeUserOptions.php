<?php

// -----------------------------------------------------------
//  [*] KnowledgeUserOptions — the options a member chose
//
//  One bei_users_knowledge_options row per option a member
//  chose while taking the knowledge test, with the option's
//  correct flag copied in. The attempt's result is the
//  number of correct rows over the questions the attempt
//  drew, so a question left without an answer counts as
//  wrong.
//
//  Used by:
//    - KnowledgeTestController (Http/Controllers/)
//    - Admin\ManagerMainController (Http/Controllers/Admin/)
// -----------------------------------------------------------


namespace App\Models;

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;








// -----------------------------------------------------------
// KnowledgeUserOptions
// -----------------------------------------------------------
//
// Columns: test_id and public_id (the attempt, in
// KnowledgeUserTest), user_id, question_id, cat_id (the
// question's category, KnowledgeTest::getCatId) and
// correct; which option was chosen is not kept, only
// whether it was right.
//
// Used by:
//   - KnowledgeTestController::save_answer — a row per
//     answered question
//   - KnowledgeTestController::calculate_results — the
//     attempt's correct rows, over the questions it drew
//   - Admin\ManagerMainController::knowledge_report — a
//     member's attempt, question by question
// -----------------------------------------------------------

class KnowledgeUserOptions extends Model
{

    // The table, which has no created_at / updated_at
    // columns; rememberTokenName belongs to the account
    // models, a plain model never reads it
    protected $table = "bei_users_knowledge_options";
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
