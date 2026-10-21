<?php

// -----------------------------------------------------------
//  [*] LearningDefectsTest — known defects of learning, pinned
//
//  Each test states the behaviour the learning material
//  should have and pins the defect that breaks it today
//  (KnownDefects): the run reports them as incomplete,
//  tagged with their ids, until a fix makes one pass — then
//  the test fails as the cue to remove its pin and move it
//  into its home file.
//
//    selfeval-foreign-options — a self-evaluation scores any
//                               option ids posted for a
//                               question, as often as posted
//    course-progress-selfeval — the progress counts finished
//                               self-evaluations against the
//                               number of questions
//
//  Used by:
//    - PHPUnit — the Feature suite (runTests.sh)
// -----------------------------------------------------------


namespace Tests\Feature\Learning;

use App\Models\LMUsers;
use Illuminate\Support\Facades\DB;
use Tests\FeatureTestCase;
use Tests\Support\Flows;








// -----------------------------------------------------------
// LearningDefectsTest
// -----------------------------------------------------------
//
// One pinned defect per test, named in the header; English
// material and a member for every test.
//
// Used by:
//   - PHPUnit — the Feature suite (runTests.sh)
// -----------------------------------------------------------

class LearningDefectsTest extends FeatureTestCase
{

    // Taking a self-evaluation the way a member does
    use Flows;

    // The English id and the member of the test running now
    private $en;
    private $member;






    // -----------------------------------------------------------
    // setUp
    // -----------------------------------------------------------
    //
    // English and a member.
    //
    // Used by:
    //   - PHPUnit, before every test
    // -----------------------------------------------------------

    protected function setUp(): void
    {
        parent::setUp();
        $this->en = $this->world->language('en');
        $this->member = $this->world->member();
    }






    // -----------------------------------------------------------
    // testSelfEvaluationNeverScoresAboveItsMaximum
    // -----------------------------------------------------------
    //
    // LMController::self_eval_test_save stores every option id
    // posted for a question — any option of any question, as
    // many times as it comes — and FinishedSe adds the
    // question's best points for each correct row. Two
    // questions worth 100 each cannot honestly make more than
    // 200, yet posting a correct option three times for the
    // first one makes 400, and the public ranking shows it.
    // Wanted: the score stays within 200.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testSelfEvaluationNeverScoresAboveItsMaximum(): void
    {
        $this->world->setting(2, 'self_eval_pertime', null, null, 2);
        $category = $this->world->lmCategory($this->en);
        $first = $this->world->selfEvalQuestion($category, [['Right', true, 100], ['Wrong', false, 0]]);
        $second = $this->world->selfEvalQuestion($category, [['Right', true, 100], ['Wrong', false, 0]]);
        $publicId = $this->startSelfEvaluation($this->member, $category);
        $correct = $first['options'][0];
        $this->answerSelfEvaluation($this->member, $publicId, $category, $first['question'], [$correct, $correct, $correct]);
        $this->answerSelfEvaluation($this->member, $publicId, $category, $second['question'], [$second['options'][0]]);
        $this->signInMember($this->member)->get("/cp/se/$publicId/$category");

        $this->pinDefect('selfeval-foreign-options', function () use ($publicId) {
            $points = DB::table('bei_users_selfeval_test')->where('public_id', $publicId)->value('points');
            $this->assertLessThanOrEqual(200, $points, 'the attempt scored more than its questions are worth');
        });
    }






    // -----------------------------------------------------------
    // testFinishedSelfEvaluationsCountTheirFullShare
    // -----------------------------------------------------------
    //
    // bei_user_course_progress divides the number of finished
    // self-evaluations by the number of self-evaluation
    // QUESTIONS of the language, though one self-evaluation
    // asks several questions: a member who finished the only
    // category's self-evaluation — two questions — gets half
    // of the share, and with the seeded five questions a test
    // a fifth. Wanted: completed lessons (25) plus the full
    // self-evaluation share (20).
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testFinishedSelfEvaluationsCountTheirFullShare(): void
    {
        $this->world->setting(2, 'self_eval_pertime', null, null, 2);
        $category = $this->world->lmCategory($this->en);
        $this->world->lessonDone($this->member->id, $this->world->lmContent($category, $this->en));
        $this->world->scenario($this->en);
        $first = $this->world->selfEvalQuestion($category, [['Right', true, 100], ['Wrong', false, 0]]);
        $second = $this->world->selfEvalQuestion($category, [['Right', true, 100], ['Wrong', false, 0]]);
        $publicId = $this->startSelfEvaluation($this->member, $category);
        $this->answerSelfEvaluation($this->member, $publicId, $category, $first['question'], [$first['options'][0]]);
        $this->answerSelfEvaluation($this->member, $publicId, $category, $second['question'], [$second['options'][0]]);
        $this->signInMember($this->member)->get("/cp/se/$publicId/$category");

        $this->pinDefect('course-progress-selfeval', function () {
            $this->assertEquals(45, LMUsers::course_progress($this->member->id, $this->en));
        });
    }
}
