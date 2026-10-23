<?php

// -----------------------------------------------------------
//  [*] KnowledgeUserTest — the knowledge-test attempts
//
//  One bei_users_knowledge_test row per attempt at the
//  knowledge test. An attempt carries its own question set,
//  drawn when it starts, and closes with its result once
//  every question is answered; a result above 74 is a pass,
//  which unlocks the certificate. Its random public id is
//  what the attempt's URLs carry.
//
//  Used by:
//    - KnowledgeTestController, CertController
//      (Http/Controllers/)
//    - Admin\ManagerMainController (Http/Controllers/Admin/)
// -----------------------------------------------------------


namespace App\Models;

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;








// -----------------------------------------------------------
// KnowledgeUserTest
// -----------------------------------------------------------
//
// Columns: public_id, user_id, lang_id, finished (0 or 1),
// started, ended, question_set — a JSON object from
// question id to 0 (unanswered) or 1 (answered) — and
// results, the percentage of the drawn questions answered
// correctly; a question left without an answer counts as
// wrong.
//
//   getPrivateId — an attempt's id from its public id
//   getQIds      — an attempt's question set
//
// Used by:
//   - KnowledgeTestController — index, draw_or_resume,
//     gen_test_id, progress, save_answer, mark_answered,
//     calculate_results, knowledge_results
//   - CertController::download — the passed attempt and its
//     date
//   - Admin\ManagerMainController — knowledge_results,
//     knowledge_report
// -----------------------------------------------------------

class KnowledgeUserTest extends Model
{

    // The table, which has no created_at / updated_at
    // columns; rememberTokenName belongs to the account
    // models, a plain model never reads it
    protected $table = "bei_users_knowledge_test";
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
    // getPrivateId
    // -----------------------------------------------------------
    //
    // The id of the attempt with that public id, or null when
    // there is none. It goes by the public id alone, whoever
    // the attempt belongs to.
    //
    // Used by:
    //   - nothing calls this at the moment —
    //     KnowledgeTestController::progress and save_answer
    //     look the attempt up together with its member
    // -----------------------------------------------------------

    public static function getPrivateId($public_id)
    {
        return KnowledgeUserTest::where(['public_id'=>$public_id])->get()->pluck('id')->first();
    }






    // -----------------------------------------------------------
    // getQIds
    // -----------------------------------------------------------
    //
    // The attempt's question set decoded into an array, from
    // question id to 0 (unanswered) or 1 (answered); null for
    // an unknown attempt, which progress has answered with a
    // 404 before it asks — calculate_results runs only behind
    // progress's check.
    //
    // Used by:
    //   - KnowledgeTestController::progress — the next
    //     unanswered question
    //   - KnowledgeTestController::calculate_results — the
    //     number of questions the attempt drew
    // -----------------------------------------------------------

    public static function getQIds($id)
    {
        return json_decode(KnowledgeUserTest::where(['id'=>$id])->get()->pluck('question_set')->first(), 1);
    }
}
