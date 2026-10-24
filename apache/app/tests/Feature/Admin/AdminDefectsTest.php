<?php

// -----------------------------------------------------------
//  [*] AdminDefectsTest — known defects of the admin panel
//
//  Each test states the behaviour the panel should have and
//  pins the defect that breaks it today (KnownDefects): the
//  run reports them as incomplete, tagged with their ids,
//  until a fix makes one pass — then the test fails as the
//  cue to remove its pin and move it into its home file.
//
//    manager-edit-country         — the manager edit dialog
//                                   never shows the stored
//                                   country, so a save
//                                   overwrites it
//
//  Used by:
//    - PHPUnit — the Feature suite (runTests.sh)
// -----------------------------------------------------------


namespace Tests\Feature\Admin;

use Tests\FeatureTestCase;








// -----------------------------------------------------------
// AdminDefectsTest
// -----------------------------------------------------------
//
// One pinned defect per test, named in the header; a main
// admin for every test.
//
// Used by:
//   - PHPUnit — the Feature suite (runTests.sh)
// -----------------------------------------------------------

class AdminDefectsTest extends FeatureTestCase
{

    // The main admin of the test running now
    private $admin;






    // -----------------------------------------------------------
    // setUp
    // -----------------------------------------------------------
    //
    // A main admin.
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
    // testManagerEditDialogShowsTheStoredCountry
    // -----------------------------------------------------------
    //
    // The edit dialog gets only the manager's address, so its
    // required country select opens on the placeholder: the
    // main admin must pick some country to save a new
    // password, and that pick replaces the stored one — a
    // Greek manager reset this way reads Lithuania's members.
    // Wanted: the dialog opens on the stored country.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testManagerEditDialogShowsTheStoredCountry(): void
    {
        $manager = $this->world->manager(['main' => 0, 'country' => 3]);

        $html = $this->signInManager($this->admin)->get("/admin-panel/dialog/manager/edit/{$manager->id}")->assertOk()->getContent();

        $this->pinDefect('manager-edit-country', function () use ($html) {
            $this->assertMatchesRegularExpression('#<option value="3" selected="selected">#', $html);
        });
    }
}
