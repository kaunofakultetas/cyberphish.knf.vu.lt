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
//    selfeval-partial-points    — a fully right question with
//                                 three right options scores 99
//    finished-course-message    — the finished-course badge is
//                                 announced on a page that
//                                 shows no message
//
//  Used by:
//    - PHPUnit — the Feature suite (runTests.sh)
// -----------------------------------------------------------


namespace Tests\Feature\Learning;

use Illuminate\Support\Facades\DB;
use Tests\FeatureTestCase;
use Tests\Support\Flows;








// -----------------------------------------------------------
// LearningDefectsTest
// -----------------------------------------------------------
//
// One pinned defect per test, named in the header; an
// English category and a member for every test.
//
// Used by:
//   - PHPUnit — the Feature suite (runTests.sh)
// -----------------------------------------------------------

class LearningDefectsTest extends FeatureTestCase
{

    // Starting and answering self-evaluations
    use Flows;

    // The English id, the category and the member of the test
    // running now
    private $en;
    private $category;
    private $member;






    // -----------------------------------------------------------
    // setUp
    // -----------------------------------------------------------
    //
    // An English category, and a member.
    //
    // Used by:
    //   - PHPUnit, before every test
    // -----------------------------------------------------------

    protected function setUp(): void
    {
        parent::setUp();
        $this->en = $this->world->language('en');
        $this->category = $this->world->lmCategory($this->en);
        $this->member = $this->world->member();
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
}
