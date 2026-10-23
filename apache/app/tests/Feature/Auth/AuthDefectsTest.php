<?php

// -----------------------------------------------------------
//  [*] AuthDefectsTest — known defects of signing in, pinned
//
//  Each test states the behaviour the app should have and
//  pins the defect that breaks it today (KnownDefects): the
//  run reports them as incomplete, tagged with their ids,
//  until a fix makes one pass — then the test fails as the
//  cue to remove its pin and move it into its home file.
//
//    reset-link-logout — signing out kills a pending reset
//                        link; the fix needs a column of its
//                        own for the reset token
//
//  Used by:
//    - PHPUnit — the Feature suite (runTests.sh)
// -----------------------------------------------------------


namespace Tests\Feature\Auth;

use Illuminate\Support\Facades\DB;
use Tests\FeatureTestCase;








// -----------------------------------------------------------
// AuthDefectsTest
// -----------------------------------------------------------
//
// One pinned defect per test, named in the header.
//
// Used by:
//   - PHPUnit — the Feature suite (runTests.sh)
// -----------------------------------------------------------

class AuthDefectsTest extends FeatureTestCase
{






    // -----------------------------------------------------------
    // testSigningOutKeepsAPendingResetLink
    // -----------------------------------------------------------
    //
    // The reset link's token lives in remember_token, the
    // column Laravel's session guard keeps for remember-me,
    // and the guard's logout replaces a non-empty one with a
    // random string: a member still signed in on another
    // device who signs out there after asking for a link
    // kills the link before it is opened. Wanted: the token
    // survives the logout.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testSigningOutKeepsAPendingResetLink(): void
    {
        $member = $this->world->member(['remember_token' => 'pending-reset-token-of-the-test']);

        $this->signInMember($member)->get('/cp/logout');

        $this->pinDefect('reset-link-logout', function () use ($member) {
            $token = DB::table('bei_users')->where('id', $member->id)->value('remember_token');
            $this->assertSame('pending-reset-token-of-the-test', $token, 'the logout replaced the reset token');
        });
    }
}
