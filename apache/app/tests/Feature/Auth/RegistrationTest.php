<?php

// -----------------------------------------------------------
//  [*] RegistrationTest — signing up for an account
//
//  POST /register (RegisterController::register): a valid
//  form creates an unverified member — status 0, a generated
//  User<number> name, a 50-character verify token — and
//  mails the verification link; a mismatched password, an
//  incomplete form — a country outside the form's 0 to 5
//  among them — or an unsolved reCAPTCHA creates nothing and
//  mails nothing. An email already in use gets the answer a
//  new one gets, and its owner a mail of its own. Both
//  password fields keep the blanks at their ends, and a form
//  sent back keeps neither in the session. The verification
//  link is built from APP_URL, so a forged Host or
//  X-Forwarded-Host header cannot point it anywhere else.
//  The form is for visitors only: a signed-in member is sent
//  to the home page. Both reCAPTCHA errors read in words,
//  and the generated name costs one random draw. A mail
//  outage creates nothing and says so. A refused form comes
//  back with its email, country and terms box.
//
//  Used by:
//    - PHPUnit — the Feature suite (runTests.sh)
// -----------------------------------------------------------


namespace Tests\Feature\Auth;

use Anhskohbo\NoCaptcha\Facades\NoCaptcha;
use App\Http\Controllers\Auth\RegisterController;
use App\Mail\SendAccountDeactivated;
use App\Mail\SendAccountExists;
use App\Mail\SendActivationLink;
use App\Mail\SendNewPass;
use App\Mail\SendVerification;
use Illuminate\Support\Facades\App;
use App\Models\Members;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Mailer\Exception\TransportException;
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
    // testAddressInUseIsAnsweredAsANewOne
    // -----------------------------------------------------------
    //
    // A sign-up of an address that already has an account gets
    // the very answer a new address gets — the redirect back
    // with user_registered, no error, no form kept — so the
    // form tells no one which addresses have accounts; it once
    // answered email_already_exists. The account stays as it
    // was, its password included, and its owner hears of the
    // sign-up by mail — one mail for every account, as for a
    // new address, so that no address answers sooner: an
    // active account gets SendAccountExists, which points to
    // the login form; one still waiting for its verification
    // gets SendActivationLink, and its earlier verification
    // link is cancelled
    // (testPendingAccountSignedUpAgainOpensOnlyToItsMailbox);
    // one an admin switched off gets SendAccountDeactivated,
    // where it once got nothing and answered sooner. Passwords
    // that differ are refused first, for any address.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testAddressInUseIsAnsweredAsANewOne(): void
    {
        $active = $this->world->member(['email' => 'active.student@example.test']);
        $waiting = $this->world->member(['email' => 'waiting.student@example.test', 'status' => 0, 'verify_token' => str_repeat('w', 50)]);
        $off = $this->world->member(['email' => 'off.student@example.test', 'status' => 2]);
        $this->solveCaptcha();

        foreach ([self::EMAIL, $active->email, $waiting->email, $off->email] as $email) {
            $this->from('/register')->post('/register', $this->form(['email' => $email]))
                ->assertRedirect('/register')
                ->assertSessionHasNoErrors()
                ->assertSessionHas('success', __('main.user_registered'));

            $this->assertSame([], session()->getOldInput(), $email);
            $this->assertSame(1, Members::where('email', $email)->count(), $email);
        }

        foreach ([$active, $waiting, $off] as $member) {
            $this->assertEquals([$member->password, $member->status], [Members::find($member->id)->password, Members::find($member->id)->status]);
        }
        $this->assertSame([$active->verify_token, $off->verify_token], [Members::find($active->id)->verify_token, Members::find($off->id)->verify_token]);
        $reset = Members::find($waiting->id)->reset_token;
        $this->assertSame(50, strlen($reset));
        $this->assertSame('', Members::find($waiting->id)->verify_token);
        Mail::assertSent(SendAccountExists::class, 1);
        Mail::assertSent(SendAccountExists::class, function (SendAccountExists $mail) use ($active) {
            return $mail->hasTo($active->email) && $this->mailLink($mail) === 'https://cyberphish.knf.vu.lt/login';
        });
        Mail::assertSent(SendActivationLink::class, 1);
        Mail::assertSent(SendActivationLink::class, function (SendActivationLink $mail) use ($waiting, $reset) {
            return $mail->hasTo($waiting->email) && $this->mailLink($mail) === "https://cyberphish.knf.vu.lt/reset/$reset";
        });
        Mail::assertSent(SendAccountDeactivated::class, 1);
        Mail::assertSent(SendAccountDeactivated::class, function (SendAccountDeactivated $mail) use ($off) {
            return $mail->hasTo($off->email);
        });
        Mail::assertSent(SendVerification::class, 1);
        Mail::assertSent(SendVerification::class, function (SendVerification $mail) {
            return $mail->hasTo(self::EMAIL);
        });

        $this->from('/register')->post('/register', $this->form(['email' => $active->email, 'password2' => 'Another-Long-Password-2']))
            ->assertRedirect('/register')
            ->assertSessionHasErrors();

        $this->assertSame(__('main.pass_dont_match'), session('errors')->first());
        Mail::assertSent(SendAccountExists::class, 1);
    }






    // -----------------------------------------------------------
    // testAddressInUseIsFoundWhateverItsCase
    // -----------------------------------------------------------
    //
    // The email column compares without case (its
    // case-insensitive collation), so the same address in
    // capitals opens no second account: it is answered as the
    // address in use, and the mail goes to the address the
    // account holds.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testAddressInUseIsFoundWhateverItsCase(): void
    {
        $this->world->member(['email' => self::EMAIL]);
        $this->solveCaptcha();

        $this->from('/register')->post('/register', $this->form(['email' => strtoupper(self::EMAIL)]))
            ->assertSessionHas('success', __('main.user_registered'));

        $this->assertSame(1, Members::where('email', self::EMAIL)->count());
        Mail::assertSent(SendAccountExists::class, function (SendAccountExists $mail) {
            return $mail->hasTo(self::EMAIL);
        });
        Mail::assertNotSent(SendVerification::class);
    }






    // -----------------------------------------------------------
    // testSignUpThatLosesARaceAnswersAsAnAddressInUse
    // -----------------------------------------------------------
    //
    // Two sign-ups of one new address that arrive together both
    // find it free, and the one that writes its row second
    // meets the email's unique key. That one gives the answer
    // every sign-up gets and mails nothing — the winner's mail
    // carries the account's link — and leaves the account as
    // the winner wrote it. It was a 500, after a mail whose
    // link no account held. Here a rival row is written the
    // moment this sign-up's row is about to be.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testSignUpThatLosesARaceAnswersAsAnAddressInUse(): void
    {
        $this->solveCaptcha();
        $rival = str_repeat('r', 50);
        Members::creating(function () use ($rival) {
            if (!Members::where('email', self::EMAIL)->exists()) {
                $this->world->member(['email' => self::EMAIL, 'status' => 0, 'verify_token' => $rival]);
            }
        });

        $this->from('/register')->post('/register', $this->form())
            ->assertRedirect('/register')
            ->assertSessionHasNoErrors()
            ->assertSessionHas('success', __('main.user_registered'));

        $this->assertSame(1, Members::where('email', self::EMAIL)->count());
        $this->assertSame([$rival, ''], [Members::where('email', self::EMAIL)->value('verify_token'), Members::where('email', self::EMAIL)->value('reset_token')]);
        Mail::assertNothingSent();
    }






    // -----------------------------------------------------------
    // testPendingAccountSignedUpAgainOpensOnlyToItsMailbox
    // -----------------------------------------------------------
    //
    // Once its address is signed up again, an account waiting
    // for its verification opens only with a password its
    // mailbox receives: the second sign-up mails an activation
    // link — the reset page, which mails a fresh password and
    // switches the account on — and cancels the first sign-up's
    // verification link, so whoever signed the address up
    // first, with a password of their own, is not let in by
    // the owner's clicks. That second sign-up once mailed the
    // first link again, and the owner's click switched the
    // account on with the first sign-up's password. The first
    // link clicked before any second sign-up still does, as it
    // always did (tests/README.md, Found but not pinned).
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testPendingAccountSignedUpAgainOpensOnlyToItsMailbox(): void
    {
        $this->solveCaptcha();
        $this->from('/register')->post('/register', $this->form(['password' => 'Squatter-Password-1', 'password2' => 'Squatter-Password-1']));
        $first = Members::where('email', self::EMAIL)->value('verify_token');

        $this->from('/register')->post('/register', $this->form(['password' => 'Owner-Password-12', 'password2' => 'Owner-Password-12']))
            ->assertSessionHas('success', __('main.user_registered'));
        $reset = Members::where('email', self::EMAIL)->value('reset_token');
        Mail::assertSent(SendActivationLink::class, function (SendActivationLink $mail) use ($reset) {
            return $mail->hasTo(self::EMAIL) && $mail->data['uid'] === $reset;
        });

        $this->get("/verify/$first");
        $this->assertEquals(0, Members::where('email', self::EMAIL)->value('status'), 'the first sign-up\'s link switched the account on');

        $this->post("/reset/$reset")->assertSessionHas('success', __('main.new_pass_emailed'));
        $mailed = null;
        Mail::assertSent(SendNewPass::class, function (SendNewPass $mail) use (&$mailed) {
            $mailed = $mail->data['new_pass'];
            return $mail->hasTo(self::EMAIL);
        });

        $member = Members::where('email', self::EMAIL)->first();
        $this->assertEquals(1, $member->status);
        $this->assertTrue(Hash::check($mailed, $member->password));
        $this->assertFalse(Hash::check('Squatter-Password-1', $member->password));
    }






    // -----------------------------------------------------------
    // testAddressInUseCostsTheHashOfANewOne
    // -----------------------------------------------------------
    //
    // A sign-up hashes its password whatever the address, so
    // that one for an address in use takes as long as one for
    // a new address, which stores the hash: bcrypt's tenth of
    // a second would otherwise tell the two apart.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testAddressInUseCostsTheHashOfANewOne(): void
    {
        $this->world->member(['email' => self::EMAIL]);
        $this->solveCaptcha();
        Hash::spy();

        $this->from('/register')->post('/register', $this->form())->assertSessionHas('success', __('main.user_registered'));

        Hash::shouldHaveReceived('make')->once()->with(self::PASSWORD);
    }






    // -----------------------------------------------------------
    // testMailsToAnAddressInUseSayWhatToDo
    // -----------------------------------------------------------
    //
    // The two mails an address in use can get besides
    // SendAccountExists say what happened in the owner's own
    // words, in each of the five languages: SendActivationLink
    // that the account was never activated and how its link —
    // the reset page — mails a password to sign in with, where
    // a plain password-reset mail once arrived instead;
    // SendAccountDeactivated that the account is switched off.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testMailsToAnAddressInUseSayWhatToDo(): void
    {
        foreach (['en', 'lt', 'lv', 'ee', 'gr'] as $locale) {
            App::setLocale($locale);
            foreach (['account_pending_subject', 'account_pending_mail', 'account_off_subject', 'account_off_mail'] as $key) {
                $this->assertNotSame("main.$key", __("main.$key"), "$locale lacks $key");
            }

            $activation = new SendActivationLink(['uid' => str_repeat('a', 50)]);
            $this->assertSame('https://cyberphish.knf.vu.lt/reset/' . str_repeat('a', 50), $this->mailLink($activation));
            $this->assertSame(__('main.account_pending_subject'), $activation->subject);
            $this->assertStringContainsString(e(__('main.account_pending_mail')), view($activation->view, $activation->viewData)->render());

            $deactivated = new SendAccountDeactivated();
            $deactivated->build();
            $this->assertSame(__('main.account_off_subject'), $deactivated->subject);
            $this->assertStringContainsString(e(__('main.account_off_mail')), view($deactivated->view, $deactivated->viewData)->render());
        }
        App::setLocale('en');
    }






    // -----------------------------------------------------------
    // testMailOutageAnswersAlikeForEveryAddress
    // -----------------------------------------------------------
    //
    // While no mail can go out, a sign-up says so
    // (mail_failed) for every address — new, active, waiting
    // for its verification or switched off — and changes
    // nothing: an account an admin switched off once answered
    // user_registered there, as it sent no mail, and so stood
    // out from the rest.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testMailOutageAnswersAlikeForEveryAddress(): void
    {
        $active = $this->world->member(['email' => 'active.student@example.test']);
        $waiting = $this->world->member(['email' => 'waiting.student@example.test', 'status' => 0, 'verify_token' => str_repeat('w', 50)]);
        $off = $this->world->member(['email' => 'off.student@example.test', 'status' => 2]);
        Mail::shouldReceive('to')->andThrow(new TransportException('Connection to the mail relay refused'));
        $this->solveCaptcha();

        foreach ([self::EMAIL, $active->email, $waiting->email, $off->email] as $email) {
            $this->from('/register')->post('/register', $this->form(['email' => $email]))
                ->assertRedirect('/register')
                ->assertSessionHasErrors();

            $this->assertSame(__('main.mail_failed'), session('errors')->first(), $email);
        }

        $this->assertFalse(Members::where('email', self::EMAIL)->exists());
        $this->assertSame([str_repeat('w', 50), ''], [Members::find($waiting->id)->verify_token, Members::find($waiting->id)->reset_token]);
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
    // repetition, the country (one of the form's ids, 0 to 5),
    // the reCAPTCHA token and the privacy policy's box, which
    // the server once let a post leave unticked
    // (register-terms-not-enforced). Either password posted as
    // a list is refused too, and so is a password with a NUL
    // byte, which bcrypt refuses: the rule's min once counted
    // the list's items, and the sign-up mailed its link before
    // Hash::make failed on the list or the byte with a 500
    // (login-password-list).
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
            'terms not ticked'     => [['terms' => ''], 'terms'],
            'password as a list'   => [['password' => array_fill(0, 12, 'x'), 'password2' => array_fill(0, 12, 'x')], 'password'],
            'repetition as a list' => [['password2' => array_fill(0, 12, 'x')], 'password2'],
            'password with a NUL'  => [['password' => "Long-Enough\0Password-1", 'password2' => "Long-Enough\0Password-1"], 'password'],
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
    // testMailOutageCreatesNoAccount
    // -----------------------------------------------------------
    //
    // While the mail relay cannot be reached, a sign-up goes
    // back to the form saying the mail could not be sent, and
    // creates no account, so the address can sign up once mail
    // goes out again. It once answered with a 500.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testMailOutageCreatesNoAccount(): void
    {
        Mail::shouldReceive('to')->andThrow(new TransportException('Connection to the mail relay refused'));
        $this->solveCaptcha();

        $this->from('/register')->post('/register', $this->form())
            ->assertRedirect('/register')
            ->assertSessionHasErrors();

        $this->assertSame(__('main.mail_failed'), session('errors')->first());
        $this->assertFalse(Members::where('email', self::EMAIL)->exists());
    }






    // -----------------------------------------------------------
    // testRefusedSignUpKeepsTheForm
    // -----------------------------------------------------------
    //
    // A sign-up refused for passwords that differ, or because
    // its mail could not go out, comes back with the email,
    // the country and the terms box as they were sent — as a
    // refused validation does — and without the passwords. It
    // once came back empty, the address and the country to be
    // typed again. An address in use is no longer refused
    // (testAddressInUseIsAnsweredAsANewOne).
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testRefusedSignUpKeepsTheForm(): void
    {
        Mail::shouldReceive('to')->andThrow(new TransportException('Connection to the mail relay refused'));
        $this->solveCaptcha();
        $refusals = [
            'passwords that differ'  => ['email' => 'kept.student@example.test', 'password2' => 'Another-Long-Password-2'],
            'a mail outage'          => ['email' => 'outage.student@example.test'],
        ];

        foreach ($refusals as $case => $fields) {
            $this->from('/register')->post('/register', $this->form($fields + ['country' => 3, 'terms' => 1]))
                ->assertRedirect('/register')
                ->assertSessionHasErrors();

            $old = session()->getOldInput();
            $this->assertSame([$fields['email'], '3', '1'], [$old['email'] ?? null, (string) ($old['country'] ?? ''), (string) ($old['terms'] ?? '')], $case);
            $this->assertArrayNotHasKey('password', $old, $case);
            $this->assertArrayNotHasKey('password2', $old, $case);
        }

        // The double solveCaptcha put in place draws no widget
        NoCaptcha::shouldReceive('display', 'renderJs')->andReturn('');
        $html = $this->get('/register')->assertOk()->getContent();
        $this->assertStringContainsString('value="outage.student@example.test"', $html);
        $this->assertMatchesRegularExpression('#<option value="3" selected="selected">#', $html);
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
            'terms'                => 1,
            'g-recaptcha-response' => 'token-from-the-widget',
        ], $fields);
    }
}
