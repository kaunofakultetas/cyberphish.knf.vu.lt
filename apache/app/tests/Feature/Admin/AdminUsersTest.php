<?php

// -----------------------------------------------------------
//  [*] AdminUsersTest — the members page of the panel
//
//  admin-panel/users (UsersController) lists every member
//  on one page, newest first, with a quick search;
//  user/status/{status_id}/{user_id} switches a member on
//  or off and user/delete/{user_id} removes the account
//  with its completed lessons. Both changes are POST routes
//  behind the CSRF check: a link opened from a mail changes
//  nothing.
//
//  Used by:
//    - PHPUnit — the Feature suite (runTests.sh)
// -----------------------------------------------------------


namespace Tests\Feature\Admin;

use App\Models\Members;
use Dom\HTMLDocument;
use Illuminate\Support\Facades\DB;
use Tests\FeatureTestCase;
use Tests\Support\World;








// -----------------------------------------------------------
// AdminUsersTest
// -----------------------------------------------------------
//
// A main admin works the page in every test.
//
// Used by:
//   - PHPUnit — the Feature suite (runTests.sh)
// -----------------------------------------------------------

class AdminUsersTest extends FeatureTestCase
{

    // The main admin of the test running now
    private $admin;






    // -----------------------------------------------------------
    // setUp
    // -----------------------------------------------------------
    //
    // A main admin for every test.
    //
    // Used by:
    //   - PHPUnit, before every test
    // -----------------------------------------------------------

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = $this->world->manager();
    }






    // -----------------------------------------------------------
    // testListShowsTheMembers
    // -----------------------------------------------------------
    //
    // Every member's address is on the page, whatever their
    // country.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testListShowsTheMembers(): void
    {
        $first = $this->world->member(['country' => 1]);
        $second = $this->world->member(['country' => 4]);

        $this->signInManager($this->admin)->get('/admin-panel/users')
            ->assertOk()
            ->assertSee($first->email)
            ->assertSee($second->email);
    }






    // -----------------------------------------------------------
    // testEveryMemberIsOnTheOnePage
    // -----------------------------------------------------------
    //
    // Thirty members all stand on the one page, newest first,
    // where the list once showed 25 a page and the quick search
    // could not have reached the rest.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testEveryMemberIsOnTheOnePage(): void
    {
        $members = [];
        for ($i = 0; $i < 30; $i++) {
            $members[] = $this->world->member();
        }

        $page = $this->signInManager($this->admin)->get('/admin-panel/users')->assertOk();

        foreach ($members as $member) {
            $page->assertSee($member->email);
        }
        $page->assertSeeInOrder([end($members)->email, $members[0]->email]);
    }






    // -----------------------------------------------------------
    // testEveryRowCarriesItsSearchText
    // -----------------------------------------------------------
    //
    // The page has the quick search box with its clear button,
    // each member's row carries the user name and email that
    // User_Search of admn.js matches and names the member's
    // country, and the list has the hidden row that answers a
    // search finding nobody.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testEveryRowCarriesItsSearchText(): void
    {
        $member = $this->world->member(['country' => 4]);

        $html = $this->signInManager($this->admin)->get('/admin-panel/users')->assertOk()->getContent();
        $document = HTMLDocument::createFromString($html, LIBXML_NOERROR);

        $this->assertNotNull($document->querySelector('.user-search #user-search'), 'the page has no search box');
        $this->assertNotNull($document->querySelector('.user-search .user-search-clear'), 'the search box has no clear button');
        $row = $document->querySelector("tr[data-search=\"{$member->username} {$member->email}\"]");
        $this->assertNotNull($row, 'the member has no searchable row');
        $this->assertSame('Latvia', trim($row->querySelectorAll('td')->item(2)->textContent));
        $this->assertCount(1, $document->querySelectorAll('tr.search-none'));
    }






    // -----------------------------------------------------------
    // testHugePageNumberOpensTheMemberList
    // -----------------------------------------------------------
    //
    // A page number past PHP's integers still opens the member
    // list instead of a 500: the list has no pages any more,
    // and such a number once reached the page cutting as a
    // float.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testHugePageNumberOpensTheMemberList(): void
    {
        $member = $this->world->member();

        $this->signInManager($this->admin)->get('/admin-panel/users?page=99999999999999999999')
            ->assertOk()
            ->assertSee($member->email);
    }






    // -----------------------------------------------------------
    // testSwitchingAMemberOffLocksThemOut
    // -----------------------------------------------------------
    //
    // Status 0 makes the member inactive, and their password
    // no longer signs them in.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testSwitchingAMemberOffLocksThemOut(): void
    {
        $member = $this->world->member();

        $this->signInManager($this->admin)->post("/admin-panel/user/status/0/{$member->id}")
            ->assertRedirect('/admin-panel/users');

        $this->assertSame(0, Members::find($member->id)->status);
        $this->post('/login', ['email' => $member->email, 'password' => World::PASSWORD]);
        $this->assertGuest('mem');
    }






    // -----------------------------------------------------------
    // testSwitchingAMemberOffEndsTheirSession
    // -----------------------------------------------------------
    //
    // A member switched off while signed in loses the session
    // at the next page, where the status was once checked at
    // sign-in only and the session ran on.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testSwitchingAMemberOffEndsTheirSession(): void
    {
        $member = $this->world->member();
        $this->signInMember($member)->get('/cp/dashboard')->assertOk();

        $this->signInManager($this->admin)->post("/admin-panel/user/status/2/{$member->id}");

        $this->get('/cp/dashboard')->assertRedirect('/login');
        $this->assertGuest('mem');
    }






    // -----------------------------------------------------------
    // testStatusOfAnUnknownMemberIsRefused
    // -----------------------------------------------------------
    //
    // A member id that does not exist gets the not-found
    // message.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testStatusOfAnUnknownMemberIsRefused(): void
    {
        $member = $this->world->member();

        $this->signInManager($this->admin)->post('/admin-panel/user/status/0/' . ($member->id + 1000))
            ->assertRedirect('/admin-panel/users')
            ->assertSessionHasErrors();
    }






    // -----------------------------------------------------------
    // testDeletingAMemberNeedsMoreThanALink
    // -----------------------------------------------------------
    //
    // A plain GET of the delete URL — a link a signed-in admin
    // opens from a mail — is refused and deletes nothing; like
    // every delete and status change of the panel, it takes a
    // POST with the CSRF token.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testDeletingAMemberNeedsMoreThanALink(): void
    {
        $member = $this->world->member();

        $this->signInManager($this->admin)->get("/admin-panel/user/delete/{$member->id}")->assertStatus(405);

        $this->assertNotNull(Members::find($member->id));
    }






    // -----------------------------------------------------------
    // testDeletingAMemberRemovesTheAccountAndLessons
    // -----------------------------------------------------------
    //
    // The account goes, and with it the member's completed
    // lessons.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testDeletingAMemberRemovesTheAccountAndLessons(): void
    {
        $en = $this->world->language('en');
        $member = $this->world->member();
        $this->world->lessonDone($member->id, $this->world->lmContent($this->world->lmCategory($en), $en));

        $this->signInManager($this->admin)->post("/admin-panel/user/delete/{$member->id}")
            ->assertRedirect('/admin-panel/users')
            ->assertSessionHas('success');

        $this->assertNull(Members::find($member->id));
        $this->assertFalse(DB::table('bei_lm_users')->where('user_id', $member->id)->exists());
    }






    // -----------------------------------------------------------
    // testDeletingAMemberLeavesNothingOfTheirs
    // -----------------------------------------------------------
    //
    // Every row of a deleted member goes with the account:
    // logins, badges, lessons, each kind of attempt and its
    // answers. A row left behind would pass to the next member
    // MySQL hands the id to.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testDeletingAMemberLeavesNothingOfTheirs(): void
    {
        $member = $this->world->member();
        $en = $this->world->language('en');
        $category = $this->world->lmCategory($en);
        $this->world->loginLog($member->id, date('Y-m-d H:i:s'));
        $this->world->badge($member->id, $en, 'badge_final_test');
        $this->world->lessonDone($member->id, $this->world->lmContent($category, $en));
        $selfEval = $this->world->selfEvalAttempt($member->id, $category, $en);
        $question = $this->world->selfEvalQuestion($category, [['Right', true, 100]]);
        DB::table('bei_users_selfeval')->insert(['cat_id' => $category, 'test_id' => $selfEval, 'public_id' => 'gone', 'option_id' => $question['options'][0], 'user_id' => $member->id, 'question_id' => $question['question'], 'correct' => 1, 'points' => 100]);
        $knowledge = $this->world->knowledgeAttempt($member->id, $en, ['results' => 80]);
        DB::table('bei_users_knowledge_options')->insert(['cat_id' => $category, 'test_id' => $knowledge, 'public_id' => 'gone', 'user_id' => $member->id, 'question_id' => 1, 'correct' => 1]);
        $scenario = $this->world->scenario($en);
        $run = $this->world->scenarioAttempt($member->id, $scenario, $en);
        DB::table('bei_users_scenarios_options')->insert(['user_id' => $member->id, 'user_scenario_id' => $run, 'user_scenario_public_id' => 'gone', 'scenario_id' => $scenario, 'option_id' => 1, 'option_type' => 3, 'points' => 200]);

        $this->signInManager($this->admin)->post("/admin-panel/user/delete/{$member->id}")->assertRedirect('/admin-panel/users');

        foreach (['bei_users_loginlog', 'bei_users_badges', 'bei_lm_users', 'bei_users_selfeval', 'bei_users_selfeval_test', 'bei_users_knowledge_options', 'bei_users_knowledge_test', 'bei_users_scenarios_options', 'bei_users_scenarios'] as $table) {
            $this->assertFalse(DB::table($table)->where('user_id', $member->id)->exists(), "$table keeps rows of the deleted member");
        }
    }






    // -----------------------------------------------------------
    // testMemberActionsReturnToTheMemberList
    // -----------------------------------------------------------
    //
    // Deleting a member and switching one off land on the
    // member list, also when a member page visited earlier in
    // the same browser parked its address in the session —
    // they once followed that address instead.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testMemberActionsReturnToTheMemberList(): void
    {
        $first = $this->world->member();
        $second = $this->world->member();

        $this->get('/cp/dashboard')->assertRedirect('/login');
        $this->signInManager($this->admin)->post("/admin-panel/user/status/2/{$first->id}")->assertRedirect('/admin-panel/users');

        $this->get('/cp/dashboard')->assertRedirect('/login');
        $this->signInManager($this->admin)->post("/admin-panel/user/delete/{$second->id}")->assertRedirect('/admin-panel/users');
    }






    // -----------------------------------------------------------
    // testSelfEvaluationHistoryCountsOutOfTheAttemptsQuestions
    // -----------------------------------------------------------
    //
    // The self-evaluation history gives each attempt's right
    // answers out of the questions it answered — two of three
    // here — not out of a fixed 5.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testSelfEvaluationHistoryCountsOutOfTheAttemptsQuestions(): void
    {
        $member = $this->world->member();
        $en = $this->world->language('en');
        $category = $this->world->lmCategory($en);
        $attempt = $this->world->selfEvalAttempt($member->id, $category, $en);
        $publicId = DB::table('bei_users_selfeval_test')->where('id', $attempt)->value('public_id');
        foreach ([0, 0, 1] as $choice) {
            $question = $this->world->selfEvalQuestion($category, [['Right', true, 100], ['Wrong', false, 0]]);
            DB::table('bei_users_selfeval')->insert([
                'cat_id'      => $category,
                'test_id'     => $attempt,
                'public_id'   => $publicId,
                'user_id'     => $member->id,
                'question_id' => $question['question'],
                'option_id'   => $question['options'][$choice],
                'correct'     => $choice == 0 ? 1 : 0,
                'points'      => $choice == 0 ? 100 : 0,
            ]);
        }

        $this->signInManager($this->admin)->get("/admin-panel/user/self_evaluation_history/$en/{$member->id}")
            ->assertOk()
            ->assertSee('2/3');
    }
}
