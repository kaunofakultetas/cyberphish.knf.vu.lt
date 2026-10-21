<?php

// -----------------------------------------------------------
//  [*] RegistrationTest — signing up for an account
//
//  POST /register (RegisterController::register): a valid
//  form creates an unverified member — status 0, a
//  generated User<number> name, a 50-character verify token
//  — and mails the verification link; a mismatched
//  password, an email already in use, an incomplete form or
//  an unsolved reCAPTCHA creates nothing and mails nothing.
//  The verification link is built from APP_URL, so a forged
//  Host or X-Forwarded-Host header cannot point it anywhere
//  else.
//
//  Used by:
//    - PHPUnit — the Feature suite (runTests.sh)
// -----------------------------------------------------------


namespace Tests\Feature\Auth;

use App\Mail\SendVerification;
use App\Models\Members;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
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
        $this->assertRegExp('/^User\d+$/', $member->username);
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
    // The email column compares without case (latin1's
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

    /** @dataProvider incompleteForms */
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
    // repetition, the country (a number) and the reCAPTCHA
    // token.
    //
    // Used by:
    //   - testIncompleteFormIsRefused (above)
    // -----------------------------------------------------------

    public function incompleteForms(): array
    {
        return [
            'no email'             => [['email' => ''], 'email'],
            'not an email'         => [['email' => 'not-an-email'], 'email'],
            'password too short'   => [['password' => 'Short-pass1', 'password2' => 'Short-pass1'], 'password'],
            'password too long'    => [['password' => str_repeat('a', 51), 'password2' => str_repeat('a', 51)], 'password'],
            'no repeated password' => [['password2' => ''], 'password2'],
            'country not a number' => [['country' => 'Lithuania'], 'country'],
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
