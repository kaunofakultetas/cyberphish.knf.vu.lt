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
//    verify-blank-token   — /verify/%20 switches every
//                           deactivated account back on
//    login-throttle       — the member login takes any
//                           number of password guesses
//    country-out-of-range — a country id above 5 breaks
//                           every page of that member
//
//  Used by:
//    - PHPUnit — the Feature suite (runTests.sh)
// -----------------------------------------------------------


namespace Tests\Feature\Auth;

use App\Models\Members;
use Tests\FeatureTestCase;
use Tests\Support\World;








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
    // testBlankVerificationLinkLeavesDeactivatedAccountsAlone
    // -----------------------------------------------------------
    //
    // An account an admin switched off (status 0) after it was
    // verified has an empty verify token. RegisterController::
    // verify looks members up by the token from the URL with
    // no "exactly one" guard, and MySQL ignores trailing
    // spaces, so /verify/%20 matches every empty token and
    // sets every such account back to status 1. Wanted: the
    // account stays off.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testBlankVerificationLinkLeavesDeactivatedAccountsAlone(): void
    {
        $deactivated = $this->world->member(['status' => 0]);

        $this->pinDefect('verify-blank-token', function () use ($deactivated) {
            $this->get('/verify/%20');
            $this->assertSame(0, Members::find($deactivated->id)->status, 'the deactivated account is active again');
        });
    }






    // -----------------------------------------------------------
    // testRepeatedWrongPasswordsLockTheLoginForAWhile
    // -----------------------------------------------------------
    //
    // LoginController::login replaces Laravel's login without
    // calling ThrottlesLogins, and the form has no reCAPTCHA,
    // so password guessing is unlimited. Wanted: after ten
    // wrong passwords even the right one is refused for a
    // while.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testRepeatedWrongPasswordsLockTheLoginForAWhile(): void
    {
        $member = $this->world->member();

        $this->pinDefect('login-throttle', function () use ($member) {
            for ($guess = 1; $guess <= 10; $guess++) {
                $this->from('/login')->post('/login', ['email' => $member->email, 'password' => 'Wrong-Guess-' . $guess]);
            }
            $this->from('/login')->post('/login', ['email' => $member->email, 'password' => World::PASSWORD]);
            $this->assertGuest('mem');
        });
    }






    // -----------------------------------------------------------
    // testMemberOfAnyCountryCanOpenTheDashboard
    // -----------------------------------------------------------
    //
    // Registration accepts any integer as the country, but
    // LMController::pc_questionare_link knows countries 0 to 5
    // only, and BaseController calls it on every page of a
    // signed-in member — an undefined index, a 500 on every
    // page. Wanted: the dashboard opens.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testMemberOfAnyCountryCanOpenTheDashboard(): void
    {
        $member = $this->world->member(['country' => 9]);

        $this->pinDefect('country-out-of-range', function () use ($member) {
            $this->signInMember($member)->get('/cp/dashboard')->assertOk();
        });
    }
}
