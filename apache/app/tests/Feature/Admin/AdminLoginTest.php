<?php

// -----------------------------------------------------------
//  [*] AdminLoginTest — managers signing in to the panel
//
//  POST /admin-panel and POST admin-panel/logout
//  (AdminLoginController): an active manager (status 1)
//  with the right password lands on the dashboard, and the
//  session gets the manager's main flag and country — main
//  admins pass the Perms middleware, country managers see
//  their country only. A wrong password, a switched-off
//  manager and a member's account are refused — back on
//  the form with the message, even when a panel page sent
//  the visitor there — and ten wrong passwords for one
//  email from one address lock that pair out for a while,
//  the right password included. The panel's password
//  dialog answers in words.
//
//  Used by:
//    - PHPUnit — the Feature suite (runTests.sh)
// -----------------------------------------------------------


namespace Tests\Feature\Admin;

use App\Models\Managers;
use Illuminate\Testing\TestResponse;
use Tests\FeatureTestCase;
use Tests\Support\World;








// -----------------------------------------------------------
// AdminLoginTest
// -----------------------------------------------------------
//
// Every sign-in posts the form from the admin login page.
//
// Used by:
//   - PHPUnit — the Feature suite (runTests.sh)
// -----------------------------------------------------------

class AdminLoginTest extends FeatureTestCase
{






    // -----------------------------------------------------------
    // testLoginFormOpens
    // -----------------------------------------------------------
    //
    // The admin login asks for an email and a password.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testLoginFormOpens(): void
    {
        $this->get('/admin-panel')
            ->assertOk()
            ->assertSee('name="email"', false)
            ->assertSee('name="password"', false);
    }






    // -----------------------------------------------------------
    // testMainAdminSignsIn
    // -----------------------------------------------------------
    //
    // A main admin lands on the dashboard; the session says
    // main 1, and the visit is recorded on the manager.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testMainAdminSignsIn(): void
    {
        $admin = $this->world->manager();

        $this->signIn($admin->email, World::PASSWORD)
            ->assertRedirect('/admin-panel/dashboard')
            ->assertSessionHas('main', 1);

        $this->assertAuthenticated('man');
        $admin = Managers::find($admin->id);
        $this->assertNotNull($admin->last_login);
        $this->assertSame('127.0.0.1', $admin->last_ip);
    }






    // -----------------------------------------------------------
    // testCountryManagerSignsInWithItsCountry
    // -----------------------------------------------------------
    //
    // A country manager's session says main 0 and carries the
    // manager's country.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testCountryManagerSignsInWithItsCountry(): void
    {
        $manager = $this->world->manager(['main' => 0, 'country' => 2]);

        $this->signIn($manager->email, World::PASSWORD)
            ->assertRedirect('/admin-panel/dashboard')
            ->assertSessionHas('main', 0)
            ->assertSessionHas('country', 2);
    }






    // -----------------------------------------------------------
    // testWrongPasswordIsRefused
    // -----------------------------------------------------------
    //
    // Back to the admin login with the failure message.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testWrongPasswordIsRefused(): void
    {
        $admin = $this->world->manager();

        $this->signIn($admin->email, 'Not-The-Password')
            ->assertRedirect('/admin-panel')
            ->assertSessionHasErrors();

        $this->assertSame(__('auth.failed'), session('errors')->first());
        $this->assertGuest('man');
    }






    // -----------------------------------------------------------
    // testFailedAdminLoginShowsItsError
    // -----------------------------------------------------------
    //
    // A failure that a panel page led to — auth.admin parked
    // that page in the session — comes back to the form with
    // its message, not to the parked page.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testFailedAdminLoginShowsItsError(): void
    {
        $admin = $this->world->manager();
        $this->get('/admin-panel/dashboard')->assertRedirect('/admin-panel');

        $this->signIn($admin->email, 'Wrong-Password-1')
            ->assertRedirect('/admin-panel')
            ->assertSessionHasErrors();
    }






