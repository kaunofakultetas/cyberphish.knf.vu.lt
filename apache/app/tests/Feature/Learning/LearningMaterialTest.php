<?php

// -----------------------------------------------------------
//  [*] LearningMaterialTest — lessons and their completion
//
//  The learning-material overview (members only), the
//  lessons themselves — open to anyone, with their slides
//  and extra files — and POST cp/mark_completed, which
//  records a finished lesson from the salted hash the
//  lesson page posts (LMContent::get_id) and hands out the
//  badges: badge_topic for the first lesson, badge_category
//  when a whole category is done, badge_all_presentations
//  when every lesson of the language is — all that fall due
//  with the same completion. A badge stored twice still
//  reads as held. Deleting the lesson, or the category,
//  that a member had left hands out
//  badge_all_presentations, which the knowledge test waits
//  for. badge_finished_course, earned on any member page, is
//  announced once, on the first page that shows messages.
//
//  Used by:
//    - PHPUnit — the Feature suite (runTests.sh)
// -----------------------------------------------------------


namespace Tests\Feature\Learning;

use Illuminate\Support\Facades\DB;
use Illuminate\Testing\TestResponse;
use Tests\FeatureTestCase;








// -----------------------------------------------------------
// LearningMaterialTest
// -----------------------------------------------------------
//
// English learning material with one category of two
// lessons for every test, and a member to complete them.
//
// Used by:
//   - PHPUnit — the Feature suite (runTests.sh)
// -----------------------------------------------------------

class LearningMaterialTest extends FeatureTestCase
{

    // The English id, the category and its two lessons, and
    // the member of the test running now
    private $en;
    private $category;
    private $firstLesson;
    private $secondLesson;
    private $member;






    // -----------------------------------------------------------
    // setUp
    // -----------------------------------------------------------
    //
    // One English category with two lessons, and a member.
    //
    // Used by:
    //   - PHPUnit, before every test
    // -----------------------------------------------------------

    protected function setUp(): void
    {
        parent::setUp();
        $this->en = $this->world->language('en');
        $this->category = $this->world->lmCategory($this->en, ['name' => 'Recognising phishing']);
        $this->firstLesson = $this->world->lmContent($this->category, $this->en, ['title' => 'Fake senders']);
        $this->secondLesson = $this->world->lmContent($this->category, $this->en, ['title' => 'Suspicious links']);
        $this->member = $this->world->member();
    }






    // -----------------------------------------------------------
    // testOverviewNeedsAnAccount
    // -----------------------------------------------------------
    //
    // A visitor opening the overview is sent to the login
    // form.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testOverviewNeedsAnAccount(): void
    {
        $this->get('/en/cp/learning-material')->assertRedirect('/login');
    }






    // -----------------------------------------------------------
    // testOverviewListsTheCategoriesAndLessons
    // -----------------------------------------------------------
    //
    // The overview lists the language's categories with
    // their lessons, and nothing of another language.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testOverviewListsTheCategoriesAndLessons(): void
    {
        $lt = $this->world->language('lt');
        $this->world->lmContent($this->world->lmCategory($lt, ['name' => 'Socialinė inžinerija']), $lt);

        $this->signInMember($this->member)->get('/en/cp/learning-material')
            ->assertOk()
            ->assertSee('Recognising phishing')
            ->assertSee('Fake senders')
            ->assertSee('Suspicious links')
            ->assertDontSee('Socialinė inžinerija');
    }






    // -----------------------------------------------------------
    // testLessonOpensForAnyone
    // -----------------------------------------------------------
    //
    // A lesson is public: a visitor reads its title and body
    // but gets no mark-as-completed form.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testLessonOpensForAnyone(): void
    {
        DB::table('bei_lm_content')->where('id', $this->firstLesson)->update(['content' => '<p>Check the sender address.</p>']);

        $this->get("/en/learn/fake-senders/{$this->firstLesson}")
            ->assertOk()
            ->assertSee('Fake senders')
            ->assertSee('Check the sender address.')
            ->assertDontSee('cp/mark_completed');
    }






    // -----------------------------------------------------------
    // testMissingLessonIsNotFound
    // -----------------------------------------------------------
    //
    // LMExists answers an unknown or non-numeric lesson id
    // with a 404.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testMissingLessonIsNotFound(): void
    {
        $this->get('/en/learn/gone/' . ($this->secondLesson + 1000))->assertNotFound();
        $this->get('/en/learn/gone/abc')->assertNotFound();
    }






