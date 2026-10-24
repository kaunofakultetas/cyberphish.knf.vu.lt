<?php

// -----------------------------------------------------------
//  [*] CourseProgressTest — the progress bar of a member
//
//  The course progress every member page shows comes from
//  the stored function bei_user_course_progress of
//  mysql/init.sql, through LMUsers::course_progress: a
//  quarter for the hours of completed lessons — each
//  counted once, and only the lessons filed under a
//  category — a fifth for the self-evaluations — one per
//  category, counted once however often taken — 30 points
//  for a passed knowledge test (75 or more) and a quarter
//  for the simulations finished, per language. A share the
//  language does not offer, or the member has not begun,
//  counts 0 and leaves the others standing. These tests
//  run the real function on MySQL 5.7.
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
// CourseProgressTest
// -----------------------------------------------------------
//
// English material with two one-hour lessons, a category
// with one self-evaluation question (a self-evaluation one
// question long), and one simulation on offer, in a
// category, for every test.
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
        $this->scenario = $this->world->scenario($this->en, [], [$this->world->scenarioCategory($this->en)]);
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
        $this->finishSelfEvaluation([$this->question]);
        $this->world->knowledgeAttempt($this->member->id, $this->en, ['results' => 90]);
        $this->world->scenarioAttempt($this->member->id, $this->scenario, $this->en);

        $this->assertEquals(100, $this->progress());
    }






    // -----------------------------------------------------------
    // testFinishedSelfEvaluationsCountTheirFullShare
    // -----------------------------------------------------------
    //
    // A self-evaluation two questions long, finished once,
    // earns the whole fifth on top of the lessons' quarter,
    // and finishing it again adds nothing. The function once
    // divided the finished attempts by the number of
    // questions, which gave half the share here and made
    // every repeat count.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testFinishedSelfEvaluationsCountTheirFullShare(): void
    {
        $this->world->setting(2, 'self_eval_pertime', null, null, 2);
        $second = $this->world->selfEvalQuestion($this->category, [['Right', true, 100], ['Wrong', false, 0]]);
        $this->completeLessons();

        $this->finishSelfEvaluation([$this->question, $second]);
        $this->assertEquals(45, $this->progress());

        $this->finishSelfEvaluation([$this->question, $second]);
        $this->assertEquals(45, $this->progress());
    }






    // -----------------------------------------------------------
    // testPassedKnowledgeTestCountsWithoutLessons
    // -----------------------------------------------------------
    //
    // A member who completed no lesson has no lesson hours to
    // add up, a share of 0 that no longer takes the rest down
    // with it: a passed knowledge test alone shows its 30.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testPassedKnowledgeTestCountsWithoutLessons(): void
    {
        $this->world->knowledgeAttempt($this->member->id, $this->en, ['results' => 80]);

        $this->assertEquals(30, $this->progress());
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
        $this->world->scenario($lt, [], [$this->world->scenarioCategory($lt)]);
        $this->completeLessons();

        $this->assertEquals(0, LMUsers::course_progress($this->member->id, $lt));
    }






    // -----------------------------------------------------------
    // testLanguageWithoutSimulationsStillShowsTheProgress
    // -----------------------------------------------------------
    //
    // A language without simulations has no simulation share
    // to offer, which counts 0: a member who completed every
    // Lithuanian lesson, where a self-evaluation question but
    // no simulation exists, has the lessons' 25 — not the 0
    // a NULL share once made of the whole sum.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testLanguageWithoutSimulationsStillShowsTheProgress(): void
    {
        $lt = $this->world->language('lt');
        $ltCategory = $this->world->lmCategory($lt);
        $this->world->lessonDone($this->member->id, $this->world->lmContent($ltCategory, $lt));
        $this->world->selfEvalQuestion($ltCategory, [['Taip', true, 100]]);

        $this->assertEquals(25, LMUsers::course_progress($this->member->id, $lt));
    }






    // -----------------------------------------------------------
    // testLessonStoredTwiceCountsOnce
    // -----------------------------------------------------------
    //
    // mark_completed reads, then inserts, and bei_lm_users has
    // no unique key, so a double-clicked button can store a
    // lesson twice; it still counts once. One of the two
    // lessons is half the hours, 12 — MySQL rounds the 12.5 to
    // the even 12 — where the lesson stored twice once counted
    // its hours twice, 25.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testLessonStoredTwiceCountsOnce(): void
    {
        $this->world->lessonDone($this->member->id, $this->lessons[0]);
        $this->world->lessonDone($this->member->id, $this->lessons[0]);

        $this->assertEquals(12, $this->progress());
    }






    // -----------------------------------------------------------
    // testLessonsMembersCannotReachDoNotCount
    // -----------------------------------------------------------
    //
    // Deleting a lesson category keeps its lessons, with no
    // category, where no member page lists them; they no
    // longer count, so a member who finished every lesson
    // still on offer has the full quarter. The progress once
    // divided by their hours too, and held such a member short
    // of it for good.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testLessonsMembersCannotReachDoNotCount(): void
    {
        $doomed = $this->world->lmCategory($this->en);
        $this->world->lmContent($doomed, $this->en);
        $this->completeLessons();
        $this->assertLessThan(25, $this->progress());

        $this->signInManager($this->world->manager())->from('/admin-panel/lm/categories')->post("/admin-panel/lm/categores/delete/$doomed");

        $this->assertFalse(DB::table('bei_lm_category')->where('id', $doomed)->exists());
        $this->assertEquals(25, $this->progress());
    }






    // -----------------------------------------------------------
    // testSimulationsMembersCannotReachDoNotCount
    // -----------------------------------------------------------
    //
    // The simulations quarter counts the simulations a
    // category of the language offers — the ones
    // badge_all_simulations waits for — on both sides of the
    // division: a finished run of a simulation filed under no
    // category adds nothing, and finishing every simulation
    // on offer gives the whole quarter. The stored function
    // and LMUsers::country_progress once divided by every
    // simulation of the language, and one out of reach held
    // every member below the quarter for good
    // (simulation-share-counts-unoffered).
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testSimulationsMembersCannotReachDoNotCount(): void
    {
        $unoffered = $this->world->scenario($this->en, ['descr' => 'Filed under no category']);
        $this->world->scenarioAttempt($this->member->id, $unoffered, $this->en);
        $this->assertEquals(0, $this->progress(), 'a run of a simulation no member page offers counted');

        $this->world->scenarioAttempt($this->member->id, $this->scenario, $this->en);

        $this->assertEquals(25, $this->progress());
    }






    // -----------------------------------------------------------
    // testSelfEvaluationShareCountsOnlyWhatMembersCanFinish
    // -----------------------------------------------------------
    //
    // The self-evaluations' fifth counts only the categories
    // whose self-evaluation a member can take and finish — a
    // lesson to offer it and a question to answer — on both
    // sides of the division. Beside the setUp's category, one
    // with a question but no lesson is offered on no member
    // page, and one whose only question has no options asks
    // nothing; a member who has done every lesson and finished
    // the one self-evaluation on offer has the lessons' 25 and
    // the whole 20. The stored function and
    // LMUsers::country_progress once counted every category
    // that holds a question, and held such a member at 32 for
    // good (selfeval-share-counts-unreachable).
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testSelfEvaluationShareCountsOnlyWhatMembersCanFinish(): void
    {
        $withoutLessons = $this->world->lmCategory($this->en);
        $this->world->selfEvalQuestion($withoutLessons, [['Right', true, 100], ['Wrong', false, 0]]);
        $withoutOptions = $this->world->lmCategory($this->en);
        $otherLesson = $this->world->lmContent($withoutOptions, $this->en);
        DB::table('bei_selfeval_question')->insert(['cat_id' => $withoutOptions, 'question' => 'A question without options', 'q_type' => 1]);
        $this->completeLessons();
        $this->world->lessonDone($this->member->id, $otherLesson);

        $this->finishSelfEvaluation([$this->question]);

        // No page links the self-evaluation of the category
        // without lessons, and the other one has nothing to ask
        $this->signInMember($this->member)->get('/en/cp/learning-material')->assertOk()->assertDontSee("self-evaluation-test/$withoutLessons", false);
        $this->signInMember($this->member)->get("/en/learn/lesson/{$this->lessons[0]}")->assertOk()->assertDontSee("self-evaluation-test/$withoutLessons", false);
        $empty = $this->startSelfEvaluation($this->member, $withoutOptions);
        $this->signInMember($this->member)->get("/cp/se/$empty/$withoutOptions")->assertOk()->assertSee(__('main.no_selfeval_questions'));

        $this->assertEquals(45, $this->progress(), 'every lesson and every self-evaluation a member can finish is not the 25 and the whole 20');
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
    //     testEverythingDoneIsAHundred,
    //     testFinishedSelfEvaluationsCountTheirFullShare,
    //     testProgressIsPerLanguage,
    //     testLessonsMembersCannotReachDoNotCount,
    //     testSelfEvaluationShareCountsOnlyWhatMembersCanFinish
    //     (above)
    // -----------------------------------------------------------

    private function completeLessons(): void
    {
        foreach ($this->lessons as $lesson) {
            $this->world->lessonDone($this->member->id, $lesson);
        }
    }






    // -----------------------------------------------------------
    // finishSelfEvaluation
    // -----------------------------------------------------------
    //
    // Takes the category's self-evaluation the way a member
    // does: starts it, answers each of the questions given
    // right and opens its question page once more, where
    // FinishedSe closes the attempt.
    //
    // Used by:
    //   - testEverythingDoneIsAHundred,
    //     testFinishedSelfEvaluationsCountTheirFullShare,
    //     testSelfEvaluationShareCountsOnlyWhatMembersCanFinish
    //     (above)
    // -----------------------------------------------------------

    private function finishSelfEvaluation(array $questions): void
    {
        $publicId = $this->startSelfEvaluation($this->member, $this->category);
        foreach ($questions as $question) {
            $this->answerSelfEvaluation($this->member, $publicId, $this->category, $question['question'], [$question['options'][0]]);
        }
        $this->signInMember($this->member)->get("/cp/se/$publicId/{$this->category}");
    }






    // -----------------------------------------------------------
    // progress
    // -----------------------------------------------------------
    //
    // The member's English course progress, as member pages
    // get it, as a whole number of percent.
    //
    // Used by:
    //   - every test of this class but testProgressIsPerLanguage
    //     and testLanguageWithoutSimulationsStillShowsTheProgress,
    //     which read Lithuanian
    // -----------------------------------------------------------

    private function progress(): int
    {
        return (int) LMUsers::course_progress($this->member->id, $this->en);
    }
}
