<?php

// -----------------------------------------------------------
//  [*] LearningDefectsTest — known learning defects
//
//  Each test states the behaviour the course progress, the
//  lessons, the badges and the self-evaluations should have
//  and pins the defect that breaks it today (KnownDefects):
//  the run reports them as incomplete, tagged with their
//  ids, until a fix makes one pass — then the test fails as
//  the cue to remove its pin and move it into its home
//  file.
//
//    selfeval-share-counts-unreachable — the progress
//      counts self-evaluations no member can take or finish
//    category-done-without-category — a category that is
//      gone counts as finished
//    selfeval-deleted-question-scores-nothing — a right
//      answer to a question deleted during the attempt
//      scores nothing
//    selfeval-results-blank-question — the results show a
//      deleted question as an empty entry
//    badge-images-not-shipped — the badges page and the
//      rankings link pictures nothing ships
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
// One pinned defect per test, named in the header; each
// test builds its own course.
//
// Used by:
//   - PHPUnit — the Feature suite (runTests.sh)
// -----------------------------------------------------------

class LearningDefectsTest extends FeatureTestCase
{

    // Starting and answering self-evaluations
    use Flows;






    // -----------------------------------------------------------
    // testSelfEvaluationShareCountsOnlyWhatMembersCanFinish
    // -----------------------------------------------------------
    //
    // The self-evaluations' fifth of the course progress divides
    // by every category of the language that holds a question,
    // in bei_user_course_progress and LMUsers::country_progress
    // alike. A self-evaluation opens only from the lesson page
    // of its category, once every lesson there is done, so a
    // category with questions but no lesson is offered on no
    // member page; and one whose questions all lack options
    // asks nothing, its attempt never closing. Both still count:
    // a member who has done every lesson and finished the one
    // self-evaluation on offer stands at 32 instead of 45, and
    // no member of the language reaches 100 through the pages.
    // The lessons and the simulations once counted what members
    // could not reach, and no longer do. Wanted: the share
    // counts only the categories whose self-evaluation a member
    // can take and finish — a lesson to offer it and a question
    // to answer — on both sides of the division.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testSelfEvaluationShareCountsOnlyWhatMembersCanFinish(): void
    {
        $en = $this->world->language('en');
        $this->world->setting(2, 'self_eval_pertime', null, null, 1);
        $category = $this->world->lmCategory($en);
        $lesson = $this->world->lmContent($category, $en);
        $question = $this->world->selfEvalQuestion($category, [['Right', true, 100], ['Wrong', false, 0]]);
        $withoutLessons = $this->world->lmCategory($en);
        $this->world->selfEvalQuestion($withoutLessons, [['Right', true, 100], ['Wrong', false, 0]]);
        $withoutOptions = $this->world->lmCategory($en);
        $otherLesson = $this->world->lmContent($withoutOptions, $en);
        DB::table('bei_selfeval_question')->insert(['cat_id' => $withoutOptions, 'question' => 'A question without options', 'q_type' => 1]);
        $member = $this->world->member();
        $this->world->lessonDone($member->id, $lesson);
        $this->world->lessonDone($member->id, $otherLesson);

        $publicId = $this->startSelfEvaluation($member, $category);
        $this->answerSelfEvaluation($member, $publicId, $category, $question['question'], [$question['options'][0]]);
        $this->signInMember($member)->get("/cp/se/$publicId/$category")->assertRedirect("/cp/se_r/$publicId");

        // No page links the self-evaluation of the category
        // without lessons, and the other one has nothing to ask
        $this->signInMember($member)->get('/en/cp/learning-material')->assertOk()->assertDontSee("self-evaluation-test/$withoutLessons", false);
        $this->signInMember($member)->get("/en/learn/lesson/$lesson")->assertOk()->assertDontSee("self-evaluation-test/$withoutLessons", false);
        $empty = $this->startSelfEvaluation($member, $withoutOptions);
        $this->signInMember($member)->get("/cp/se/$empty/$withoutOptions")->assertOk()->assertSee(__('main.no_selfeval_questions'));

        $this->pinDefect('selfeval-share-counts-unreachable', function () use ($member, $en) {
            $this->assertEquals(45, (int) LMUsers::course_progress($member->id, $en), 'every lesson and every self-evaluation a member can finish is not the 25 and the whole 20');
        });
    }






