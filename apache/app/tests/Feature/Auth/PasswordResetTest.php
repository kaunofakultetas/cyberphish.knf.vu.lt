<?php

// -----------------------------------------------------------
//  [*] PasswordResetTest — the forgotten-password flow
//
//  POST /forgot-password mails a reset link to a registered
//  address — and answers an unknown address with the very
//  same message, mailing nothing, so the form does not
//  reveal who has an account. GET /reset/{reset_token}
//  opens a page whose button posts the token back; the
//  POST, behind the ValidateReset middleware, mails the
//  member a new random password and uses the token up,
//  while opening the link alone changes nothing. The token
//  waits in a column of its own, reset_token, which a
//  sign-out leaves alone. The link is built from APP_URL
//  whatever host the request claimed, and a blank token is
//  refused, as the verification link refuses one
//  (VerificationTest). The tokens and the new password come
//  from a cryptographic generator, and the message after
//  the link reaches the login form even when a member page
//  parked another destination. A mail outage changes
//  nothing and is no server error: the button says so,
//  forgot-password answers as ever. A reset switches on an
//  account still waiting for its verification, never one an
//  admin switched off.
//
//  Used by:
//    - PHPUnit — the Feature suite (runTests.sh)
// -----------------------------------------------------------


namespace Tests\Feature\Auth;

use App\Http\Controllers\SettingsController;
use App\Mail\SendNewPass;
use App\Mail\SendRemindLink;
use App\Models\Members;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Testing\TestResponse;
use Symfony\Component\Mailer\Exception\TransportException;
use Tests\FeatureTestCase;
use Tests\Support\World;








// -----------------------------------------------------------
// PasswordResetTest
// -----------------------------------------------------------
//
// The reset requests are posted from the forgotten-password
// page; the links are opened as a visitor.
//
// Used by:
//   - PHPUnit — the Feature suite (runTests.sh)
// -----------------------------------------------------------

class PasswordResetTest extends FeatureTestCase
{

    // A reset token of the length LoginController generates
    private const TOKEN = 'Rt5kYp2LmW8qXz3vNb6cHd9fJg1sKa4eUo7iQx0rWt3yEuIoPa';






    // -----------------------------------------------------------
    // testForgotPasswordFormOpens
    // -----------------------------------------------------------
    //
    // The form asks for the email address.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testForgotPasswordFormOpens(): void
    {
        $this->get('/forgot-password')->assertOk()->assertSee('name="email"', false);
    }






    // -----------------------------------------------------------
    // testRegisteredAddressGetsAResetLink
    // -----------------------------------------------------------
    //
    // The member's reset_token becomes a fresh 50-character
    // token and one SendRemindLink mail carries it.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testRegisteredAddressGetsAResetLink(): void
    {
        $member = $this->world->member();
        $this->solveCaptcha();

        $this->requestReset($member->email)->assertSessionHas('success', __('main.forgot_pass_msg'));

        $token = Members::find($member->id)->reset_token;
        $this->assertSame(50, strlen($token));
        Mail::assertSent(SendRemindLink::class, function (SendRemindLink $mail) use ($member, $token) {
            return $mail->hasTo($member->email) && $mail->data['uid'] === $token;
        });
    }






    // -----------------------------------------------------------
    // testUnknownAddressGetsTheSameAnswer
    // -----------------------------------------------------------
    //
    // The same success message, and no mail at all.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testUnknownAddressGetsTheSameAnswer(): void
    {
        $this->solveCaptcha();

        $this->requestReset('nobody@example.test')->assertSessionHas('success', __('main.forgot_pass_msg'));

        Mail::assertNothingSent();
    }






