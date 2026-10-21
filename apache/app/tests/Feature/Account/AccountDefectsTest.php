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
}
