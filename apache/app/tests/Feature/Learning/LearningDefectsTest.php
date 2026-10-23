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
//    course-progress-selfeval — the progress counts finished
//                               self-evaluations against the
//                               number of questions
//    course-progress-null     — one share the progress
//                               cannot work out turns it all
//                               into 0
//
//  Both live in the stored function bei_user_course_progress
//  of the database, not in the app: the fix is a change to
//  the production database, or a progress worked out in PHP.
//
//  Used by:
//    - PHPUnit — the Feature suite (runTests.sh)
// -----------------------------------------------------------


namespace Tests\Feature\Learning;

use App\Models\LMUsers;
use Tests\FeatureTestCase;
use Tests\Support\Flows;








// -----------------------------------------------------------
// LearningDefectsTest
// -----------------------------------------------------------
//
// One pinned defect per test, named in the header; English
// and a member for every test.
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






    // -----------------------------------------------------------
    // testLanguageWithoutSimulationsStillShowsTheProgress
    // -----------------------------------------------------------
    //
    // The stored function bei_user_course_progress of
    // mysql/init.sql works out three of its shares as
    // divisions and adds all four up unguarded: a share it
    // cannot work out — in a language without simulations
    // or without self-evaluation questions, for a member who
    // completed no lesson — is NULL, the whole sum with it,
    // and LMUsers::course_progress hands that out as 0
    // whatever else is done. Here the member completed every
    // lesson of a language that has a self-evaluation
    // question but no simulation. Wanted: the lessons' 25.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testLanguageWithoutSimulationsStillShowsTheProgress(): void
    {
        $category = $this->world->lmCategory($this->en);
        $this->world->lessonDone($this->member->id, $this->world->lmContent($category, $this->en));
        $this->world->selfEvalQuestion($category, [['Right', true, 100], ['Wrong', false, 0]]);

        $this->pinDefect('course-progress-null', function () {
            $this->assertEquals(25, LMUsers::course_progress($this->member->id, $this->en));
        });
    }
}