    // -----------------------------------------------------------
    // testResetLinkIgnoresTheRequestHost
    // -----------------------------------------------------------
    //
    // A reset request claiming another host through
    // X-Forwarded-Host — TrustProxies trusts every proxy — still
    // gets a link to APP_URL, so the token cannot be steered to
    // a stranger's server.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testResetLinkIgnoresTheRequestHost(): void
    {
        $member = $this->world->member();
        $this->solveCaptcha();

        $this->withHeaders(['Host' => 'evil.example', 'X-Forwarded-Host' => 'evil.example'])
            ->requestReset($member->email);

        $token = Members::find($member->id)->reset_token;
        Mail::assertSent(SendRemindLink::class, function (SendRemindLink $mail) use ($token) {
            return $this->mailLink($mail) === 'https://cyberphish.knf.vu.lt/reset/' . $token;
        });
    }






    // -----------------------------------------------------------
    // testResetLinkGoesOnlyToTheAccountsOwnAddress
    // -----------------------------------------------------------
    //
    // A reset request for an address that differs from a
    // member's only by an accent in the domain — a look-alike
    // domain anyone can register — finds the member's account,
    // as the email column ignores case and accents, and the
    // link still goes to the address the account holds, and to
    // no other; the account's exact address gets its link as
    // ever. The link once went to the address as typed, and
    // whoever held the look-alike mailbox could reset the
    // member's password (reset-link-typed-address).
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testResetLinkGoesOnlyToTheAccountsOwnAddress(): void
    {
        $member = $this->world->member();
        $lookAlike = str_replace('@example.test', '@exämple.test', $member->email);
        $this->solveCaptcha();

        $this->requestReset($lookAlike)->assertSessionHasNoErrors();
        $this->requestReset($member->email)->assertSessionHasNoErrors();

        Mail::assertNotSent(SendRemindLink::class, function (SendRemindLink $mail) use ($lookAlike) {
            return $mail->hasTo($lookAlike);
        });
        $this->assertCount(2, Mail::sent(SendRemindLink::class, function (SendRemindLink $mail) use ($member) {
            return $mail->hasTo($member->email);
        }), 'both requests mail the link to the address the account holds');
    }






    // -----------------------------------------------------------
    // testUnsolvedCaptchaSendsNothing
    // -----------------------------------------------------------
    //
    // An unsolved reCAPTCHA stops the request before any
    // token is written or mail sent.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testUnsolvedCaptchaSendsNothing(): void
    {
        $member = $this->world->member();
        $this->solveCaptcha(false);

        $this->requestReset($member->email)->assertSessionHasErrors('g-recaptcha-response');

        $this->assertSame('', Members::find($member->id)->reset_token);
        Mail::assertNothingSent();
    }






    // -----------------------------------------------------------
    // testResetRequestCaptchaSpeaksTheSitesLanguage
    // -----------------------------------------------------------
    //
    // The reset request page asks Google for its reCAPTCHA in
    // the site's language, by its ISO code, as the sign-up
    // page does — el for Greek and et for Estonian. Its layout
    // once loaded the script with no language, and the widget
    // spoke whatever the browser was set to, in the middle of
    // a Lithuanian, Latvian, Estonian or Greek page
    // (reset-captcha-language).
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testResetRequestCaptchaSpeaksTheSitesLanguage(): void
    {
        foreach (['lt' => 'lt', 'lv' => 'lv', 'ee' => 'et', 'gr' => 'el'] as $locale => $iso) {
            $page = $this->withSession(['lang' => $locale])->get('/forgot-password')->assertOk()->getContent();
            $this->assertStringContainsString('recaptcha/api.js', $page, "the $locale reset request page loads no reCAPTCHA");
            $this->assertStringContainsString('hl=' . $iso, $page, "the reset request page of a $locale session asks Google for no language");
        }
    }






