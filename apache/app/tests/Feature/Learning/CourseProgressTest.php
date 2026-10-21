<?php

// -----------------------------------------------------------
//  [*] CourseProgressTest — the progress bar of a member
//
//  The course progress every member page shows comes from
//  the stored function bei_user_course_progress of
//  mysql/init.sql, through LMUsers::course_progress: a
//  quarter for the hours of completed lessons, a fifth for
//  finished self-evaluations, 30 points for a passed
//  knowledge test (75 or more) and a quarter for the
//  simulations finished, per language. These tests run the
//  real function on MySQL 5.7. The function's NULL parts
//  (a language without self-evaluation questions or
//  simulations, a member with no completed lesson) make the
//  whole sum NULL, which the helper turns into 0, so every
//  scene here has both. The miscounted self-evaluation
//  share is pinned in LearningDefectsTest.
//
//  Used by:
//    - PHPUnit — the Feature suite (runTests.sh)
// -----------------------------------------------------------


namespace Tests\Feature\Learning;

use App\Models\LMUsers;
use Tests\FeatureTestCase;
use Tests\Support\Flows;








// -----------------------------------------------------------
// CourseProgressTest
// -----------------------------------------------------------
//
// English material with two one-hour lessons, a category
// with one self-evaluation question (a self-evaluation one
// question long), and one simulation, for every test.
//
// Used by:
//   - PHPUnit — the Feature suite (runTests.sh)
// -----------------------------------------------------------

class CourseProgressTest extends FeatureTestCase
{

    // Finishing a self-evaluation the way a member does
    use Flows;

    // The English id, the two lessons, the category with its
    // question, the simulation and the member of the test
    // running now
    private $en;
    private $lessons;
    private $category;
    private $question;
    private $scenario;
    private $member;






    // -----------------------------------------------------------
    // setUp
    // -----------------------------------------------------------
    //
    // The material every scene shares.
    //
    // Used by:
    //   - PHPUnit, before every test
    // -----------------------------------------------------------

    protected function setUp(): void
    {
        parent::setUp();
        $this->en = $this->world->language('en');
        $this->world->setting(2, 'self_eval_pertime', null, null, 1);
        $this->category = $this->world->lmCategory($this->en);
        $this->lessons = [
            $this->world->lmContent($this->category, $this->en),
            $this->world->lmContent($this->category, $this->en),
        ];
        $this->question = $this->world->selfEvalQuestion($this->category, [['Right', true, 100], ['Wrong', false, 0]]);
        $this->scenario = $this->world->scenario($this->en);
        $this->member = $this->world->member();
    }






    // -----------------------------------------------------------
    // testNewMemberHasNoProgress
    // -----------------------------------------------------------
    //
    // Nothing done is 0.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testNewMemberHasNoProgress(): void
    {
        $this->assertEquals(0, $this->progress());
    }






    // -----------------------------------------------------------
    // testLessonHoursMakeAQuarter
    // -----------------------------------------------------------
    //
    // One hour of two completed is half of the lessons' 25:
    // the hours column is a FLOAT, so the share is the double
    // 12.5, which MySQL rounds to the even 12 when the function
    // returns its INT. Both hours are the full 25.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testLessonHoursMakeAQuarter(): void
    {
        $this->world->lessonDone($this->member->id, $this->lessons[0]);
        $this->assertEquals(12, $this->progress());

        $this->world->lessonDone($this->member->id, $this->lessons[1]);
        $this->assertEquals(25, $this->progress());
    }






    // -----------------------------------------------------------
    // testPassedKnowledgeTestAddsThirty
    // -----------------------------------------------------------
    //
    // A finished knowledge test with 75 or more adds 30; one
    // below that adds nothing.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testPassedKnowledgeTestAddsThirty(): void
    {
        $this->completeLessons();
        $this->world->knowledgeAttempt($this->member->id, $this->en, ['results' => 74]);
        $this->assertEquals(25, $this->progress());

        $this->world->knowledgeAttempt($this->member->id, $this->en, ['results' => 75]);
        $this->assertEquals(55, $this->progress());
    }






    // -----------------------------------------------------------
    // testFinishedSimulationsMakeAQuarter
    // -----------------------------------------------------------
    //
    // Every simulation of the language finished adds 25; an
    // unfinished run adds nothing.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testFinishedSimulationsMakeAQuarter(): void
    {
        $this->completeLessons();
        $this->world->scenarioAttempt($this->member->id, $this->scenario, $this->en, ['finished' => 0]);
        $this->assertEquals(25, $this->progress());

        $this->world->scenarioAttempt($this->member->id, $this->scenario, $this->en);
        $this->assertEquals(50, $this->progress());
    }






    // -----------------------------------------------------------
    // testEverythingDoneIsAHundred
    // -----------------------------------------------------------
    //
    // Lessons, the self-evaluation (taken through the real
    // flow), a passed knowledge test and the simulation add up
    // to 100.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testEverythingDoneIsAHundred(): void
    {
        $this->completeLessons();
        $publicId = $this->startSelfEvaluation($this->member, $this->category);
        $this->answerSelfEvaluation($this->member, $publicId, $this->category, $this->question['question'], [$this->question['options'][0]]);
        $this->signInMember($this->member)->get("/cp/se/$publicId/{$this->category}");
        $this->world->knowledgeAttempt($this->member->id, $this->en, ['results' => 90]);
        $this->world->scenarioAttempt($this->member->id, $this->scenario, $this->en);

        $this->assertEquals(100, $this->progress());
    }






    // -----------------------------------------------------------
    // testProgressIsPerLanguage
    // -----------------------------------------------------------
    //
    // Work done in English counts nothing towards the
    // Lithuanian course.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testProgressIsPerLanguage(): void
    {
        $lt = $this->world->language('lt');
        $ltCategory = $this->world->lmCategory($lt);
        $this->world->lmContent($ltCategory, $lt);
        $this->world->selfEvalQuestion($ltCategory, [['Taip', true, 100]]);
        $this->world->scenario($lt);
        $this->completeLessons();

        $this->assertEquals(0, LMUsers::course_progress($this->member->id, $lt));
    }






    // -----------------------------------------------------------
    // completeLessons
    // -----------------------------------------------------------
    //
    // Marks both lessons done for the member.
    //
    // Used by:
    //   - testPassedKnowledgeTestAddsThirty,
    //     testFinishedSimulationsMakeAQuarter,
    //     testEverythingDoneIsAHundred, testProgressIsPerLanguage
    //     (above)
    // -----------------------------------------------------------

    private function completeLessons(): void
    {
        foreach ($this->lessons as $lesson) {
            $this->world->lessonDone($this->member->id, $lesson);
        }
    }






    // -----------------------------------------------------------
    // progress
    // -----------------------------------------------------------
    //
    // The member's English course progress, as member pages
    // get it, as a whole number of percent.
    //
    // Used by:
    //   - every test of this class but testProgressIsPerLanguage,
    //     which reads another language
    // -----------------------------------------------------------

    private function progress(): int
    {
        return (int) LMUsers::course_progress($this->member->id, $this->en);
    }
}
