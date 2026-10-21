<?php

// -----------------------------------------------------------
//  [*] AccountDefectsTest — known defects of account forms
//
//  Each test states the behaviour the account forms of the
//  dashboard should have and pins the defect that breaks it
//  today (KnownDefects): the run reports them as incomplete,
//  tagged with their ids, until a fix makes one pass — then
//  the test fails as the cue to remove its pin and move it
//  into AccountSettingsTest.
//
//    username-lithuanian — a user name with Lithuanian
//                          letters is a 500
//    username-length     — a user name longer than its
//                          column is cut without a word
//    password-policy     — a changed password may be 6
//                          characters, a new one needs 12
//    fullname-latin1     — Lithuanian letters of the full
//                          name turn into question marks
//    ee-strings          — the Estonian user-name form
//                          talks of the password, and its
//                          button ends in a stray 2
//
//  Used by:
//    - PHPUnit — the Feature suite (runTests.sh)
// -----------------------------------------------------------


namespace Tests\Feature\Account;

use App\Models\Members;
use Illuminate\Support\Facades\Hash;
use Tests\FeatureTestCase;
use Tests\Support\World;








// -----------------------------------------------------------
// AccountDefectsTest
// -----------------------------------------------------------
//
// One pinned defect per test, named in the header.
//
// Used by:
//   - PHPUnit — the Feature suite (runTests.sh)
// -----------------------------------------------------------

class AccountDefectsTest extends FeatureTestCase
{






    // -----------------------------------------------------------
    // testUsernameWithLithuanianLettersIsSaved
    // -----------------------------------------------------------
    //
    // The username column is latin1 while the connection
    // speaks utf8mb4, so the uniqueness lookup in
    // ManagerMainController::change_username fails with an
    // illegal mix of collations — the 500 the production log
    // recorded for students' real names. Wanted: the name is
    // saved.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testUsernameWithLithuanianLettersIsSaved(): void
    {
        $member = $this->world->member();

        $this->pinDefect('username-lithuanian', function () use ($member) {
            $this->signInMember($member)->from('/cp/dashboard')
                ->post('/cp/change-username', ['username' => 'Vaiva Vaitkevičiūtė'])
                ->assertRedirect('/cp/dashboard');
            $this->assertSame('Vaiva Vaitkevičiūtė', Members::find($member->id)->username);
        });
    }






    // -----------------------------------------------------------
    // testUsernameTheColumnCannotHoldIsRefused
    // -----------------------------------------------------------
    //
    // change_username accepts up to 100 characters for a
    // column of 45, and the non-strict SQL mode the
    // connection runs in cuts the rest off silently. Wanted:
    // the name is refused and the old one kept.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testUsernameTheColumnCannotHoldIsRefused(): void
    {
        $member = $this->world->member();

        $this->pinDefect('username-length', function () use ($member) {
            $this->signInMember($member)->from('/cp/dashboard')
                ->post('/cp/change-username', ['username' => str_repeat('n', 60)])
                ->assertSessionHasErrors();
            $this->assertSame($member->username, Members::find($member->id)->username);
        });
    }






    // -----------------------------------------------------------
    // testChangedPasswordMeetsTheRegistrationMinimum
    // -----------------------------------------------------------
    //
    // Registration and the login demand 12 characters, but
    // ManagerMainController::change_pass accepts 6. Wanted: a
    // 6-character password is refused and the old one kept.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testChangedPasswordMeetsTheRegistrationMinimum(): void
    {
        $member = $this->world->member();

        $this->pinDefect('password-policy', function () use ($member) {
            $this->signInMember($member)->from('/cp/dashboard')->post('/cp/change-pass', [
                'password'  => World::PASSWORD,
                'newpass'   => 'six-ch',
                'renewpass' => 'six-ch',
            ])->assertSessionHasErrors();
            $this->assertTrue(Hash::check(World::PASSWORD, Members::find($member->id)->password));
        });
    }






    // -----------------------------------------------------------
    // testFullNameWithLithuanianLettersIsStoredAsTyped
    // -----------------------------------------------------------
    //
    // The certificate prints the full name, but bei_users is a
    // latin1 table: letters latin1 lacks — ą, č, ę, ė, į, ų, ū
    // — are stored as question marks without an error, and the
    // certificate shows them so (eight production full names
    // already carry one). Wanted: the name comes back exactly
    // as typed.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testFullNameWithLithuanianLettersIsStoredAsTyped(): void
    {
        $member = $this->world->member();

        $this->pinDefect('fullname-latin1', function () use ($member) {
            $this->signInMember($member)->from('/cp/dashboard')
                ->post('/cp/change-fullname', ['fullname' => 'Ona Petraitytė']);
            $this->assertSame('Ona Petraitytė', Members::find($member->id)->fullname);
        });
    }






    // -----------------------------------------------------------
    // testEstonianUserNameFormSpeaksOfTheUserName
    // -----------------------------------------------------------
    //
    // resources/lang/ee/main.php gets three strings of the
    // dashboard's user-name form wrong: the button,
    // change_username2, ends in a stray 2, and pass_updated
    // and pass_unavailable — the answers
    // ManagerMainController::change_username gives a rename
    // and a name already taken — say the password was
    // updated or is unavailable. An Estonian member who
    // renames is told the password changed. Wanted: a button
    // without the stray 2, and both answers about the user
    // name, not the password.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testEstonianUserNameFormSpeaksOfTheUserName(): void
    {
        $this->world->language('ee');
        $member = $this->world->member();
        $other = $this->world->member();
        $this->signInMember($member)->withSession(['lang' => 'ee'])->from('/cp/dashboard');

        // The first submit button after the user-name form's
        // action is that form's own
        $dashboard = $this->get('/cp/dashboard')->assertOk()->getContent();
        $this->assertSame(1, preg_match('#cp/change-username".*?type="submit" value="([^"]*)"#s', $dashboard, $button), 'the dashboard has no user-name button');

        $this->post('/cp/change-username', ['username' => 'Renamed' . $member->id])->assertSessionHas('success');
        $renamed = session('success');
        $this->post('/cp/change-username', ['username' => $other->username])->assertSessionHasErrors();
        $taken = session('errors')->first();

        $this->pinDefect('ee-strings', function () use ($button, $renamed, $taken) {
            $this->assertNotRegExp('/\d/', $button[1], "the button reads {$button[1]}");
            foreach (['the rename' => $renamed, 'the taken name' => $taken] as $case => $answer) {
                $this->assertStringContainsStringIgnoringCase('kasutaja', $answer, "$case answers $answer");
                $this->assertStringNotContainsStringIgnoringCase('parool', $answer, "$case answers $answer");
            }
        });
    }
}
