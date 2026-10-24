<?php

// -----------------------------------------------------------
//  [*] VerificationTest — the link in the verification mail
//
//  GET /verify/{verify_token} (RegisterController::verify):
//  the token of an unverified member activates the account
//  (status 1) and is used up; an unknown token changes
//  nothing, and neither does a token of blanks, which MySQL
//  would match against every emptied token, nor the token
//  of an account an admin has switched off. Every answer
//  sends the visitor to the login form — the form itself,
//  even when a member page parked another destination.
//
//  Used by:
//    - PHPUnit — the Feature suite (runTests.sh)
// -----------------------------------------------------------


namespace Tests\Feature\Auth;

use App\Models\Members;
use Tests\FeatureTestCase;








// -----------------------------------------------------------
// VerificationTest
// -----------------------------------------------------------
//
// Each test opens the link the way a mail client would, as
// a visitor with no session.
//
// Used by:
//   - PHPUnit — the Feature suite (runTests.sh)
// -----------------------------------------------------------

class VerificationTest extends FeatureTestCase
{

    // A token of the length RegisterController generates
    private const TOKEN = 'Vq3mZp8XkR2sT7wYb4nJc6dLf9hGa1eUo5iQx0rWt3yEuIoPaS';






    // -----------------------------------------------------------
    // testLinkActivatesTheAccount
    // -----------------------------------------------------------
    //
    // The member becomes active, the token is cleared, and the
    // login form greets them with the verified message.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testLinkActivatesTheAccount(): void
    {
        $member = $this->world->member(['status' => 0, 'verify_token' => self::TOKEN]);

        $this->get('/verify/' . self::TOKEN)
            ->assertRedirect('/login')
            ->assertSessionHas('success', __('main.user_verified'));

        $member = Members::find($member->id);
        $this->assertSame(1, $member->status);
        $this->assertSame('', $member->verify_token);
    }






    // -----------------------------------------------------------
    // testLinkWorksOnce
    // -----------------------------------------------------------
    //
    // A second click finds no token any more: nothing changes
    // and no verified message is shown.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testLinkWorksOnce(): void
    {
        $this->world->member(['status' => 0, 'verify_token' => self::TOKEN]);
        $this->get('/verify/' . self::TOKEN);

        $this->get('/verify/' . self::TOKEN)
            ->assertRedirect('/login')
            ->assertSessionMissing('success');
    }






    // -----------------------------------------------------------
    // testUnknownLinkChangesNothing
    // -----------------------------------------------------------
    //
    // A token no member holds leaves every account as it was.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testUnknownLinkChangesNothing(): void
    {
        $member = $this->world->member(['status' => 0, 'verify_token' => self::TOKEN]);

        $this->get('/verify/' . strrev(self::TOKEN))->assertRedirect('/login');

        $this->assertSame(0, Members::find($member->id)->status);
        $this->assertSame(self::TOKEN, Members::find($member->id)->verify_token);
    }






    // -----------------------------------------------------------
    // testBlankLinkLeavesDeactivatedAccountsAlone
    // -----------------------------------------------------------
    //
    // An account an admin switched off after it was verified
    // has an empty token, which a token of one space matches
    // in MySQL, trailing spaces being ignored. The account
    // stays off, and the login form shows no verified
    // message.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testBlankLinkLeavesDeactivatedAccountsAlone(): void
    {
        $deactivated = $this->world->member(['status' => 0]);

        $this->get('/verify/%20')
            ->assertRedirect('/login')
            ->assertSessionMissing('success');

        $this->assertSame(0, Members::find($deactivated->id)->status);
    }






    // -----------------------------------------------------------
    // testVerificationGreetsAVisitorSentFromAMemberPage
    // -----------------------------------------------------------
    //
    // A visitor whom a member page sent to the login form
    // earlier — the page is parked in the session — still
    // lands on the form after the link, with the greeting.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testVerificationGreetsAVisitorSentFromAMemberPage(): void
    {
        $this->world->member(['status' => 0, 'verify_token' => self::TOKEN]);
        $this->get('/cp/dashboard')->assertRedirect('/login');

        $this->get('/verify/' . self::TOKEN)
            ->assertRedirect('/login')
            ->assertSessionHas('success', __('main.user_verified'));
    }






    // -----------------------------------------------------------
    // testLinkLeavesASwitchedOffAccountOff
    // -----------------------------------------------------------
    //
    // An admin who switches an unverified member on leaves the
    // token in place; once that member is switched off again,
    // the old mail's link leaves the account off. The link is
    // used up, and the login form opens without the greeting.
    // It once switched any account holding the token back on.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testLinkLeavesASwitchedOffAccountOff(): void
    {
        $member = $this->world->member(['status' => 2, 'verify_token' => self::TOKEN]);

        $this->get('/verify/' . self::TOKEN)
            ->assertRedirect('/login')
            ->assertSessionMissing('success');

        $member = Members::find($member->id);
        $this->assertSame(2, $member->status, 'the link switched the account back on');
        $this->assertSame('', $member->verify_token);
    }






    // -----------------------------------------------------------
    // testLinkOfAnAccountSwitchedOnAlreadyGreets
    // -----------------------------------------------------------
    //
    // An account an admin switched on before its link was
    // opened stays on, and the link greets it as verified and
    // is used up.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testLinkOfAnAccountSwitchedOnAlreadyGreets(): void
    {
        $member = $this->world->member(['status' => 1, 'verify_token' => self::TOKEN]);

        $this->get('/verify/' . self::TOKEN)
            ->assertRedirect('/login')
            ->assertSessionHas('success', __('main.user_verified'));

        $member = Members::find($member->id);
        $this->assertSame(1, $member->status);
        $this->assertSame('', $member->verify_token);
    }
}
