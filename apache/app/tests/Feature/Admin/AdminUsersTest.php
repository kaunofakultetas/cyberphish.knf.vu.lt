<?php

// -----------------------------------------------------------
//  [*] AdminUsersTest — the members page of the panel
//
//  admin-panel/users (UsersController) lists every member,
//  25 a page; user/status/{status_id}/{user_id} switches a
//  member on or off and user/delete/{user_id} removes the
//  account with its completed lessons. Both changes are
//  POST routes behind the CSRF check: a link opened from a
//  mail changes nothing.
//
//  Used by:
//    - PHPUnit — the Feature suite (runTests.sh)
// -----------------------------------------------------------


namespace Tests\Feature\Admin;

use App\Models\Members;
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
}
