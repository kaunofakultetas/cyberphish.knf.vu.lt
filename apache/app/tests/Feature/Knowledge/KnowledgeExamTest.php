<?php

// -----------------------------------------------------------
//  [*] KnowledgeExamTest — the final knowledge test
//
//  The members-only knowledge test of a language
//  (KnowledgeTestController): POST knowledge_start draws
//  the questions settings row 3 asks for — so many per
//  learning-material category — into a new attempt, or
//  resumes the open one; the progress page asks the
//  unanswered questions one by one, and once all are
//  answered it closes the attempt with the share of correct
//  answers as its result. A result above 74 passes, earns
//  badge_final_test with the first finished attempt, and
//  unlocks the certificate (CertificateTest). The scoring
//  defects are pinned in KnowledgeDefectsTest.
//
//  Used by:
//    - PHPUnit — the Feature suite (runTests.sh)
// -----------------------------------------------------------


namespace Tests\Feature\Knowledge;

use Illuminate\Support\Facades\DB;
use Tests\FeatureTestCase;
use Tests\Support\Flows;








// -----------------------------------------------------------
// KnowledgeExamTest
// -----------------------------------------------------------
//
// One English category with three questions — each with one
// correct option, listed first, and two wrong ones — all
// three drawn for a test, and a member, for every test.
//
// Used by:
//   - PHPUnit — the Feature suite (runTests.sh)
// -----------------------------------------------------------

class KnowledgeExamTest extends FeatureTestCase
{

    // Starting the test and answering its questions
    use Flows;

    // The English id, the category, its questions with their
    // option ids, and the member of the test running now
    private $en;
    private $category;
    private $questions = [];
    private $member;






    // -----------------------------------------------------------
    // setUp
    // -----------------------------------------------------------
    //
    // The category "Phishing basics", its three questions, the
    // limits drawing all three, and a member; the next attempt
    // ids are covered the way production's are
    // (World::coverKnowledgeAttempts).
    //
    // Used by:
    //   - PHPUnit, before every test
    // -----------------------------------------------------------

    protected function setUp(): void
    {
        parent::setUp();
        $this->en = $this->world->language('en');
        $this->category = $this->world->lmCategory($this->en, ['name' => 'Phishing basics']);
        for ($i = 0; $i < 3; $i++) {
            $this->questions[] = $this->world->knowledgeQuestion($this->category, [['Right', true], ['Wrong', false], ['Also wrong', false]]);
        }
        $this->world->knowledgeLimits([$this->en => [$this->category => 3]]);
        $this->world->coverKnowledgeAttempts(3);
        $this->member = $this->world->member();
    }






    // -----------------------------------------------------------
    // testTestNeedsAnAccount
    // -----------------------------------------------------------
    //
    // A visitor is sent to the login form.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testTestNeedsAnAccount(): void
    {
        $this->get('/en/knowledge_test')->assertRedirect('/login');
    }






    // -----------------------------------------------------------
    // testTestPageOffersTheStart
    // -----------------------------------------------------------
    //
    // A member who has not tried yet sees no tries used and
    // the start form.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testTestPageOffersTheStart(): void
    {
        $this->signInMember($this->member)->get('/en/knowledge_test')
            ->assertOk()
            ->assertSee('0/3')
            ->assertSee('en/knowledge_start', false);
    }






    // -----------------------------------------------------------
    // testStartDrawsTheQuestionsTheSettingsAsk
    // -----------------------------------------------------------
    //
    // The new attempt holds the three questions, none
    // answered, unfinished and without a result.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testStartDrawsTheQuestionsTheSettingsAsk(): void
    {
        $publicId = $this->startKnowledgeTest($this->member);

        $attempt = $this->attemptRow($publicId);
        $drawn = json_decode($attempt->question_set, true);
        $this->assertEqualsCanonicalizing(array_column($this->questions, 'question'), array_keys($drawn));
        $this->assertSame([0, 0, 0], array_values($drawn));
        $this->assertEquals(0, $attempt->finished);
        $this->assertEquals(0, $attempt->results);
    }






    // -----------------------------------------------------------
    // testLimitDrawsOnlyThatMany
    // -----------------------------------------------------------
    //
    // A limit of two draws two of the category's three
    // questions.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testLimitDrawsOnlyThatMany(): void
    {
        $this->world->knowledgeLimits([$this->en => [$this->category => 2]]);

        $drawn = json_decode($this->attemptRow($this->startKnowledgeTest($this->member))->question_set, true);

        $this->assertCount(2, $drawn);
        $this->assertEmpty(array_diff(array_keys($drawn), array_column($this->questions, 'question')));
    }






    // -----------------------------------------------------------
    // testStartingAgainResumesTheOpenTest
    // -----------------------------------------------------------
    //
    // A second start while a test is open returns to it
    // instead of drawing another.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testStartingAgainResumesTheOpenTest(): void
    {
        $publicId = $this->startKnowledgeTest($this->member);

        $this->assertSame($publicId, $this->startKnowledgeTest($this->member));
        $this->assertSame(1, DB::table('bei_users_knowledge_test')->where('user_id', $this->member->id)->count());
    }






