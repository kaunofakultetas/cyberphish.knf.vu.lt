<?php

// -----------------------------------------------------------
//  [*] KnowledgeUserOptions — the options a member chose
//
//  One bei_users_knowledge_options row per option a member
//  chose while taking the knowledge test, with the option's
//  correct flag copied in. The attempt's result is the share
//  of these rows that are correct.
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
// KnowledgeUserTest), user_id, question_id, cat_id and
// correct; which option was chosen is not kept, only
// whether it was right. cat_id should be the question's
// category, but the attempt's id is what progress_save
// looks the category up by — see KnowledgeTest::getCatId
// (pinned as knowledge-category-lookup).
//
// Used by:
//   - KnowledgeTestController::progress_save — a row per
//     chosen option
//   - KnowledgeTestController::calculate_results — correct
//     rows over all rows of the attempt
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
