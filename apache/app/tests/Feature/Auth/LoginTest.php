<?php

// -----------------------------------------------------------
//  [*] LoginTest — members signing in and out
//
//  POST /login and POST cp/logout (LoginController): a
//  verified member with the right password is signed in on
//  the mem guard and sent to the dashboard — or back to the
//  page asked for — while the visit is recorded (last
//  login, last address, the login log) and any pending
//  password reset is cancelled. A wrong password, an
//  unknown address and an unverified account all get the
//  same refusal, and ten of them for one email from one
//  address lock that pair out for a while, the right
//  password included — the panel's failures count apart. A
//  panel page parked before the sign-in is not followed. A
//  refusal comes back to the form with its error, even when
//  a member page sent the visitor there. The
//  ten-days-in-a-row badge is earned, and announced, at
//  sign-in. Signing out takes a POST, not a link.
//
//  Used by:
//    - PHPUnit — the Feature suite (runTests.sh)
// -----------------------------------------------------------


namespace Tests\Feature\Auth;

use App\Models\Members;
use DateTime;
use DateTimeZone;
use Dom\HTMLDocument;
use Illuminate\Support\Facades\DB;
use Illuminate\Testing\TestResponse;
use Tests\FeatureTestCase;
use Tests\Support\World;








// -----------------------------------------------------------
// LoginTest
// -----------------------------------------------------------
//
// Every sign-in posts the form from the login page.
//
// Used by:
//   - PHPUnit — the Feature suite (runTests.sh)
// -----------------------------------------------------------

class LoginTest extends FeatureTestCase
{






    // -----------------------------------------------------------
    // testLoginFormOpens
    // -----------------------------------------------------------
    //
    // The form asks for the email and the password.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testLoginFormOpens(): void
    {
        $this->get('/login')
            ->assertOk()
            ->assertSee('name="email"', false)
            ->assertSee('name="password"', false);
    }






    // -----------------------------------------------------------
    // testMemberSignsIn
    // -----------------------------------------------------------
    //
    // The right password signs the member in and opens the
    // dashboard.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testMemberSignsIn(): void
    {
        $member = $this->world->member();

        $this->signIn($member->email, World::PASSWORD)->assertRedirect('/cp/dashboard');

        $this->assertAuthenticated('mem');
        $this->assertSame($member->id, (int) auth('mem')->id());
    }






    // -----------------------------------------------------------
    // testSignInRecordsTheVisit
    // -----------------------------------------------------------
    //
    // The member's last login and last address are set and the
    // login log gains a row.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testSignInRecordsTheVisit(): void
    {
        $member = $this->world->member();

        $this->signIn($member->email, World::PASSWORD);

        $member = Members::find($member->id);
        $this->assertNotNull($member->last_login);
        $this->assertSame('127.0.0.1', $member->last_ip);
        $this->assertSame(1, DB::table('bei_users_loginlog')->where('user_id', $member->id)->count());
    }






    // -----------------------------------------------------------
    // testSignInCancelsAPendingPasswordReset
    // -----------------------------------------------------------
    //
    // A member who remembers the password after asking for a
    // reset link signs in normally, and the link stops
    // working — the login clears reset_token.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testSignInCancelsAPendingPasswordReset(): void
    {
        $member = $this->world->member(['reset_token' => str_repeat('r', 50)]);

        $this->signIn($member->email, World::PASSWORD);

        $this->assertSame('', Members::find($member->id)->reset_token);
        $this->get('/reset/' . str_repeat('r', 50))->assertNotFound();
    }






    // -----------------------------------------------------------
    // testWrongPasswordIsRefused
    // -----------------------------------------------------------
    //
    // Back to the login form with the failure message, still a
    // visitor.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testWrongPasswordIsRefused(): void
    {
        $member = $this->world->member();

        $this->signIn($member->email, 'Not-The-Right-Password')
            ->assertRedirect('/login')
            ->assertSessionHasErrors();

        $this->assertSame(__('main.auth_failed'), session('errors')->first());
        $this->assertGuest('mem');
    }






