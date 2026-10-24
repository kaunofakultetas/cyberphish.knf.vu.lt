<?php

// -----------------------------------------------------------
//  [*] AuthDefectsTest — known defects of signing in
//
//  Each test states the behaviour the logins should have
//  and pins the defect that breaks it today (KnownDefects):
//  the run reports them as incomplete, tagged with their
//  ids, until a fix makes one pass — then the test fails as
//  the cue to remove its pin and move it into its home file.
//
//    login-password-list           — a password posted as a
//                                    list is a 500 for an
//                                    active account, which the
//                                    answer then gives away
//    login-streak-counts-utc-days  — the ten-days-in-a-row
//                                    badge counts days from
//                                    UTC midnight, not the
//                                    members'
//    register-terms-not-enforced   — a sign-up without the
//                                    terms ticked creates the
//                                    account
//
//  Used by:
//    - PHPUnit — the Feature suite (runTests.sh)
// -----------------------------------------------------------


namespace Tests\Feature\Auth;

use DateTime;
use DateTimeZone;
use Illuminate\Support\Facades\DB;
use Tests\FeatureTestCase;
use Tests\Support\World;








// -----------------------------------------------------------
// AuthDefectsTest
// -----------------------------------------------------------
//
// One pinned defect per test, named in the header; each test
// builds its own account.
//
// Used by:
//   - PHPUnit — the Feature suite (runTests.sh)
// -----------------------------------------------------------

class AuthDefectsTest extends FeatureTestCase
{






    // -----------------------------------------------------------
    // testPasswordPostedAsAListIsRefusedQuietly
    // -----------------------------------------------------------
    //
    // The logins' min and max count a list's items, so twelve
    // passwords pass the member form's rule and six the
    // panel's; for an active account the guard then hands the
    // list to password_verify, a TypeError and a 500, while an
    // unknown or switched-off address gets the usual refusal —
    // so the answer tells active accounts apart, with no
    // reCAPTCHA in the way, and the 500 comes before the
    // failure is counted towards the lockout. The sign-up form
    // mails its verification link, then fails on Hash::make.
    // Wanted: every one refused without a server error.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testPasswordPostedAsAListIsRefusedQuietly(): void
    {
        $member = $this->world->member();
        $manager = $this->world->manager();
        $this->solveCaptcha();

        $statuses = [
            'member login, active account'  => $this->from('/login')->post('/login', ['email' => $member->email, 'password' => array_fill(0, 12, 'x')])->getStatusCode(),
            'member login, unknown address' => $this->from('/login')->post('/login', ['email' => 'nobody.here@example.test', 'password' => array_fill(0, 12, 'x')])->getStatusCode(),
            'panel login, active account'   => $this->from('/admin-panel')->post('/admin-panel', ['email' => $manager->email, 'password' => array_fill(0, 6, 'x')])->getStatusCode(),
            'sign-up'                       => $this->from('/register')->post('/register', [
                'email' => 'listed.password@example.test', 'password' => array_fill(0, 12, 'x'), 'password2' => array_fill(0, 12, 'x'),
                'country' => 1, 'g-recaptcha-response' => 'token-from-the-widget',
            ])->getStatusCode(),
        ];

        $this->pinDefect('login-password-list', function () use ($statuses) {
            $this->assertSame([], array_keys(array_filter($statuses, function (int $status) {
                return $status >= 500;
            })), 'posts answered with a server error');
        });
    }






    // -----------------------------------------------------------
    // testStreakCountsTheMembersDays
    // -----------------------------------------------------------
    //
    // LoginLog::if_login_10 counts logins per date as the app
    // stores them, in UTC, so the ten days of badge_login_10
    // end at UTC midnight — 02:00 or 03:00 for the members in
    // Lithuania, Latvia, Estonia and Greece, the hours the
    // pages show (config app.display_timezone). A member who
    // signed in at half past midnight on each of the nine
    // days before today has nine UTC days a day early, and
    // yesterday's UTC date empty: ten days in a row, and no
    // badge. Wanted: the days the members live by. Skipped in
    // the hours when the local date and UTC's differ.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testStreakCountsTheMembersDays(): void
    {
        $local = new DateTimeZone(config('app.display_timezone'));
        if ((new DateTime('now', $local))->format('Y-m-d') !== gmdate('Y-m-d')) {
            $this->markTestSkipped('the local date and the UTC date differ right now');
        }
        $member = $this->world->member();
        for ($day = 1; $day <= 9; $day++) {
            $at = (new DateTime("today -$day days 00:30", $local))->setTimezone(new DateTimeZone('UTC'));
            $this->world->loginLog($member->id, $at->format('Y-m-d H:i:s'));
        }

        $this->from('/login')->post('/login', ['email' => $member->email, 'password' => World::PASSWORD]);

        $this->pinDefect('login-streak-counts-utc-days', function () use ($member) {
            $this->assertTrue(DB::table('bei_users_badges')
                ->where(['user_id' => $member->id, 'name' => 'badge_login_10'])->exists(), 'ten local days in a row earned no badge');
        });
    }






    // -----------------------------------------------------------
    // testSignUpNeedsTheTermsAccepted
    // -----------------------------------------------------------
    //
    // The sign-up form will not post without its privacy
    // policy box ticked, but the server checks no such field:
    // a post without it — a script, or a form whose required
    // attribute was taken off — creates the account and mails
    // its link, the consent neither asked for nor kept.
    // Wanted: no account without the terms accepted. The
    // RegistrationTest form helper posts no terms box, so its
    // sign-ups need one once this is fixed.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testSignUpNeedsTheTermsAccepted(): void
    {
        $this->solveCaptcha();

        $this->from('/register')->post('/register', [
            'email'                => 'no.terms@example.test',
            'password'             => 'Long-Enough-Password-1',
            'password2'            => 'Long-Enough-Password-1',
            'country'              => 1,
            'g-recaptcha-response' => 'token-from-the-widget',
        ]);

        $this->pinDefect('register-terms-not-enforced', function () {
            $this->assertFalse(DB::table('bei_users')->where('email', 'no.terms@example.test')->exists(), 'an account was created without the terms accepted');
        });
    }
}
