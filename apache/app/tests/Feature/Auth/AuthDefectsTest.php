<?php

// -----------------------------------------------------------
//  [*] AuthDefectsTest — known defects of signing in and up
//
//  Each test states the behaviour the logins, the sign-up
//  and the password reset should have and pins the defect
//  that breaks it today (KnownDefects): the run reports
//  them as incomplete, tagged with their ids, until a fix
//  makes one pass — then the test fails as the cue to
//  remove its pin and move it into its home file.
//
//    reset-link-typed-address — the reset link goes to the
//      address as typed, which the accent- and case-blind
//      collation matches to another account
//    login-throttle-collation-bypass — a locked login opens
//      under a spelling of the address that the collation
//      folds and the lockout key does not
//    register-password-loose-compare — the sign-up takes
//      two different numeric passwords for the same
//    reset-captcha-language — the reset request page's
//      reCAPTCHA ignores the site language
//
//  Used by:
//    - PHPUnit — the Feature suite (runTests.sh)
// -----------------------------------------------------------


namespace Tests\Feature\Auth;

use App\Mail\SendRemindLink;
use App\Models\Members;
use Illuminate\Support\Facades\Mail;
use Tests\FeatureTestCase;
use Tests\Support\World;








// -----------------------------------------------------------
// AuthDefectsTest
// -----------------------------------------------------------
//
// One pinned defect per test, named in the header; each
// test builds its own account.
//
// Used by:
//   - PHPUnit — the Feature suite (runTests.sh)
// -----------------------------------------------------------

class AuthDefectsTest extends FeatureTestCase
{






    // -----------------------------------------------------------
    // testResetLinkGoesOnlyToTheAccountsOwnAddress
    // -----------------------------------------------------------
    //
    // The forgotten-password form finds the account through
    // bei_users.email, whose collation, utf8mb4_general_ci,
    // ignores case and accents, and then mails the reset link
    // to the address as typed instead of the one the account
    // holds. An address that differs from a member's only by
    // an accent in the domain — a look-alike domain anyone can
    // register, such as gmail.com with an umlaut on the a —
    // matches the member's account, and the link goes to the
    // look-alike mailbox; Symfony Mailer writes such a domain
    // in punycode, so the mail is delivered. The link's button
    // then replaces the member's password with a random one,
    // and switches on an account still waiting for its
    // verification. Wanted: the link goes to the address the
    // account holds, and to no other.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testResetLinkGoesOnlyToTheAccountsOwnAddress(): void
    {
        $member = $this->world->member();
        $lookAlike = str_replace('@example.test', '@exämple.test', $member->email);
        $this->solveCaptcha();

        $this->from('/forgot-password')->post('/forgot-password', [
            'email'                => $lookAlike,
            'g-recaptcha-response' => 'token-from-the-widget',
        ])->assertSessionHasNoErrors();

        $this->pinDefect('reset-link-typed-address', function () use ($lookAlike) {
            Mail::assertNotSent(SendRemindLink::class, function (SendRemindLink $mail) use ($lookAlike) {
                return $mail->hasTo($lookAlike);
            });
        });
    }






    // -----------------------------------------------------------
    // testLockedAccountCannotBeReachedByAnEquivalentSpelling
    // -----------------------------------------------------------
    //
    // The login lockout keys on Str::transliterate(Str::lower
    // (email)).'|'.ip (ThrottlesLogins::throttleKey), while the
    // guard matches the account through bei_users.email, which
    // is utf8mb4_general_ci. The two disagree on the German ß:
    // the collation folds it to a single s, so "victim@example
    // .teßt" is the same account as "victim@example.test", but
    // transliterate expands ß to ss, so the two spellings count
    // their failures under different keys. After ten wrong
    // passwords lock the account under its registered spelling,
    // the very same account opens to the correct password typed
    // with the ß spelling — the lockout guards one spelling of
    // an address, not the account behind it, so the ten-guess
    // limit is multiplied by the equivalent spellings of the
    // address. Wanted: once an account is locked, it stays
    // locked whichever equivalent spelling of its address is
    // used.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testLockedAccountCannotBeReachedByAnEquivalentSpelling(): void
    {
        $member = $this->world->member(['email' => 'throttle.victim@example.test']);
        for ($guess = 1; $guess <= 10; $guess++) {
            $this->from('/login')->post('/login', ['email' => $member->email, 'password' => 'Wrong-Guess-Nr-' . $guess]);
        }

        $this->from('/login')->post('/login', ['email' => 'throttle.victim@example.teßt', 'password' => World::PASSWORD]);

        $this->pinDefect('login-throttle-collation-bypass', function () {
            $this->assertGuest('mem');
        });
    }






    // -----------------------------------------------------------
    // testSignUpRejectsPasswordsThatOnlyLookEqual
    // -----------------------------------------------------------
    //
    // RegisterController::register compares the password and its
    // repetition with !=, PHP's loose operator, which compares
    // two numeric strings as numbers. Two different passwords
    // that read as the same number — "0e11111111111" and
    // "0e22222222222", both numeric zero, or a pair differing
    // only by a leading zero — pass the != check as a match, so
    // the account is created and its hash is taken from the
    // first field while the repetition, which the member sees
    // as confirming a different password, is dropped. The
    // password-change form already compares with strcmp; the
    // sign-up does not. Wanted: a sign-up whose two password
    // fields are not the same string is refused and no account
    // is created, as the change-password form refuses it.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testSignUpRejectsPasswordsThatOnlyLookEqual(): void
    {
        $this->solveCaptcha();
        $email = 'loose.compare@example.test';

        $this->from('/register')->post('/register', [
            'email'                => $email,
            'password'             => '0e11111111111',
            'password2'            => '0e22222222222',
            'country'              => 1,
            'terms'                => 1,
            'g-recaptcha-response' => 'token-from-the-widget',
        ]);

        $this->pinDefect('register-password-loose-compare', function () use ($email) {
            $this->assertFalse(
                Members::where('email', $email)->exists(),
                'an account was created from two different password fields'
            );
        });
    }






    // -----------------------------------------------------------
    // testResetRequestCaptchaSpeaksTheSitesLanguage
    // -----------------------------------------------------------
    //
    // The sign-up page loads Google's reCAPTCHA script in the
    // site's language, by its ISO code (layouts/register), but
    // the reset request page, the other form with a reCAPTCHA,
    // extends layouts/login, which loads the script with no
    // language: there the widget speaks whatever the browser
    // is set to, in the middle of a Lithuanian, Latvian,
    // Estonian or Greek page. Wanted: the reset request page's
    // reCAPTCHA in the site's language, as on the sign-up
    // page — el for Greek and et for Estonian.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testResetRequestCaptchaSpeaksTheSitesLanguage(): void
    {
        $pages = [];
        foreach (['lt', 'lv', 'ee', 'gr'] as $locale) {
            $pages[$locale] = $this->withSession(['lang' => $locale])->get('/forgot-password')->assertOk()->getContent();
            $this->assertStringContainsString('recaptcha/api.js', $pages[$locale], "the $locale reset request page loads no reCAPTCHA");
        }

        $this->pinDefect('reset-captcha-language', function () use ($pages) {
            foreach (['lt' => 'lt', 'lv' => 'lv', 'ee' => 'et', 'gr' => 'el'] as $locale => $iso) {
                $this->assertStringContainsString('hl=' . $iso, $pages[$locale], "the reset request page of a $locale session asks Google for no language");
            }
        });
    }
}