    // -----------------------------------------------------------
    // testUnknownEmailIsRefusedTheSameWay
    // -----------------------------------------------------------
    //
    // An address no member has gets the very answer a wrong
    // password gets, so the form does not tell which addresses
    // are registered.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testUnknownEmailIsRefusedTheSameWay(): void
    {
        $this->signIn('nobody@example.test', World::PASSWORD)
            ->assertRedirect('/login')
            ->assertSessionHasErrors();

        $this->assertSame(__('main.auth_failed'), session('errors')->first());
    }






    // -----------------------------------------------------------
    // testUnverifiedMemberCannotSignIn
    // -----------------------------------------------------------
    //
    // An account whose verification link was never opened
    // (status 0) is refused even with the right password.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testUnverifiedMemberCannotSignIn(): void
    {
        $member = $this->world->member(['status' => 0, 'verify_token' => str_repeat('v', 50)]);

        $this->signIn($member->email, World::PASSWORD)->assertRedirect('/login');

        $this->assertGuest('mem');
    }






    // -----------------------------------------------------------
    // testShortPasswordFailsValidation
    // -----------------------------------------------------------
    //
    // A password under the 12-character minimum fails
    // validation on the password field before any lookup.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testShortPasswordFailsValidation(): void
    {
        $member = $this->world->member();

        $this->signIn($member->email, 'short')->assertSessionHasErrors('password');

        $this->assertGuest('mem');
    }






    // -----------------------------------------------------------
    // testPasswordPostedAsAListIsRefused
    // -----------------------------------------------------------
    //
    // A password posted as a list goes back to the form with a
    // validation error on the password, the same for an active
    // account as for an unknown address, and signs nobody in:
    // like a password too short, it is refused before any
    // account is looked at. The rule's min once counted the
    // list's twelve items, and for an active account the guard
    // handed the list to password_verify — a 500 an unknown
    // address never got, so the answer told active accounts
    // apart (login-password-list).
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testPasswordPostedAsAListIsRefused(): void
    {
        $member = $this->world->member();

        foreach ([$member->email, 'nobody.here@example.test'] as $email) {
            $this->from('/login')->post('/login', ['email' => $email, 'password' => array_fill(0, 12, 'x')])
                ->assertRedirect('/login')
                ->assertSessionHasErrors('password');
        }

        $this->assertGuest('mem');
    }






    // -----------------------------------------------------------
    // testPasswordWithANulByteIsRefused
    // -----------------------------------------------------------
    //
    // A password with a NUL byte goes back to the form with a
    // validation error before any account is looked at, even
    // the member's real password followed by the byte and
    // more. bcrypt reads a password only up to such a byte, so
    // that password once signed the member in. Where the
    // stored hash had another cost than the configured 10 — the
    // 12 of PHP 8.4's password_hash — the rehash on sign-in
    // failed on it with a 500 (login-password-list).
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testPasswordWithANulByteIsRefused(): void
    {
        $members = [
            $this->world->member(),
            $this->world->member(['password' => password_hash(World::PASSWORD, PASSWORD_BCRYPT, ['cost' => 12])]),
        ];

        foreach ($members as $member) {
            $this->from('/login')->post('/login', ['email' => $member->email, 'password' => World::PASSWORD . "\0anything"])
                ->assertRedirect('/login')
                ->assertSessionHasErrors('password');
        }

        $this->assertGuest('mem');
    }






    // -----------------------------------------------------------
    // testFailedLoginShowsItsError
    // -----------------------------------------------------------
    //
    // A refusal that a member page led to — the auth
    // middleware parked that page in the session — comes back
    // to the form with its error, not to the parked page.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testFailedLoginShowsItsError(): void
    {
        $member = $this->world->member();
        $this->get('/cp/dashboard')->assertRedirect('/login');

        $this->signIn($member->email, 'Wrong-Password-12')
            ->assertRedirect('/login')
            ->assertSessionHasErrors();
    }






