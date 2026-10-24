<?php

// -----------------------------------------------------------
//  [*] LearningDefectsTest — known learning defects
//
//  Each test states the behaviour the learning material and
//  the self-evaluations should have and pins the defect that
//  breaks it today (KnownDefects): the run reports them as
//  incomplete, tagged with their ids, until a fix makes one
//  pass — then the test fails as the cue to remove its pin
//  and move it into its home file.
//
//    lesson-delete-badge        — deleting the lesson a
//                                 member had left locks the
//                                 knowledge test away for good
//    lesson-double-completion   — a lesson stored twice counts
//                                 its hours twice
//    selfeval-partial-points    — a fully right question with
//                                 three right options scores 99
//    finished-course-message    — the finished-course badge is
//                                 announced on a page that
//                                 shows no message
//    selfeval-badge-language    — the self-evaluation badge
//                                 goes to the session's
//                                 language, not the attempt's
//    learning-array-input       — a field posted as a list is
//                                 a 500
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
// One pinned defect per test, named in the header; an
// English category with two one-hour lessons and a member
// for every test.
//
// Used by:
//   - PHPUnit — the Feature suite (runTests.sh)
// -----------------------------------------------------------

class LearningDefectsTest extends FeatureTestCase
{

    // Starting and answering self-evaluations
    use Flows;

    // The English id, the category, its two lessons and the
    // member of the test running now
    private $en;
    private $category;
    private $firstLesson;
    private $secondLesson;
    private $member;






    // -----------------------------------------------------------
    // setUp
    // -----------------------------------------------------------
    //
    // An English category with two lessons of an hour each.
    //
    // Used by:
    //   - PHPUnit, before every test
    // -----------------------------------------------------------

    protected function setUp(): void
    {
        parent::setUp();
        $this->en = $this->world->language('en');
        $this->category = $this->world->lmCategory($this->en);
        $this->firstLesson = $this->world->lmContent($this->category, $this->en);
        $this->secondLesson = $this->world->lmContent($this->category, $this->en);
        $this->member = $this->world->member();
    }






    // -----------------------------------------------------------
    // testDeletedLessonDoesNotLockTheTestAway
    // -----------------------------------------------------------
    //
    // badge_all_presentations, which opens the knowledge test,
    // is only awarded when a lesson is marked completed, and a
    // completed lesson shows no form to mark again: once a
    // main admin deletes the one lesson the member had left,
    // every lesson is done, yet nothing ever awards the badge.
    // Wanted: the knowledge test opens.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testDeletedLessonDoesNotLockTheTestAway(): void
    {
        $this->signInMember($this->member)->post('/cp/mark_completed', ['ued' => $this->lessonHash($this->firstLesson)]);
        $this->signInManager($this->world->manager())->post("/admin-panel/lm/content_delete/{$this->secondLesson}");
        $this->assertFalse(DB::table('bei_lm_content')->where('id', $this->secondLesson)->exists());

        $this->pinDefect('lesson-delete-badge', function () {
            $this->signInMember($this->member)->get('/en/knowledge_test')->assertOk();
        });
    }






    // -----------------------------------------------------------
    // testRepeatedCompletionCountsOnce
    // -----------------------------------------------------------
    //
    // mark_completed reads, then inserts (firstOrCreate), and
    // bei_lm_users has no unique key, so a double-clicked
    // button can store a lesson twice — and the progress sums
    // the hours of every row, the lesson's twice. One of two
    // lessons done is 12; stored twice it shows 25. Wanted: a
    // lesson counts once however often it is stored.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testRepeatedCompletionCountsOnce(): void
    {
        $this->world->lessonDone($this->member->id, $this->firstLesson);
        $this->world->lessonDone($this->member->id, $this->firstLesson);

        $this->pinDefect('lesson-double-completion', function () {
            $this->assertEquals(12, LMUsers::course_progress($this->member->id, $this->en));
        });
    }






    // -----------------------------------------------------------
    // testFullyRightMultipleAnswerScoresHundred
    // -----------------------------------------------------------
    //
    // A question's right options share 100 points — 33.33
    // each when three are right — and FinishedSe adds the
    // best option's points for each right answer, but cast to
    // a whole number first: 33 three times, 99. Production
    // holds 153 right options with such fractions. Wanted: a
    // fully right answer scores 100.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testFullyRightMultipleAnswerScoresHundred(): void
    {
        $this->world->setting(2, 'self_eval_pertime', null, null, 1);
        $question = $this->world->selfEvalQuestion($this->category, [
            ['First', true, 33.33], ['Second', true, 33.33], ['Third', true, 33.33], ['Wrong', false, 0],
        ]);
        $publicId = $this->startSelfEvaluation($this->member, $this->category);
        $this->answerSelfEvaluation($this->member, $publicId, $this->category, $question['question'], array_slice($question['options'], 0, 3));
        $this->signInMember($this->member)->get("/cp/se/$publicId/{$this->category}")->assertRedirect("/cp/se_r/$publicId");

        $this->pinDefect('selfeval-partial-points', function () use ($publicId) {
            $this->assertEquals(100, DB::table('bei_users_selfeval_test')->where('public_id', $publicId)->value('points'));
        });
    }






