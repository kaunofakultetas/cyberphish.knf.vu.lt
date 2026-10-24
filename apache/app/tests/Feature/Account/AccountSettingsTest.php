<?php

// -----------------------------------------------------------
//  [*] AccountSettingsTest — a member's own account forms
//
//  The member's three account forms (ManagerMainController
//  on the member side). Two sit on the dashboard:
//  cp/change-pass checks the current password and the
//  repetition before it stores a new hash, and
//  cp/change-username keeps names unique between members
//  and within the column's 45 characters. The third,
//  cp/change-fullname, sits on the knowledge-test pages and
//  takes the name printed on the certificate (6 to 100
//  characters). Both names keep Lithuanian letters as
//  typed. Visitors are sent to the login form. The
//  dashboard opens for a member of any country, one the
//  questionnaire links do not know included. The Estonian
//  strings of the user-name form speak of the user name. A
//  field posted as a list is refused by every form.
//
//  Used by:
//    - PHPUnit — the Feature suite (runTests.sh)
// -----------------------------------------------------------


namespace Tests\Feature\Account;

use App\Models\Members;
use Illuminate\Support\Facades\Hash;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Attributes\DataProvider;
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
    // current one, a repetition that differs, a new password
    // outside the login's 12 to 50 characters, and one with a
    // NUL byte — which bcrypt refuses, a 500 at Hash::make
    // once (login-password-list) — each get their message, and
    // the password stays.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    #[DataProvider('refusedPasswordChanges')]
    public function testPasswordChangeIsRefused(string $current, string $new, string $repeated, string $message): void
    {
        $this->changePassword($current, $new, $repeated)->assertSessionHasErrors();

        $this->assertSame(__($message), session('errors')->first());
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

    public static function refusedPasswordChanges(): array
    {
        return [
            'wrong current password' => ['Not-The-Password-1', self::NEW_PASSWORD, self::NEW_PASSWORD, 'main.curr_pass_wrong'],
            'new equals current'     => [World::PASSWORD, World::PASSWORD, World::PASSWORD, 'main.curr_new_same'],
            'repetition differs'     => [World::PASSWORD, self::NEW_PASSWORD, 'Something-Else-8', 'main.new_pass_not_match'],
            'new under 12 characters' => [World::PASSWORD, 'Eleven-Char', 'Eleven-Char', 'main.pass_too_short'],
            'new over 50 characters'  => [World::PASSWORD, str_repeat('Long-', 10) . 'x', str_repeat('Long-', 10) . 'x', 'main.pass_too_long'],
            'new with a NUL byte'     => [World::PASSWORD, "New-Pass\0word-12", "New-Pass\0word-12", 'main.new_pass_not_match'],
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
    // testRefusedUsernameSaysWhyInTheMembersLanguage
    // -----------------------------------------------------------
    //
    // A name of the wrong length comes back with a message
    // that gives both limits, in the member's language — not
    // the bare, untranslated "Error!" it once got.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testRefusedUsernameSaysWhyInTheMembersLanguage(): void
    {
        $this->withSession(['lang' => 'lt'])->submit('/cp/change-username', ['username' => 'Ann'])->assertSessionHasErrors();

        $this->assertSame(__('main.username_length', ['min' => 6, 'max' => 45], 'lt'), session('errors')->first());
    }






    // -----------------------------------------------------------
    // testUsernameFormAsksForWhatTheServerTakes
    // -----------------------------------------------------------
    //
    // The dashboard's user-name field asks the browser for the
    // server's 6 to 45 characters, so a name the browser lets
    // through is never refused for its length; it once asked
    // for 3 to 20.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testUsernameFormAsksForWhatTheServerTakes(): void
    {
        $html = $this->signInMember($this->member)->get('/cp/dashboard')->assertOk()->getContent();

        $this->assertSame(1, preg_match('/<input[^>]*name="username"[^>]*>/', $html, $field));
        $this->assertStringContainsString('minlength="6"', $field[0]);
        $this->assertStringContainsString('maxlength="45"', $field[0]);
    }






    // -----------------------------------------------------------
    // testUsernameTheColumnCannotHoldIsRefused
    // -----------------------------------------------------------
    //
    // A name over the column's 45 characters fails validation
    // instead of being cut short, and the old name stays.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testUsernameTheColumnCannotHoldIsRefused(): void
    {
        $this->submit('/cp/change-username', ['username' => str_repeat('n', 46)])->assertSessionHasErrors();

        $this->assertSame($this->member->username, Members::find($this->member->id)->username);
    }






    // -----------------------------------------------------------
    // testUsernameOfFortyFiveCharactersIsTaken
    // -----------------------------------------------------------
    //
    // A name that fills the column exactly is stored whole.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testUsernameOfFortyFiveCharactersIsTaken(): void
    {
        $name = 'Phish' . str_repeat('n', 40);

        $this->submit('/cp/change-username', ['username' => $name])->assertSessionHas('success');

        $this->assertSame($name, Members::find($this->member->id)->username);
    }






    // -----------------------------------------------------------
    // testUsernameWithLithuanianLettersIsSaved
    // -----------------------------------------------------------
    //
    // A name with Lithuanian letters is saved as typed. While
    // bei_users was a latin1 table, the uniqueness lookup
    // failed on such a name with an illegal mix of collations
    // — the 500 the production log recorded for students'
    // real names.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testUsernameWithLithuanianLettersIsSaved(): void
    {
        $this->submit('/cp/change-username', ['username' => 'Vaiva Vaitkevičiūtė'])
            ->assertSessionHas('success', __('main.pass_updated'));

        $this->assertSame('Vaiva Vaitkevičiūtė', Members::find($this->member->id)->username);
    }






    // -----------------------------------------------------------
    // testFullNameIsStored
    // -----------------------------------------------------------
    //
    // A full name is stored for the certificate.
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
    // testFullNameWithLithuanianLettersIsStoredAsTyped
    // -----------------------------------------------------------
    //
    // The name the certificate prints comes back exactly as
    // typed, Lithuanian letters included, where bei_users,
    // while it was a latin1 table, stored question marks
    // without an error.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testFullNameWithLithuanianLettersIsStoredAsTyped(): void
    {
        $this->submit('/cp/change-fullname', ['fullname' => 'Ona Petraitytė'])
            ->assertSessionHas('success', __('main.updated'));

        $this->assertSame('Ona Petraitytė', Members::find($this->member->id)->fullname);
    }






    // -----------------------------------------------------------
    // testLongFullNameIsStored
    // -----------------------------------------------------------
    //
    // A name well past the 20 characters the form once
    // stopped at is stored whole; the certificate makes it
    // fit (CertificateTest).
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testLongFullNameIsStored(): void
    {
        $name = 'Annemarie Wilhelmina van der Westhuizen-Smith';

        $this->submit('/cp/change-fullname', ['fullname' => $name])->assertSessionHas('success', __('main.updated'));

        $this->assertSame($name, Members::find($this->member->id)->fullname);
    }






    // -----------------------------------------------------------
    // testFullNameOutsideItsLengthIsRefused
    // -----------------------------------------------------------
    //
    // Under 6 characters, or over the form's 100, fails
    // validation and the stored name stays empty.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testFullNameOutsideItsLengthIsRefused(): void
    {
        foreach (['Ona P', str_repeat('x', 101)] as $fullName) {
            $this->submit('/cp/change-fullname', ['fullname' => $fullName])->assertSessionHasErrors();
        }

        $this->assertNull(Members::find($this->member->id)->fullname);
    }






    // -----------------------------------------------------------
    // testRefusedFullNameSaysWhyInTheMembersLanguage
    // -----------------------------------------------------------
    //
    // A full name of the wrong length comes back with a
    // message that gives both limits, in the member's
    // language, as the user name's does.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testRefusedFullNameSaysWhyInTheMembersLanguage(): void
    {
        $this->withSession(['lang' => 'lt'])->submit('/cp/change-fullname', ['fullname' => 'Ona P'])->assertSessionHasErrors();

        $this->assertSame(__('main.fullname_length', ['min' => 6, 'max' => 100], 'lt'), session('errors')->first());
    }






    // -----------------------------------------------------------
    // testMemberOfAnUnknownCountryOpensTheDashboard
    // -----------------------------------------------------------
    //
    // Registration takes the form's countries, 0 to 5, but a
    // row from before it checked may hold any number. Every
    // page of such a member looks up the questionnaire link of
    // the country, and the dashboard still opens — with no
    // link, like country 0 (Other).
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testMemberOfAnUnknownCountryOpensTheDashboard(): void
    {
        $member = $this->world->member(['country' => 9]);

        $this->signInMember($member)->get('/cp/dashboard')->assertOk();
    }






    // -----------------------------------------------------------
    // testEstonianUserNameFormSpeaksOfTheUserName
    // -----------------------------------------------------------
    //
    // In Estonian the user-name button reads without a stray
    // digit, and both answers of change_username — a rename
    // and a name already taken — speak of the user name
    // (kasutajanimi), not the password.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testEstonianUserNameFormSpeaksOfTheUserName(): void
    {
        $this->world->language('ee');
        $other = $this->world->member();
        $this->signInMember($this->member)->withSession(['lang' => 'ee'])->from('/cp/dashboard');

        // The first submit button after the user-name form's
        // action is that form's own
        $dashboard = $this->get('/cp/dashboard')->assertOk()->getContent();
        $this->assertSame(1, preg_match('#cp/change-username".*?type="submit" value="([^"]*)"#s', $dashboard, $button), 'the dashboard has no user-name button');
        $this->assertDoesNotMatchRegularExpression('/\d/', $button[1], "the button reads {$button[1]}");

        $this->post('/cp/change-username', ['username' => 'Renamed' . $this->member->id])->assertSessionHas('success');
        $renamed = session('success');
        $this->post('/cp/change-username', ['username' => $other->username])->assertSessionHasErrors();
        $taken = session('errors')->first();

        foreach (['the rename' => $renamed, 'the taken name' => $taken] as $case => $answer) {
            $this->assertStringContainsStringIgnoringCase('kasutaja', $answer, "$case answers $answer");
            $this->assertStringNotContainsStringIgnoringCase('parool', $answer, "$case answers $answer");
        }
    }






    // -----------------------------------------------------------
    // testFieldsPostedAsListsAreRefused
    // -----------------------------------------------------------
    //
    // A field posted as a list is refused, and the account
    // stays as it was. The password form once handed a list to
    // password_verify or strcmp — a TypeError, a 500 — and the
    // two name forms counted a list's elements against their
    // lengths, so six words passed and MySQL stored the list's
    // JSON text as the name.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testFieldsPostedAsListsAreRefused(): void
    {
        $this->changePasswordFields([World::PASSWORD], 'A-new-password-1', 'A-new-password-1')
            ->assertRedirect('/cp/dashboard')->assertSessionHasErrors();
        $this->changePasswordFields(World::PASSWORD, ['A-new-password-1'], 'A-new-password-1')
            ->assertRedirect('/cp/dashboard')->assertSessionHasErrors();
        $this->submit('/cp/change-username', ['username' => array_fill(0, 6, 'abcdef')])->assertSessionHasErrors();
        $this->submit('/cp/change-fullname', ['fullname' => array_fill(0, 6, 'abcdef')])->assertSessionHasErrors();

        $row = Members::find($this->member->id);
        $this->assertTrue(Hash::check(World::PASSWORD, $row->password), 'the password changed');
        $this->assertSame($this->member->username, $row->username, 'the user name became the list');
        $this->assertSame($this->member->fullname, $row->fullname, 'the full name became the list');
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
        return $this->changePasswordFields($current, $new, $repeated);
    }






    // -----------------------------------------------------------
    // changePasswordFields
    // -----------------------------------------------------------
    //
    // Posts the password form as the member with any value in
    // each field, a list included.
    //
    // Used by:
    //   - changePassword,
    //     testFieldsPostedAsListsAreRefused (above)
    // -----------------------------------------------------------

    private function changePasswordFields($current, $new, $repeated): TestResponse
    {
        return $this->submit('/cp/change-pass', ['password' => $current, 'newpass' => $new, 'renewpass' => $repeated]);
    }






    // -----------------------------------------------------------
    // submit
    // -----------------------------------------------------------
    //
    // Posts one of the forms as the test's member, sent from
    // the dashboard, where the password and user-name forms
    // live; every form answers with back(), so each one
    // returns there.
    //
    // Used by:
    //   - the user-name and full-name tests of this class
    //   - changePasswordFields (above)
    // -----------------------------------------------------------

    private function submit(string $form, array $fields): TestResponse
    {
        return $this->signInMember($this->member)->from('/cp/dashboard')->post($form, $fields);
    }
}
