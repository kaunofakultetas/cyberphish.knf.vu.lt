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
//    fullname-latin1     — Lithuanian letters of the full
//                          name turn into question marks
//
//  Both come from bei_users being a latin1 table: the fix is
//  a conversion of the table to utf8mb4, a change to the
//  production database.
//
//  Used by:
//    - PHPUnit — the Feature suite (runTests.sh)
// -----------------------------------------------------------


namespace Tests\Feature\Account;

use App\Models\Members;
use Tests\FeatureTestCase;








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
