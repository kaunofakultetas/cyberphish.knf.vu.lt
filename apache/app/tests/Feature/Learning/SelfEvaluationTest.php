<?php

// -----------------------------------------------------------
//  [*] SelfEvaluationTest — the self-evaluation of a category
//
//  {lang}/self-evaluation-test/{cat_id} starts an attempt,
//  cp/se/{public_id}/{cat_id} asks the category's questions
//  one by one in random order and stores the chosen
//  options, and the FinishedSe middleware closes the
//  attempt once as many questions are answered as settings
//  row 2 asks for, or every question of a category that has
//  fewer: every answer worth the question's best
//  option is added for a correct choice and taken away for
//  a wrong one, never below zero. A question takes one
//  answer, from its own options, each counted once, so a
//  forged form scores no more than an honest one; a closed
//  attempt keeps its end date. Visiting the start page again
//  resumes the open attempt. The first finished
//  self-evaluation earns its badge, announced in the
//  member's language. cp/se_r shows the result and
//  cp/self-evaluation-history the member's attempts. An
//  attempt is its member's alone.
//
//  Used by:
//    - PHPUnit — the Feature suite (runTests.sh)
// -----------------------------------------------------------


namespace Tests\Feature\Learning;

use Illuminate\Support\Facades\DB;
use Tests\FeatureTestCase;
use Tests\Support\Flows;








// -----------------------------------------------------------
// SelfEvaluationTest
// -----------------------------------------------------------
//
// One English category with two questions worth 100 points
// each, a self-evaluation length of two questions, and a
// member for every test.
//
// Used by:
//   - PHPUnit — the Feature suite (runTests.sh)
// -----------------------------------------------------------

class SelfEvaluationTest extends FeatureTestCase
{

    // Starting an attempt and answering its questions
    use Flows;

    // The English id, the category, its two questions with
    // their option ids, and the member of the test running now
    private $en;
    private $category;
    private $first;
    private $second;
    private $member;






    // -----------------------------------------------------------
    // setUp
    // -----------------------------------------------------------
    //
    // The category and its questions: each has one correct
    // option worth 100 points (the first) and a wrong one.
    //
    // Used by:
    //   - PHPUnit, before every test
    // -----------------------------------------------------------

    protected function setUp(): void
    {
        parent::setUp();
        $this->en = $this->world->language('en');
        $this->world->setting(2, 'self_eval_pertime', null, null, 2);
        $this->category = $this->world->lmCategory($this->en, ['name' => 'Password hygiene']);
        $this->first = $this->world->selfEvalQuestion($this->category, [['Use a manager', true, 100], ['Reuse it', false, 0]]);
        $this->second = $this->world->selfEvalQuestion($this->category, [['Turn on 2FA', true, 100], ['Write it down', false, 0]]);
        $this->member = $this->world->member();
    }






    // -----------------------------------------------------------
    // testStartingCreatesAnAttempt
    // -----------------------------------------------------------
    //
    // An open attempt of the member for the category, in the
    // page's language, with a 16-character public id that the
    // redirect opens.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testStartingCreatesAnAttempt(): void
    {
        $publicId = $this->startSelfEvaluation($this->member, $this->category);

        $attempt = DB::table('bei_users_selfeval_test')->where('public_id', $publicId)->first();
        $this->assertSame(16, strlen($publicId));
        $this->assertEquals($this->member->id, $attempt->user_id);
        $this->assertEquals($this->category, $attempt->cat_id);
        $this->assertEquals($this->en, $attempt->lang_id);
        $this->assertEquals(0, $attempt->finished);
    }






    // -----------------------------------------------------------
    // testUnknownCategoryIsNotFound
    // -----------------------------------------------------------
    //
    // R_Exists answers a category that does not exist with a
    // 404, and no attempt is created.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testUnknownCategoryIsNotFound(): void
    {
        $this->signInMember($this->member)->get('/en/self-evaluation-test/' . ($this->category + 1000))
            ->assertNotFound();

        $this->assertFalse(DB::table('bei_users_selfeval_test')->where('user_id', $this->member->id)->exists());
    }






    // -----------------------------------------------------------
    // testRevisitingTheStartPageOpensNoSecondAttempt
    // -----------------------------------------------------------
    //
    // A second visit of the start page while an attempt of the
    // category is open — another click on the button, a
    // browser's prefetch — resumes that attempt.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testRevisitingTheStartPageOpensNoSecondAttempt(): void
    {
        $publicId = $this->startSelfEvaluation($this->member, $this->category);

        $this->signInMember($this->member)->get("/en/self-evaluation-test/{$this->category}")
            ->assertRedirect("/cp/se/$publicId/{$this->category}");

        $this->assertSame(1, DB::table('bei_users_selfeval_test')->where(['user_id' => $this->member->id, 'cat_id' => $this->category])->count());
    }