    // -----------------------------------------------------------
    // testRefusedLoginKeepsTheEmail
    // -----------------------------------------------------------
    //
    // A refused sign-in brings the form back with the email
    // already in its field and the cursor in the password
    // field, so only the password needs typing again; the
    // password itself never goes into the session. The form
    // once came back empty.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testRefusedLoginKeepsTheEmail(): void
    {
        $member = $this->world->member();

        $this->signIn($member->email, 'Wrong-Password-12')->assertRedirect('/login');
        $this->assertSame($member->email, session()->getOldInput('email'));
        $this->assertNull(session()->getOldInput('password'));

        $html = $this->get('/login')->assertOk()->assertSee('value="' . $member->email . '"', false)->getContent();
        $document = HTMLDocument::createFromString($html, LIBXML_NOERROR);
        $this->assertTrue($document->querySelector('input[name="password"]')->hasAttribute('autofocus'), 'the cursor is not in the password field');
        $this->assertFalse($document->querySelector('input[name="email"]')->hasAttribute('autofocus'), 'the cursor is in the email field');
    }






    // -----------------------------------------------------------
    // testTenWrongPasswordsLockTheLogin
    // -----------------------------------------------------------
    //
    // After ten wrong passwords for one email from one
    // address even the right one is refused — with the
    // lockout message on the email field, not auth_failed —
    // and the member stays a visitor.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testTenWrongPasswordsLockTheLogin(): void
    {
        $member = $this->world->member();
        for ($guess = 1; $guess <= 10; $guess++) {
            $this->signIn($member->email, 'Wrong-Guess-' . $guess);
        }

        $this->signIn($member->email, World::PASSWORD)
            ->assertRedirect('/login')
            ->assertSessionHasErrors('email');

        $this->assertGuest('mem');
    }






    // -----------------------------------------------------------
    // testNineWrongPasswordsStillLetTheMemberIn
    // -----------------------------------------------------------
    //
    // Nine wrong passwords are not enough for the lock: the
    // right one still signs the member in.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testNineWrongPasswordsStillLetTheMemberIn(): void
    {
        $member = $this->world->member();
        for ($guess = 1; $guess <= 9; $guess++) {
            $this->signIn($member->email, 'Wrong-Guess-' . $guess);
        }

        $this->signIn($member->email, World::PASSWORD)->assertRedirect('/cp/dashboard');

        $this->assertAuthenticated('mem');
    }






    // -----------------------------------------------------------
    // testSignedInMemberIsSentAwayFromTheLoginForm
    // -----------------------------------------------------------
    //
    // The login form is for visitors only (guest:mem): a
    // signed-in member is sent to the home page.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testSignedInMemberIsSentAwayFromTheLoginForm(): void
    {
        $this->signInMember($this->world->member())->get('/login')->assertRedirect('/');
    }






    // -----------------------------------------------------------
    // testVisitorIsAskedToSignInFirst
    // -----------------------------------------------------------
    //
    // A member page opened by a visitor redirects to the login
    // form.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testVisitorIsAskedToSignInFirst(): void
    {
        $this->get('/cp/dashboard')->assertRedirect('/login');
    }






    // -----------------------------------------------------------
    // testSignInReturnsToThePageAsked
    // -----------------------------------------------------------
    //
    // After being sent to the login form from a member page,
    // signing in returns the member to that page rather than
    // to the dashboard.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testSignInReturnsToThePageAsked(): void
    {
        $member = $this->world->member();
        $this->get('/cp/badges')->assertRedirect('/login');

        $this->signIn($member->email, World::PASSWORD)->assertRedirect('/cp/badges');
    }






