<?php

// -----------------------------------------------------------
//  [*] LoginTest — members signing in and out
//
//  POST /login and GET cp/logout (LoginController): a
//  verified member with the right password is signed in on
//  the mem guard and sent to the dashboard — or back to the
//  page asked for — while the visit is recorded (last login,
//  last address, the login log) and any pending password
//  reset is cancelled. A wrong password, an unknown address
//  and an unverified account all get the same refusal, and
//  ten of them for one email from one address lock that
//  pair out for a while, the right password included. A
//  refusal comes back to the form with its error, even when
//  a member page sent the visitor there. The
//  ten-days-in-a-row badge is earned, and announced, at
//  sign-in.
//
//  Used by:
//    - PHPUnit — the Feature suite (runTests.sh)
// -----------------------------------------------------------


namespace Tests\Feature\Auth;

use App\Models\Members;
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
    // working — the login clears remember_token.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testSignInCancelsAPendingPasswordReset(): void
    {
        $member = $this->world->member(['remember_token' => str_repeat('r', 50)]);

        $this->signIn($member->email, World::PASSWORD);

        $this->assertSame('', Members::find($member->id)->remember_token);
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
        $this->signInMember($this->world->member())->get('/cp/logout')->assertRedirect('/');

        $this->assertGuest('mem');
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
            $this->world->loginLog($member->id, date('Y-m-d 12:00:00', strtotime("-$day days")));
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
            $this->world->loginLog($member->id, date('Y-m-d 12:00:00', strtotime("-$day days")));
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
            $this->world->loginLog($member->id, date('Y-m-d 12:00:00', strtotime("-$day days")));
        }

        $this->signIn($member->email, World::PASSWORD);

        $this->assertFalse(DB::table('bei_users_badges')->where('user_id', $member->id)->exists());
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