    // -----------------------------------------------------------
    // testAttemptAsksAQuestionOfTheCategory
    // -----------------------------------------------------------
    //
    // The page shows one of the category's questions.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testAttemptAsksAQuestionOfTheCategory(): void
    {
        $publicId = $this->startSelfEvaluation($this->member, $this->category);

        $html = $this->signInMember($this->member)->get("/cp/se/$publicId/{$this->category}")->assertOk()->getContent();

        $this->assertMatchesRegularExpression('/Self-evaluation question \d+/', $html);
    }






    // -----------------------------------------------------------
    // testAnswerIsStoredWithItsScore
    // -----------------------------------------------------------
    //
    // The chosen option is stored with whether it is correct
    // and its points.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testAnswerIsStoredWithItsScore(): void
    {
        $publicId = $this->startSelfEvaluation($this->member, $this->category);

        $this->answerSelfEvaluation($this->member, $publicId, $this->category, $this->first['question'], [$this->first['options'][0]])
            ->assertRedirect("/cp/se/$publicId/{$this->category}");

        $answer = DB::table('bei_users_selfeval')->where('public_id', $publicId)->first();
        $this->assertEquals($this->first['question'], $answer->question_id);
        $this->assertEquals($this->first['options'][0], $answer->option_id);
        $this->assertEquals(1, $answer->correct);
        $this->assertEquals(100, $answer->points);
    }






    // -----------------------------------------------------------
    // testAnsweredQuestionIsNotAskedAgain
    // -----------------------------------------------------------
    //
    // After the first question is answered, the attempt asks
    // the second.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testAnsweredQuestionIsNotAskedAgain(): void
    {
        $publicId = $this->startSelfEvaluation($this->member, $this->category);
        $this->answerSelfEvaluation($this->member, $publicId, $this->category, $this->first['question'], [$this->first['options'][0]]);

        $this->signInMember($this->member)->get("/cp/se/$publicId/{$this->category}")
            ->assertSee('Turn on 2FA')
            ->assertDontSee('Use a manager');
    }






    // -----------------------------------------------------------
    // testLastAnswerClosesAndScoresTheAttempt
    // -----------------------------------------------------------
    //
    // Two correct answers out of two close the attempt with
    // 200 points and send the member to the result, with the
    // first-test badge.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testLastAnswerClosesAndScoresTheAttempt(): void
    {
        $publicId = $this->startSelfEvaluation($this->member, $this->category);
        $this->answerSelfEvaluation($this->member, $publicId, $this->category, $this->first['question'], [$this->first['options'][0]]);
        $this->answerSelfEvaluation($this->member, $publicId, $this->category, $this->second['question'], [$this->second['options'][0]]);

        $this->signInMember($this->member)->get("/cp/se/$publicId/{$this->category}")
            ->assertRedirect("/cp/se_r/$publicId")
            ->assertSessionHas('success', __('main.new_badge'));

        $attempt = DB::table('bei_users_selfeval_test')->where('public_id', $publicId)->first();
        $this->assertEquals(1, $attempt->finished);
        $this->assertNotNull($attempt->ended);
        $this->assertEquals(200, $attempt->points);
        $this->assertTrue(DB::table('bei_users_badges')
            ->where(['user_id' => $this->member->id, 'name' => 'badge_self_evaluation_test'])->exists());
    }






    // -----------------------------------------------------------
    // testAttemptClosesWhenTheCategoryRunsOutOfQuestions
    // -----------------------------------------------------------
    //
    // An attempt three questions long in a category of two
    // closes after the second answer: the page after it sends
    // the member to the result.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testAttemptClosesWhenTheCategoryRunsOutOfQuestions(): void
    {
        $this->world->setting(2, 'self_eval_pertime', null, null, 3);
        $publicId = $this->startSelfEvaluation($this->member, $this->category);
        $this->answerSelfEvaluation($this->member, $publicId, $this->category, $this->first['question'], [$this->first['options'][0]]);
        $this->answerSelfEvaluation($this->member, $publicId, $this->category, $this->second['question'], [$this->second['options'][0]]);

        $this->signInMember($this->member)->get("/cp/se/$publicId/{$this->category}")->assertRedirect("/cp/se_r/$publicId");

        $this->assertEquals(1, DB::table('bei_users_selfeval_test')->where('public_id', $publicId)->value('finished'));
    }






