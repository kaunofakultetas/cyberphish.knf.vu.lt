<?php

// -----------------------------------------------------------
//  [*] KnowledgeExamTest — the final knowledge test
//
//  The members-only knowledge test of a language
//  (KnowledgeTestController): POST knowledge_start draws
//  the questions settings row 3 asks for — so many per
//  learning-material category — into a new attempt, or
//  resumes the open one, and draws none after three tries;
//  the progress page asks the unanswered questions one by
//  one, for the member's own attempt only, and once all are
//  answered it closes the attempt for good, with the share
//  of the drawn questions answered right as its result — a
//  question answered with no option counts as wrong. Each
//  answer counts once, under its question's category. A
//  result above 74 passes, earns badge_final_test on
//  whichever try, and unlocks the certificate
//  (CertificateTest). A start with nothing to draw — a
//  language the limits leave out — stores no attempt. The
//  lessons gate and the badge both follow the language of
//  the attempt — the URL's at the start — and not the
//  session's, nor the code the closing visit's URL carries.
//
//  Used by:
//    - PHPUnit — the Feature suite (runTests.sh)
// -----------------------------------------------------------


namespace Tests\Feature\Knowledge;

use Dom\HTMLDocument;
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
    // limits drawing all three, and a member.
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
        $this->member = $this->world->member();
        $this->world->badge($this->member->id, $this->en, 'badge_all_presentations');
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
    // congratulates, and the pass earns badge_final_test.
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
    // so no badge_final_test.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testTwoRightOfThreeFail(): void
    {
        $publicId = $this->finishWith([0, 0, 1]);

        $this->assertEquals(67, $this->attemptRow($publicId)->results);
        $this->assertFalse(DB::table('bei_users_badges')
            ->where(['user_id' => $this->member->id, 'name' => 'badge_final_test'])->exists());
    }






    // -----------------------------------------------------------
    // testSkippedQuestionsCountAsWrong
    // -----------------------------------------------------------
    //
    // A question answered with no option counts against the
    // result: one right of three is 33, a fail.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testSkippedQuestionsCountAsWrong(): void
    {
        $publicId = $this->finishWith([0, null, null]);

        $this->assertEquals(33, $this->attemptRow($publicId)->results);
    }






    // -----------------------------------------------------------
    // testTestWithEveryQuestionSkippedCloses
    // -----------------------------------------------------------
    //
    // Skipping every question closes the attempt with 0 — the
    // progress page opens, and the member is free to start
    // another try.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testTestWithEveryQuestionSkippedCloses(): void
    {
        $publicId = $this->finishWith([null, null, null]);

        $attempt = $this->attemptRow($publicId);
        $this->assertEquals(1, $attempt->finished);
        $this->assertEquals(0, $attempt->results);
    }






    // -----------------------------------------------------------
    // testPassOnALaterTryEarnsTheBadge
    // -----------------------------------------------------------
    //
    // A member who failed the first try earns
    // badge_final_test with the pass on the second.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testPassOnALaterTryEarnsTheBadge(): void
    {
        $this->world->knowledgeAttempt($this->member->id, $this->en, ['results' => 40]);

        $this->finishWith([0, 0, 0]);

        $this->assertTrue(DB::table('bei_users_badges')
            ->where(['user_id' => $this->member->id, 'name' => 'badge_final_test'])->exists());
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
    // testLanguageMissingFromTheLimitsDrawsNothing
    // -----------------------------------------------------------
    //
    // A language with a category and a question but no entry
    // in the limits — its first category came after they were
    // last saved — has nothing to draw: the start goes back to
    // the test page and stores no attempt.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testLanguageMissingFromTheLimitsDrawsNothing(): void
    {
        $lt = $this->world->language('lt');
        $this->world->badge($this->member->id, $lt, 'badge_all_presentations');
        $this->world->knowledgeQuestion($this->world->lmCategory($lt), [['Right', true], ['Wrong', false]]);
        $this->signInMember($this->member)->get('/lt/knowledge_test')->assertOk()->assertSee('/lt/knowledge_start');

        $this->signInMember($this->member)->post('/lt/knowledge_start')->assertRedirect('/lt/knowledge_test');

        $this->assertFalse(DB::table('bei_users_knowledge_test')->where(['user_id' => $this->member->id, 'lang_id' => $lt])->exists());
    }






    // -----------------------------------------------------------
    // testBlankLimitDrawsNothing
    // -----------------------------------------------------------
    //
    // A limit stored blank draws no question of its category —
    // the 0 the settings page shows for it — where it once drew
    // every question the category holds.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testBlankLimitDrawsNothing(): void
    {
        $other = $this->world->lmCategory($this->en, ['name' => 'Social engineering']);
        $this->world->knowledgeQuestion($other, [['Right', true], ['Wrong', false]]);
        $this->world->knowledgeQuestion($other, [['Right', true], ['Wrong', false]]);
        $this->world->knowledgeLimits([$this->en => [$this->category => 3, $other => '']]);

        $publicId = $this->startKnowledgeTest($this->member);

        $drawn = array_keys(json_decode(DB::table('bei_users_knowledge_test')->where('public_id', $publicId)->value('question_set'), true));
        $this->assertEqualsCanonicalizing(array_map(function (array $question) {
            return $question['question'];
        }, $this->questions), $drawn);
    }






    // -----------------------------------------------------------
    // testTestNeedsEveryLessonDone
    // -----------------------------------------------------------
    //
    // The test opens once the member has done every lesson of
    // the language, badge_all_presentations, which the
    // sidebar's button waits for too: before that its page and
    // its start lead to the lessons, and no attempt is drawn.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testTestNeedsEveryLessonDone(): void
    {
        $member = $this->world->member();

        $this->signInMember($member)->get('/en/knowledge_test')->assertRedirect('/en/cp/learning-material');
        $this->signInMember($member)->post('/en/knowledge_start')->assertRedirect('/en/cp/learning-material');

        $this->assertFalse(DB::table('bei_users_knowledge_test')->where('user_id', $member->id)->exists());
    }






    // -----------------------------------------------------------
    // testFourthAttemptIsRefused
    // -----------------------------------------------------------
    //
    // knowledge_test.blade.php hides the start form after three
    // tries, and start refuses a fourth posted by hand too: the
    // member goes back to the test page and no attempt is
    // drawn.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testFourthAttemptIsRefused(): void
    {
        for ($try = 0; $try < 3; $try++) {
            $this->world->knowledgeAttempt($this->member->id, $this->en, ['results' => 40]);
        }

        $this->signInMember($this->member)->post('/en/knowledge_start')->assertRedirect('/en/knowledge_test');

        $this->assertSame(3, DB::table('bei_users_knowledge_test')->where('user_id', $this->member->id)->count());
    }






    // -----------------------------------------------------------
    // testUnknownAttemptIsNotFound
    // -----------------------------------------------------------
    //
    // A public id no attempt has is a 404.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testUnknownAttemptIsNotFound(): void
    {
        $this->signInMember($this->member)->get('/en/knowledge_test/progress/no-such-attempt')->assertNotFound();
    }






    // -----------------------------------------------------------
    // testAnotherMembersAttemptIsNotFound
    // -----------------------------------------------------------
    //
    // The public id alone opens nothing: a second member who
    // has it gets a 404 for the page and for an answer, and
    // the attempt takes no answer row.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testAnotherMembersAttemptIsNotFound(): void
    {
        $publicId = $this->startKnowledgeTest($this->member);
        $stranger = $this->world->member();
        $question = $this->questions[0];

        $this->signInMember($stranger)->get("/en/knowledge_test/progress/$publicId")->assertNotFound();
        $this->answerKnowledgeQuestion($stranger, $publicId, $question['question'], [$question['options'][0]])->assertNotFound();

        $this->assertFalse(DB::table('bei_users_knowledge_options')->where('public_id', $publicId)->exists());
    }






    // -----------------------------------------------------------
    // testAnswerIsFiledUnderItsQuestionsCategory
    // -----------------------------------------------------------
    //
    // An answer is filed under its own question's category,
    // whatever the attempt's id. The answer's category used to
    // be looked up by the attempt's id, which broke answering
    // once attempt ids passed the question ids; here the
    // attempt gets an id no question has.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testAnswerIsFiledUnderItsQuestionsCategory(): void
    {
        // Moves the attempt counter past any id an existing
        // question has
        $other = $this->world->member();
        do {
            $this->world->knowledgeAttempt($other->id, $this->en);
        } while (DB::table('bei_knowledge_test')->where('id', $this->world->nextId('bei_users_knowledge_test'))->exists());
        $publicId = $this->startKnowledgeTest($this->member);
        $question = $this->questions[0];

        $this->answerKnowledgeQuestion($this->member, $publicId, $question['question'], [$question['options'][0]])
            ->assertRedirect("/en/knowledge_test/progress/$publicId");

        $this->assertEquals(
            $this->category,
            DB::table('bei_users_knowledge_options')->where('public_id', $publicId)->value('cat_id')
        );
    }






    // -----------------------------------------------------------
    // testRepeatedOptionCountsOnce
    // -----------------------------------------------------------
    //
    // A question takes one answer, from an option of its own:
    // the correct option posted eight times for the first
    // question and wrong answers for the other two score one
    // of three, 33 — a fail.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testRepeatedOptionCountsOnce(): void
    {
        $publicId = $this->startKnowledgeTest($this->member);
        $first = $this->questions[0];
        $this->answerKnowledgeQuestion($this->member, $publicId, $first['question'], array_fill(0, 8, $first['options'][0]));
        foreach (array_slice($this->questions, 1) as $question) {
            $this->answerKnowledgeQuestion($this->member, $publicId, $question['question'], [$question['options'][1]]);
        }
        $this->signInMember($this->member)->get("/en/knowledge_test/progress/$publicId")->assertOk();

        $this->assertEquals(33, $this->attemptRow($publicId)->results);
    }






    // -----------------------------------------------------------
    // testAnswerStoredTwiceCountsOnce
    // -----------------------------------------------------------
    //
    // Two posts of one answer at the same moment once stored
    // two correct rows and counted both. The save now locks
    // the attempt while it checks and stores; should an
    // attempt hold such a pair from before, its result still
    // counts the question once: one right of three is 33.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testAnswerStoredTwiceCountsOnce(): void
    {
        $publicId = $this->startKnowledgeTest($this->member);
        $this->answerKnowledgeQuestion($this->member, $publicId, $this->questions[0]['question'], [$this->questions[0]['options'][0]]);
        $row = (array) DB::table('bei_users_knowledge_options')->where('public_id', $publicId)->first();
        unset($row['id']);
        DB::table('bei_users_knowledge_options')->insert($row);
        $this->answerKnowledgeQuestion($this->member, $publicId, $this->questions[1]['question'], [$this->questions[1]['options'][1]]);
        $this->answerKnowledgeQuestion($this->member, $publicId, $this->questions[2]['question'], [$this->questions[2]['options'][1]]);

        $this->signInMember($this->member)->get("/en/knowledge_test/progress/$publicId")->assertOk();

        $this->assertEquals(33, $this->attemptRow($publicId)->results);
    }






    // -----------------------------------------------------------
    // testDeletedQuestionDoesNotHoldTheAttempt
    // -----------------------------------------------------------
    //
    // A drawn question deleted before it was answered is
    // passed over and counts as wrong, as a skipped one does,
    // so the attempt still closes: two right of three is 67.
    // It once stayed open for good, its page offering nothing
    // to answer.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testDeletedQuestionDoesNotHoldTheAttempt(): void
    {
        $publicId = $this->startKnowledgeTest($this->member);
        $this->answerKnowledgeQuestion($this->member, $publicId, $this->questions[0]['question'], [$this->questions[0]['options'][0]]);
        DB::table('bei_knowledge_option')->where('question_id', $this->questions[1]['question'])->delete();
        DB::table('bei_knowledge_test')->where('id', $this->questions[1]['question'])->delete();
        $this->answerKnowledgeQuestion($this->member, $publicId, $this->questions[2]['question'], [$this->questions[2]['options'][0]]);

        $this->signInMember($this->member)->get("/en/knowledge_test/progress/$publicId")->assertOk();

        $attempt = $this->attemptRow($publicId);
        $this->assertEquals(1, $attempt->finished);
        $this->assertEquals(67, $attempt->results);
    }






    // -----------------------------------------------------------
    // testQuestionWithoutOptionsIsNotDrawn
    // -----------------------------------------------------------
    //
    // A question saved without options offers nothing to
    // choose, so the draw leaves it out, even when the limit
    // asks for every question of the category.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testQuestionWithoutOptionsIsNotDrawn(): void
    {
        $empty = $this->world->knowledgeQuestion($this->category, []);
        $this->world->knowledgeLimits([$this->en => [$this->category => 4]]);

        $publicId = $this->startKnowledgeTest($this->member);

        $drawn = array_keys(json_decode($this->attemptRow($publicId)->question_set, true));
        $this->assertNotContains($empty['question'], $drawn);
        $this->assertCount(3, $drawn);
    }






    // -----------------------------------------------------------
    // testFinishedAttemptStaysClosed
    // -----------------------------------------------------------
    //
    // A closed attempt keeps its end date — the date the
    // certificate prints — and its result when its progress
    // page is opened again.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testFinishedAttemptStaysClosed(): void
    {
        $publicId = $this->finishWith([0, 0, 0]);
        DB::table('bei_users_knowledge_test')->where('public_id', $publicId)->update(['ended' => '2020-01-01 00:00:00']);

        $this->signInMember($this->member)->get("/en/knowledge_test/progress/$publicId")->assertOk();

        $row = $this->attemptRow($publicId);
        $this->assertSame('2020-01-01 00:00:00', $row->ended, 'the visit moved the end date');
        $this->assertEquals(100, $row->results);
    }






    // -----------------------------------------------------------
    // testLessonsGateFollowsTheTestsLanguage
    // -----------------------------------------------------------
    //
    // The lessons gate looks for badge_all_presentations in
    // the URL's language, the one the attempt is filed under.
    // A member with every Lithuanian lesson done, none in
    // English, browsing in Lithuanian, who posts an English
    // start at /EN/, is sent to the lessons and draws no
    // English attempt: the gate once read the session's
    // language, which moves only for the five exact codes,
    // while the language lookup ignores case.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testLessonsGateFollowsTheTestsLanguage(): void
    {
        $lt = $this->world->language('lt');
        $member = $this->world->member();
        $this->world->badge($member->id, $lt, 'badge_all_presentations');

        $this->signInMember($member)->withSession(['lang' => 'lt'])->post('/EN/knowledge_start')
            ->assertRedirect('/EN/cp/learning-material');

        $this->assertFalse(DB::table('bei_users_knowledge_test')->where(['user_id' => $member->id, 'lang_id' => $this->en])->exists(), 'an English attempt was drawn without the English lessons');
    }






    // -----------------------------------------------------------
    // testClosingAwardsTheAttemptsLanguage
    // -----------------------------------------------------------
    //
    // The visit that closes an attempt awards badge_final_test
    // in the attempt's own language: an English pass closed
    // under /lt/ earns the English badge, and no Lithuanian
    // one. The URL's language once took it, and as later
    // visits find the attempt closed and award nothing,
    // English never got its badge.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testClosingAwardsTheAttemptsLanguage(): void
    {
        $this->world->language('lt');
        $publicId = $this->answerEverythingRight();

        $this->signInMember($this->member)->get("/lt/knowledge_test/progress/$publicId")->assertOk();

        $this->assertEquals([$this->en], DB::table('bei_users_badges')
            ->where(['user_id' => $this->member->id, 'name' => 'badge_final_test'])
            ->pluck('lang_id')->map(function ($id) {
                return (int) $id;
            })->all());
    }






    // -----------------------------------------------------------
    // testUnknownLanguageClosingIsNoServerError
    // -----------------------------------------------------------
    //
    // Under a language code no language has, the closing visit
    // closes the attempt and awards the badge of its own
    // language. It once stored badge_final_test with no
    // language, which the NOT NULL column refused — a 500, and
    // the attempt, closed already, never awarded it again.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testUnknownLanguageClosingIsNoServerError(): void
    {
        $publicId = $this->answerEverythingRight();

        $this->signInMember($this->member)->get("/xx/knowledge_test/progress/$publicId")->assertOk();

        $this->assertEquals(1, $this->attemptRow($publicId)->finished);
        $this->assertTrue(DB::table('bei_users_badges')
            ->where(['user_id' => $this->member->id, 'lang_id' => $this->en, 'name' => 'badge_final_test'])->exists());
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
        $publicId = $this->startKnowledgeTest($this->member);

        $this->signInMember($this->member)->post("/en/knowledge_test/progress/$publicId", [
            'pbid' => [md5(md5($this->questions[0]['question'])), 'x'],
            'a'    => [$this->questions[0]['options'][0]],
        ])->assertRedirect("/en/knowledge_test/progress/$publicId");

        $this->assertFalse(DB::table('bei_users_knowledge_options')->where('public_id', $publicId)->exists());
        $this->assertSame([0, 0, 0], array_values(json_decode($this->attemptRow($publicId)->question_set, true)));
    }






    // -----------------------------------------------------------
    // testQuestionWithoutACorrectOptionIsNeverDrawn
    // -----------------------------------------------------------
    //
    // A question none of whose options is correct is never
    // drawn into a member's test, however it came to be — here
    // an import block whose correct option is indented by a
    // space, a line the import skips, so the question is
    // stored with its wrong options only; the test draws the
    // setUp's three questions and leaves it out. The draw once
    // took every question with options, so a member who got
    // this one lost it whatever they chose, in a test passed
    // at 75 (knowledge-question-without-correct).
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testQuestionWithoutACorrectOptionIsNeverDrawn(): void
    {
        $category = $this->world->lmCategory($this->en);
        $this->signInManager($this->world->manager())->from("/admin-panel/lm/knowledge_test/$category")->post('/admin-panel/lm/knowledge_test/import', [
            'cat_id' => $category,
            'doc'    => $this->uploadedFile('questions.txt', "::\nQ\\: Who may ask for your password?\n =Nobody\n~Your bank\n~The IT desk\n"),
        ]);
        $imported = DB::table('bei_knowledge_test')->where('cat_id', $category)->value('id');
        $this->assertNotNull($imported, 'the import stored no question');
        $this->assertFalse(DB::table('bei_knowledge_option')->where(['question_id' => $imported, 'correct' => 1])->exists());
        $this->world->knowledgeLimits([$this->en => [$this->category => 3, $category => 1]]);

        $drawn = array_keys(json_decode($this->attemptRow($this->startKnowledgeTest($this->member))->question_set, true));

        $unanswerable = array_values(array_filter($drawn, function ($question) {
            return !DB::table('bei_knowledge_option')->where(['question_id' => $question, 'correct' => 1])->exists();
        }));
        $this->assertSame([], $unanswerable, 'the test drew a question no option of which is correct');
        $this->assertEqualsCanonicalizing(array_column($this->questions, 'question'), $drawn);
    }






    // -----------------------------------------------------------
    // testLanguageWithNothingToDrawOffersNoStart
    // -----------------------------------------------------------
    //
    // A language none of whose questions has a correct option
    // has nothing a test can draw, and its test page says so
    // (main.no_questions) instead of offering a start. The
    // page once counted every question, so a language whose
    // questions offered nothing to answer right showed a start
    // that drew none of them and came back without a word.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testLanguageWithNothingToDrawOffersNoStart(): void
    {
        $lt = $this->world->language('lt');
        $category = $this->world->lmCategory($lt);
        $this->world->knowledgeQuestion($category, [['A', false], ['B', false]]);
        $this->world->knowledgeLimits([$lt => [$category => 1]]);
        $this->world->badge($this->member->id, $lt, 'badge_all_presentations');

        $this->signInMember($this->member)->get('/lt/knowledge_test')->assertOk()
            ->assertSee(__('main.no_questions', [], 'lt'))
            ->assertDontSee('/lt/knowledge_start', false);
    }






    // -----------------------------------------------------------
    // testClosingPageCountsThePassInTheCourseProgress
    // -----------------------------------------------------------
    //
    // The page a member lands on after the last answer of a
    // passed attempt, every redirect followed, announces the
    // pass and the new badge beside a course progress that
    // counts it: the 30 per cent the pass is worth, all this
    // member has done, as the test page shows next.
    // BaseController once worked the figure out before
    // KnowledgeTestController::progress closed the attempt, so
    // the page that said "You passed!" showed 0 per cent
    // (progress-shown-before-the-close).
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testClosingPageCountsThePassInTheCourseProgress(): void
    {
        $publicId = $this->startKnowledgeTest($this->member);
        list($first, $second, $last) = $this->questions;
        foreach ([$first, $second] as $question) {
            $this->answerKnowledgeQuestion($this->member, $publicId, $question['question'], [$question['options'][0]]);
        }

        $landing = $this->signInMember($this->member)->followingRedirects()
            ->post("/en/knowledge_test/progress/$publicId", [
                'pbid' => md5(md5($last['question'])),
                'a'    => [$last['options'][0]],
            ])
            ->assertOk()
            ->assertSee('You passed!')
            ->assertSee(__('main.new_badge'), false)
            ->getContent();
        $this->signInMember($this->member)->get('/en/knowledge_test')
            ->assertOk()
            ->assertSee('<span class="float-right">30%</span>', false);

        $this->assertStringNotContainsString('<span class="float-right">0%</span>', $landing, 'the page announcing the pass shows the course progress without it');
        $this->assertStringContainsString('<span class="float-right">30%</span>', $landing);
    }






    // -----------------------------------------------------------
    // testKnowledgeQuestionPageClosesItsBoldHeader
    // -----------------------------------------------------------
    //
    // The card header of the question page closes its bold
    // tag, so none of the options to choose from shows in
    // bold. The header once left the tag open, and the browser
    // carried the bold on through the card's body to the
    // page's end, on every question of the test
    // (knowledge-page-bold).
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testKnowledgeQuestionPageClosesItsBoldHeader(): void
    {
        $publicId = $this->startKnowledgeTest($this->member);

        $html = $this->signInMember($this->member)->get("/en/knowledge_test/progress/$publicId")->assertOk()->getContent();
        $labels = HTMLDocument::createFromString($html, LIBXML_NOERROR)->querySelectorAll('.custom-control-label');

        $bold = 0;
        foreach ($labels as $label) {
            if ($label->closest('b') !== null) {
                $bold++;
            }
        }

        $this->assertCount(3, $labels);
        $this->assertSame(0, $bold, 'options of the question show in bold');
    }






    // -----------------------------------------------------------
    // answerEverythingRight
    // -----------------------------------------------------------
    //
    // Starts a test and answers its three questions right,
    // without the visit that closes it; returns the attempt's
    // public id.
    //
    // Used by:
    //   - testClosingAwardsTheAttemptsLanguage,
    //     testUnknownLanguageClosingIsNoServerError (above)
    // -----------------------------------------------------------

    private function answerEverythingRight(): string
    {
        $publicId = $this->startKnowledgeTest($this->member);
        foreach ($this->questions as $question) {
            $this->answerKnowledgeQuestion($this->member, $publicId, $question['question'], [$question['options'][0]]);
        }
        return $publicId;
    }






    // -----------------------------------------------------------
    // finishWith
    // -----------------------------------------------------------
    //
    // Starts a test, answers the three questions with the
    // options at the given positions (0 is the correct one,
    // null sends no option) and opens the progress page,
    // which closes the attempt; returns its public id.
    //
    // Used by:
    //   - the result tests of this class
    //   - testFinishedAttemptStaysClosed (above)
    // -----------------------------------------------------------

    private function finishWith(array $positions): string
    {
        $publicId = $this->startKnowledgeTest($this->member);
        foreach ($this->questions as $i => $question) {
            $options = $positions[$i] === null ? [] : [$question['options'][$positions[$i]]];
            $this->answerKnowledgeQuestion($this->member, $publicId, $question['question'], $options);
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
    //     testThreeRightAnswersPass, testTwoRightOfThreeFail,
    //     testSkippedQuestionsCountAsWrong,
    //     testTestWithEveryQuestionSkippedCloses,
    //     testRepeatedOptionCountsOnce,
    //     testFinishedAttemptStaysClosed,
    //     testUnknownLanguageClosingIsNoServerError,
    //     testAnswerPostedAsAListIsNoServerError,
    //     testQuestionWithoutACorrectOptionIsNeverDrawn
    //     (above)
    // -----------------------------------------------------------

    private function attemptRow(string $publicId): \stdClass
    {
        return DB::table('bei_users_knowledge_test')->where('public_id', $publicId)->first();
    }
}
