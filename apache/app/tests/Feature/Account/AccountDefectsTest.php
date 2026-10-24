<?php

// -----------------------------------------------------------
//  [*] AccountDefectsTest — known defects of the account forms
//
//  Each test states the behaviour the dashboard's account
//  forms should have and pins the defect that breaks it today
//  (KnownDefects): the run reports them as incomplete,
//  tagged with their ids, until a fix makes one pass — then
//  the test fails as the cue to remove its pin and move it
//  into AccountSettingsTest.
//
//    account-array-input  — a field posted as a list is a 500,
//                           or stored as JSON text
//
//  Used by:
//    - PHPUnit — the Feature suite (runTests.sh)
// -----------------------------------------------------------


namespace Tests\Feature\Account;

use Illuminate\Support\Facades\DB;
use Tests\FeatureTestCase;








// -----------------------------------------------------------
// AccountDefectsTest
// -----------------------------------------------------------
//
// One pinned defect per test, named in the header; a member
// posting from the dashboard in every test.
//
// Used by:
//   - PHPUnit — the Feature suite (runTests.sh)
// -----------------------------------------------------------

class AccountDefectsTest extends FeatureTestCase
{






    // -----------------------------------------------------------
    // testFieldsPostedAsListsAreRefused
    // -----------------------------------------------------------
    //
    // The password form hands a current password posted as a
    // list to password_verify, a TypeError and a 500; the
    // user-name form's 6 to 45 counts a list's elements, so
    // six words pass and MySQL stores the list's JSON text,
    // cut to 45 characters, as the user name. Wanted: both
    // posts are refused and the account stays as it was.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testFieldsPostedAsListsAreRefused(): void
    {
        $member = $this->world->member();

        $status = $this->signInMember($member)->from('/cp/dashboard')->post('/cp/change-pass', [
            'password'  => ['x'],
            'newpass'   => 'A-new-password-1',
            'renewpass' => 'A-new-password-1',
        ])->getStatusCode();
        $this->signInMember($member)->from('/cp/dashboard')->post('/cp/change-username', ['username' => array_fill(0, 6, 'abcdef')]);

        $this->pinDefect('account-array-input', function () use ($status, $member) {
            $this->assertLessThan(500, $status, 'the password form answered with a server error');
            $this->assertSame($member->username, DB::table('bei_users')->where('id', $member->id)->value('username'), 'the user name became the list');
        });
    }
}
