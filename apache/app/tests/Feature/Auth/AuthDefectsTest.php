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
//    reset-link-logout   — signing out kills a pending reset
//                          link
//    login-error-lost    — a failed login that a member page
//                          led to loses its error
//    login-badge-message — the login that earns the ten-days
//                          badge never says so
//    captcha-message     — a failed reCAPTCHA shows a raw key,
//                          an unticked one the field's raw
//                          name
//
//  Used by:
//    - PHPUnit — the Feature suite (runTests.sh)
// -----------------------------------------------------------


namespace Tests\Feature\Auth;

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