    // -----------------------------------------------------------
    // testCategoryThatIsGoneIsNeverFinished
    // -----------------------------------------------------------
    //
    // LMController::category_done reads a category as finished
    // when none of its lessons is left undone, and a category
    // that no longer exists has no lessons at all. Deleting a
    // category leaves its lessons without one, and their pages
    // still open by their address, from a bookmark or the
    // browser's history: completing such a lesson reads the
    // missing category as finished, so it earns badge_category
    // though no category was finished, and the page then offers
    // a self-evaluation button whose address names no category,
    // a 404. The results page of an attempt whose category was
    // deleted offers its do-it-again button the same way, again
    // a 404. Wanted: a category that is gone is never finished
    // — no badge for it, and no button to a self-evaluation
    // that cannot start.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testCategoryThatIsGoneIsNeverFinished(): void
    {
        $en = $this->world->language('en');
        $kept = $this->world->lmCategory($en);
        $this->world->lmContent($kept, $en);
        $doomed = $this->world->lmCategory($en);
        $orphan = $this->world->lmContent($doomed, $en, ['title' => 'Orphan lesson']);
        $gone = $this->world->lmCategory($en);
        $member = $this->world->member();
        $attempt = $this->world->selfEvalAttempt($member->id, $gone, $en, ['points' => 100]);
        $publicId = DB::table('bei_users_selfeval_test')->where('id', $attempt)->value('public_id');
        $admin = $this->world->manager();
        $this->signInManager($admin)->from('/admin-panel/lm/categories')->post("/admin-panel/lm/categores/delete/$doomed");
        $this->signInManager($admin)->from('/admin-panel/lm/categories')->post("/admin-panel/lm/categores/delete/$gone");
        $this->assertNull(DB::table('bei_lm_content')->where('id', $orphan)->value('cat_id'));
        $this->assertFalse(DB::table('bei_lm_category')->where('id', $gone)->exists());

        $this->signInMember($member)->from("/en/learn/orphan-lesson/$orphan")
            ->post('/cp/mark_completed', ['ued' => md5(md5(env('APP_SALT') . $orphan))])
            ->assertRedirect("/en/learn/orphan-lesson/$orphan");
        $lessonPage = $this->signInMember($member)->get("/en/learn/orphan-lesson/$orphan")->assertOk()->getContent();
        $resultsPage = $this->signInMember($member)->get("/cp/se_r/$publicId")->assertOk()->getContent();

        // Where the two buttons would lead
        $this->signInMember($member)->get('/en/self-evaluation-test/')->assertNotFound();
        $this->signInMember($member)->get("/en/self-evaluation-test/$gone")->assertNotFound();

        $this->pinDefect('category-done-without-category', function () use ($member, $lessonPage, $resultsPage, $gone) {
            $this->assertNotContains('badge_category', DB::table('bei_users_badges')->where('user_id', $member->id)->pluck('name')->all(), 'a lesson without a category earned the category badge');
            $this->assertStringNotContainsString('self-evaluation-test/"', $lessonPage, 'the lesson page offers a self-evaluation of no category');
            $this->assertStringNotContainsString("self-evaluation-test/$gone\"", $resultsPage, 'the results offer to repeat a self-evaluation whose category is gone');
        });
    }






    // -----------------------------------------------------------
    // testAnswerToADeletedQuestionKeepsItsScore
    // -----------------------------------------------------------
    //
    // FinishedSe scores an attempt when it closes, and reads
    // what each answered question is worth from the question's
    // options as they are then, not from the answer rows, which
    // keep the chosen option's correct flag and points. A
    // question a main admin deletes while a member's attempt is
    // open has no options left, so the right answer the member
    // gave it scores nothing; and it still counts towards the
    // attempt's length, which the delete has shortened, so the
    // attempt closes without asking another question in its
    // place. Here three questions are asked, all answered
    // right, and the attempt closes with 200 points out of 300.
    // Wanted: a member who answers every question asked right
    // gets the attempt's full marks — the deleted answer
    // keeping the points it earned, or another question asked
    // in its place.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testAnswerToADeletedQuestionKeepsItsScore(): void
    {
        $en = $this->world->language('en');
        $this->world->setting(2, 'self_eval_pertime', null, null, 3);
        $category = $this->world->lmCategory($en);
        $questions = [];
        for ($i = 0; $i < 4; $i++) {
            $question = $this->world->selfEvalQuestion($category, [['Right', true, 100], ['Wrong', false, 0]]);
            $questions[md5(md5($question['question']))] = $question;
        }
        $member = $this->world->member();
        $publicId = $this->startSelfEvaluation($member, $category);
        list($deleted, $kept) = array_values($questions);
        $this->answerSelfEvaluation($member, $publicId, $category, $deleted['question'], [$deleted['options'][0]]);
        $this->answerSelfEvaluation($member, $publicId, $category, $kept['question'], [$kept['options'][0]]);

        $this->signInManager($this->world->manager())->from("/admin-panel/lm/self_eval/$category")
            ->post("/admin-panel/lm/self_eval/delete/{$deleted['question']}");
        $this->assertFalse(DB::table('bei_selfeval_question')->where('id', $deleted['question'])->exists());

        // Every question the page asks from now on, answered
        // right, until the attempt closes
        for ($asked = 0; $asked < 10; $asked++) {
            $page = $this->signInMember($member)->get("/cp/se/$publicId/$category");
            if ($page->isRedirect()) {
                break;
            }
            $this->assertSame(1, preg_match('/name="pbid"[^>]*value="([0-9a-f]{32})"/', $page->getContent(), $match));
            $question = $questions[$match[1]];
            $this->answerSelfEvaluation($member, $publicId, $category, $question['question'], [$question['options'][0]]);
        }

        $attempt = DB::table('bei_users_selfeval_test')->where('public_id', $publicId)->first();
        $this->assertEquals(1, $attempt->finished);

        $this->pinDefect('selfeval-deleted-question-scores-nothing', function () use ($attempt) {
            $this->assertEquals(300, $attempt->points, 'three questions answered right do not score 300');
        });
    }