    // -----------------------------------------------------------
    // testSignInIgnoresAPanelPagesParking
    // -----------------------------------------------------------
    //
    // A panel page parked before the member login, by the
    // panel's own sign-in redirect, is not followed: the
    // member lands on the dashboard, where it once landed on
    // the admin login.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testSignInIgnoresAPanelPagesParking(): void
    {
        $member = $this->world->member();
        $this->get('/admin-panel/dashboard')->assertRedirect('/admin-panel');

        $this->signIn($member->email, World::PASSWORD)->assertRedirect('/cp/dashboard');
    }






    // -----------------------------------------------------------
    // testFailedPanelLoginsDoNotLockTheLogin
    // -----------------------------------------------------------
    //
    // Ten wrong panel passwords for a member's address leave
    // the member login of that address open: each login counts
    // its own failures, where both once counted under one key.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testFailedPanelLoginsDoNotLockTheLogin(): void
    {
        $member = $this->world->member();
        for ($guess = 1; $guess <= 10; $guess++) {
            $this->from('/admin-panel')->post('/admin-panel', ['email' => $member->email, 'password' => 'Wrong-Guess-' . $guess]);
        }

        $this->signIn($member->email, World::PASSWORD)->assertRedirect('/cp/dashboard');

        $this->assertAuthenticated('mem');
    }






    // -----------------------------------------------------------
    // testLogoutEndsTheSession
    // -----------------------------------------------------------
    //
    // Signing out leaves a visitor on the home page.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testLogoutEndsTheSession(): void
    {
        $this->signInMember($this->world->member())->post('/cp/logout')->assertRedirect('/');

        $this->assertGuest('mem');
    }






    // -----------------------------------------------------------
    // testSigningOutNeedsAPost
    // -----------------------------------------------------------
    //
    // A plain link to cp/logout — another site's link or image
    // included — signs nobody out: the route answers only a
    // POST, which carries the session's token.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testSigningOutNeedsAPost(): void
    {
        $this->signInMember($this->world->member())->get('/cp/logout')->assertStatus(405);

        $this->assertAuthenticated('mem');
    }






    // -----------------------------------------------------------
    // testTenDaysInARowEarnTheBadge
    // -----------------------------------------------------------
    //
    // A member who signed in on each of the nine days before
    // today earns badge_login_10 with today's sign-in, in the
    // language of the session.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testTenDaysInARowEarnTheBadge(): void
    {
        $member = $this->world->member();
        for ($day = 1; $day <= 9; $day++) {
            $this->world->loginLog($member->id, $this->localNoon($day));
        }

        $this->signIn($member->email, World::PASSWORD);

        $this->assertTrue(DB::table('bei_users_badges')
            ->where(['user_id' => $member->id, 'name' => 'badge_login_10', 'lang_id' => $this->world->language('en')])
            ->exists());
    }






    // -----------------------------------------------------------
    // testLoginThatEarnsTheBadgeAnnouncesIt
    // -----------------------------------------------------------
    //
    // The login that earns badge_login_10 flashes the
    // new-badge message, and the dashboard it leads to shows
    // it.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testLoginThatEarnsTheBadgeAnnouncesIt(): void
    {
        $member = $this->world->member();
        for ($day = 1; $day <= 9; $day++) {
            $this->world->loginLog($member->id, $this->localNoon($day));
        }

        $this->signIn($member->email, World::PASSWORD)->assertRedirect('/cp/dashboard');

        $this->get('/cp/dashboard')->assertSee(__('main.new_badge'), false);
    }






    // -----------------------------------------------------------
    // testNineDaysAreNotEnoughForTheBadge
    // -----------------------------------------------------------
    //
    // A gap in the last ten days — here the day before
    // yesterday — earns no badge.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testNineDaysAreNotEnoughForTheBadge(): void
    {
        $member = $this->world->member();
        foreach ([1, 3, 4, 5, 6, 7, 8, 9] as $day) {
            $this->world->loginLog($member->id, $this->localNoon($day));
        }

        $this->signIn($member->email, World::PASSWORD);

        $this->assertFalse(DB::table('bei_users_badges')->where('user_id', $member->id)->exists());
    }






