<?php

// -----------------------------------------------------------
//  [*] AccountSettingsTest — a member's own account forms
//
//  The three forms of the dashboard (ManagerMainController
//  on the member side): cp/change-pass checks the current
//  password and the repetition before it stores a new hash,
//  cp/change-username keeps names unique between members,
//  cp/change-fullname takes the name printed on the
//  certificate (6 to 20 characters). Visitors are sent to
//  the login form. The defects of these forms are pinned in
//  AccountDefectsTest.
//
//  Used by:
//    - PHPUnit — the Feature suite (runTests.sh)
// -----------------------------------------------------------


namespace Tests\Feature\Account;

use App\Models\Members;
use Illuminate\Support\Facades\Hash;
use Illuminate\Testing\TestResponse;
use Tests\FeatureTestCase;
use Tests\Support\World;








// -----------------------------------------------------------
// AccountSettingsTest
// -----------------------------------------------------------
//
// Every form is posted from the dashboard by a signed-in
// member, so the controller's back() returns there.
//
// Used by:
//   - PHPUnit — the Feature suite (runTests.sh)
// -----------------------------------------------------------

class AccountSettingsTest extends FeatureTestCase
{

    // The new password the password tests move to
    private const NEW_PASSWORD = 'Brand-New-Password-7';

    // The member of the test running now
    private $member;






    // -----------------------------------------------------------
    // setUp
    // -----------------------------------------------------------
    //
    // A member with the World's password for every test.
    //
    // Used by:
    //   - PHPUnit, before every test
    // -----------------------------------------------------------

    protected function setUp(): void
    {
        parent::setUp();
        $this->member = $this->world->member();
    }






    // -----------------------------------------------------------
    // testVisitorIsSentToTheLoginForm
    // -----------------------------------------------------------
    //
    // None of the forms works without a session.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testVisitorIsSentToTheLoginForm(): void
    {
        foreach (['/cp/change-pass', '/cp/change-username', '/cp/change-fullname'] as $form) {
            $this->post($form, [])->assertRedirect('/login');
        }
    }






    // -----------------------------------------------------------
    // testPasswordChangesWithTheRightCurrentPassword
    // -----------------------------------------------------------
    //
    // The current password, a new one and its repetition store
    // the new password.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testPasswordChangesWithTheRightCurrentPassword(): void
    {
        $this->changePassword(World::PASSWORD, self::NEW_PASSWORD, self::NEW_PASSWORD)
            ->assertSessionHas('success', __('main.pass_changed'));

        $this->assertTrue(Hash::check(self::NEW_PASSWORD, Members::find($this->member->id)->password));
    }






    // -----------------------------------------------------------
    // testPasswordChangeIsRefused
    // -----------------------------------------------------------
    //
    // A wrong current password, a new password equal to the
    // current one, a repetition that differs, and a new
    // password under 6 characters each get their message, and
    // the password stays.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    /** @dataProvider refusedPasswordChanges */
    public function testPasswordChangeIsRefused(string $current, string $new, string $repeated, string $message): void
    {
        $this->changePassword($current, $new, $repeated)->assertSessionHasErrors();

        $this->assertSame(__($message, ['length' => 6]), session('errors')->first());
        $this->assertTrue(Hash::check(World::PASSWORD, Members::find($this->member->id)->password));
    }






    // -----------------------------------------------------------
    // refusedPasswordChanges
    // -----------------------------------------------------------
    //
    // The current password, the new one, its repetition, and
    // the translation key of the message each case gets.
    //
    // Used by:
    //   - testPasswordChangeIsRefused (above)
    // -----------------------------------------------------------

    public function refusedPasswordChanges(): array
    {
        return [
            'wrong current password' => ['Not-The-Password-1', self::NEW_PASSWORD, self::NEW_PASSWORD, 'main.curr_pass_wrong'],
            'new equals current'     => [World::PASSWORD, World::PASSWORD, World::PASSWORD, 'main.curr_new_same'],
            'repetition differs'     => [World::PASSWORD, self::NEW_PASSWORD, 'Something-Else-8', 'main.new_pass_not_match'],
            'new under 6 characters' => [World::PASSWORD, 'abc', 'abc', 'main.pass_too_short'],
        ];
    }






