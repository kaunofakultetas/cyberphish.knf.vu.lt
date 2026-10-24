<?php

// -----------------------------------------------------------
//  [*] SelfEvaluationTest — the self-evaluation of a category
//
//  {lang}/self-evaluation-test/{cat_id} starts an attempt,
//  cp/se/{public_id}/{cat_id} asks the category's questions
//  one by one in random order and stores the chosen options,
//  and the FinishedSe middleware closes the attempt once as
//  many questions are answered as settings row 2 asks for,
//  or every question of a category that has fewer: every
//  answer worth the question's best option is added for a
//  correct choice and taken away for a wrong one — to the
//  hundredth, the total rounded once — never below zero. A
//  question takes one answer, from its own
//  options, each counted once, so a forged form scores no
//  more than an honest one; a closed attempt keeps its end
//  date. Visiting the start page again resumes the open
//  attempt. The first finished self-evaluation earns its
//  badge in the attempt's language, announced in the
//  member's. cp/se_r shows the result and
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
    // testAttemptTakesItsCategorysLanguage
    // -----------------------------------------------------------
    //
    // An attempt is filed under its category's language, not
    // the URL's: a lesson opens under any language's URL, and
    // its self-evaluation button keeps that URL's language.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testAttemptTakesItsCategorysLanguage(): void
    {
        $this->world->language('lt');

        $this->signInMember($this->member)->get("/lt/self-evaluation-test/{$this->category}");

        $this->assertEquals($this->en, DB::table('bei_users_selfeval_test')->where(['user_id' => $this->member->id, 'cat_id' => $this->category])->value('lang_id'));
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
    // testProgressBarCountsTowardsTheAttemptsLength
    // -----------------------------------------------------------
    //
    // In a category shorter than the self-evaluation length
    // the progress bar counts towards the questions the
    // category holds, the number the attempt closes at: one of
    // two answered is 50%, where it once showed 33% of the
    // setting's three and never filled.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testProgressBarCountsTowardsTheAttemptsLength(): void
    {
        $this->world->setting(2, 'self_eval_pertime', null, null, 3);
        $publicId = $this->startSelfEvaluation($this->member, $this->category);
        $this->answerSelfEvaluation($this->member, $publicId, $this->category, $this->first['question'], [$this->first['options'][0]]);

        $this->signInMember($this->member)->get("/cp/se/$publicId/{$this->category}")
            ->assertOk()
            ->assertSee('width: 50%', false)
            ->assertDontSee('width: 33%', false);
    }






    // -----------------------------------------------------------
    // testAttemptWithMoreAnswersThanItsLengthCloses
    // -----------------------------------------------------------
    //
    // An attempt that holds more answers than it now asks —
    // the length was lowered while it was open — closes at its
    // next page instead of asking on for good.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testAttemptWithMoreAnswersThanItsLengthCloses(): void
    {
        $this->world->setting(2, 'self_eval_pertime', null, null, 3);
        $this->world->selfEvalQuestion($this->category, [['Hover first', true, 100], ['Click it', false, 0]]);
        $publicId = $this->startSelfEvaluation($this->member, $this->category);
        $this->answerSelfEvaluation($this->member, $publicId, $this->category, $this->first['question'], [$this->first['options'][0]]);
        $this->answerSelfEvaluation($this->member, $publicId, $this->category, $this->second['question'], [$this->second['options'][0]]);
        $this->world->setting(2, 'self_eval_pertime', null, null, 1);

        $this->signInMember($this->member)->get("/cp/se/$publicId/{$this->category}")->assertRedirect("/cp/se_r/$publicId");

        $this->assertEquals(1, DB::table('bei_users_selfeval_test')->where('public_id', $publicId)->value('finished'));
    }






    // -----------------------------------------------------------
    // testLengthDroppingToZeroClosesTheAttempt
    // -----------------------------------------------------------
    //
    // A length of 0 saved while an attempt holds an answer
    // closes the attempt at its next page — the page once
    // divided its progress by the 0 and answered with a 500.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testLengthDroppingToZeroClosesTheAttempt(): void
    {
        $publicId = $this->startSelfEvaluation($this->member, $this->category);
        $this->answerSelfEvaluation($this->member, $publicId, $this->category, $this->first['question'], [$this->first['options'][0]]);
        $this->world->setting(2, 'self_eval_pertime', null, null, 0);

        $this->signInMember($this->member)->get("/cp/se/$publicId/{$this->category}")->assertRedirect("/cp/se_r/$publicId");
    }






    // -----------------------------------------------------------
    // testQuestionWithoutAnswersIsNeverAsked
    // -----------------------------------------------------------
    //
    // A question saved without a type or options has no answer
    // button, so the page never draws it and the attempt does
    // not wait for it: here the length is three, the category
    // holds two answerable questions and one empty one, and
    // answering the two closes the attempt.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testQuestionWithoutAnswersIsNeverAsked(): void
    {
        $this->world->setting(2, 'self_eval_pertime', null, null, 3);
        DB::table('bei_selfeval_question')->insert(['cat_id' => $this->category, 'question' => 'A question nobody can answer', 'q_type' => null]);
        $publicId = $this->startSelfEvaluation($this->member, $this->category);
        $this->answerSelfEvaluation($this->member, $publicId, $this->category, $this->first['question'], [$this->first['options'][0]]);

        $this->signInMember($this->member)->get("/cp/se/$publicId/{$this->category}")
            ->assertOk()
            ->assertSee('Turn on 2FA')
            ->assertDontSee('A question nobody can answer');

        $this->answerSelfEvaluation($this->member, $publicId, $this->category, $this->second['question'], [$this->second['options'][0]]);
        $this->signInMember($this->member)->get("/cp/se/$publicId/{$this->category}")->assertRedirect("/cp/se_r/$publicId");
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
    // testTimesShowInLocalTime
    // -----------------------------------------------------------
    //
    // The attempt's times are stored in UTC and shown in the
    // site's local time, Eastern European: an attempt started
    // at 22:30 UTC on a summer evening shows 01:30 of the next
    // day on its result page and in the history.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testTimesShowInLocalTime(): void
    {
        $publicId = $this->startSelfEvaluation($this->member, $this->category);
        $this->answerSelfEvaluation($this->member, $publicId, $this->category, $this->first['question'], [$this->first['options'][0]]);
        $this->answerSelfEvaluation($this->member, $publicId, $this->category, $this->second['question'], [$this->second['options'][0]]);
        $this->signInMember($this->member)->get("/cp/se/$publicId/{$this->category}");
        DB::table('bei_users_selfeval_test')->where('public_id', $publicId)->update(['started' => '2026-07-14 22:30:00', 'ended' => '2026-07-14 22:40:00']);

        $this->signInMember($this->member)->get("/cp/se_r/$publicId")->assertSee('2026-07-15 01:30:00')->assertSee('2026-07-15 01:40:00');
        $this->signInMember($this->member)->get('/cp/self-evaluation-history')->assertSee('2026-07-15 01:30:00')->assertDontSee('2026-07-14 22:30:00');
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
    // testAnswerStoredTwiceScoresOnce
    // -----------------------------------------------------------
    //
    // Two posts of one answer at the same moment once stored it
    // twice and scored it twice. The save now locks the
    // attempt while it checks and stores; should an attempt
    // hold such a pair from before, its score still counts the
    // option once, and its results mark it once.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testAnswerStoredTwiceScoresOnce(): void
    {
        $publicId = $this->startSelfEvaluation($this->member, $this->category);
        $this->answerSelfEvaluation($this->member, $publicId, $this->category, $this->first['question'], [$this->first['options'][0]]);
        $row = (array) DB::table('bei_users_selfeval')->where('public_id', $publicId)->first();
        unset($row['id']);
        DB::table('bei_users_selfeval')->insert($row);
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






    // -----------------------------------------------------------
    // testBadgeTakesTheAttemptsLanguage
    // -----------------------------------------------------------
    //
    // An attempt takes its category's language, and so does
    // badge_self_evaluation_test: a Lithuanian attempt
    // finished after an English page — in another tab, or
    // after signing in again — earns the Lithuanian badge.
    // FinishedSe once filed it under the session's language.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testBadgeTakesTheAttemptsLanguage(): void
    {
        $lt = $this->world->language('lt');
        $category = $this->world->lmCategory($lt);
        $question = $this->world->selfEvalQuestion($category, [['Right', true, 100], ['Wrong', false, 0]]);
        $location = $this->signInMember($this->member)->get("/lt/self-evaluation-test/$category")->assertRedirect()->headers->get('Location');
        $publicId = explode('/', parse_url($location, PHP_URL_PATH))[3];
        $this->signInMember($this->member)->get('/en/news')->assertOk();
        $this->answerSelfEvaluation($this->member, $publicId, $category, $question['question'], [$question['options'][0]]);

        $this->signInMember($this->member)->get("/cp/se/$publicId/$category")->assertRedirect("/cp/se_r/$publicId");

        $this->assertEquals([$lt], DB::table('bei_users_badges')
            ->where(['user_id' => $this->member->id, 'name' => 'badge_self_evaluation_test'])
            ->pluck('lang_id')->map(function ($id) {
                return (int) $id;
            })->all());
    }






    // -----------------------------------------------------------
    // testAnswerPostedAsAListIsNoServerError
    // -----------------------------------------------------------
    //
    // An answer whose question hash comes as a list stores
    // nothing and leads back to the question. The list once
    // reached the query's one binding as several values, which
    // PDO refused: a 500.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testAnswerPostedAsAListIsNoServerError(): void
    {
        $publicId = $this->startSelfEvaluation($this->member, $this->category);

        $this->signInMember($this->member)->post("/cp/se/$publicId/{$this->category}", [
            'pbid' => [md5(md5($this->first['question'])), 'x'],
            'a'    => [$this->first['options'][0]],
        ])->assertRedirect("/cp/se/$publicId/{$this->category}");

        $this->assertFalse(DB::table('bei_users_selfeval')->where('public_id', $publicId)->exists());
    }






    // -----------------------------------------------------------
    // testPartlyWorthyOptionsAddUpExactly
    // -----------------------------------------------------------
    //
    // A question's right options share 100 points — 33.33
    // each when three are right — and the score counts them
    // as stored, rounded to a whole number once, at the end:
    // all three right is 100, two of them 67, two and the
    // wrong one 33. The points were once cut to whole numbers
    // first, so all three right scored 99 and two 66;
    // production holds 153 right options of 33.33.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testPartlyWorthyOptionsAddUpExactly(): void
    {
        $category = $this->world->lmCategory($this->en);
        $question = $this->world->selfEvalQuestion($category, [
            ['First', true, 33.33], ['Second', true, 33.33], ['Third', true, 33.33], ['Wrong', false, 0],
        ]);
        list($first, $second, $third, $wrong) = $question['options'];
        $answers = [
            'all three right'         => [[$first, $second, $third], 100],
            'two of them'             => [[$first, $second], 67],
            'two and the wrong one'   => [[$first, $second, $wrong], 33],
        ];

        foreach ($answers as $case => list($options, $points)) {
            $member = $this->world->member();
            $publicId = $this->startSelfEvaluation($member, $category);
            $this->answerSelfEvaluation($member, $publicId, $category, $question['question'], $options);
            $this->signInMember($member)->get("/cp/se/$publicId/$category")->assertRedirect("/cp/se_r/$publicId");

            $this->assertEquals($points, DB::table('bei_users_selfeval_test')->where('public_id', $publicId)->value('points'), $case);
        }
    }






    // -----------------------------------------------------------
    // testClosedAttemptStaysClosed
    // -----------------------------------------------------------
    //
    // An attempt closed before leads to its results and takes
    // no more answers, its end date and points as they were,
    // when attempts grow longer after it closed — here a
    // question added to a category that had fewer than the
    // length. The middleware once looked at the attempt's
    // closing only when its answers reached the length, and
    // the closed attempt's question page opened again and
    // took answers no score counted
    // (selfeval-closed-attempt-reopens).
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testClosedAttemptStaysClosed(): void
    {
        $this->world->setting(2, 'self_eval_pertime', null, null, 5);
        $publicId = $this->startSelfEvaluation($this->member, $this->category);
        $this->answerSelfEvaluation($this->member, $publicId, $this->category, $this->first['question'], [$this->first['options'][0]]);
        $this->answerSelfEvaluation($this->member, $publicId, $this->category, $this->second['question'], [$this->second['options'][0]]);
        $this->signInMember($this->member)->get("/cp/se/$publicId/{$this->category}")->assertRedirect("/cp/se_r/$publicId");
        $closed = DB::table('bei_users_selfeval_test')->where('public_id', $publicId)->first();
        $third = $this->world->selfEvalQuestion($this->category, [['Lock the screen', true, 100], ['Leave it open', false, 0]]);

        $this->signInMember($this->member)->get("/cp/se/$publicId/{$this->category}")->assertRedirect("/cp/se_r/$publicId");
        $this->answerSelfEvaluation($this->member, $publicId, $this->category, $third['question'], [$third['options'][0]]);

        $this->assertSame(2, DB::table('bei_users_selfeval')->where('public_id', $publicId)->count(), 'the closed attempt took another answer');
        $after = DB::table('bei_users_selfeval_test')->where('public_id', $publicId)->first();
        $this->assertEquals([1, $closed->ended, $closed->points], [$after->finished, $after->ended, $after->points]);
    }






    // -----------------------------------------------------------
    // testNewAttemptWaitsForTheCategorysLessons
    // -----------------------------------------------------------
    //
    // A new attempt starts only once the member has done every
    // lesson of the category, as the lesson page's button
    // offers it: before that the start address leads to the
    // learning material and stores nothing. The address alone
    // once started an attempt, whose close earned
    // badge_self_evaluation_test and counted in the progress
    // and the ranking (selfeval-starts-without-lessons).
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testNewAttemptWaitsForTheCategorysLessons(): void
    {
        $lesson = $this->world->lmContent($this->category, $this->en);

        $this->signInMember($this->member)->get("/en/self-evaluation-test/{$this->category}")
            ->assertRedirect('/en/cp/learning-material');
        $this->assertFalse(DB::table('bei_users_selfeval_test')->where(['user_id' => $this->member->id, 'cat_id' => $this->category])->exists(), 'an attempt started before the lessons');

        $this->world->lessonDone($this->member->id, $lesson);

        $this->startSelfEvaluation($this->member, $this->category);
    }






    // -----------------------------------------------------------
    // testOpenAttemptResumesAfterALessonIsAdded
    // -----------------------------------------------------------
    //
    // The lessons gate only a new attempt: one the member has
    // open still resumes from the start address, and its
    // questions still open, when the category got a lesson
    // after it began.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testOpenAttemptResumesAfterALessonIsAdded(): void
    {
        $publicId = $this->startSelfEvaluation($this->member, $this->category);
        $this->world->lmContent($this->category, $this->en);

        $this->assertSame($publicId, $this->startSelfEvaluation($this->member, $this->category));
        $this->signInMember($this->member)->get("/cp/se/$publicId/{$this->category}")->assertOk();
    }






    // -----------------------------------------------------------
    // testAnswerOfNestedListsIsRefused
    // -----------------------------------------------------------
    //
    // An answer whose options hold a list — beside another
    // option or alone — goes back to the question, nothing
    // stored, as an answer with no option does. Such a list
    // once reached array_unique or whereIn, a 500
    // (selfeval-nested-answer-500).
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testAnswerOfNestedListsIsRefused(): void
    {
        $publicId = $this->startSelfEvaluation($this->member, $this->category);
        list($right, $wrong) = $this->first['options'];

        foreach (['two options, one a list' => [[$right], $wrong], 'one option, a list' => [[$right, $wrong]]] as $case => $answer) {
            $this->signInMember($this->member)->post("/cp/se/$publicId/{$this->category}", [
                'pbid' => md5(md5($this->first['question'])),
                'a'    => $answer,
            ])->assertRedirect("/cp/se/$publicId/{$this->category}");
        }

        $this->assertFalse(DB::table('bei_users_selfeval')->where('public_id', $publicId)->exists());
    }






    // -----------------------------------------------------------
    // testResultsOfferAnotherAttemptOnlyWhereOneWouldStart
    // -----------------------------------------------------------
    //
    // The results page's do-it-again button follows the start
    // address: it shows once every lesson of the category is
    // done, and while the member has an attempt of the
    // category open, which the address resumes; when the
    // category gained a lesson the member has not done, it is
    // gone, as the lesson page's button is. It showed always,
    // and since new attempts wait for the lessons it led such
    // a member to the learning material with no word why.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testResultsOfferAnotherAttemptOnlyWhereOneWouldStart(): void
    {
        $attempt = $this->world->selfEvalAttempt($this->member->id, $this->category, $this->en);
        $publicId = DB::table('bei_users_selfeval_test')->where('id', $attempt)->value('public_id');
        $button = "/self-evaluation-test/{$this->category}\"";

        $this->signInMember($this->member)->get("/cp/se_r/$publicId")->assertOk()->assertSee($button, false);

        $this->world->lmContent($this->category, $this->en);

        $this->signInMember($this->member)->get("/cp/se_r/$publicId")->assertOk()->assertDontSee($button, false);

        $this->world->selfEvalAttempt($this->member->id, $this->category, $this->en, ['finished' => 0, 'ended' => null]);

        $this->signInMember($this->member)->get("/cp/se_r/$publicId")->assertOk()->assertSee($button, false);
    }
}
