<?php

// -----------------------------------------------------------
//  [*] SelfEvalUserTest — the members' self-evaluations
//
//  One bei_users_selfeval_test row per self-evaluation a
//  member starts in a category. It closes, with its score,
//  once the member has answered as many questions as
//  settings row 2 asks for. Its random public id is what
//  the self-evaluation URLs carry.
//
//  Used by:
//    - LMController (Http/Controllers/)
//    - FinishedSe, R_Exists (Http/Middleware/)
//    - Admin\ManagerMainController (Http/Controllers/Admin/)
// -----------------------------------------------------------


namespace App\Models;

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;








// -----------------------------------------------------------
// SelfEvalUserTest
// -----------------------------------------------------------
//
// Columns: public_id, cat_id (bei_lm_category), user_id,
// lang_id, finished (0 or 1), started, ended and points.
// The best finished score per member and category feeds
// the ranking (ViewMaxPointsSelfEvalCat).
//
// The model defines no gen_test_id, though
// LMController::gen_test_id retries through
// SelfEvalUserTest::gen_test_id when a fresh public id is
// already taken: Eloquent hands the unknown name to the
// query builder, which throws.
//
// Used by:
//   - LMController — self_evaluation_test, self_eval_test,
//     self_eval_test_save, gen_test_id,
//     self_eval_test_results, self_evaluation_history
//   - FinishedSe::handle — closes the attempt
//   - R_Exists::handle — the se_public_unfinished check: the
//     attempt exists and is the member's
//   - Admin\ManagerMainController::self_evaluation_history
// -----------------------------------------------------------

class SelfEvalUserTest extends Model
{

    // The table, which has no created_at / updated_at
    // columns; rememberTokenName belongs to the account
    // models, a plain model never reads it
    protected $table = "bei_users_selfeval_test";
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
