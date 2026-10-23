<?php

// -----------------------------------------------------------
//  [*] PasswordResetTest — the forgotten-password flow
//
//  POST /forgot-password mails a reset link to a registered
//  address — and answers an unknown address with the very
//  same message, mailing nothing, so the form does not
//  reveal who has an account. GET /reset/{reset_token},
//  behind the ValidateReset middleware, mails the member a
//  new random password and uses the token up. The link is
//  built from APP_URL whatever host the request claimed,
//  and a blank token is refused, as the verification link
//  refuses one (VerificationTest). The tokens and the new
//  password come from a cryptographic generator, and the
//  message after the link reaches the login form even when
//  a member page parked another destination.
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
    // The member's remember_token becomes a fresh 50-character
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

        $token = Members::find($member->id)->remember_token;
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

        $token = Members::find($member->id)->remember_token;
        Mail::assertSent(SendRemindLink::class, function (SendRemindLink $mail) use ($token) {
            return $this->mailLink($mail) === 'https://cyberphish.knf.vu.lt/reset/' . $token;
        });
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

        $this->assertSame('', Members::find($member->id)->remember_token);
        Mail::assertNothingSent();
    }






    // -----------------------------------------------------------
    // testResetLinkMailsANewPassword
    // -----------------------------------------------------------
    //
    // Opening the link mails the member a new 12-character
    // password, which is the password from then on; the token
    // is used up and the login form shows the mailed message.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testResetLinkMailsANewPassword(): void
    {
        $member = $this->world->member(['remember_token' => self::TOKEN]);

        $this->get('/reset/' . self::TOKEN)
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
        $this->assertSame('', $member->remember_token);
    }






    // -----------------------------------------------------------
    // testResetLinkWorksOnce
    // -----------------------------------------------------------
    //
    // The second click on the same link is a 404, and no
    // second password is mailed.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testResetLinkWorksOnce(): void
    {
        $this->world->member(['remember_token' => self::TOKEN]);
        $this->get('/reset/' . self::TOKEN);

        $this->get('/reset/' . self::TOKEN)->assertNotFound();

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
        $this->world->member(['remember_token' => self::TOKEN]);

        $this->get('/reset/' . strrev(self::TOKEN))->assertNotFound();

        Mail::assertNothingSent();
    }






    // -----------------------------------------------------------
    // testBlankResetLinkIsNotFound
    // -----------------------------------------------------------
    //
    // Every member who signed in has an empty remember_token,
    // and MySQL ignores trailing spaces when it compares
    // strings, so a token of one space would match them all;
    // ValidateReset refuses an empty token and demands
    // exactly one match, so the link is a 404 and no password
    // changes.
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
        $this->world->member(['remember_token' => self::TOKEN]);
        $this->get('/cp/dashboard')->assertRedirect('/login');

        $this->get('/reset/' . self::TOKEN)
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
}