    // -----------------------------------------------------------
    // testFirstFinishedSelfEvaluationEarnsTheBadge
    // -----------------------------------------------------------
    //
    // The first self-evaluation that closes earns
    // badge_self_evaluation_test, though an attempt in another
    // category was opened before and left unanswered.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testFirstFinishedSelfEvaluationEarnsTheBadge(): void
    {
        $this->world->setting(2, 'self_eval_pertime', null, null, 1);
        $otherCategory = $this->world->lmCategory($this->en);
        $this->world->selfEvalQuestion($otherCategory, [['Right', true, 100], ['Wrong', false, 0]]);
        $this->startSelfEvaluation($this->member, $otherCategory);

        $publicId = $this->startSelfEvaluation($this->member, $this->category);
        $this->answerSelfEvaluation($this->member, $publicId, $this->category, $this->first['question'], [$this->first['options'][0]]);
        $this->signInMember($this->member)->get("/cp/se/$publicId/{$this->category}")
            ->assertRedirect("/cp/se_r/$publicId")
            ->assertSessionHas('success', __('main.new_badge'));

        $this->assertTrue(DB::table('bei_users_badges')->where(['user_id' => $this->member->id, 'name' => 'badge_self_evaluation_test'])->exists());
    }






    // -----------------------------------------------------------
    // testNewBadgeMessageIsInTheMembersLanguage
    // -----------------------------------------------------------
    //
    // A member taking the self-evaluation in Lithuanian gets
    // the new-badge message in Lithuanian, though FinishedSe
    // runs before BaseController sets the request's locale.
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
            ->assertSessionHas('success', __('main.new_badge', [], 'lt'));
    }






    // -----------------------------------------------------------
    // testWrongAnswerTakesItsPointsAway
    // -----------------------------------------------------------
    //
    // One right and one wrong answer cancel out: 100 - 100.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testWrongAnswerTakesItsPointsAway(): void
    {
        $publicId = $this->startSelfEvaluation($this->member, $this->category);
        $this->answerSelfEvaluation($this->member, $publicId, $this->category, $this->first['question'], [$this->first['options'][0]]);
        $this->answerSelfEvaluation($this->member, $publicId, $this->category, $this->second['question'], [$this->second['options'][1]]);

        $this->signInMember($this->member)->get("/cp/se/$publicId/{$this->category}");

        $this->assertEquals(0, DB::table('bei_users_selfeval_test')->where('public_id', $publicId)->value('points'));
    }






    // -----------------------------------------------------------
    // testScoreNeverGoesBelowZero
    // -----------------------------------------------------------
    //
    // Two wrong answers would be -200; the attempt scores 0.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testScoreNeverGoesBelowZero(): void
    {
        $publicId = $this->startSelfEvaluation($this->member, $this->category);
        $this->answerSelfEvaluation($this->member, $publicId, $this->category, $this->first['question'], [$this->first['options'][1]]);
        $this->answerSelfEvaluation($this->member, $publicId, $this->category, $this->second['question'], [$this->second['options'][1]]);

        $this->signInMember($this->member)->get("/cp/se/$publicId/{$this->category}");

        $this->assertEquals(0, DB::table('bei_users_selfeval_test')->where('public_id', $publicId)->value('points'));
    }






    // -----------------------------------------------------------
    // testResultAndHistoryShowTheAttempt
    // -----------------------------------------------------------
    //
    // The result page shows the category and the points; the
    // history lists the attempt under its category.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testResultAndHistoryShowTheAttempt(): void
    {
        $publicId = $this->startSelfEvaluation($this->member, $this->category);
        $this->answerSelfEvaluation($this->member, $publicId, $this->category, $this->first['question'], [$this->first['options'][0]]);
        $this->answerSelfEvaluation($this->member, $publicId, $this->category, $this->second['question'], [$this->second['options'][0]]);
        $this->signInMember($this->member)->get("/cp/se/$publicId/{$this->category}");

        $this->signInMember($this->member)->get("/cp/se_r/$publicId")
            ->assertOk()
            ->assertSee('Password hygiene')
            ->assertSee('200');
        $this->signInMember($this->member)->get('/cp/self-evaluation-history')
            ->assertOk()
            ->assertSee("cp/se_r/$publicId", false);
    }






    // -----------------------------------------------------------
    // testCategoryWithoutQuestionsSaysSo
    // -----------------------------------------------------------
    //
    // A category with no question of its own gets its own
    // text on the test page, no_selfeval_questions, and every
    // language file defines the generic no_questions — the
    // knowledge test's — once, so neither hides the other.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testCategoryWithoutQuestionsSaysSo(): void
    {
        $empty = $this->world->lmCategory($this->en);
        $publicId = $this->startSelfEvaluation($this->member, $empty);

        $this->signInMember($this->member)->get("/cp/se/$publicId/$empty")->assertOk()->assertSee(__('main.no_selfeval_questions'));

        foreach (['en', 'lt', 'lv', 'ee', 'gr'] as $locale) {
            $source = file_get_contents(resource_path("lang/$locale/main.php"));
            $this->assertSame(1, preg_match_all("/^\\s*'no_questions'\\s*=>/m", $source), "$locale defines no_questions more than once");
        }
    }






    // -----------------------------------------------------------
    // testAnotherMembersAttemptIsNotFound
    // -----------------------------------------------------------
    //
    // A second member who learns the public id gets a 404 for
    // the questions, for answering and for the result.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testAnotherMembersAttemptIsNotFound(): void
    {
        $publicId = $this->startSelfEvaluation($this->member, $this->category);
        $stranger = $this->world->member();

        $this->signInMember($stranger)->get("/cp/se/$publicId/{$this->category}")->assertNotFound();
        $this->signInMember($stranger)->post("/cp/se/$publicId/{$this->category}", [
            'pbid' => md5(md5($this->first['question'])),
            'a'    => [$this->first['options'][0]],
        ])->assertNotFound();
        $this->signInMember($stranger)->get("/cp/se_r/$publicId")->assertNotFound();
        $this->assertFalse(DB::table('bei_users_selfeval')->where('public_id', $publicId)->exists());
    }






    // -----------------------------------------------------------
    // testRepeatedOptionScoresOnce
    // -----------------------------------------------------------
    //
    // An option posted many times counts once: the first
    // question's correct option posted three times and the
    // second's once score 200, the attempt's maximum — not the
    // 400 the public ranking would show for stored repeats.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testRepeatedOptionScoresOnce(): void
    {
        $publicId = $this->startSelfEvaluation($this->member, $this->category);
        $correct = $this->first['options'][0];
        $this->answerSelfEvaluation($this->member, $publicId, $this->category, $this->first['question'], [$correct, $correct, $correct]);
        $this->answerSelfEvaluation($this->member, $publicId, $this->category, $this->second['question'], [$this->second['options'][0]]);
        $this->signInMember($this->member)->get("/cp/se/$publicId/{$this->category}");

        $this->assertEquals(200, DB::table('bei_users_selfeval_test')->where('public_id', $publicId)->value('points'));
    }






    // -----------------------------------------------------------
    // testForgedAnswersAreNotStored
    // -----------------------------------------------------------
    //
    // An option of another question stores nothing, and a
    // question already answered takes no second answer: the
    // first question keeps the one row of its first real
    // answer.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testForgedAnswersAreNotStored(): void
    {
        $publicId = $this->startSelfEvaluation($this->member, $this->category);
        $question = $this->first['question'];

        $this->answerSelfEvaluation($this->member, $publicId, $this->category, $question, [$this->second['options'][0]]);
        $this->assertFalse(DB::table('bei_users_selfeval')->where('public_id', $publicId)->exists());

        $this->answerSelfEvaluation($this->member, $publicId, $this->category, $question, [$this->first['options'][0]]);
        $this->answerSelfEvaluation($this->member, $publicId, $this->category, $question, [$this->first['options'][1]]);

        $rows = DB::table('bei_users_selfeval')->where('public_id', $publicId)->pluck('option_id')->all();
        $this->assertEquals([$this->first['options'][0]], $rows);
    }






    // -----------------------------------------------------------
    // testFinishedAttemptKeepsItsEndDate
    // -----------------------------------------------------------
    //
    // A closed attempt keeps its end date — on the result page
    // and in both histories — and its points: a later visit
    // of its test page only leads to the result.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testFinishedAttemptKeepsItsEndDate(): void
    {
        $publicId = $this->startSelfEvaluation($this->member, $this->category);
        $this->answerSelfEvaluation($this->member, $publicId, $this->category, $this->first['question'], [$this->first['options'][0]]);
        $this->answerSelfEvaluation($this->member, $publicId, $this->category, $this->second['question'], [$this->second['options'][0]]);
        $this->signInMember($this->member)->get("/cp/se/$publicId/{$this->category}")->assertRedirect("/cp/se_r/$publicId");
        DB::table('bei_users_selfeval_test')->where('public_id', $publicId)->update(['ended' => '2020-01-01 00:00:00']);

        $this->signInMember($this->member)->get("/cp/se/$publicId/{$this->category}")->assertRedirect("/cp/se_r/$publicId");

        $attempt = DB::table('bei_users_selfeval_test')->where('public_id', $publicId)->first();
        $this->assertSame('2020-01-01 00:00:00', $attempt->ended, 'the visit moved the end date');
        $this->assertEquals(200, $attempt->points);
    }
}
