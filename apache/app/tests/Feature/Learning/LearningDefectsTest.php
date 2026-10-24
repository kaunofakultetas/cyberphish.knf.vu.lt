<?php

// -----------------------------------------------------------
//  [*] LearningDefectsTest — known learning defects
//
//  Each test states the behaviour the course progress, the
//  badges and the self-evaluations should have and pins the
//  defect that breaks it today (KnownDefects): the run
//  reports them as incomplete, tagged with their ids, until
//  a fix makes one pass — then the test fails as the cue to
//  remove its pin and move it into its home file.
//
//    simulation-share-counts-unoffered — the progress counts
//                                       simulations no member
//                                       page offers
//    badge-note-outlives-the-member    — a badge announcement
//                                       waiting in the session
//                                       greets whoever comes
//                                       next
//    selfeval-closed-attempt-reopens   — a closed attempt takes
//                                       questions again once
//                                       attempts grow longer
//    selfeval-starts-without-lessons   — a self-evaluation
//                                       starts before the
//                                       category's lessons
//    selfeval-nested-answer-500        — an answer of nested
//                                       lists is a 500
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
// and a member for every test.
//
// Used by:
//   - PHPUnit — the Feature suite (runTests.sh)
// -----------------------------------------------------------

class LearningDefectsTest extends FeatureTestCase
{

    // Starting and answering self-evaluations
    use Flows;

    // The English id and the member of the test running now
    private $en;
    private $member;






    // -----------------------------------------------------------
    // setUp
    // -----------------------------------------------------------
    //
    // English, and a member.
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
    // testSimulationsMembersCannotReachDoNotCount
    // -----------------------------------------------------------
    //
    // badge_all_simulations waits only for the simulations a
    // category of the language offers, but the progress's
    // simulation share — the stored function and
    // LMUsers::country_progress alike — still divides by
    // every simulation of the language: one filed under no
    // category, which an import without a marked category or
    // a category delete leaves, holds every member below the
    // share's 25 for good, the badges saying the course is
    // done. Wanted: finishing every simulation on offer gives
    // the full share.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testSimulationsMembersCannotReachDoNotCount(): void
    {
        $offered = $this->world->scenario($this->en, [], [$this->world->scenarioCategory($this->en)]);
        $this->world->scenario($this->en, ['descr' => 'Filed under no category']);
        $this->world->scenarioAttempt($this->member->id, $offered, $this->en);

        $this->pinDefect('simulation-share-counts-unoffered', function () {
            $this->assertEquals(25, LMUsers::course_progress($this->member->id, $this->en));
        });
    }






    // -----------------------------------------------------------
    // testBadgeAnnouncementStaysWithItsMember
    // -----------------------------------------------------------
    //
    // The announcement of badge_finished_course waits in the
    // session as a bare flag, and neither signing out nor in
    // clears it: a member who earns the badge on a page
    // without messages and signs out leaves it to the next
    // member on that browser, whose first page with messages
    // announces a badge they never earned. Wanted: only the
    // member who earned it hears of it.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testBadgeAnnouncementStaysWithItsMember(): void
    {
        $this->world->badge($this->member->id, $this->en, 'badge_all_presentations');
        $this->world->badge($this->member->id, $this->en, 'badge_all_simulations');
        $this->signInMember($this->member)->get('/en/simulations')->assertOk();
        $this->signInMember($this->member)->post('/cp/logout');
        $next = $this->world->member();

        $html = $this->signInMember($next)->get('/cp/dashboard')->assertOk()->getContent();

        $this->pinDefect('badge-note-outlives-the-member', function () use ($html) {
            $this->assertStringNotContainsString(__('main.new_badge'), $html, 'the next member heard of a badge they never earned');
        });
    }