    // -----------------------------------------------------------
    // testFinishedCourseBadgeIsAnnounced
    // -----------------------------------------------------------
    //
    // BaseController awards badge_finished_course on whatever
    // member page first sees both of its badges and announces
    // it with session()->now(), which only that page can show
    // — most pages, the simulations list among them, have no
    // place for the message, and every later page finds the
    // badge held and stays silent. Wanted: the award reaches
    // the member, at the latest on the next page that shows
    // messages.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testFinishedCourseBadgeIsAnnounced(): void
    {
        $this->world->badge($this->member->id, $this->en, 'badge_all_presentations');
        $this->world->badge($this->member->id, $this->en, 'badge_all_simulations');
        $this->signInMember($this->member)->get('/en/simulations')->assertOk();
        $this->assertTrue(DB::table('bei_users_badges')
            ->where(['user_id' => $this->member->id, 'name' => 'badge_finished_course'])->exists());

        $this->pinDefect('finished-course-message', function () {
            $this->signInMember($this->member)->get('/cp/dashboard')->assertOk()->assertSee(__('main.new_badge'), false);
        });
    }






    // -----------------------------------------------------------
    // testSelfEvaluationBadgeTakesTheAttemptsLanguage
    // -----------------------------------------------------------
    //
    // An attempt takes its category's language, but FinishedSe
    // files badge_self_evaluation_test under the session's —
    // a Lithuanian attempt finished after an English page, in
    // another tab or after signing in again, counts in the
    // Lithuanian progress and earns the English badge. Wanted:
    // the badge of the attempt's language.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testSelfEvaluationBadgeTakesTheAttemptsLanguage(): void
    {
        $lt = $this->world->language('lt');
        $this->world->setting(2, 'self_eval_pertime', null, null, 1);
        $category = $this->world->lmCategory($lt);
        $question = $this->world->selfEvalQuestion($category, [['Right', true, 100], ['Wrong', false, 0]]);
        $location = $this->signInMember($this->member)->get("/lt/self-evaluation-test/$category")->assertRedirect()->headers->get('Location');
        $publicId = explode('/', parse_url($location, PHP_URL_PATH))[3];
        $this->signInMember($this->member)->get('/en/news')->assertOk();
        $this->answerSelfEvaluation($this->member, $publicId, $category, $question['question'], [$question['options'][0]]);
        $this->signInMember($this->member)->get("/cp/se/$publicId/$category")->assertRedirect("/cp/se_r/$publicId");

        $this->pinDefect('selfeval-badge-language', function () use ($lt) {
            $this->assertEquals([$lt], DB::table('bei_users_badges')
                ->where(['user_id' => $this->member->id, 'name' => 'badge_self_evaluation_test'])
                ->pluck('lang_id')->map(function ($id) {
                    return (int) $id;
                })->all());
        });
    }






    // -----------------------------------------------------------
    // testFieldsPostedAsListsAreNoServerError
    // -----------------------------------------------------------
    //
    // A self-evaluation answer whose question id comes as a
    // list, and a lesson completion whose lesson comes as one,
    // reach a query binding meant for one value — PDO refuses
    // the extra values, a 500. Wanted: the posts are refused
    // without a server error.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testFieldsPostedAsListsAreNoServerError(): void
    {
        $this->world->setting(2, 'self_eval_pertime', null, null, 1);
        $question = $this->world->selfEvalQuestion($this->category, [['Right', true, 100], ['Wrong', false, 0]]);
        $publicId = $this->startSelfEvaluation($this->member, $this->category);

        $statuses = [
            'self-evaluation answer' => $this->signInMember($this->member)
                ->post("/cp/se/$publicId/{$this->category}", ['pbid' => ['x', 'y'], 'a' => [$question['options'][0]]])
                ->getStatusCode(),
            'lesson completion'      => $this->signInMember($this->member)
                ->post('/cp/mark_completed', ['ued' => ['x', 'y']])
                ->getStatusCode(),
        ];

        $this->pinDefect('learning-array-input', function () use ($statuses) {
            $this->assertSame([], array_keys(array_filter($statuses, function (int $status) {
                return $status >= 500;
            })), 'posts answered with a server error');
        });
    }






    // -----------------------------------------------------------
    // lessonHash
    // -----------------------------------------------------------
    //
    // The ued value a lesson's Completed form posts: the salted
    // double md5 of its id that mark_completed checks.
    //
    // Used by:
    //   - testDeletedLessonDoesNotLockTheTestAway (above)
    // -----------------------------------------------------------

    private function lessonHash(int $lessonId): string
    {
        return md5(md5(env('APP_SALT') . $lessonId));
    }
}