    // -----------------------------------------------------------
    // testTenWrongPasswordsLockTheAdminLogin
    // -----------------------------------------------------------
    //
    // After ten wrong passwords for one email from one
    // address even the right one is refused — with the
    // lockout message on the email field, not auth.failed —
    // and the manager stays out.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testTenWrongPasswordsLockTheAdminLogin(): void
    {
        $admin = $this->world->manager();
        for ($guess = 1; $guess <= 10; $guess++) {
            $this->signIn($admin->email, 'Wrong-' . $guess);
        }

        $this->signIn($admin->email, World::PASSWORD)
            ->assertRedirect('/admin-panel')
            ->assertSessionHasErrors('email');

        $this->assertGuest('man');
    }






    // -----------------------------------------------------------
    // testSwitchedOffManagerCannotSignIn
    // -----------------------------------------------------------
    //
    // A manager with status 0 — every new manager, until a
    // main admin switches them on — is refused.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testSwitchedOffManagerCannotSignIn(): void
    {
        $manager = $this->world->manager(['main' => 0, 'status' => '0']);

        $this->signIn($manager->email, World::PASSWORD)->assertRedirect('/admin-panel');

        $this->assertGuest('man');
    }






    // -----------------------------------------------------------
    // testMemberAccountDoesNotOpenThePanel
    // -----------------------------------------------------------
    //
    // Managers live in their own table: a member's address and
    // password sign nobody in.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testMemberAccountDoesNotOpenThePanel(): void
    {
        $member = $this->world->member();

        $this->signIn($member->email, World::PASSWORD)->assertRedirect('/admin-panel');

        $this->assertGuest('man');
    }






    // -----------------------------------------------------------
    // testSignedInManagerIsSentToTheDashboard
    // -----------------------------------------------------------
    //
    // The login form is for visitors only (guest:man).
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testSignedInManagerIsSentToTheDashboard(): void
    {
        $this->signInManager($this->world->manager())->get('/admin-panel')->assertRedirect('/admin-panel/dashboard');
    }






    // -----------------------------------------------------------
    // testLogoutEndsTheSession
    // -----------------------------------------------------------
    //
    // Signing out returns to the admin login as a visitor.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testLogoutEndsTheSession(): void
    {
        $this->signInManager($this->world->manager())->post('/admin-panel/logout')->assertRedirect('/admin-panel');

        $this->assertGuest('man');
    }






    // -----------------------------------------------------------
    // testSigningOutNeedsAPost
    // -----------------------------------------------------------
    //
    // A plain link to admin-panel/logout — another site's link
    // or image included — signs no manager out: the route
    // answers only a POST, which carries the session's token.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testSigningOutNeedsAPost(): void
    {
        $this->signInManager($this->world->manager())->get('/admin-panel/logout')->assertStatus(405);

        $this->assertAuthenticated('man');
    }






    // -----------------------------------------------------------
    // testPasswordDialogAnswersInWords
    // -----------------------------------------------------------
    //
    // The panel's password dialog refuses a wrong current
    // password with the member side's message for it, not a
    // raw main.msg_* key.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testPasswordDialogAnswersInWords(): void
    {
        $this->signInManager($this->world->manager())->from('/admin-panel/dashboard')->post('/admin-panel/change-pass', [
            'password'  => 'Not-The-Password-1',
            'newpass'   => 'Brand-New-Password-1',
            'renewpass' => 'Brand-New-Password-1',
        ])->assertSessionHasErrors();

        $this->assertSame(__('main.curr_pass_wrong'), session('errors')->first());
    }






    // -----------------------------------------------------------
    // signIn
    // -----------------------------------------------------------
    //
    // Posts the admin login form, as sent from its page.
    //
    // Used by:
    //   - the sign-in tests of this class
    // -----------------------------------------------------------

    private function signIn(string $email, string $password): TestResponse
    {
        return $this->from('/admin-panel')->post('/admin-panel', ['email' => $email, 'password' => $password]);
    }
}
