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
//    lesson-badge-chain       — a completed lesson earns one
//                               badge at most, however many
//                               fall due
//    selfeval-get-creates     — every visit of the start
//                               page opens one more attempt
//    selfeval-short-category  — an attempt longer than its
//                               category's questions never
//                               closes
//    selfeval-badge-count     — a member who left an attempt
//                               unfinished never gets the
//                               self-evaluation badge
//    selfeval-badge-locale    — the new-badge message of a
//                               self-evaluation is always in
//                               English
//    course-progress-null     — one share the progress
//                               cannot work out turns it all
//                               into 0
//    badge-duplicate          — a badge stored twice reads
//                               as not held
//    lang-no-questions        — the self-evaluation page
//                               never shows its own
//                               no-questions text
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
    // testCompletionAwardsEveryBadgeThatFallsDue
    // -----------------------------------------------------------
    //
    // LMController::mark_completed hands out one badge per
    // completion at most: its chain returns as soon as one
    // step applies. The member's first lesson returns after
    // badge_topic, before the category check, and a new
    // badge_category returns before the check of the whole
    // language. A first lesson that is the only one of its
    // category and of the language earns badge_topic alone,
    // and a completed lesson offers no second completion:
    // badge_category and badge_all_presentations never come,
    // and so neither does badge_finished_course. Wanted: all
    // three lesson badges.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testCompletionAwardsEveryBadgeThatFallsDue(): void
    {
        $lesson = $this->world->lmContent($this->world->lmCategory($this->en), $this->en);

        // The lesson page's form posts the salted hash of the
        // lesson id, not the id
        $this->signInMember($this->member)->from("/en/learn/lesson/$lesson")
            ->post('/cp/mark_completed', ['ued' => md5(md5(env('APP_SALT') . $lesson))])
            ->assertSessionHas('success', __('main.new_badge'));

        $this->pinDefect('lesson-badge-chain', function () {
            $badges = DB::table('bei_users_badges')->where('user_id', $this->member->id)->orderBy('name')->pluck('name')->all();
            $this->assertSame(['badge_all_presentations', 'badge_category', 'badge_topic'], $badges, 'the completion stopped after its first badge');
        });
    }






    // -----------------------------------------------------------
    // testRevisitingTheStartPageOpensNoSecondAttempt
    // -----------------------------------------------------------
    //
    // LMController::self_evaluation_test creates an attempt
    // on a plain GET of the start page and never looks for
    // one the member has open: every visit of that URL —
    // another click on the button, a browser's prefetch —
    // opens one more attempt, and the open ones pile up in
    // the member's history. Wanted: a second visit while an
    // attempt is open leaves the member with that one
    // attempt.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testRevisitingTheStartPageOpensNoSecondAttempt(): void
    {
        $category = $this->world->lmCategory($this->en);
        $this->startSelfEvaluation($this->member, $category);

        $this->signInMember($this->member)->get("/en/self-evaluation-test/$category");

        $this->pinDefect('selfeval-get-creates', function () use ($category) {
            $attempts = DB::table('bei_users_selfeval_test')->where(['user_id' => $this->member->id, 'cat_id' => $category])->count();
            $this->assertSame(1, $attempts, 'the second visit opened another attempt');
        });
    }






    // -----------------------------------------------------------
    // testAttemptClosesWhenTheCategoryRunsOutOfQuestions
    // -----------------------------------------------------------
    //
    // FinishedSe::handle closes an attempt only once as many
    // questions are answered as settings row 2 asks for. In a
    // category with fewer questions the questions run out
    // first, LMController::self_eval_test then shows a page
    // without a question, and the attempt stays open for
    // good — no result, no points, no badge. Here an attempt
    // three questions long meets a category of two. Wanted:
    // the page after the last answer closes the attempt and
    // sends the member to its result.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testAttemptClosesWhenTheCategoryRunsOutOfQuestions(): void
    {
        $this->world->setting(2, 'self_eval_pertime', null, null, 3);
        $category = $this->world->lmCategory($this->en);
        $first = $this->world->selfEvalQuestion($category, [['Right', true, 100], ['Wrong', false, 0]]);
        $second = $this->world->selfEvalQuestion($category, [['Right', true, 100], ['Wrong', false, 0]]);
        $publicId = $this->startSelfEvaluation($this->member, $category);
        $this->answerSelfEvaluation($this->member, $publicId, $category, $first['question'], [$first['options'][0]]);
        $this->answerSelfEvaluation($this->member, $publicId, $category, $second['question'], [$second['options'][0]]);
        $this->assertSame(2, DB::table('bei_users_selfeval')->where('public_id', $publicId)->count());

        $response = $this->signInMember($this->member)->get("/cp/se/$publicId/$category");

        $this->pinDefect('selfeval-short-category', function () use ($response, $publicId) {
            $finished = DB::table('bei_users_selfeval_test')->where('public_id', $publicId)->value('finished');
            $this->assertEquals(1, $finished, 'the attempt is still open');
            $response->assertRedirect("/cp/se_r/$publicId");
        });
    }






    // -----------------------------------------------------------
    // testFirstFinishedSelfEvaluationEarnsTheBadge
    // -----------------------------------------------------------
    //
    // FinishedSe::handle saves badge_self_evaluation_test
    // only when the member has exactly one attempt in the
    // language, finished or not, as an attempt closes. A
    // member who left an attempt unfinished — here in another
    // category — has two when the first one closes, and every
    // later attempt adds one more: the badge never comes.
    // Wanted: the first finished self-evaluation earns the
    // badge.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testFirstFinishedSelfEvaluationEarnsTheBadge(): void
    {
        $this->world->setting(2, 'self_eval_pertime', null, null, 1);
        $otherCategory = $this->world->lmCategory($this->en);
        $this->world->selfEvalQuestion($otherCategory, [['Right', true, 100], ['Wrong', false, 0]]);
        $category = $this->world->lmCategory($this->en);
        $question = $this->world->selfEvalQuestion($category, [['Right', true, 100], ['Wrong', false, 0]]);

        // Opened in the other category and left unanswered
        $this->startSelfEvaluation($this->member, $otherCategory);

        $publicId = $this->startSelfEvaluation($this->member, $category);
        $this->answerSelfEvaluation($this->member, $publicId, $category, $question['question'], [$question['options'][0]]);
        $this->signInMember($this->member)->get("/cp/se/$publicId/$category")->assertRedirect("/cp/se_r/$publicId");

        $this->pinDefect('selfeval-badge-count', function () {
            $badge = DB::table('bei_users_badges')->where(['user_id' => $this->member->id, 'name' => 'badge_self_evaluation_test']);
            $this->assertTrue($badge->exists(), 'the first finished self-evaluation earned no badge');
        });
    }






    // -----------------------------------------------------------
    // testNewBadgeMessageIsInTheMembersLanguage
    // -----------------------------------------------------------
    //
    // FinishedSe::handle runs as route middleware and
    // translates the new-badge message there, before
    // BaseController's middleware sets the site language of
    // the request, so the message always comes out in the
    // configured locale, English: a member taking the
    // self-evaluation in Lithuanian finds an English sentence
    // on the Lithuanian result page. Wanted: the message in
    // Lithuanian.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testNewBadgeMessageIsInTheMembersLanguage(): void
    {
        $lt = $this->world->language('lt');
        $this->world->setting(2, 'self_eval_pertime', null, null, 1);
        $category = $this->world->lmCategory($lt);
        $question = $this->world->selfEvalQuestion($category, [['Taip', true, 100], ['Ne', false, 0]]);
        $configured = $this->app->getLocale();
        $this->signInMember($this->member)->get("/lt/self-evaluation-test/$category")->assertRedirect();
        $publicId = DB::table('bei_users_selfeval_test')->where('user_id', $this->member->id)->value('public_id');
        $this->answerSelfEvaluation($this->member, $publicId, $category, $question['question'], [$question['options'][0]]);

        // Every production request starts in the configured
        // locale, while the test's app still holds the one the
        // answer's request set
        $this->app->setLocale($configured);
        $this->signInMember($this->member)->get("/cp/se/$publicId/$category")
            ->assertRedirect("/cp/se_r/$publicId")
            ->assertSessionHas('success');

        $this->pinDefect('selfeval-badge-locale', function () {
            $this->assertSame(__('main.new_badge', [], 'lt'), session('success'), 'the new-badge message is not in Lithuanian');
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






    // -----------------------------------------------------------
    // testBadgeStoredTwiceStillReadsAsHeld
    // -----------------------------------------------------------
    //
    // Badges::get_badge answers true for exactly one row of
    // the badge and false for none. The table has no unique
    // key and save_badge checks before it inserts, so two
    // requests at the same moment can store a badge twice,
    // and two rows match neither branch: the method answers
    // null. BaseController shares that as
    // badge_all_presentations, and the sidebar of the member
    // pages offers the knowledge test only while it reads
    // true — the button is gone for a member who holds the
    // badge twice, and badge_finished_course never follows.
    // Wanted: the dashboard offers the knowledge test.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testBadgeStoredTwiceStillReadsAsHeld(): void
    {
        $badge = ['lang_id' => $this->en, 'user_id' => $this->member->id, 'name' => 'badge_all_presentations'];
        DB::table('bei_users_badges')->insert($badge);
        $this->signInMember($this->member)->get('/cp/dashboard')->assertSee('/en/knowledge_test', false);

        // The same badge once more, as two requests at the same
        // moment can store it
        DB::table('bei_users_badges')->insert($badge);
        $dashboard = $this->signInMember($this->member)->get('/cp/dashboard');

        $this->pinDefect('badge-duplicate', function () use ($dashboard) {
            $this->assertStringContainsString('/en/knowledge_test', $dashboard->getContent(), 'the dashboard no longer offers the knowledge test');
        });
    }






    // -----------------------------------------------------------
    // testNoQuestionsIsDefinedOnce
    // -----------------------------------------------------------
    //
    // The main.php files of resources/lang/en, gr, lt and
    // lv define no_questions twice — first the text written
    // for a category without self-evaluation questions,
    // later a generic one, which the knowledge-test page
    // uses too — and PHP keeps the later entry of a repeated
    // key, so the self-evaluation page never shows the text
    // written for it. Wanted: each of the four files defines
    // no_questions once.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testNoQuestionsIsDefinedOnce(): void
    {
        $definitions = [];
        foreach (['en', 'gr', 'lt', 'lv'] as $locale) {
            $source = file_get_contents(resource_path("lang/$locale/main.php"));
            $definitions[$locale] = preg_match_all("/^\\s*'no_questions'\\s*=>/m", $source);
        }
        $this->assertGreaterThan(0, min($definitions), 'a language file has no no_questions entry to count');

        $this->pinDefect('lang-no-questions', function () use ($definitions) {
            $this->assertSame(['en' => 1, 'gr' => 1, 'lt' => 1, 'lv' => 1], $definitions, 'no_questions is defined more than once');
        });
    }
}
