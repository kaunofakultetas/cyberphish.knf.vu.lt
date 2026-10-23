<?php

// -----------------------------------------------------------
//  [*] KnowledgeTest — the questions of the knowledge test
//
//  One bei_knowledge_test row per question of the final
//  knowledge test, filed under a learning-material category
//  (LMCategory), which ties it to a language. An attempt
//  draws, per category, as many random questions as
//  settings row 3 says; passing it unlocks the certificate.
//  The options live in KnowledgeOption.
//
//  Used by:
//    - KnowledgeTestController (Http/Controllers/)
//    - Admin\KnowledgeController, Admin\ManagerMainController,
//      Admin\LMController (Http/Controllers/Admin/)
// -----------------------------------------------------------


namespace App\Models;

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;








// -----------------------------------------------------------
// KnowledgeTest
// -----------------------------------------------------------
//
// Columns: cat_id (bei_lm_category) and question, the text.
//
//   getQuestion     — a question's text
//   getDecryptedId  — a question's id from the hash its page
//                     posts
//   getCatId        — a question's category
//   scopeAnswerable — the questions with an option to choose
//
// Used by:
//   - KnowledgeTestController — index (the number of a
//     language's questions), draw_or_resume (an attempt's
//     draw), progress, save_answer
//   - Admin\KnowledgeController — index, import_questions,
//     question_edit, question_update, question_delete
//   - Admin\ManagerMainController::knowledge_report — a
//     member's attempt, question by question
//   - Admin\LMController::categories_delete — a category
//     that still holds questions is not deleted
// -----------------------------------------------------------

class KnowledgeTest extends Model
{

    // The table, which has no created_at / updated_at
    // columns; rememberTokenName belongs to the account
    // models, a plain model never reads it
    protected $table = "bei_knowledge_test";
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
    // getQuestion
    // -----------------------------------------------------------
    //
    // The text of the question with that id, or null when
    // there is no such question.
    //
    // Used by:
    //   - KnowledgeTestController::progress — the next
    //     question of an attempt
    // -----------------------------------------------------------

    public static function getQuestion($id)
    {
        return KnowledgeTest::where(['id'=>$id])->get()->pluck('question')->first();
    }






    // -----------------------------------------------------------
    // getDecryptedId
    // -----------------------------------------------------------
    //
    // The id of the question a hash stands for, or null. The
    // question page posts the MD5 of the MD5 of the id
    // (knowledge_progress.blade.php builds it) and MySQL
    // hashes every row of the table to find the match. With
    // no salt, the hash hides the id from no one who cares
    // to compute it.
    //
    // Used by:
    //   - KnowledgeTestController::save_answer — the
    //     question an answer belongs to
    // -----------------------------------------------------------

    public static function getDecryptedId($hashed_id)
    {
        return KnowledgeTest::whereraw("MD5(MD5(id))=?", [$hashed_id])->get()->pluck('id')->first();
    }






    // -----------------------------------------------------------
    // getCatId
    // -----------------------------------------------------------
    //
    // The category of the question with that id, or null
    // when there is no such question.
    //
    // Used by:
    //   - KnowledgeTestController::save_answer — the cat_id
    //     of every saved answer
    // -----------------------------------------------------------

    public static function getCatId($id)
    {
        return KnowledgeTest::where(['id'=>$id])->get()->pluck('cat_id')->first();
    }






    // -----------------------------------------------------------
    // scopeAnswerable
    // -----------------------------------------------------------
    //
    // Narrows a query to the questions a member can answer:
    // those with at least one option. A question saved
    // without options — an import block with no option lines
    // — offers nothing to choose and would hold an attempt
    // open for good, so new attempts draw only these and an
    // open attempt passes over any other.
    //
    // Used by:
    //   - KnowledgeTestController::draw_or_resume — the draw
    //   - KnowledgeTestController::progress — whether the next
    //     question can be asked
    // -----------------------------------------------------------

    public function scopeAnswerable($query)
    {
        return $query->whereExists(function ($options) {
            $options->select(DB::raw(1))->from('bei_knowledge_option')->whereColumn('bei_knowledge_option.question_id', 'bei_knowledge_test.id');
        });
    }
}