    // -----------------------------------------------------------
    // testResetLinkMailsANewPassword
    // -----------------------------------------------------------
    //
    // The button of the link's page mails the member a new
    // 12-character password, which is the password from then
    // on; the token is used up and the login form shows the
    // mailed message.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testResetLinkMailsANewPassword(): void
    {
        $member = $this->world->member(['reset_token' => self::TOKEN]);

        $this->post('/reset/' . self::TOKEN)
            ->assertRedirect('/login')
            ->assertSessionHas('success', __('main.new_pass_emailed'));

        $newPassword = null;
        Mail::assertSent(SendNewPass::class, function (SendNewPass $mail) use ($member, &$newPassword) {
            $newPassword = $mail->data['new_pass'];
            return $mail->hasTo($member->email);
        });
        $member = Members::find($member->id);
        $this->assertSame(12, strlen($newPassword));
        $this->assertTrue(Hash::check($newPassword, $member->password));
        $this->assertFalse(Hash::check(World::PASSWORD, $member->password));
        $this->assertSame('', $member->reset_token);
    }






    // -----------------------------------------------------------
    // testResetLinkSignsTheMemberOutEverywhere
    // -----------------------------------------------------------
    //
    // The new password the reset link mails ends every session
    // the member had signed in with the old one: a browser
    // signed in before the link was followed, elsewhere, is
    // sent to the login form at its next page. The browsers
    // are the session the test keeps aside and puts back; that
    // it puts them back signed in shows before the reset. Such
    // a session once went on with the old password.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testResetLinkSignsTheMemberOutEverywhere(): void
    {
        $member = $this->world->member();
        $this->from('/login')->post('/login', ['email' => $member->email, 'password' => World::PASSWORD])->assertRedirect('/cp/dashboard');
        $this->get('/cp/dashboard')->assertOk();
        $browser = session()->all();
        $this->leaveTheBrowser();
        $this->withSession($browser)->get('/cp/dashboard')->assertOk();

        // The link is followed in another browser; signing in
        // empties a pending token, so it comes afterwards
        Members::where('id', $member->id)->update(['reset_token' => self::TOKEN]);
        $this->leaveTheBrowser();
        $this->post('/reset/' . self::TOKEN)->assertRedirect('/login');

        $this->leaveTheBrowser();
        $this->withSession($browser)->get('/cp/dashboard')->assertRedirect('/login');
        $this->assertGuest('mem');
    }






    // -----------------------------------------------------------
    // testOpeningTheResetLinkChangesNothing
    // -----------------------------------------------------------
    //
    // Opening the link only shows its page, whose button posts
    // the token back: the password, the token and the mailbox
    // stay as they were, so a mail scanner that fetches the
    // link before the member does uses nothing up.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testOpeningTheResetLinkChangesNothing(): void
    {
        $member = $this->world->member(['reset_token' => self::TOKEN]);

        $this->get('/reset/' . self::TOKEN)
            ->assertOk()
            ->assertSee('action="https://cyberphish.knf.vu.lt/reset/' . self::TOKEN . '"', false);

        $member = Members::find($member->id);
        $this->assertSame(self::TOKEN, $member->reset_token);
        $this->assertTrue(Hash::check(World::PASSWORD, $member->password));
        Mail::assertNothingSent();
    }






    // -----------------------------------------------------------
    // testResetLinkWorksOnce
    // -----------------------------------------------------------
    //
    // A second press of the page's button for the same token
    // is a 404, and no second password is mailed.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testResetLinkWorksOnce(): void
    {
        $this->world->member(['reset_token' => self::TOKEN]);
        $this->post('/reset/' . self::TOKEN);

        $this->post('/reset/' . self::TOKEN)->assertNotFound();

        Mail::assertSent(SendNewPass::class, 1);
    }






    // -----------------------------------------------------------
    // testSigningOutKeepsAPendingResetLink
    // -----------------------------------------------------------
    //
    // Laravel's session guard replaces a non-empty
    // remember_token when the member signs out, which killed
    // a pending link while the token lived in that column: a
    // member still signed in on another device who signed
    // out there after asking for a link lost it. The member
    // here signs out with both columns filled, and the link's
    // button still works.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testSigningOutKeepsAPendingResetLink(): void
    {
        $member = $this->world->member(['reset_token' => self::TOKEN, 'remember_token' => strrev(self::TOKEN)]);

        $this->signInMember($member)->post('/cp/logout');

        $this->post('/reset/' . self::TOKEN)->assertRedirect('/login');
        Mail::assertSent(SendNewPass::class, 1);
    }






