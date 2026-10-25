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
//  the right password included; the member login's
//  failures count apart. A member page parked before the
//  sign-in is not followed. The panel's password dialog
//  answers in words.
//
//  Used by:
//    - PHPUnit — the Feature suite (runTests.sh)
// -----------------------------------------------------------


namespace Tests\Feature\Admin;

use App\Models\Managers;
use Dom\HTMLDocument;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
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
    // testPasswordPostedAsAListIsRefused
    // -----------------------------------------------------------
    //
    // A password posted as a list goes back to the form with a
    // validation error on the password, the same for an active
    // manager as for an unknown address, and opens nothing. The
    // rule's min once counted the list's six items, and for an
    // active manager the guard handed the list to
    // password_verify, a 500 (login-password-list).
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testPasswordPostedAsAListIsRefused(): void
    {
        $manager = $this->world->manager();

        foreach ([$manager->email, 'nobody.here@example.test'] as $email) {
            $this->from('/admin-panel')->post('/admin-panel', ['email' => $email, 'password' => array_fill(0, 6, 'x')])
                ->assertRedirect('/admin-panel')
                ->assertSessionHasErrors('password');
        }

        $this->assertGuest('man');
    }






    // -----------------------------------------------------------
    // testPasswordWithANulByteIsRefused
    // -----------------------------------------------------------
    //
    // A password with a NUL byte goes back to the form with a
    // validation error, even the manager's real password
    // followed by the byte and more, which bcrypt — reading
    // only up to the byte — once took for the password. With a
    // stored hash of another cost than the configured 10, as
    // init.sql's password_hash command makes, the rehash on
    // sign-in failed on it with a 500 (login-password-list).
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testPasswordWithANulByteIsRefused(): void
    {
        $managers = [
            $this->world->manager(),
            $this->world->manager(['password' => password_hash(World::PASSWORD, PASSWORD_BCRYPT, ['cost' => 12])]),
        ];

        foreach ($managers as $manager) {
            $this->from('/admin-panel')->post('/admin-panel', ['email' => $manager->email, 'password' => World::PASSWORD . "\0anything"])
                ->assertRedirect('/admin-panel')
                ->assertSessionHasErrors('password');
        }

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
    // testRefusedLoginKeepsTheEmail
    // -----------------------------------------------------------
    //
    // A refused sign-in brings the form back with the email
    // already in its field and the cursor in the password
    // field, so only the password needs typing again; the
    // password itself never goes into the session. The form
    // once came back empty.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testRefusedLoginKeepsTheEmail(): void
    {
        $admin = $this->world->manager();

        $this->signIn($admin->email, 'Wrong-Password-1')->assertRedirect('/admin-panel');
        $this->assertSame($admin->email, session()->getOldInput('email'));
        $this->assertNull(session()->getOldInput('password'));

        $html = $this->get('/admin-panel')->assertOk()->assertSee('value="' . $admin->email . '"', false)->getContent();
        $document = HTMLDocument::createFromString($html, LIBXML_NOERROR);
        $this->assertTrue($document->querySelector('input[name="password"]')->hasAttribute('autofocus'), 'the cursor is not in the password field');
        $this->assertFalse($document->querySelector('input[name="email"]')->hasAttribute('autofocus'), 'the cursor is in the email field');
    }






    // -----------------------------------------------------------
    // testPanelLoginFormSurvivesAnEmailPostedAsAList
    // -----------------------------------------------------------
    //
    // An email posted as a list fails the email rule, and the
    // login form comes back with its error. The form once
    // printed the flashed list straight into the email field,
    // a TypeError, and the next load of /admin-panel was the
    // 500 page (admin-login-email-list-500).
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testPanelLoginFormSurvivesAnEmailPostedAsAList(): void
    {
        $this->from('/admin-panel')->post('/admin-panel', ['email' => ['manager@example.test'], 'password' => 'Some-Password-1'])
            ->assertRedirect('/admin-panel')
            ->assertSessionHasErrors('email');
        $error = session('errors')->first('email');

        $this->get('/admin-panel')->assertOk()->assertSee($error);
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
    // testLockoutHoldsUnderEverySpellingOfTheAddress
    // -----------------------------------------------------------
    //
    // After ten wrong passwords the address stays locked under
    // every spelling the email column folds together — a
    // manager's, the right password typed with ß for an s, and
    // just as much an address no manager has — so the lockout
    // neither opens to another spelling nor tells which
    // addresses are managers'. The failures once counted under
    // the address transliterated, ß turned into ss, and that
    // spelling opened the locked account
    // (login-throttle-collation-bypass).
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testLockoutHoldsUnderEverySpellingOfTheAddress(): void
    {
        $this->world->manager(['email' => 'panel.locked@example.test']);
        foreach (['panel.locked@example.test', 'panel.nobody@example.test'] as $address) {
            for ($guess = 1; $guess <= 10; $guess++) {
                $this->signIn($address, 'Wrong-' . $guess);
            }
        }

        foreach (['panel.locked@example.teßt' => World::PASSWORD, 'panel.nobody@example.teßt' => 'Wrong-11'] as $spelling => $password) {
            $this->signIn($spelling, $password)
                ->assertRedirect('/admin-panel')
                ->assertSessionHasErrors('email');
            $this->assertStringStartsWith(strstr(__('auth.throttle'), ':seconds', true), session('errors')->first('email'), "$spelling was answered, but not by the lockout");
        }

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
    // testPanelLoginOpensForASignedInMember
    // -----------------------------------------------------------
    //
    // A member signed in on the course site — an organiser who
    // also takes the course — gets the panel's login form, and
    // the manager's password signs the manager in and lands on
    // the dashboard, the member staying signed in; from then
    // on the form sends the manager to the dashboard, as it
    // does every signed-in manager. The form once sent a
    // signed-in member to the course's home page (guest:mem),
    // so such an organiser could not sign in to the panel at
    // all (panel-login-bounces-members).
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testPanelLoginOpensForASignedInMember(): void
    {
        $member = $this->world->member();
        $manager = $this->world->manager(['main' => 0, 'country' => 1]);

        $this->signInMember($member)->get('/admin-panel')->assertOk();

        $this->signIn($manager->email, World::PASSWORD)->assertRedirect('/admin-panel/dashboard');
        $this->assertAuthenticated('man');
        $this->assertSame($manager->id, (int) $this->app['auth']->guard('man')->id());
        $this->assertAuthenticated('mem');

        $this->get('/admin-panel')->assertRedirect('/admin-panel/dashboard');
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
    // testPasswordDialogRefusesANulByte
    // -----------------------------------------------------------
    //
    // A new password with a NUL byte, which bcrypt refuses, is
    // turned away like a list, and the password stays. It once
    // reached Hash::make, a 500 (login-password-list).
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testPasswordDialogRefusesANulByte(): void
    {
        $admin = $this->world->manager();

        $this->signInManager($admin)->from('/admin-panel/dashboard')->post('/admin-panel/change-pass', [
            'password'  => World::PASSWORD,
            'newpass'   => "Fresh-Admin\0Pass-1",
            'renewpass' => "Fresh-Admin\0Pass-1",
        ])->assertRedirect('/admin-panel/dashboard')->assertSessionHasErrors();

        $this->assertSame(__('main.new_pass_not_match'), session('errors')->first());
        $this->assertTrue(Hash::check(World::PASSWORD, Managers::find($admin->id)->password));
    }






    // -----------------------------------------------------------
    // testCountryManagerChangesItsOwnPassword
    // -----------------------------------------------------------
    //
    // The menu's Change password works for a country manager as
    // for a main admin: the dialog opens, and the change sets
    // the signed-in manager's own password and no one else's.
    // The route was kept to main admins, so a country
    // manager's dialog opened empty — a 404 — and no country
    // manager could change their password.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testCountryManagerChangesItsOwnPassword(): void
    {
        $manager = $this->world->manager(['main' => 0, 'country' => 2]);
        $other = $this->world->manager(['main' => 0, 'country' => 3]);

        $this->signInManager($manager)->get('/admin-panel/change-pass')->assertOk()->assertSee('name="newpass"', false);
        $this->signInManager($manager)->from('/admin-panel/dashboard')->post('/admin-panel/change-pass', [
            'password'  => World::PASSWORD,
            'newpass'   => 'Fresh-Country-Pass-1',
            'renewpass' => 'Fresh-Country-Pass-1',
        ])->assertRedirect('/admin-panel/dashboard')->assertSessionHas('success', __('main.pass_changed'));

        $this->assertTrue(Hash::check('Fresh-Country-Pass-1', Managers::find($manager->id)->password));
        $this->assertTrue(Hash::check(World::PASSWORD, Managers::find($other->id)->password));
    }






    // -----------------------------------------------------------
    // testOwnPasswordChangeKeepsTheManagerSignedIn
    // -----------------------------------------------------------
    //
    // The manager who changes their password in the dialog
    // stays signed in there: the next panel page opens, the
    // session marked with the new password as the change went
    // through, while the manager's other sessions end. The
    // session keeps its CSRF token as it moves to a new id, so
    // the panel's other open tabs can still post.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testOwnPasswordChangeKeepsTheManagerSignedIn(): void
    {
        $manager = $this->world->manager(['main' => 0, 'country' => 2]);
        $this->signInManager($manager)->get('/admin-panel/dashboard')->assertOk();
        $token = session()->token();

        $this->from('/admin-panel/dashboard')->post('/admin-panel/change-pass', [
            'password'  => World::PASSWORD,
            'newpass'   => 'Fresh-Own-Panel-Pass-1',
            'renewpass' => 'Fresh-Own-Panel-Pass-1',
        ])->assertSessionHas('success', __('main.pass_changed'));
        $this->assertSame($token, session()->token(), 'the change gave the session a new CSRF token, which the other open tabs lack');

        $this->get('/admin-panel/dashboard')->assertOk();
        $this->assertAuthenticated('man');
    }






    // -----------------------------------------------------------
    // testNewPasswordSetElsewhereSignsTheManagerOut
    // -----------------------------------------------------------
    //
    // A manager's password changed outside this session — by
    // a main admin's manager dialog, or by the manager in
    // another browser — ends it: the next panel page sends the
    // manager to the panel's login form, signed out. A session
    // once went on with the old password until it ran out.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testNewPasswordSetElsewhereSignsTheManagerOut(): void
    {
        $manager = $this->world->manager(['main' => 0, 'country' => 2]);
        $this->signInManager($manager)->get('/admin-panel/dashboard')->assertOk();

        // What the change elsewhere leaves in the row
        DB::table('bei_managers')->where('id', $manager->id)->update(['password' => Hash::make('Fresh-Elsewhere-Pass-1')]);

        $this->get('/admin-panel/dashboard')->assertRedirect('/admin-panel');
        $this->assertGuest('man');
    }






    // -----------------------------------------------------------
    // testMembersSignOutLeavesTheManagerSignedIn
    // -----------------------------------------------------------
    //
    // A browser signed in on both sides — an organiser who
    // also takes the course — loses only the side whose
    // password changed: the member is signed out, and the
    // manager's panel pages go on opening.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testMembersSignOutLeavesTheManagerSignedIn(): void
    {
        $manager = $this->world->manager(['main' => 0, 'country' => 2]);
        $member = $this->world->member();
        $this->signInManager($manager)->get('/admin-panel/dashboard')->assertOk();
        $this->signInMember($member)->get('/cp/dashboard')->assertOk();

        DB::table('bei_users')->where('id', $member->id)->update(['password' => Hash::make('Fresh-Member-Pass-12')]);

        $this->get('/admin-panel/dashboard')->assertOk();
        $this->get('/cp/dashboard')->assertRedirect('/login');
        $this->assertAuthenticated('man');
        $this->assertGuest('mem');
    }






    // -----------------------------------------------------------
    // testDeletedManagersSessionSignsInNoOneLater
    // -----------------------------------------------------------
    //
    // The session of a manager deleted while signed in drops
    // the manager's id, main and country at its next request,
    // so it signs in no one later given that id — MySQL 5.7
    // hands the highest id out again after a restart. The id
    // once stayed in the session, ready for the next manager
    // to take it. The browser is the session the test keeps
    // aside and puts back.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testDeletedManagersSessionSignsInNoOneLater(): void
    {
        $manager = $this->world->manager(['main' => 0, 'country' => 2]);
        $this->signIn($manager->email, World::PASSWORD)->assertRedirect('/admin-panel/dashboard');
        $browser = session()->all();

        DB::table('bei_managers')->where('id', $manager->id)->delete();
        $this->leaveTheBrowser();
        $this->withSession($browser)->get('/admin-panel/dashboard')->assertRedirect('/admin-panel');
        $browser = session()->all();
        $this->assertArrayNotHasKey('main', $browser);

        DB::table('bei_managers')->insert(['id' => $manager->id, 'email' => 'newcomer-manager' . $manager->id . '@example.test', 'password' => Hash::make('Newcomer-Password-12'), 'main' => 0, 'status' => '1', 'country' => 4]);
        $this->leaveTheBrowser();
        $this->withSession($browser)->get('/admin-panel/dashboard')->assertRedirect('/admin-panel');
        $this->assertGuest('man');
    }






    // -----------------------------------------------------------
    // testAdminLoginIgnoresAMemberPagesParking
    // -----------------------------------------------------------
    //
    // Both sign-in redirects park the page asked for under one
    // key; a member page parked before the admin login is not
    // followed, and the manager lands on the dashboard. It
    // once was — onto the member login, since auth:mem refuses
    // a manager.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testAdminLoginIgnoresAMemberPagesParking(): void
    {
        $manager = $this->world->manager();
        $this->get('/cp/dashboard')->assertRedirect('/login');

        $this->signIn($manager->email, World::PASSWORD)->assertRedirect('/admin-panel/dashboard');
    }






    // -----------------------------------------------------------
    // testFailedMemberLoginsDoNotLockThePanel
    // -----------------------------------------------------------
    //
    // Each login counts its own failures: ten wrong member
    // passwords for an organiser's address leave the panel's
    // sign-in of that address open. Both once counted under
    // the address and the IP alone, in one cache, and locked
    // each other.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testFailedMemberLoginsDoNotLockThePanel(): void
    {
        $manager = $this->world->manager();
        for ($guess = 1; $guess <= 10; $guess++) {
            $this->from('/login')->post('/login', ['email' => $manager->email, 'password' => 'Wrong-Guess-' . $guess]);
        }

        $this->signIn($manager->email, World::PASSWORD)->assertRedirect('/admin-panel/dashboard');

        $this->assertAuthenticated('man');
    }






    // -----------------------------------------------------------
    // testPasswordDialogTakesNoLists
    // -----------------------------------------------------------
    //
    // The panel's password dialog refuses a field posted as a
    // list at the check it would have failed, and the password
    // stays. Such a list once reached password_verify or
    // strcmp — a TypeError, a 500 (admin-change-pass-array-500).
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testPasswordDialogTakesNoLists(): void
    {
        $admin = $this->world->manager();
        $posts = [
            'main.curr_pass_wrong'     => ['password' => [World::PASSWORD], 'newpass' => 'Fresh-Admin-Pass-1', 'renewpass' => 'Fresh-Admin-Pass-1'],
            'main.new_pass_not_match'  => ['password' => World::PASSWORD, 'newpass' => ['Fresh-Admin-Pass-1'], 'renewpass' => 'Fresh-Admin-Pass-1'],
        ];

        foreach ($posts as $message => $fields) {
            $this->signInManager($admin)->from('/admin-panel/dashboard')->post('/admin-panel/change-pass', $fields)
                ->assertRedirect('/admin-panel/dashboard')
                ->assertSessionHasErrors();

            $this->assertSame(__($message), session('errors')->first());
        }
        $this->assertTrue(Hash::check(World::PASSWORD, Managers::find($admin->id)->password));
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
    //   - testDeletedManagersSessionSignsInNoOneLater (above)
    // -----------------------------------------------------------

    private function leaveTheBrowser(): void
    {
        $this->flushSession();
        $this->app['auth']->forgetGuards();
    }
}
