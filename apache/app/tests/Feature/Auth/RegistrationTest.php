<?php

// -----------------------------------------------------------
//  [*] RegistrationTest — signing up for an account
//
//  POST /register (RegisterController::register): a valid
//  form creates an unverified member — status 0, a
//  generated User<number> name, a 50-character verify token
//  — and mails the verification link; a mismatched
//  password, an email already in use, an incomplete form —
//  a country outside the form's 0 to 5 among them — or an
//  unsolved reCAPTCHA creates nothing and mails nothing.
//  Both password fields keep the blanks at their ends, and a
//  form sent back keeps neither in the session. The
//  verification link is built from APP_URL, so a forged
//  Host or X-Forwarded-Host header cannot point it anywhere
//  else. The form is for visitors only: a signed-in member
//  is sent to the home page. Both reCAPTCHA errors read in
//  words, and the generated name costs one random draw.
//
//  Used by:
//    - PHPUnit — the Feature suite (runTests.sh)
// -----------------------------------------------------------


namespace Tests\Feature\Auth;

use App\Http\Controllers\Auth\RegisterController;
use App\Mail\SendVerification;
use App\Models\Members;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\FeatureTestCase;








// -----------------------------------------------------------
// RegistrationTest
// -----------------------------------------------------------
//
// Every test signs up from the registration page, so the
// controller's back() redirects return there.
//
// Used by:
//   - PHPUnit — the Feature suite (runTests.sh)
// -----------------------------------------------------------

class RegistrationTest extends FeatureTestCase
{

    // The account the tests sign up for
    private const EMAIL = 'new.student@example.test';
    private const PASSWORD = 'Long-Enough-Password-1';






    // -----------------------------------------------------------
    // testRegistrationFormOpens
    // -----------------------------------------------------------
    //
    // The form asks for the email, the password twice and the
    // country.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testRegistrationFormOpens(): void
    {
        $this->get('/register')
            ->assertOk()
            ->assertSee('name="email"', false)
            ->assertSee('name="password2"', false)
            ->assertSee('name="country"', false);
    }






    // -----------------------------------------------------------
    // testSignUpCreatesAnUnverifiedMember
    // -----------------------------------------------------------
    //
    // The new row: inactive until verified, a generated user
    // name, the country chosen, a verify token, and the
    // password stored as a hash that checks against it.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testSignUpCreatesAnUnverifiedMember(): void
    {
        $this->solveCaptcha();
        $this->from('/register')->post('/register', $this->form())
            ->assertRedirect('/register')
            ->assertSessionHas('success', __('main.user_registered'));

        $member = Members::where('email', self::EMAIL)->first();
        $this->assertNotNull($member);
        $this->assertSame(0, $member->status);
        $this->assertSame(1, $member->country);
        $this->assertMatchesRegularExpression('/^User\d+$/', $member->username);
        $this->assertSame(50, strlen($member->verify_token));
        $this->assertTrue(Hash::check(self::PASSWORD, $member->password));
    }






    // -----------------------------------------------------------
    // testSignUpMailsTheVerificationLink
    // -----------------------------------------------------------
    //
    // One SendVerification mail goes to the address signed up
    // with, carrying the member's verify token, and its link
    // opens /verify/<token> on the site.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testSignUpMailsTheVerificationLink(): void
    {
        $this->solveCaptcha();
        $this->from('/register')->post('/register', $this->form());

        $token = Members::where('email', self::EMAIL)->value('verify_token');
        Mail::assertSent(SendVerification::class, 1);
        Mail::assertSent(SendVerification::class, function (SendVerification $mail) use ($token) {
            return $mail->hasTo(self::EMAIL)
                && $mail->data['uid'] === $token
                && $this->mailLink($mail) === 'https://cyberphish.knf.vu.lt/verify/' . $token;
        });
    }






    // -----------------------------------------------------------
    // testVerificationLinkIgnoresTheRequestHost
    // -----------------------------------------------------------
    //
    // TrustProxies trusts every proxy, so a sign-up can claim
    // any host through X-Forwarded-Host; the link in the mail
    // still points at APP_URL, never at the claimed host.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testVerificationLinkIgnoresTheRequestHost(): void
    {
        $this->solveCaptcha();
        $this->from('/register')
            ->withHeaders(['Host' => 'evil.example', 'X-Forwarded-Host' => 'evil.example'])
            ->post('/register', $this->form());

        Mail::assertSent(SendVerification::class, function (SendVerification $mail) {
            return strpos($this->mailLink($mail), 'https://cyberphish.knf.vu.lt/verify/') === 0;
        });
    }






    // -----------------------------------------------------------
    // testPasswordsMustMatch
    // -----------------------------------------------------------
    //
    // A repeated password that differs sends the visitor back
    // with the mismatch message; no account, no mail.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testPasswordsMustMatch(): void
    {
        $this->solveCaptcha();
        $this->from('/register')->post('/register', $this->form(['password2' => 'Another-Long-Password-2']))
            ->assertRedirect('/register')
            ->assertSessionHasErrors();

        $this->assertSame(__('main.pass_dont_match'), session('errors')->first());
        $this->assertFalse(Members::where('email', self::EMAIL)->exists());
        Mail::assertNothingSent();
    }