    // -----------------------------------------------------------
    // testUnknownResetLinkIsNotFound
    // -----------------------------------------------------------
    //
    // A token no member holds is a 404.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testUnknownResetLinkIsNotFound(): void
    {
        $this->world->member(['reset_token' => self::TOKEN]);

        $this->get('/reset/' . strrev(self::TOKEN))->assertNotFound();

        Mail::assertNothingSent();
    }






    // -----------------------------------------------------------
    // testBlankResetLinkIsNotFound
    // -----------------------------------------------------------
    //
    // Every member with no reset pending has an empty
    // reset_token, and MySQL ignores trailing spaces when it
    // compares strings, so a token of one space would match
    // them all; ValidateReset refuses an empty token and
    // demands exactly one match, so the link is a 404 and no
    // password changes.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testBlankResetLinkIsNotFound(): void
    {
        $first = $this->world->member();
        $second = $this->world->member();

        $this->get('/reset/%20')->assertNotFound();

        $this->assertTrue(Hash::check(World::PASSWORD, Members::find($first->id)->password));
        $this->assertTrue(Hash::check(World::PASSWORD, Members::find($second->id)->password));
        Mail::assertNothingSent();
    }






    // -----------------------------------------------------------
    // testNewPasswordMessageReachesAVisitorSentFromAMemberPage
    // -----------------------------------------------------------
    //
    // A visitor whom a member page sent to the login form
    // earlier — the page is parked in the session — still
    // lands on the form after the link, with new_pass_emailed.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testNewPasswordMessageReachesAVisitorSentFromAMemberPage(): void
    {
        $this->world->member(['reset_token' => self::TOKEN]);
        $this->get('/cp/dashboard')->assertRedirect('/login');

        $this->post('/reset/' . self::TOKEN)
            ->assertRedirect('/login')
            ->assertSessionHas('success', __('main.new_pass_emailed'));
    }






    // -----------------------------------------------------------
    // testTokensDoNotFollowTheMersenneTwistersSeed
    // -----------------------------------------------------------
    //
    // SettingsController::random_string — the reset and
    // verification tokens, the mailed new passwords, the
    // attempt ids — draws from PHP's cryptographic generator:
    // seeding the Mersenne Twister the same twice does not
    // repeat a token.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testTokensDoNotFollowTheMersenneTwistersSeed(): void
    {
        mt_srand(20261023);
        $first = SettingsController::random_string(50);
        mt_srand(20261023);
        $second = SettingsController::random_string(50);
        // A random seed again for the tests that follow
        mt_srand();

        $this->assertNotSame($first, $second);
        $this->assertMatchesRegularExpression('/^[0-9a-zA-Z]{50}$/', $first);
    }






    // -----------------------------------------------------------
    // testMailOutageIsNoServerErrorAndTellsNothing
    // -----------------------------------------------------------
    //
    // While the mail relay cannot be reached, the reset button
    // leaves the password and the token as they were and says
    // the mail could not be sent, and forgot-password answers
    // a registered address as it answers an unknown one,
    // storing no token. Both once answered with a 500 — on
    // forgot-password only for a registered address, which
    // told who has an account.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testMailOutageIsNoServerErrorAndTellsNothing(): void
    {
        $member = $this->world->member(['reset_token' => self::TOKEN]);
        $other = $this->world->member();
        Mail::shouldReceive('to')->andThrow(new TransportException('Connection to the mail relay refused'));
        $this->solveCaptcha();

        $this->post('/reset/' . self::TOKEN)
            ->assertRedirect('/login')
            ->assertSessionHasErrors();
        $this->assertSame(__('main.mail_failed'), session('errors')->first());
        $this->assertTrue(Hash::check(World::PASSWORD, Members::find($member->id)->password));
        $this->assertSame(self::TOKEN, Members::find($member->id)->reset_token);

        $this->requestReset($other->email)->assertRedirect('/forgot-password')->assertSessionHas('success', __('main.forgot_pass_msg'));
        $this->requestReset('nobody.here@example.test')->assertRedirect('/forgot-password')->assertSessionHas('success', __('main.forgot_pass_msg'));
        $this->assertSame('', Members::find($other->id)->reset_token);
    }