    // -----------------------------------------------------------
    // testTestPageResumesTheOpenTest
    // -----------------------------------------------------------
    //
    // With a test open, the test page sends the member back
    // into it.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testTestPageResumesTheOpenTest(): void
    {
        $publicId = $this->startKnowledgeTest($this->member);

        $this->signInMember($this->member)->get('/en/knowledge_test')
            ->assertRedirect("/en/knowledge_test/progress/$publicId");
    }






    // -----------------------------------------------------------
    // testProgressAsksAnUnansweredQuestion
    // -----------------------------------------------------------
    //
    // The progress page shows a question with its options.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testProgressAsksAnUnansweredQuestion(): void
    {
        $publicId = $this->startKnowledgeTest($this->member);

        $html = $this->signInMember($this->member)->get("/en/knowledge_test/progress/$publicId")
            ->assertOk()
            ->assertSee('Also wrong')
            ->getContent();
        $this->assertMatchesRegularExpression('/Knowledge question \d+/', $html);
    }






    // -----------------------------------------------------------
    // testAnswerMarksTheQuestionAndRecordsTheChoice
    // -----------------------------------------------------------
    //
    // The question is marked answered in the attempt and the
    // choice is recorded as correct.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testAnswerMarksTheQuestionAndRecordsTheChoice(): void
    {
        $publicId = $this->startKnowledgeTest($this->member);
        $question = $this->questions[0];

        $this->answerKnowledgeQuestion($this->member, $publicId, $question['question'], [$question['options'][0]])
            ->assertRedirect("/en/knowledge_test/progress/$publicId");

        $drawn = json_decode($this->attemptRow($publicId)->question_set, true);
        $this->assertSame(1, $drawn[$question['question']]);
        $this->assertEquals(1, DB::table('bei_users_knowledge_options')
            ->where(['public_id' => $publicId, 'question_id' => $question['question']])->value('correct'));
    }






    // -----------------------------------------------------------
    // testThreeRightAnswersPass
    // -----------------------------------------------------------
    //
    // All three right: the attempt closes with 100, the page
    // congratulates, and the first finished pass earns
    // badge_final_test.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testThreeRightAnswersPass(): void
    {
        $publicId = $this->finishWith([0, 0, 0]);

        $attempt = $this->attemptRow($publicId);
        $this->assertEquals(1, $attempt->finished);
        $this->assertEquals(100, $attempt->results);
        $this->assertNotNull($attempt->ended);
        $this->assertTrue(DB::table('bei_users_badges')
            ->where(['user_id' => $this->member->id, 'name' => 'badge_final_test'])->exists());
    }






    // -----------------------------------------------------------
    // testTwoRightOfThreeFail
    // -----------------------------------------------------------
    //
    // Two right of three is 67 — below the pass mark of 75 —
    // so no badge.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testTwoRightOfThreeFail(): void
    {
        $publicId = $this->finishWith([0, 0, 1]);

        $this->assertEquals(67, $this->attemptRow($publicId)->results);
        $this->assertFalse(DB::table('bei_users_badges')->where('user_id', $this->member->id)->exists());
    }






    // -----------------------------------------------------------
    // testFinishedResultsAreListed
    // -----------------------------------------------------------
    //
    // After a finished test the test page counts the try and
    // lists the result.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testFinishedResultsAreListed(): void
    {
        $this->finishWith([0, 0, 1]);

        $this->signInMember($this->member)->get('/en/knowledge_test')
            ->assertOk()
            ->assertSee('1/3')
            ->assertSee('67%');
    }






    // -----------------------------------------------------------
    // testNewTestStartsAfterTheLastIsFinished
    // -----------------------------------------------------------
    //
    // With no test open, a start draws a fresh attempt.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testNewTestStartsAfterTheLastIsFinished(): void
    {
        $first = $this->finishWith([0, 0, 1]);

        $this->assertNotSame($first, $this->startKnowledgeTest($this->member));
        $this->assertSame(2, DB::table('bei_users_knowledge_test')->where('user_id', $this->member->id)->count());
    }






    // -----------------------------------------------------------
    // finishWith
    // -----------------------------------------------------------
    //
    // Starts a test, answers the three questions with the
    // options at the given positions (0 is the correct one)
    // and opens the progress page, which closes the attempt;
    // returns its public id.
    //
    // Used by:
    //   - the result tests of this class
    // -----------------------------------------------------------

    private function finishWith(array $positions): string
    {
        $publicId = $this->startKnowledgeTest($this->member);
        foreach ($this->questions as $i => $question) {
            $this->answerKnowledgeQuestion($this->member, $publicId, $question['question'], [$question['options'][$positions[$i]]]);
        }
        $this->signInMember($this->member)->get("/en/knowledge_test/progress/$publicId")->assertOk();
        return $publicId;
    }






    // -----------------------------------------------------------
    // attemptRow
    // -----------------------------------------------------------
    //
    // The attempt's row.
    //
    // Used by:
    //   - testStartDrawsTheQuestionsTheSettingsAsk,
    //     testLimitDrawsOnlyThatMany,
    //     testAnswerMarksTheQuestionAndRecordsTheChoice,
    //     testThreeRightAnswersPass, testTwoRightOfThreeFail
    //     (above)
    // -----------------------------------------------------------

    private function attemptRow(string $publicId): \stdClass
    {
        return DB::table('bei_users_knowledge_test')->where('public_id', $publicId)->first();
    }
}