    // -----------------------------------------------------------
    // testLessonOffersItsFiles
    // -----------------------------------------------------------
    //
    // Slides (embed 1) are linked for download under /upload,
    // extra files (embed 0) are listed by name.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testLessonOffersItsFiles(): void
    {
        $this->world->lmFile($this->firstLesson, ['name' => 'Slides', 'file_name' => 'lm/slides-1.pdf', 'embed' => 1]);
        $this->world->lmFile($this->firstLesson, ['name' => 'Checklist for staff', 'file_name' => 'lm/checklist.pdf']);

        $this->get("/en/learn/fake-senders/{$this->firstLesson}")
            ->assertSee('upload/lm/slides-1.pdf', false)
            ->assertSee('Checklist for staff')
            ->assertSee('upload/lm/checklist.pdf', false);
    }






    // -----------------------------------------------------------
    // testMemberGetsTheCompletionForm
    // -----------------------------------------------------------
    //
    // A signed-in member sees the form, carrying the salted
    // hash of the lesson id instead of the id itself.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testMemberGetsTheCompletionForm(): void
    {
        $this->signInMember($this->member)->get("/en/learn/fake-senders/{$this->firstLesson}")
            ->assertSee('cp/mark_completed', false)
            ->assertSee($this->lessonHash($this->firstLesson), false);
    }






    // -----------------------------------------------------------
    // testCompletingALessonRecordsIt
    // -----------------------------------------------------------
    //
    // The lesson is recorded as done for the member, once,
    // however often the form is sent.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testCompletingALessonRecordsIt(): void
    {
        $this->complete($this->firstLesson);
        $this->complete($this->firstLesson);

        $this->assertSame(1, DB::table('bei_lm_users')
            ->where(['user_id' => $this->member->id, 'content_id' => $this->firstLesson])
            ->count());
    }






    // -----------------------------------------------------------
    // testFirstLessonEarnsTheTopicBadge
    // -----------------------------------------------------------
    //
    // The member's very first completed lesson earns
    // badge_topic, announced as a new badge.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testFirstLessonEarnsTheTopicBadge(): void
    {
        $this->complete($this->firstLesson)->assertSessionHas('success', __('main.new_badge'));

        $this->assertSame(['badge_topic'], $this->badges());
    }






    // -----------------------------------------------------------
    // testFinishingACategoryEarnsTheCategoryBadge
    // -----------------------------------------------------------
    //
    // Completing the last lesson of a category earns
    // badge_category — and nothing more while another
    // category of the language is unfinished.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testFinishingACategoryEarnsTheCategoryBadge(): void
    {
        $this->world->lmContent($this->world->lmCategory($this->en), $this->en);
        $this->complete($this->firstLesson);

        $this->complete($this->secondLesson)->assertSessionHas('success', __('main.new_badge'));

        $this->assertSame(['badge_category', 'badge_topic'], $this->badges());
    }






    // -----------------------------------------------------------
    // testFinishingEveryLessonEarnsTheAllPresentationsBadge
    // -----------------------------------------------------------
    //
    // With badge_category already earned, completing the last
    // lesson of the language — here in a second category —
    // earns badge_all_presentations.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testFinishingEveryLessonEarnsTheAllPresentationsBadge(): void
    {
        $lastLesson = $this->world->lmContent($this->world->lmCategory($this->en), $this->en);
        $this->complete($this->firstLesson);
        $this->complete($this->secondLesson);

        $this->complete($lastLesson)->assertSessionHas('success', __('main.new_badge'));

        $this->assertSame(['badge_all_presentations', 'badge_category', 'badge_topic'], $this->badges());
    }






    // -----------------------------------------------------------
    // testCompletionAwardsEveryBadgeThatFallsDue
    // -----------------------------------------------------------
    //
    // A first lesson that is the only one of its category and
    // of its language finishes all three at once, and earns
    // all three badges with the one completion — here in
    // Lithuanian, the setUp's English lessons aside.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testCompletionAwardsEveryBadgeThatFallsDue(): void
    {
        $lt = $this->world->language('lt');
        $lesson = $this->world->lmContent($this->world->lmCategory($lt), $lt);

        $this->complete($lesson)->assertSessionHas('success', __('main.new_badge'));

        $this->assertSame(
            ['badge_all_presentations', 'badge_category', 'badge_topic'],
            DB::table('bei_users_badges')->where(['user_id' => $this->member->id, 'lang_id' => $lt])->orderBy('name')->pluck('name')->all()
        );
    }