    // -----------------------------------------------------------
    // testResetGivesAnUnverifiedMemberAWorkingLogin
    // -----------------------------------------------------------
    //
    // A member who never opened the verification link — the
    // mail lost, and nothing sends it again — gets an account
    // that signs in from the reset: the reset link reached
    // the address as the verification mail would have, so the
    // reset switches the account on and uses the verification
    // token up. The login takes only active accounts, and the
    // mailed password once never signed in.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testResetGivesAnUnverifiedMemberAWorkingLogin(): void
    {
        $member = $this->world->member(['status' => 0, 'verify_token' => 'Unused-' . self::TOKEN, 'reset_token' => self::TOKEN]);
        $this->post('/reset/' . self::TOKEN)->assertRedirect('/login');
        $newPassword = null;
        Mail::assertSent(SendNewPass::class, function (SendNewPass $mail) use (&$newPassword) {
            $newPassword = $mail->data['new_pass'];
            return true;
        });

        $this->from('/login')->post('/login', ['email' => $member->email, 'password' => $newPassword])->assertRedirect('/cp/dashboard');

        $this->assertAuthenticated('mem');
        $member = Members::find($member->id);
        $this->assertSame(1, $member->status);
        $this->assertSame('', $member->verify_token);
    }






    // -----------------------------------------------------------
    // testResetLeavesASwitchedOffAccountOff
    // -----------------------------------------------------------
    //
    // An account an admin switched off stays off through a
    // reset: the new password is mailed and stored, and the
    // login still refuses it.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testResetLeavesASwitchedOffAccountOff(): void
    {
        $member = $this->world->member(['status' => 2, 'reset_token' => self::TOKEN]);
        $this->post('/reset/' . self::TOKEN)->assertRedirect('/login');
        $newPassword = null;
        Mail::assertSent(SendNewPass::class, function (SendNewPass $mail) use (&$newPassword) {
            $newPassword = $mail->data['new_pass'];
            return true;
        });

        $this->from('/login')->post('/login', ['email' => $member->email, 'password' => $newPassword]);

        $this->assertGuest('mem');
        $this->assertSame(2, Members::find($member->id)->status);
    }






    // -----------------------------------------------------------
    // requestReset
    // -----------------------------------------------------------
    //
    // Posts the forgotten-password form for the address, as
    // sent from its page.
    //
    // Used by:
    //   - the reset-request tests of this class
    // -----------------------------------------------------------

    private function requestReset(string $email): TestResponse
    {
        return $this->from('/forgot-password')->post('/forgot-password', [
            'email'                => $email,
            'g-recaptcha-response' => 'token-from-the-widget',
        ]);
    }






    // -----------------------------------------------------------
    // leaveTheBrowser
    // -----------------------------------------------------------
    //
    // Leaves the browser the test acted in: its session goes,
    // and so does whoever the guards had signed in, so the
    // next request comes from another browser — or, given a
    // session kept aside, from that one again.
    //
    // Used by:
    //   - testResetLinkSignsTheMemberOutEverywhere (above)
    // -----------------------------------------------------------

    private function leaveTheBrowser(): void
    {
        $this->flushSession();
        $this->app['auth']->forgetGuards();
    }
}