    // -----------------------------------------------------------
    // testFreeUsernameIsTaken
    // -----------------------------------------------------------
    //
    // A name no other member uses becomes the member's name.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testFreeUsernameIsTaken(): void
    {
        $this->submit('/cp/change-username', ['username' => 'PhishHunter'])
            ->assertSessionHas('success', __('main.pass_updated'));

        $this->assertSame('PhishHunter', Members::find($this->member->id)->username);
    }






    // -----------------------------------------------------------
    // testUsernameOfAnotherMemberIsRefused
    // -----------------------------------------------------------
    //
    // Two members never share a name: the second one gets the
    // name-unavailable message and keeps the old name.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testUsernameOfAnotherMemberIsRefused(): void
    {
        $this->world->member(['username' => 'PhishHunter']);

        $this->submit('/cp/change-username', ['username' => 'PhishHunter'])->assertSessionHasErrors();

        $this->assertSame(__('main.pass_unavailable'), session('errors')->first());
        $this->assertSame($this->member->username, Members::find($this->member->id)->username);
    }






    // -----------------------------------------------------------
    // testOwnUsernameCanBeSubmittedAgain
    // -----------------------------------------------------------
    //
    // Submitting the name the member already has is not a
    // clash with themselves.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testOwnUsernameCanBeSubmittedAgain(): void
    {
        $this->submit('/cp/change-username', ['username' => $this->member->username])
            ->assertSessionHas('success');
    }






    // -----------------------------------------------------------
    // testShortUsernameIsRefused
    // -----------------------------------------------------------
    //
    // A name under 6 characters fails validation.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testShortUsernameIsRefused(): void
    {
        $this->submit('/cp/change-username', ['username' => 'Ann'])->assertSessionHasErrors();

        $this->assertSame($this->member->username, Members::find($this->member->id)->username);
    }






    // -----------------------------------------------------------
    // testFullNameIsStored
    // -----------------------------------------------------------
    //
    // A full name of 6 to 20 characters is stored for the
    // certificate — here one latin1 can hold, since the
    // column's letters are pinned as a defect
    // (AccountDefectsTest).
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testFullNameIsStored(): void
    {
        $this->submit('/cp/change-fullname', ['fullname' => 'Jonas Jonaitis'])
            ->assertSessionHas('success', __('main.updated'));

        $this->assertSame('Jonas Jonaitis', Members::find($this->member->id)->fullname);
    }






    // -----------------------------------------------------------
    // testFullNameOutsideItsLengthIsRefused
    // -----------------------------------------------------------
    //
    // Under 6 or over 20 characters fails validation and the
    // stored name stays empty.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testFullNameOutsideItsLengthIsRefused(): void
    {
        foreach (['Ona P', str_repeat('x', 21)] as $fullName) {
            $this->submit('/cp/change-fullname', ['fullname' => $fullName])->assertSessionHasErrors();
        }

        $this->assertNull(Members::find($this->member->id)->fullname);
    }






    // -----------------------------------------------------------
    // changePassword
    // -----------------------------------------------------------
    //
    // Posts the password form as the member.
    //
    // Used by:
    //   - the password tests of this class
    // -----------------------------------------------------------

    private function changePassword(string $current, string $new, string $repeated): TestResponse
    {
        return $this->submit('/cp/change-pass', ['password' => $current, 'newpass' => $new, 'renewpass' => $repeated]);
    }






    // -----------------------------------------------------------
    // submit
    // -----------------------------------------------------------
    //
    // Posts one of the forms as the test's member, sent from
    // the dashboard — the forms live there and answer with
    // back().
    //
    // Used by:
    //   - the user-name and full-name tests of this class
    //   - changePassword (above)
    // -----------------------------------------------------------

    private function submit(string $form, array $fields): TestResponse
    {
        return $this->signInMember($this->member)->from('/cp/dashboard')->post($form, $fields);
    }
}