    // -----------------------------------------------------------
    // testBadgeStoredTwiceStillReadsAsHeld
    // -----------------------------------------------------------
    //
    // save_badge's check is not locked, so two requests at the
    // same moment can store a badge twice: it still reads as
    // held — here badge_all_presentations, so the dashboard
    // keeps offering the knowledge test.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testBadgeStoredTwiceStillReadsAsHeld(): void
    {
        $badge = ['lang_id' => $this->en, 'user_id' => $this->member->id, 'name' => 'badge_all_presentations'];
        DB::table('bei_users_badges')->insert($badge);
        DB::table('bei_users_badges')->insert($badge);

        $this->signInMember($this->member)->get('/cp/dashboard')->assertSee('/en/knowledge_test', false);
    }






    // -----------------------------------------------------------
    // testFinishedCategoryOffersTheSelfEvaluation
    // -----------------------------------------------------------
    //
    // Once every lesson of the category is done, its lessons
    // link to the category's self-evaluation test.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testFinishedCategoryOffersTheSelfEvaluation(): void
    {
        $page = "/en/learn/fake-senders/{$this->firstLesson}";
        $test = "/en/self-evaluation-test/{$this->category}";
        $this->signInMember($this->member)->get($page)->assertDontSee($test, false);

        $this->complete($this->firstLesson);
        $this->complete($this->secondLesson);

        $this->signInMember($this->member)->get($page)->assertSee($test, false);
    }






    // -----------------------------------------------------------
    // testForgedLessonHashChangesNothing
    // -----------------------------------------------------------
    //
    // A hash that matches no lesson — the plain id, say — is
    // ignored: nothing recorded, no badge.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testForgedLessonHashChangesNothing(): void
    {
        $this->signInMember($this->member)->from('/en/cp/learning-material')
            ->post('/cp/mark_completed', ['ued' => (string) $this->firstLesson])
            ->assertRedirect('/en/cp/learning-material');

        $this->assertFalse(DB::table('bei_lm_users')->where('user_id', $this->member->id)->exists());
        $this->assertSame([], $this->badges());
    }






    // -----------------------------------------------------------
    // testDeletedLessonDoesNotLockTheTestAway
    // -----------------------------------------------------------
    //
    // A member with one lesson done and one left, which a main
    // admin then deletes, gets badge_all_presentations, and
    // with it the knowledge test. The badge once came only
    // with a lesson marked completed, and a completed lesson
    // shows no form to mark again: every lesson was done, and
    // the test locked away for good.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testDeletedLessonDoesNotLockTheTestAway(): void
    {
        $this->complete($this->firstLesson);
        $this->assertNotContains('badge_all_presentations', $this->badges());

        $this->signInManager($this->world->manager())->post("/admin-panel/lm/content_delete/{$this->secondLesson}");

        $this->assertFalse(DB::table('bei_lm_content')->where('id', $this->secondLesson)->exists());
        $this->assertContains('badge_all_presentations', $this->badges());
        $this->signInMember($this->member)->get('/en/knowledge_test')->assertOk();
    }






    // -----------------------------------------------------------
    // testDeletedCategoryDoesNotLockTheTestAway
    // -----------------------------------------------------------
    //
    // The same through a category: a member who has done the
    // first category, while the one lesson of a second waits,
    // gets badge_all_presentations once a main admin deletes
    // the second category — its lesson stays, without a
    // category, on no member page.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testDeletedCategoryDoesNotLockTheTestAway(): void
    {
        $other = $this->world->lmCategory($this->en);
        $this->world->lmContent($other, $this->en);
        $this->complete($this->firstLesson);
        $this->complete($this->secondLesson);
        $this->assertNotContains('badge_all_presentations', $this->badges());

        $this->signInManager($this->world->manager())->from('/admin-panel/lm/categories')->post("/admin-panel/lm/categores/delete/$other");

        $this->assertFalse(DB::table('bei_lm_category')->where('id', $other)->exists());
        $this->assertContains('badge_all_presentations', $this->badges());
    }