    // -----------------------------------------------------------
    // testEmailInUseIsRefused
    // -----------------------------------------------------------
    //
    // A second account for an address already registered is
    // refused, and the existing account is left as it was.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testEmailInUseIsRefused(): void
    {
        $existing = $this->world->member(['email' => self::EMAIL]);
        $this->solveCaptcha();
        $this->from('/register')->post('/register', $this->form())->assertSessionHasErrors();

        $this->assertSame(__('main.email_already_exists'), session('errors')->first());
        $this->assertSame(1, Members::where('email', self::EMAIL)->count());
        $this->assertSame($existing->password, Members::find($existing->id)->password);
        Mail::assertNothingSent();
    }






    // -----------------------------------------------------------
    // testEmailInUseIsFoundWhateverItsCase
    // -----------------------------------------------------------
    //
    // The email column compares without case (its
    // case-insensitive collation), so the same address in
    // capitals cannot open a second account.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testEmailInUseIsFoundWhateverItsCase(): void
    {
        $this->world->member(['email' => self::EMAIL]);
        $this->solveCaptcha();
        $this->from('/register')->post('/register', $this->form(['email' => strtoupper(self::EMAIL)]))
            ->assertSessionHasErrors();

        $this->assertSame(__('main.email_already_exists'), session('errors')->first());
        Mail::assertNothingSent();
    }






    // -----------------------------------------------------------
    // testIncompleteFormIsRefused
    // -----------------------------------------------------------
    //
    // Each broken field fails validation on that field, and
    // nothing is created or mailed.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    #[DataProvider('incompleteForms')]
    public function testIncompleteFormIsRefused(array $fields, string $field): void
    {
        $this->solveCaptcha();
        $this->from('/register')->post('/register', $this->form($fields))
            ->assertRedirect('/register')
            ->assertSessionHasErrors($field);

        $this->assertFalse(Members::where('email', $this->form($fields)['email'])->exists());
        Mail::assertNothingSent();
    }






    // -----------------------------------------------------------
    // incompleteForms
    // -----------------------------------------------------------
    //
    // The fields to break and the field the error lands on:
    // the email, the password (12 to 50 characters), its
    // repetition, the country (one of the form's ids, 0 to 5)
    // and the reCAPTCHA token.
    //
    // Used by:
    //   - testIncompleteFormIsRefused (above)
    // -----------------------------------------------------------

    public static function incompleteForms(): array
    {
        return [
            'no email'             => [['email' => ''], 'email'],
            'not an email'         => [['email' => 'not-an-email'], 'email'],
            'password too short'   => [['password' => 'Short-pass1', 'password2' => 'Short-pass1'], 'password'],
            'password too long'    => [['password' => str_repeat('a', 51), 'password2' => str_repeat('a', 51)], 'password'],
            'no repeated password' => [['password2' => ''], 'password2'],
            'country not a number' => [['country' => 'Lithuania'], 'country'],
            'country out of range' => [['country' => 9], 'country'],
            'no reCAPTCHA token'   => [['g-recaptcha-response' => ''], 'g-recaptcha-response'],
        ];
    }






    // -----------------------------------------------------------
    // testUnsolvedCaptchaIsRefused
    // -----------------------------------------------------------
    //
    // Google answering that the reCAPTCHA was not solved
    // stops the sign-up with an error on that field.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testUnsolvedCaptchaIsRefused(): void
    {
        $this->solveCaptcha(false);
        $this->from('/register')->post('/register', $this->form())
            ->assertSessionHasErrors('g-recaptcha-response');

        $this->assertFalse(Members::where('email', self::EMAIL)->exists());
        Mail::assertNothingSent();
    }






    // -----------------------------------------------------------
    // testPasswordWithBlankEdgesRegisters
    // -----------------------------------------------------------
    //
    // Neither password field is trimmed, so a password that
    // starts and ends with a blank matches its repeat, and the
    // hash stored checks against it as typed — the way the
    // login, which never trims a password, compares it.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testPasswordWithBlankEdgesRegisters(): void
    {
        $this->solveCaptcha();
        $this->from('/register')->post('/register', $this->form(['password' => ' Blank-Edged-Pass-9 ', 'password2' => ' Blank-Edged-Pass-9 ']))
            ->assertSessionHas('success', __('main.user_registered'));

        $this->assertTrue(Hash::check(' Blank-Edged-Pass-9 ', Members::where('email', self::EMAIL)->value('password')));
    }






    // -----------------------------------------------------------
    // testFailedSignUpKeepsNoPasswordInTheSession
    // -----------------------------------------------------------
    //
    // A form its validation sends back flashes its input into
    // the session for the next page, minus every password
    // field — the repeat as well as the password.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testFailedSignUpKeepsNoPasswordInTheSession(): void
    {
        $this->solveCaptcha();
        $this->from('/register')->post('/register', $this->form(['email' => 'not-an-address']))
            ->assertSessionHasErrors('email');

        $old = session()->getOldInput();
        $this->assertSame('not-an-address', $old['email']);
        $this->assertArrayNotHasKey('password', $old);
        $this->assertArrayNotHasKey('password2', $old);
    }