    // -----------------------------------------------------------
    // testResultsListNoQuestionThatIsGone
    // -----------------------------------------------------------
    //
    // The results page of a self-evaluation lists every
    // question the attempt answered by reading the question and
    // its options as they are now. A question a main admin has
    // deleted since — its options go with it — comes out as an
    // underlined heading with no text and no options under it,
    // beside the points the attempt scored for it. Wanted: a
    // question that is gone leaves no empty entry on the
    // results page.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testResultsListNoQuestionThatIsGone(): void
    {
        $en = $this->world->language('en');
        $this->world->setting(2, 'self_eval_pertime', null, null, 2);
        $category = $this->world->lmCategory($en);
        $first = $this->world->selfEvalQuestion($category, [['Use a manager', true, 100], ['Reuse it', false, 0]]);
        $second = $this->world->selfEvalQuestion($category, [['Turn on 2FA', true, 100], ['Write it down', false, 0]]);
        $member = $this->world->member();
        $publicId = $this->startSelfEvaluation($member, $category);
        $this->answerSelfEvaluation($member, $publicId, $category, $first['question'], [$first['options'][0]]);
        $this->answerSelfEvaluation($member, $publicId, $category, $second['question'], [$second['options'][0]]);
        $this->signInMember($member)->get("/cp/se/$publicId/$category")->assertRedirect("/cp/se_r/$publicId");

        $this->signInManager($this->world->manager())->from("/admin-panel/lm/self_eval/$category")
            ->post("/admin-panel/lm/self_eval/delete/{$first['question']}");
        $this->assertFalse(DB::table('bei_selfeval_question')->where('id', $first['question'])->exists());

        $html = $this->signInMember($member)->get("/cp/se_r/$publicId")->assertOk()->assertSee('Turn on 2FA')->getContent();

        $this->pinDefect('selfeval-results-blank-question', function () use ($html) {
            $this->assertDoesNotMatchRegularExpression('#<b><u>\s*</u></b>#', $html, 'the results list a question with no text and no options');
        });
    }






    // -----------------------------------------------------------
    // testBadgePicturesShipWithTheApp
    // -----------------------------------------------------------
    //
    // The badges page shows a picture for each of its eight
    // badges, a greyed one until the badge is earned, all from
    // /upload/badges — and the rankings show the earned ones
    // from there too. That folder is the upload data mount,
    // which a fresh install starts empty; neither the
    // repository nor the image holds a badge picture, and the
    // panel has no way to add one. So on every install made
    // the way the README describes, each member's badges page
    // shows eight broken images, earned or not. Wanted: every
    // picture the page links, greyed or earned, is a file the
    // app serves.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testBadgePicturesShipWithTheApp(): void
    {
        $english = $this->world->language('en');
        $beginner = $this->world->member();
        $holder = $this->world->member();
        foreach ([
            'badge_topic', 'badge_self_evaluation_test', 'badge_category', 'badge_all_presentations',
            'badge_all_simulations', 'badge_finished_course', 'badge_final_test', 'badge_login_10',
        ] as $badge) {
            $this->world->badge($holder->id, $english, $badge);
        }

        $links = [];
        foreach ([$beginner, $holder] as $member) {
            $html = $this->signInMember($member)->get('/cp/badges')->assertOk()->getContent();
            preg_match_all('#src="' . preg_quote(config('app.url'), '#') . '(/[^"]*badge_[a-z0-9_]+\.png)"#', $html, $found);
            $this->assertCount(8, $found[1], 'the badges page no longer links eight pictures');
            $links = array_merge($links, $found[1]);
        }
        $links = array_values(array_unique($links));
        $this->assertCount(16, $links, 'the greyed and the earned pictures are no longer apart');

        $this->pinDefect('badge-images-not-shipped', function () use ($links) {
            $missing = array_values(array_filter($links, function (string $path) {
                return !is_file(public_path(ltrim($path, '/')));
            }));
            $this->assertSame([], $missing, 'badge pictures the page links but the app does not serve');
        });
    }
}