    // -----------------------------------------------------------
    // testClosedAttemptStaysClosed
    // -----------------------------------------------------------
    //
    // FinishedSe looks at an attempt's finished flag only once
    // it has as many answers as attempts are long: when the
    // length grows after it closed — a question added to a
    // category that had fewer, the length in settings raised
    // — the closed attempt's question page opens again and
    // takes answers, which no score ever counts. Wanted: a
    // closed attempt leads to its results and stores nothing
    // more.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testClosedAttemptStaysClosed(): void
    {
        $this->world->setting(2, 'self_eval_pertime', null, null, 5);
        $category = $this->world->lmCategory($this->en);
        $first = $this->world->selfEvalQuestion($category, [['Right', true, 100], ['Wrong', false, 0]]);
        $publicId = $this->startSelfEvaluation($this->member, $category);
        $this->answerSelfEvaluation($this->member, $publicId, $category, $first['question'], [$first['options'][0]]);
        $this->signInMember($this->member)->get("/cp/se/$publicId/$category")->assertRedirect("/cp/se_r/$publicId");
        $second = $this->world->selfEvalQuestion($category, [['Right', true, 100], ['Wrong', false, 0]]);

        $page = $this->signInMember($this->member)->get("/cp/se/$publicId/$category");
        $this->answerSelfEvaluation($this->member, $publicId, $category, $second['question'], [$second['options'][0]]);

        $this->pinDefect('selfeval-closed-attempt-reopens', function () use ($page, $publicId) {
            $page->assertRedirect("/cp/se_r/$publicId");
            $this->assertSame(1, DB::table('bei_users_selfeval')->where('public_id', $publicId)->count(), 'the closed attempt took another answer');
        });
    }






    // -----------------------------------------------------------
    // testSelfEvaluationNeedsTheCategorysLessons
    // -----------------------------------------------------------
    //
    // The lesson page offers a category's self-evaluation once
    // its lessons are all done (show_self_eval), but the start
    // route checks nothing of the kind: a member who opens it
    // by its address before a single lesson starts an attempt,
    // whose close earns badge_self_evaluation_test, counts in
    // the progress and enters the ranking. The knowledge test
    // got that gate (knowledge-without-lessons). Wanted: no
    // attempt before the category's lessons are done.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testSelfEvaluationNeedsTheCategorysLessons(): void
    {
        $this->world->setting(2, 'self_eval_pertime', null, null, 1);
        $category = $this->world->lmCategory($this->en);
        $this->world->lmContent($category, $this->en);
        $this->world->selfEvalQuestion($category, [['Right', true, 100], ['Wrong', false, 0]]);

        $this->signInMember($this->member)->get("/en/self-evaluation-test/$category");

        $this->pinDefect('selfeval-starts-without-lessons', function () use ($category) {
            $this->assertFalse(DB::table('bei_users_selfeval_test')->where(['user_id' => $this->member->id, 'cat_id' => $category])->exists(), 'an attempt started before the lessons');
        });
    }






    // -----------------------------------------------------------
    // testAnswerOfNestedListsIsNoServerError
    // -----------------------------------------------------------
    //
    // The options of an answer come as the list a, whose
    // elements nothing checks to be single values: two of
    // them with a list among them make array_unique convert a
    // list to a string, one holding a list reaches whereIn as
    // a nested array — a 500 either way, where the question's
    // hash already got its check (learning-array-input).
    // Wanted: the answer is refused without a server error.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testAnswerOfNestedListsIsNoServerError(): void
    {
        $this->world->setting(2, 'self_eval_pertime', null, null, 1);
        $category = $this->world->lmCategory($this->en);
        $question = $this->world->selfEvalQuestion($category, [['Right', true, 100], ['Wrong', false, 0]]);
        $publicId = $this->startSelfEvaluation($this->member, $category);

        $statuses = [];
        foreach (['two elements, one a list' => [[$question['options'][0]], $question['options'][1]], 'one element, a list' => [[$question['options'][0], $question['options'][1]]]] as $case => $answer) {
            $statuses[$case] = $this->signInMember($this->member)->post("/cp/se/$publicId/$category", [
                'pbid' => md5(md5($question['question'])),
                'a'    => $answer,
            ])->getStatusCode();
        }

        $this->pinDefect('selfeval-nested-answer-500', function () use ($statuses, $publicId) {
            $this->assertSame([], array_keys(array_filter($statuses, function (int $status) {
                return $status >= 500;
            })), 'answers answered with a server error');
            $this->assertFalse(DB::table('bei_users_selfeval')->where('public_id', $publicId)->exists());
        });
    }
}