    // -----------------------------------------------------------
    // testLessonPostedAsAListIsNoServerError
    // -----------------------------------------------------------
    //
    // A completion whose lesson hash comes as a list records
    // nothing and goes back to the lesson. The list once
    // reached the query's binding as several values, which PDO
    // refused: a 500.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testLessonPostedAsAListIsNoServerError(): void
    {
        $this->signInMember($this->member)->from("/en/learn/lesson/{$this->firstLesson}")
            ->post('/cp/mark_completed', ['ued' => [$this->lessonHash($this->firstLesson), 'x']])
            ->assertRedirect("/en/learn/lesson/{$this->firstLesson}");

        $this->assertFalse(DB::table('bei_lm_users')->where('user_id', $this->member->id)->exists());
    }






    // -----------------------------------------------------------
    // testFinishedCourseBadgeIsAnnouncedOnTheNextPageWithMessages
    // -----------------------------------------------------------
    //
    // badge_finished_course falls due on whatever member page
    // first finds both of its badges held — the simulations
    // list, which shows no messages, most often — and its
    // announcement waits for the first page that does show
    // them, then comes no more. It was once announced with
    // the page that earned it, and lost there.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testFinishedCourseBadgeIsAnnouncedOnTheNextPageWithMessages(): void
    {
        $this->world->badge($this->member->id, $this->en, 'badge_all_presentations');
        $this->world->badge($this->member->id, $this->en, 'badge_all_simulations');
        $this->signInMember($this->member)->get('/en/simulations')->assertOk();
        $this->assertContains('badge_finished_course', $this->badges());

        $this->signInMember($this->member)->get('/cp/dashboard')->assertOk()->assertSee(__('main.new_badge'), false);

        $this->signInMember($this->member)->get('/cp/dashboard')->assertOk()->assertDontSee(__('main.new_badge'), false);
    }






    // -----------------------------------------------------------
    // testFinishedCourseBadgeIsAnnouncedOnceWithTheLastLesson
    // -----------------------------------------------------------
    //
    // A member who holds badge_all_simulations and completes
    // the last lesson earns badge_all_presentations and, on
    // the lesson page the completion returns to,
    // badge_finished_course: the page announces the two with
    // one message, and the next page with messages says
    // nothing more.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testFinishedCourseBadgeIsAnnouncedOnceWithTheLastLesson(): void
    {
        $this->world->badge($this->member->id, $this->en, 'badge_all_simulations');
        $this->complete($this->firstLesson);
        $this->complete($this->secondLesson)->assertRedirect("/en/learn/lesson/{$this->secondLesson}");

        $html = $this->signInMember($this->member)->get("/en/learn/lesson/{$this->secondLesson}")->assertOk()->getContent();

        $this->assertContains('badge_finished_course', $this->badges());
        $this->assertSame(1, substr_count($html, __('main.new_badge')));
        $this->signInMember($this->member)->get('/cp/dashboard')->assertOk()->assertDontSee(__('main.new_badge'), false);
    }






    // -----------------------------------------------------------
    // complete
    // -----------------------------------------------------------
    //
    // Sends the lesson's completion form as the member, from
    // the lesson page.
    //
    // Used by:
    //   - the completion tests of this class
    // -----------------------------------------------------------

    private function complete(int $lessonId): TestResponse
    {
        return $this->signInMember($this->member)->from("/en/learn/lesson/$lessonId")
            ->post('/cp/mark_completed', ['ued' => $this->lessonHash($lessonId)]);
    }






    // -----------------------------------------------------------
    // lessonHash
    // -----------------------------------------------------------
    //
    // What the lesson page posts instead of the id: MD5 of the
    // MD5 of APP_SALT followed by the id.
    //
    // Used by:
    //   - complete, testMemberGetsTheCompletionForm,
    //     testLessonPostedAsAListIsNoServerError (above)
    // -----------------------------------------------------------

    private function lessonHash(int $lessonId): string
    {
        return md5(md5(env('APP_SALT') . $lessonId));
    }






    // -----------------------------------------------------------
    // badges
    // -----------------------------------------------------------
    //
    // The names of the badges the member holds, sorted.
    //
    // Used by:
    //   - the badge tests of this class
    // -----------------------------------------------------------

    private function badges(): array
    {
        return DB::table('bei_users_badges')->where('user_id', $this->member->id)
            ->orderBy('name')->pluck('name')->all();
    }
}
