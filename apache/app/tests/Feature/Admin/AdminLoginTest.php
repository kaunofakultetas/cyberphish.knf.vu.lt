<?php

// -----------------------------------------------------------
//  [*] AdminLoginTest — managers signing in to the panel
//
//  POST /admin-panel and GET admin-panel/logout
//  (AdminLoginController): an active manager (status 1)
//  with the right password lands on the dashboard, and the
//  session gets the manager's main flag and country — main
//  admins pass the Perms middleware, country managers see
//  their country only. A wrong password, a switched-off
//  manager and a member's account are refused. The missing
//  brute-force protection is pinned in AdminDefectsTest.
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
        $this->signInManager($this->world->manager())->get('/admin-panel/logout')->assertRedirect('/admin-panel');

        $this->assertGuest('man');
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
