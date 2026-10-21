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
//    verify-blank-token       — /verify/%20 switches every
//                               deactivated account back on
//    login-throttle           — the member login takes any
//                               number of password guesses
//    country-out-of-range     — a country id above 5 breaks
//                               every page of that member
//    password-trim            — a password with a blank at
//                               either end never matches its
//                               repeat
//    password-flash           — a failed sign-up keeps the
//                               repeated password in the
//                               session
//    reset-link-logout        — signing out kills a pending
//                               reset link
//    login-error-lost         — a failed login that a member
//                               page led to loses its error
//    login-badge-message      — the login that earns the
//                               ten-days badge never says so
//    register-while-signed-in — a signed-in member can still
//                               open and post the sign-up
//                               form
//    captcha-message          — a failed reCAPTCHA shows a
//                               raw key, an unticked one the
//                               field's raw name
//
//  Used by:
//    - PHPUnit — the Feature suite (runTests.sh)
// -----------------------------------------------------------


namespace Tests\Feature\Auth;

use App\Models\Members;
use Illuminate\Support\Facades\DB;
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






    // -----------------------------------------------------------
    // testPasswordWithBlankEdgesRegisters
    // -----------------------------------------------------------
    //
    // TrimStrings leaves only Laravel's own password fields
    // untrimmed — password and password_confirmation — so the
    // sign-up form's password2 loses the blanks at its ends
    // while password keeps them: a password that starts or
    // ends with a blank never matches its repeat, and the
    // form refuses it. The same list trims the new password
    // of both change-password forms and the manager form,
    // which then save it without the blank, and the login
    // refuses it. Wanted: the account is created.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testPasswordWithBlankEdgesRegisters(): void
    {
        $this->solveCaptcha();

        $this->from('/register')->post('/register', [
            'email'                => 'blank-edges@example.test',
            'password'             => ' Blank-Edged-Pass-9 ',
            'password2'            => ' Blank-Edged-Pass-9 ',
            'country'              => 1,
            'g-recaptcha-response' => 'solved',
        ]);

        $this->pinDefect('password-trim', function () {
            $this->assertTrue(Members::where('email', 'blank-edges@example.test')->exists(), 'the matching passwords were refused');
        });
    }






    // -----------------------------------------------------------
    // testFailedSignUpKeepsNoPasswordInTheSession
    // -----------------------------------------------------------
    //
    // A failed validation sends the form back with its input
    // flashed into the session, minus the fields the
    // exception handler's dontFlash names — Laravel's
    // password and password_confirmation only. The sign-up
    // form's password2, and the manager form's pass and
    // repass, sit in the session store as typed until the
    // next request takes them out. Wanted: no password of the
    // form in the session.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testFailedSignUpKeepsNoPasswordInTheSession(): void
    {
        $this->from('/register')->post('/register', [
            'email'     => 'not-an-address',
            'password'  => World::PASSWORD,
            'password2' => World::PASSWORD,
            'country'   => 1,
        ])->assertSessionHasErrors('email');

        $this->pinDefect('password-flash', function () {
            $this->assertArrayNotHasKey('password2', session()->getOldInput(), 'password2 was flashed');
        });
    }






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






    // -----------------------------------------------------------
    // testFailedLoginShowsItsError
    // -----------------------------------------------------------
    //
    // LoginController::login answers a refused sign-in
    // through Laravel's intended redirect, which first takes
    // the member page the auth middleware parked in the
    // session when it sent the visitor to the form. That page
    // sends the visitor straight back, and the flashed error
    // is spent on the way: the form returns without a word.
    // Wanted: the form again, with the error.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testFailedLoginShowsItsError(): void
    {
        $member = $this->world->member();
        $this->get('/cp/dashboard')->assertRedirect('/login');

        $response = $this->from('/login')->post('/login', ['email' => $member->email, 'password' => 'Wrong-Password-12']);

        $this->pinDefect('login-error-lost', function () use ($response) {
            $response->assertRedirect('/login');
            $response->assertSessionHasErrors();
        });
    }






    // -----------------------------------------------------------
    // testLoginThatEarnsTheBadgeAnnouncesIt
    // -----------------------------------------------------------
    //
    // LoginLog::if_login_10 sets the new-badge message on the
    // wrong branch — save_badge answers true for a NEW badge —
    // and with session()->now(), which lasts for the login
    // request alone, so the redirect that follows drops it
    // anyway: the login that earns badge_login_10 is never
    // announced. Wanted: the dashboard after that login says
    // a new badge came.
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

        $this->from('/login')->post('/login', ['email' => $member->email, 'password' => World::PASSWORD])
            ->assertRedirect('/cp/dashboard');
        $dashboard = $this->get('/cp/dashboard');

        $this->pinDefect('login-badge-message', function () use ($dashboard) {
            $dashboard->assertSee(__('main.new_badge'), false);
        });
    }






    // -----------------------------------------------------------
    // testSignedInMemberIsSentAwayFromTheRegistrationForm
    // -----------------------------------------------------------
    //
    // RegisterController::__construct puts a bare guest on
    // every action, and RedirectIfAuthenticated::handle
    // redirects only when the alias names a guard — man or
    // mem — so with none it lets the request through: a
    // signed-in member is still shown the sign-up form, and
    // posting it creates another account and mails its
    // verification link. The login form, behind guest:mem,
    // sends the same member to the home page. Wanted: the
    // form and its POST send the member to the home page, and
    // no account is created.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testSignedInMemberIsSentAwayFromTheRegistrationForm(): void
    {
        $this->signInMember($this->world->member());
        $form = $this->get('/register');

        // The page asks the captcha service for its widget,
        // which the mock cannot answer — so the mock comes
        // after the page
        $this->solveCaptcha();
        $signUp = $this->from('/register')->post('/register', [
            'email'                => 'second-account@example.test',
            'password'             => World::PASSWORD,
            'password2'            => World::PASSWORD,
            'country'              => 1,
            'g-recaptcha-response' => 'solved',
        ]);

        $this->pinDefect('register-while-signed-in', function () use ($form, $signUp) {
            $form->assertRedirect('/');
            $signUp->assertRedirect('/');
            $this->assertFalse(Members::where('email', 'second-account@example.test')->exists(), 'a signed-in member opened another account');
        });
    }






    // -----------------------------------------------------------
    // testCaptchaErrorsReadInWords
    // -----------------------------------------------------------
    //
    // The sign-up and reset-request forms —
    // RegisterController::register and
    // LoginController::forgotpassword — check the reCAPTCHA
    // with the captcha rule of anhskohbo/no-captcha, but no
    // resources/lang validation.php has a line for that rule
    // or a name for the g-recaptcha-response field under
    // attributes, and Laravel's translator hands a missing
    // key back as it is: in every site language, a failed
    // check makes both forms print validation.captcha, and an
    // unticked box makes them name the field
    // g-recaptcha-response. Wanted: both errors in words,
    // with no raw key and no raw field name.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testCaptchaErrorsReadInWords(): void
    {
        $this->solveCaptcha(false);
        $forms = [
            '/register'        => ['email' => 'captcha-errors@example.test', 'password' => World::PASSWORD, 'password2' => World::PASSWORD, 'country' => 1],
            '/forgot-password' => ['email' => 'captcha-errors@example.test'],
        ];

        // Both forms speak the session's language, which
        // BaseController reads on every request
        $errors = [];
        foreach (['en', 'lt', 'ee', 'lv', 'gr'] as $locale) {
            foreach ($forms as $form => $fields) {
                foreach (['a failed check' => 'expired-token', 'an unticked box' => ''] as $answer => $token) {
                    $this->withSession(['lang' => $locale])->from($form)
                        ->post($form, array_merge($fields, ['g-recaptcha-response' => $token]))
                        ->assertSessionHasErrors('g-recaptcha-response');
                    $errors["$form in $locale after $answer"] = session('errors')->first('g-recaptcha-response');
                }
            }
        }

        $this->pinDefect('captcha-message', function () use ($errors) {
            foreach ($errors as $case => $error) {
                $this->assertStringStartsNotWith('validation.', $error, "$case says $error");
                $this->assertStringNotContainsString('g-recaptcha-response', $error, "$case says $error");
            }
        });
    }
}
