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
//    reset-unverified      — a reset mails an unverified
//                            member a password that never
//                            signs in
//    register-input-lost   — a refused sign-up comes back
//                            empty
//
//  Used by:
//    - PHPUnit — the Feature suite (runTests.sh)
// -----------------------------------------------------------


namespace Tests\Feature\Auth;

use App\Mail\SendNewPass;
use Illuminate\Support\Facades\Mail;
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
