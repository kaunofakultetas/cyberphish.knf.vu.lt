<?php

// -----------------------------------------------------------
//  [*] AuthDefectsTest — known defects of signing up and in
//
//  Each test states the behaviour sign-up, verification,
//  password reset and the two logins should have and pins
//  the defect that breaks it today (KnownDefects): the run
//  reports them as incomplete, tagged with their ids, until
//  a fix makes one pass — then the test fails as the cue to
//  remove its pin and move it into its home file.
//
//    verify-switched-off   — the sign-up link switches back
//                            on an account an admin switched
//                            off
//    intended-cross-guard  — a login follows the page the
//                            other guard parked
//    reset-unverified      — a reset mails an unverified
//                            member a password that never
//                            signs in
//    auth-mail-outage      — a mail outage is a 500 on the
//                            reset, and tells forgot-password's
//                            known addresses apart
//    throttle-shared       — failed member logins lock the
//                            panel's login of the same address
//    register-input-lost   — a refused sign-up comes back
//                            empty
//
//  Used by:
//    - PHPUnit — the Feature suite (runTests.sh)
// -----------------------------------------------------------


namespace Tests\Feature\Auth;

use App\Mail\SendNewPass;
use App\Models\Members;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\Mailer\Exception\TransportException;
use Tests\FeatureTestCase;
use Tests\Support\World;








// -----------------------------------------------------------
// AuthDefectsTest
// -----------------------------------------------------------
//
// One pinned defect per test, named in the header; each test
// builds its own account.
//
// Used by:
//   - PHPUnit — the Feature suite (runTests.sh)
// -----------------------------------------------------------

class AuthDefectsTest extends FeatureTestCase
{

    // A token of the 50 characters the app hands out, for the
    // verification and the reset links
    private const TOKEN = 'Kq7mZp2XkR9sT4wYb1nJc8dLf5hGa3eUo6iQx0rWt2yEuIoPaD';






    // -----------------------------------------------------------
    // testVerificationLinkLeavesASwitchedOffAccountOff
    // -----------------------------------------------------------
    //
    // verify switches on whichever account holds the token,
    // and an admin switching an unverified member on keeps the
    // token: once that member is switched off again, the old
    // sign-up mail switches them back on. Wanted: a switched-
    // off account stays off.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testVerificationLinkLeavesASwitchedOffAccountOff(): void
    {
        $member = $this->world->member(['status' => 2, 'verify_token' => self::TOKEN]);

        $this->get('/verify/' . self::TOKEN);

        $this->pinDefect('verify-switched-off', function () use ($member) {
            $this->assertEquals(2, Members::find($member->id)->status, 'the link switched the account back on');
        });
    }






    // -----------------------------------------------------------
    // testAdminLoginIgnoresAMemberPagesParking
    // -----------------------------------------------------------
    //
    // Both logins send a success to url.intended, which either
    // guard's sign-in redirect fills: a member page opened
    // first parks itself, and the panel's next sign-in lands
    // there — on the member login, since auth:mem refuses a
    // manager. Wanted: the admin login goes to the dashboard.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testAdminLoginIgnoresAMemberPagesParking(): void
    {
        $manager = $this->world->manager();
        $this->get('/cp/dashboard')->assertRedirect('/login');

        $response = $this->from('/admin-panel')->post('/admin-panel', ['email' => $manager->email, 'password' => World::PASSWORD]);

        $this->pinDefect('intended-cross-guard', function () use ($response) {
            $response->assertRedirect('/admin-panel/dashboard');
        });
    }






    // -----------------------------------------------------------
    // testResetGivesAnUnverifiedMemberAWorkingLogin
    // -----------------------------------------------------------
    //
    // The reset mails any member a new password, but the login
    // takes only verified accounts, and nothing resends the
    // verification mail: a member who lost it is told the new
    // password is wrong. Wanted: the password a reset mails
    // signs in.
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

        $this->from('/login')->post('/login', ['email' => $member->email, 'password' => $newPassword]);

        $this->pinDefect('reset-unverified', function () {
            $this->assertAuthenticated('mem');
        });
    }






    // -----------------------------------------------------------
    // testMailOutageIsNoServerErrorAndTellsNothing
    // -----------------------------------------------------------
    //
    // The reset and forgot-password send their mail with no
    // catch: an outage is a 500 on the reset, and on
    // forgot-password a 500 for a registered address against
    // the usual answer for an unknown one — the form that
    // promises not to tell who has an account. Wanted: no
    // server error, and the same answer for both addresses.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testMailOutageIsNoServerErrorAndTellsNothing(): void
    {
        $member = $this->world->member(['reset_token' => self::TOKEN]);
        Mail::shouldReceive('to')->andThrow(new TransportException('Connection to the mail relay refused'));
        $this->solveCaptcha();

        $reset = $this->post('/reset/' . self::TOKEN)->getStatusCode();
        $known = $this->from('/forgot-password')->post('/forgot-password', [
            'email' => $member->email, 'g-recaptcha-response' => 'token-from-the-widget',
        ])->getStatusCode();
        $unknown = $this->from('/forgot-password')->post('/forgot-password', [
            'email' => 'nobody.here@example.test', 'g-recaptcha-response' => 'token-from-the-widget',
        ])->getStatusCode();

        $this->pinDefect('auth-mail-outage', function () use ($reset, $known, $unknown) {
            $this->assertLessThan(500, $reset, 'the reset button answered with a server error');
            $this->assertSame($unknown, $known, 'forgot-password answered a registered address differently');
        });
    }






    // -----------------------------------------------------------
    // testFailedMemberLoginsDoNotLockThePanel
    // -----------------------------------------------------------
    //
    // Both logins throttle with laravel/ui's ThrottlesLogins,
    // keyed on the address and the IP alike, in one cache: ten
    // wrong member passwords for an organiser's address lock
    // the panel's login of that address too. Wanted: each
    // login counts its own failures.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testFailedMemberLoginsDoNotLockThePanel(): void
    {
        $manager = $this->world->manager();
        for ($i = 0; $i < 10; $i++) {
            $this->from('/login')->post('/login', ['email' => $manager->email, 'password' => 'Wrong-Password-12']);
        }

        $this->from('/admin-panel')->post('/admin-panel', ['email' => $manager->email, 'password' => World::PASSWORD]);

        $this->pinDefect('throttle-shared', function () {
            $this->assertAuthenticated('man');
        });
    }






    // -----------------------------------------------------------
    // testRefusedSignUpKeepsTheForm
    // -----------------------------------------------------------
    //
    // A sign-up refused for passwords that differ goes back
    // with its error but no input, so the address and the
    // country are typed again — as both login forms did until
    // login-email-lost was fixed. Wanted: the address comes
    // back.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testRefusedSignUpKeepsTheForm(): void
    {
        $this->solveCaptcha();

        $response = $this->from('/register')->post('/register', [
            'email'                => 'kept.student@example.test',
            'password'             => 'Long-Enough-Password-1',
            'password2'            => 'Another-Long-Password-2',
            'country'              => 1,
            'g-recaptcha-response' => 'token-from-the-widget',
        ])->assertSessionHasErrors();

        $this->pinDefect('register-input-lost', function () use ($response) {
            $response->assertSessionHasInput('email', 'kept.student@example.test');
        });
    }
}
