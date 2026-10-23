<?php

// -----------------------------------------------------------
//  [*] AdminManagersTest — main admins managing managers
//
//  The managers page of the panel (ManagersController):
//  it lists the country managers, creates new ones switched
//  off (status 0) until a main admin switches them on,
//  updates a manager's address, password and country, and
//  deletes managers. A main admin can be neither deleted,
//  switched off nor rewritten from here.
//
//  Used by:
//    - PHPUnit — the Feature suite (runTests.sh)
// -----------------------------------------------------------


namespace Tests\Feature\Admin;

use App\Models\Managers;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Testing\TestResponse;
use Tests\FeatureTestCase;
use Tests\Support\World;








// -----------------------------------------------------------
// AdminManagersTest
// -----------------------------------------------------------
//
// A main admin works the page in every test; the forms are
// posted from the managers page, which back() returns to.
//
// Used by:
//   - PHPUnit — the Feature suite (runTests.sh)
// -----------------------------------------------------------

class AdminManagersTest extends FeatureTestCase
{

    // The main admin of the test running now
    private $admin;






    // -----------------------------------------------------------
    // setUp
    // -----------------------------------------------------------
    //
    // A main admin for every test.
    //
    // Used by:
    //   - PHPUnit, before every test
    // -----------------------------------------------------------

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = $this->world->manager();
    }






    // -----------------------------------------------------------
    // testListShowsTheCountryManagers
    // -----------------------------------------------------------
    //
    // Country managers are listed, main admins are not — the
    // table is checked, since the page's top bar shows the
    // signed-in admin's own address.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testListShowsTheCountryManagers(): void
    {
        $country = $this->world->manager(['main' => 0, 'country' => 3]);

        $html = $this->signInManager($this->admin)->get('/admin-panel/settings/managers')->assertOk()->getContent();

        $table = substr($html, strpos($html, '<tbody>'));
        $this->assertStringContainsString($country->email, $table);
        $this->assertStringNotContainsString($this->admin->email, $table);
    }






    // -----------------------------------------------------------
    // testNewManagerStartsSwitchedOff
    // -----------------------------------------------------------
    //
    // A created manager is a country manager of the chosen
    // country, with the password hashed, and switched off.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testNewManagerStartsSwitchedOff(): void
    {
        $this->create(['email' => 'latvia@example.test', 'country' => 2])->assertSessionHas('success');

        $manager = Managers::where('email', 'latvia@example.test')->first();
        $this->assertSame(0, $manager->main);
        $this->assertSame(2, $manager->country);
        $this->assertEquals(0, $manager->status);
        $this->assertTrue(Hash::check('Manager-Pass-1', $manager->password));
    }






    // -----------------------------------------------------------
    // testManagerCreationIsRefused
    // -----------------------------------------------------------
    //
    // An address already used by a manager, passwords that
    // differ, and a password under 6 characters create
    // nothing.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testManagerCreationIsRefused(): void
    {
        $taken = $this->world->manager(['main' => 0]);
        $before = Managers::count();

        $this->create(['email' => $taken->email])->assertSessionHasErrors();
        $this->create(['repass' => 'Something-Else-2'])->assertSessionHasErrors();
        $this->create(['pass' => 'abc', 'repass' => 'abc'])->assertSessionHasErrors('pass');

        $this->assertSame($before, Managers::count());
    }






    // -----------------------------------------------------------
    // testSwitchedOnManagerCanSignIn
    // -----------------------------------------------------------
    //
    // Status 1 opens the panel to the new manager.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testSwitchedOnManagerCanSignIn(): void
    {
        $manager = $this->world->manager(['main' => 0, 'status' => '0']);

        $this->signInManager($this->admin)->from('/admin-panel/settings/managers')
            ->post("/admin-panel/settings/manager/status/1/{$manager->id}")
            ->assertRedirect('/admin-panel/settings/managers');
        $this->app['auth']->guard('man')->logout();
        $this->flushSession();

        $this->post('/admin-panel', ['email' => $manager->email, 'password' => World::PASSWORD])
            ->assertRedirect('/admin-panel/dashboard');
        $this->assertSame($manager->id, (int) auth('man')->id());
    }






    // -----------------------------------------------------------
    // testSwitchedOffManagerLosesTheSession
    // -----------------------------------------------------------
    //
    // A country manager switched off while signed in loses the
    // session at the next page, where it once ran on until it
    // expired.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testSwitchedOffManagerLosesTheSession(): void
    {
        $manager = $this->world->manager(['main' => 0, 'country' => 1]);
        $this->signInManager($manager)->get('/admin-panel/dashboard')->assertOk();

        DB::table('bei_managers')->where('id', $manager->id)->update(['status' => '2']);

        $this->get('/admin-panel/dashboard')->assertRedirect('/admin-panel');
        $this->assertGuest('man');
    }






    // -----------------------------------------------------------
    // testDemotedMainAdminLosesTheMainPages
    // -----------------------------------------------------------
    //
    // A main admin made a country manager while signed in
    // loses the main admins' pages at the next request: the
    // session's main flag follows the row, where it once kept
    // the value of the sign-in.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testDemotedMainAdminLosesTheMainPages(): void
    {
        $manager = $this->world->manager();
        $this->signInManager($manager)->get('/admin-panel/users')->assertOk();

        DB::table('bei_managers')->where('id', $manager->id)->update(['main' => 0, 'country' => 1]);

        $this->get('/admin-panel/users')->assertNotFound();
        $this->get('/admin-panel/dashboard')->assertOk();
    }






    // -----------------------------------------------------------
    // testManagerGetsANewAddressAndPassword
    // -----------------------------------------------------------
    //
    // The update form changes both.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testManagerGetsANewAddressAndPassword(): void
    {
        $manager = $this->world->manager(['main' => 0]);

        $this->signInManager($this->admin)->from('/admin-panel/settings/managers')
            ->post('/admin-panel/manager/update', [
                'uid'    => $manager->id,
                'email'  => 'renamed@example.test',
                'pass'   => 'Fresh-Manager-Pass-3',
                'repass' => 'Fresh-Manager-Pass-3',
            ])
            ->assertSessionHas('success');

        $manager = Managers::find($manager->id);
        $this->assertSame('renamed@example.test', $manager->email);
        $this->assertTrue(Hash::check('Fresh-Manager-Pass-3', $manager->password));
    }






    // -----------------------------------------------------------
    // testManagerCountryChangeIsSaved
    // -----------------------------------------------------------
    //
    // The update saves the country the dialog asks for too.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testManagerCountryChangeIsSaved(): void
    {
        $manager = $this->world->manager(['main' => 0, 'country' => 2]);

        $this->signInManager($this->admin)->from('/admin-panel/settings/managers')->post('/admin-panel/manager/update', [
            'uid'     => $manager->id,
            'email'   => $manager->email,
            'pass'    => 'Fresh-Manager-Pass-3',
            'repass'  => 'Fresh-Manager-Pass-3',
            'country' => 4,
        ])->assertSessionHas('success');

        $this->assertSame(4, Managers::find($manager->id)->country);
    }






    // -----------------------------------------------------------
    // testCountryManagerCanBeDeleted
    // -----------------------------------------------------------
    //
    // Deleting a country manager removes them.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testCountryManagerCanBeDeleted(): void
    {
        $manager = $this->world->manager(['main' => 0]);

        $this->signInManager($this->admin)->from('/admin-panel/settings/managers')
            ->post("/admin-panel/settings/manager/delete/{$manager->id}");

        $this->assertNull(Managers::find($manager->id));
    }






    // -----------------------------------------------------------
    // testMainAdminIsNeverDeletedOrSwitchedOff
    // -----------------------------------------------------------
    //
    // The delete and status links pass over main admins:
    // another main admin stays, and stays active.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testMainAdminIsNeverDeletedOrSwitchedOff(): void
    {
        $other = $this->world->manager();

        $this->signInManager($this->admin)->from('/admin-panel/settings/managers')
            ->post("/admin-panel/settings/manager/status/0/{$other->id}");
        $this->signInManager($this->admin)->from('/admin-panel/settings/managers')
            ->post("/admin-panel/settings/manager/delete/{$other->id}");

        $this->assertNotNull(Managers::find($other->id));
        $this->assertEquals(1, Managers::find($other->id)->status);
    }






    // -----------------------------------------------------------
    // testMainAdminIsNeverRewritten
    // -----------------------------------------------------------
    //
    // The update passes over main admins too, which the page
    // never lists: a post naming another main admin's id
    // leaves its address and password as they were.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testMainAdminIsNeverRewritten(): void
    {
        $other = $this->world->manager();

        $this->signInManager($this->admin)->from('/admin-panel/settings/managers')
            ->post('/admin-panel/manager/update', [
                'uid'    => $other->id,
                'email'  => 'main-admin-rewritten@example.test',
                'pass'   => 'Rewritten-Pass-4',
                'repass' => 'Rewritten-Pass-4',
            ]);

        $row = Managers::find($other->id);
        $this->assertSame($other->email, $row->email);
        $this->assertTrue(Hash::check(World::PASSWORD, $row->password));
    }






    // -----------------------------------------------------------
    // create
    // -----------------------------------------------------------
    //
    // Posts the new-manager form, valid unless the given
    // fields say otherwise.
    //
    // Used by:
    //   - the creation tests of this class
    // -----------------------------------------------------------

    private function create(array $fields): TestResponse
    {
        return $this->signInManager($this->admin)->from('/admin-panel/settings/managers')
            ->post('/admin-panel/manager/create', array_merge([
                'email'   => 'new.manager@example.test',
                'pass'    => 'Manager-Pass-1',
                'repass'  => 'Manager-Pass-1',
                'country' => 1,
            ], $fields));
    }
}
