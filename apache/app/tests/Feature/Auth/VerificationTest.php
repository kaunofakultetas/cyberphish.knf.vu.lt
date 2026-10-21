<?php

// -----------------------------------------------------------
//  [*] VerificationTest — the link in the verification mail
//
//  GET /verify/{verify_token} (RegisterController::verify):
//  the token of an unverified member activates the account
//  (status 1) and is used up; an unknown token changes
//  nothing. Both answers send the visitor to the login
//  form. The blank-token defect is pinned in
//  AuthDefectsTest.
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
}