    // -----------------------------------------------------------
    // testSignedInMemberIsSentAwayFromTheRegistrationForm
    // -----------------------------------------------------------
    //
    // The form is for visitors only (guest:mem): a signed-in
    // member who opens it or posts it is sent to the home
    // page, and no second account is created.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testSignedInMemberIsSentAwayFromTheRegistrationForm(): void
    {
        $this->signInMember($this->world->member());
        $this->get('/register')->assertRedirect('/');

        // The mock comes after the page: were the form shown,
        // it would ask the captcha service for its widget,
        // which the mock cannot answer
        $this->solveCaptcha();
        $this->from('/register')->post('/register', $this->form())->assertRedirect('/');

        $this->assertFalse(Members::where('email', self::EMAIL)->exists());
        Mail::assertNothingSent();
    }






    // -----------------------------------------------------------
    // testCaptchaErrorsReadInWords
    // -----------------------------------------------------------
    //
    // A failed reCAPTCHA and an unticked one both come back in
    // words, in every site language and on both forms that
    // check one — this one and forgot-password: no raw
    // validation.captcha key and no raw g-recaptcha-response
    // field name.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testCaptchaErrorsReadInWords(): void
    {
        $this->solveCaptcha(false);
        $forms = [
            '/register'        => $this->form(['g-recaptcha-response' => null]),
            '/forgot-password' => ['email' => self::EMAIL],
        ];

        // Both forms speak the session's language, which
        // BaseController reads on every request
        foreach (['en', 'lt', 'ee', 'lv', 'gr'] as $locale) {
            foreach ($forms as $form => $fields) {
                foreach (['a failed check' => 'expired-token', 'an unticked box' => ''] as $answer => $token) {
                    $this->withSession(['lang' => $locale])->from($form)
                        ->post($form, array_merge($fields, ['g-recaptcha-response' => $token]))
                        ->assertSessionHasErrors('g-recaptcha-response');
                    $error = session('errors')->first('g-recaptcha-response');
                    $this->assertStringStartsNotWith('validation.', $error, "$form in $locale after $answer says $error");
                    $this->assertStringNotContainsString('g-recaptcha-response', $error, "$form in $locale after $answer says $error");
                }
            }
        }
    }






    // -----------------------------------------------------------
    // testCaptchaSpeaksGreekAndEstonian
    // -----------------------------------------------------------
    //
    // The sign-up page asks Google for the reCAPTCHA in the
    // site's language by its ISO code: Greek is el and
    // Estonian et, not the site's own gr and ee, which Google
    // does not know.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testCaptchaSpeaksGreekAndEstonian(): void
    {
        $this->withSession(['lang' => 'gr'])->get('/register')->assertSee('hl=el', false)->assertDontSee('hl=gr', false);
        $this->withSession(['lang' => 'ee'])->get('/register')->assertSee('hl=et', false)->assertDontSee('hl=ee', false);
    }






    // -----------------------------------------------------------
    // testGreekSignUpButtonSaysRegister
    // -----------------------------------------------------------
    //
    // The Greek sign-up button reads "Εγγραφείτε", as the
    // other languages' read "Register" — a second register key
    // further down the Greek file once replaced it with "Κανω
    // ΕΓΓΡΑΦΗ".
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testGreekSignUpButtonSaysRegister(): void
    {
        $this->withSession(['lang' => 'gr'])->get('/register')
            ->assertSee('Εγγραφείτε')
            ->assertDontSee('Κανω ΕΓΓΡΑΦΗ');
    }






    // -----------------------------------------------------------
    // testUserNameIsDrawnWithoutAMillionNumbers
    // -----------------------------------------------------------
    //
    // Drawing the generated user name costs one random draw,
    // not the range of nearly a million numbers it comes from:
    // the second draw — the first loads the classes —
    // needs less than a megabyte.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testUserNameIsDrawnWithoutAMillionNumbers(): void
    {
        RegisterController::getUsername();
        memory_reset_peak_usage();
        $before = memory_get_usage();
        $name = RegisterController::getUsername();
        $used = memory_get_peak_usage() - $before;

        $this->assertMatchesRegularExpression('/^User\d+$/', $name);
        $this->assertLessThan(1048576, $used, "a draw took $used bytes");
    }






    // -----------------------------------------------------------
    // form
    // -----------------------------------------------------------
    //
    // A complete, valid registration form, with the given
    // fields replaced.
    //
    // Used by:
    //   - every test of this class that posts the form
    // -----------------------------------------------------------

    private function form(array $fields = []): array
    {
        return array_merge([
            'email'                => self::EMAIL,
            'password'             => self::PASSWORD,
            'password2'            => self::PASSWORD,
            'country'              => 1,
            'g-recaptcha-response' => 'token-from-the-widget',
        ], $fields);
    }
}