    // -----------------------------------------------------------
    // testStreakCountsTheMembersDays
    // -----------------------------------------------------------
    //
    // The ten days of badge_login_10 are the members' days
    // (config app.display_timezone), not UTC's: a sign-in at
    // half past midnight on each of the nine days before
    // today — stored under the UTC date before it — and
    // today's earn the badge. The days once ended at UTC
    // midnight, two or three at night in the members'
    // countries, and such a streak earned nothing
    // (login-streak-counts-utc-days). This test and
    // testTenDaysInARowEarnTheBadge, whose sign-ins are at
    // noon, pass at any hour, and a return to UTC days fails
    // one of the two whatever the hour.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testStreakCountsTheMembersDays(): void
    {
        $local = new DateTimeZone(config('app.display_timezone'));
        $member = $this->world->member();
        for ($day = 1; $day <= 9; $day++) {
            $at = (new DateTime("today -$day days 00:30", $local))->setTimezone(new DateTimeZone('UTC'));
            $this->world->loginLog($member->id, $at->format('Y-m-d H:i:s'));
        }

        $this->signIn($member->email, World::PASSWORD);

        $this->assertTrue(DB::table('bei_users_badges')
            ->where(['user_id' => $member->id, 'name' => 'badge_login_10'])->exists(), 'ten local days in a row earned no badge');
    }






    // -----------------------------------------------------------
    // testLoginBadgeIsTheMembersWhateverTheLanguage
    // -----------------------------------------------------------
    //
    // badge_login_10 belongs to the member, not to a language:
    // the badges page shows it in every language, and a member
    // who holds it — here filed under English — earns no
    // second one, and hears of no new badge, with another
    // streak signed in from a session in Lithuanian. It was
    // filed under the language of the session at sign-in,
    // which a fresh session gives as English, and showed only
    // on the badges page of that language.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testLoginBadgeIsTheMembersWhateverTheLanguage(): void
    {
        $member = $this->world->member();
        $this->world->badge($member->id, $this->world->language('en'), 'badge_login_10');
        $this->world->language('lt');
        for ($day = 1; $day <= 9; $day++) {
            $this->world->loginLog($member->id, $this->localNoon($day));
        }
        $this->get('/lang/lt');

        $this->signIn($member->email, World::PASSWORD)->assertRedirect('/cp/dashboard')->assertSessionMissing('success');

        $this->assertSame(1, DB::table('bei_users_badges')->where(['user_id' => $member->id, 'name' => 'badge_login_10'])->count());
        $this->get('/cp/badges')->assertOk()->assertSee('/badges/badge_login_10.png', false);
    }






    // -----------------------------------------------------------
    // localNoon
    // -----------------------------------------------------------
    //
    // Noon on the members' clock (config app.display_timezone)
    // the given number of days before today, as the UTC the
    // login log stores — so the streak tests hold whatever the
    // hour they run at.
    //
    // Used by:
    //   - testTenDaysInARowEarnTheBadge,
    //     testLoginThatEarnsTheBadgeAnnouncesIt,
    //     testNineDaysAreNotEnoughForTheBadge (above)
    // -----------------------------------------------------------

    private function localNoon(int $daysAgo): string
    {
        $noon = new DateTime("today -$daysAgo days 12:00", new DateTimeZone(config('app.display_timezone')));
        return $noon->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s');
    }






    // -----------------------------------------------------------
    // signIn
    // -----------------------------------------------------------
    //
    // Posts the login form, as sent from the login page.
    //
    // Used by:
    //   - the sign-in tests of this class
    // -----------------------------------------------------------

    private function signIn(string $email, string $password): TestResponse
    {
        return $this->from('/login')->post('/login', ['email' => $email, 'password' => $password]);
    }
}
