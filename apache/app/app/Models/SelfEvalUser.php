<?php

// -----------------------------------------------------------
//  [*] SelfEvalUser — the options a member chose
//
//  One bei_users_selfeval row per option a member chose in a
//  self-evaluation, with the option's correct flag and
//  points copied in. The distinct questions among an
//  attempt's rows tell how far it has got.
//
//  Used by:
//    - LMController (Http/Controllers/)
//    - FinishedSe (Http/Middleware/)
//    - Admin\ManagerMainController (Http/Controllers/Admin/)
// -----------------------------------------------------------


namespace App\Models;

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;








// -----------------------------------------------------------
// SelfEvalUser
// -----------------------------------------------------------
//
// Columns: test_id and public_id (the attempt, in
// SelfEvalUserTest), cat_id, user_id, question_id,
// option_id (bei_selfeval_option), correct and points.
//
// Used by:
//   - LMController::self_eval_test — the questions answered
//     so far
//   - LMController::self_eval_test_save — a row per chosen
//     option
//   - LMController::self_eval_test_results — the answers
//     behind the results page
//   - FinishedSe::handle, calucate_points — when the attempt
//     is complete, and its score
//   - Admin\ManagerMainController::self_evaluation_history,
//     question_correct — a member's self-evaluations
// -----------------------------------------------------------

class SelfEvalUser extends Model
{

    // The table, which has no created_at / updated_at
    // columns; rememberTokenName belongs to the account
    // models, a plain model never reads it
    protected $table = "bei_users_selfeval";
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
