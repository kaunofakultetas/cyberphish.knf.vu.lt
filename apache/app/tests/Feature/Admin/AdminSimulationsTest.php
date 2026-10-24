<?php

// -----------------------------------------------------------
//  [*] AdminSimulationsTest — main admins building simulations
//
//  The simulation pages of the panel (Admin's
//  SimulationsController): categories and attributes per
//  language, and the import that turns the project's
//  simulation spreadsheet into a simulation — its texts in
//  C19 to C30, the categories and attributes marked "Yes"
//  (categories in rows 34 to 40, attributes in rows 34 to
//  53, found by name), and its steps in rows 58 to 141:
//  level, situation, answer type (Incorrect,
//  Semi-incorrect, Semi-correct, Correct — 0, 50, 100 and
//  200 points) and feedback, each step hanging under the
//  last step one level up. The names are looked up in the
//  import's language only, and a sheet marking a category
//  or attribute the language lacks is refused before
//  anything is saved, as is one with a blank description,
//  or a step without a level or an answer type, or more
//  than a level below the step before it. Editing changes
//  the texts — the description of a simulation without
//  steps too; deleting removes the simulation with
//  everything that hangs off it, and the delete icons of
//  categories and attributes reach their routes.
//
//  Used by:
//    - PHPUnit — the Feature suite (runTests.sh)
// -----------------------------------------------------------


namespace Tests\Feature\Admin;

use Illuminate\Support\Facades\DB;
use Tests\FeatureTestCase;
use Tests\Support\Sheets;








// -----------------------------------------------------------
// AdminSimulationsTest
// -----------------------------------------------------------
//
// A main admin and English for every test; forms are posted
// from the page that holds them.
//
// Used by:
//   - PHPUnit — the Feature suite (runTests.sh)
// -----------------------------------------------------------

class AdminSimulationsTest extends FeatureTestCase
{

    // The simulation spreadsheet of the import
    use Sheets;

    // The English id and the main admin of the test running
    // now
    private $en;
    private $admin;






    // -----------------------------------------------------------
    // setUp
    // -----------------------------------------------------------
    //
    // English and a main admin.
    //
    // Used by:
    //   - PHPUnit, before every test
    // -----------------------------------------------------------

    protected function setUp(): void
    {
        parent::setUp();
        $this->en = $this->world->language('en');
        $this->admin = $this->world->manager();
    }






    // -----------------------------------------------------------
    // testCategoryIsCreatedRenamedAndDeleted
    // -----------------------------------------------------------
    //
    // A simulation category of a language is created, renamed
    // and deleted.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testCategoryIsCreatedRenamedAndDeleted(): void
    {
        $page = '/admin-panel/admin_simulations/categories';
        $this->admin()->from($page)->post('/admin-panel/dialog/simulations/categories/create', ['name' => 'Vishing', 'uid' => $this->en]);
        $id = DB::table('bei_scenarios_category')->where('name', 'Vishing')->value('id');
        $this->assertEquals($this->en, DB::table('bei_scenarios_category')->where('id', $id)->value('lang_id'));

        $this->admin()->from($page)->post('/admin-panel/dialog/simulations/categories/update', ['uid' => $id, 'name' => 'Voice phishing']);
        $this->assertSame('Voice phishing', DB::table('bei_scenarios_category')->where('id', $id)->value('name'));

        $this->admin()->from($page)->post("/admin-panel/admin_simulations/categores/delete/$id");
        $this->assertFalse(DB::table('bei_scenarios_category')->where('id', $id)->exists());
    }






    // -----------------------------------------------------------
    // testAttributeIsCreatedRenamedAndDeleted
    // -----------------------------------------------------------
    //
    // The same for a simulation attribute.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testAttributeIsCreatedRenamedAndDeleted(): void
    {
        $page = '/admin-panel/admin_simulations/attributes';
        $this->admin()->from($page)->post('/admin-panel/dialog/simulations/attributes/create', ['name' => 'Authority', 'uid' => $this->en]);
        $id = DB::table('bei_scenarios_attributes')->where('name', 'Authority')->value('id');

        $this->admin()->from($page)->post('/admin-panel/dialog/simulations/attributes/update', ['uid' => $id, 'name' => 'Fake authority']);
        $this->assertSame('Fake authority', DB::table('bei_scenarios_attributes')->where('id', $id)->value('name'));

        $this->admin()->from($page)->post("/admin-panel/admin_simulations/attributes/delete/$id");
        $this->assertFalse(DB::table('bei_scenarios_attributes')->where('id', $id)->exists());
    }






    // -----------------------------------------------------------
    // testDeleteIconsDelete
    // -----------------------------------------------------------
    //
    // The delete icons of the categories and attributes pages
    // send Confirm_Dialog the routes under admin_simulations,
    // which delete the row.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testDeleteIconsDelete(): void
    {
        $category = $this->world->scenarioCategory($this->en);
        $attribute = $this->world->scenarioAttribute($this->en);

        $this->admin()->post($this->deleteLink('/admin-panel/admin_simulations/categories', $category));
        $this->admin()->post($this->deleteLink('/admin-panel/admin_simulations/attributes', $attribute));

        $this->assertFalse(DB::table('bei_scenarios_category')->where('id', $category)->exists());
        $this->assertFalse(DB::table('bei_scenarios_attributes')->where('id', $attribute)->exists());
    }






    // -----------------------------------------------------------
    // testSpreadsheetBecomesASimulation
    // -----------------------------------------------------------
    //
    // The imported simulation has the sheet's texts, its
    // category and attribute, and its three steps with their
    // points, types and places in the tree.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testSpreadsheetBecomesASimulation(): void
    {
        $category = $this->world->scenarioCategory($this->en, ['name' => 'Credential theft']);
        $attribute = $this->world->scenarioAttribute($this->en, ['name' => 'Urgency']);

        $this->admin()->from('/admin-panel/admin_simulations')->post('/admin-panel/admin_simulations/import', [
            'lang_id' => $this->en,
            'doc'     => $this->simulationSheet(),
        ])->assertSessionHas('success');

        $scenario = DB::table('bei_scenarios')->where('descr', 'An urgent invoice arrives')->first();
        $this->assertSame('Spot the fake', $scenario->goal);
        $this->assertSame('Finance clerk', $scenario->actors);
        $this->assertEquals($this->en, $scenario->lang_id);
        $this->assertTrue(DB::table('bei_scenarios_in_category')->where(['scenario_id' => $scenario->id, 'cat_id' => $category])->exists());
        $this->assertTrue(DB::table('bei_scenarios_in_attributes')->where(['scenario_id' => $scenario->id, 'attribute_id' => $attribute])->exists());

        $steps = DB::table('bei_scenarios_options')->where('scenario_id', $scenario->id)->orderBy('id')->get();
        $this->assertCount(3, $steps);
        list($check, $report, $click) = $steps;
        $this->assertEquals([1, 0, 200, 3], [$check->level, $check->parent_option_id, $check->points, $check->option_type]);
        $this->assertEquals([2, $check->id, 100, 2], [$report->level, $report->parent_option_id, $report->points, $report->option_type]);
        $this->assertEquals([1, 0, 0, 0], [$click->level, $click->parent_option_id, $click->points, $click->option_type]);
        $this->assertSame('Good: the domain is wrong', $check->feedback);
    }






    // -----------------------------------------------------------
    // testFailedImportLeavesNothingBehind
    // -----------------------------------------------------------
    //
    // A sheet that marks a category no row of the language
    // has goes back with an error, and leaves no simulation
    // behind.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testFailedImportLeavesNothingBehind(): void
    {
        $this->world->scenarioAttribute($this->en, ['name' => 'Urgency']);

        $this->admin()->from('/admin-panel/admin_simulations')->post('/admin-panel/admin_simulations/import', [
            'lang_id' => $this->en,
            'doc'     => $this->simulationSheet(['C34' => 'A category nobody created']),
        ])->assertSessionHasErrors();

        $this->assertFalse(DB::table('bei_scenarios')->where('descr', 'An urgent invoice arrives')->exists());
    }






    // -----------------------------------------------------------
    // testImportWithoutAFileSaysSo
    // -----------------------------------------------------------
    //
    // An import posted without a spreadsheet goes back to the
    // panel with "No file was uploaded!", as the lesson-file
    // form does, where it once answered with a 500.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testImportWithoutAFileSaysSo(): void
    {
        $this->admin()->from('/admin-panel/admin_simulations')->post('/admin-panel/admin_simulations/import', ['lang_id' => $this->en])
            ->assertRedirect('/admin-panel/admin_simulations')
            ->assertSessionHasErrors();

        $this->assertSame('No file was uploaded!', session('errors')->first());
    }






    // -----------------------------------------------------------
    // testSheetWithBlankCellsIsRefusedWithAMessage
    // -----------------------------------------------------------
    //
    // A sheet without a description, or with a step that has
    // no level, no answer type, or a level more than one below
    // the step before it, goes back with a message naming the
    // cell or the step's row, and nothing is saved. The first
    // three were 500s halfway through the
    // saves — the NOT NULL description, the parent of level
    // -1, an undefined answer type — and the last step was
    // saved where no run could reach it.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testSheetWithBlankCellsIsRefusedWithAMessage(): void
    {
        $this->world->scenarioCategory($this->en, ['name' => 'Credential theft']);
        $this->world->scenarioAttribute($this->en, ['name' => 'Urgency']);
        $cases = [
            'no description'                 => [['C19' => null], 'The description in C19 is empty!'],
            'a step without a level'         => [['C61' => 'Forward it', 'D61' => 'Incorrect'], 'Row 61: the step needs a level of 1 or more in column A!'],
            'a step without an answer type'  => [['A61' => 1, 'C61' => 'Forward it'], 'Row 61: the answer type in column D must be Correct, Semi-correct, Semi-incorrect or Incorrect!'],
            'a step two levels below the last' => [['A61' => 3, 'C61' => 'Forward it', 'D61' => 'Incorrect'], 'Row 61: the step is more than one level below the step before it!'],
        ];

        foreach ($cases as $case => list($cells, $message)) {
            $response = $this->admin()->from('/admin-panel/admin_simulations')
                ->post('/admin-panel/admin_simulations/import', ['lang_id' => $this->en, 'doc' => $this->simulationSheet($cells)]);

            $this->assertLessThan(500, $response->getStatusCode(), "$case: a server error");
            $response->assertRedirect('/admin-panel/admin_simulations')->assertSessionHasErrors();
            $this->assertSame($message, session('errors')->first(), $case);
        }
        $this->assertFalse(DB::table('bei_scenarios')->where('lang_id', $this->en)->exists());
    }






    // -----------------------------------------------------------
    // testImportFilesUnderTheCategoriesOfItsLanguage
    // -----------------------------------------------------------
    //
    // Where another language has a category and an attribute
    // of the same names, the import files its simulation under
    // its own language's.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testImportFilesUnderTheCategoriesOfItsLanguage(): void
    {
        // The Lithuanian rows go in first: a lookup that
        // ignored the language would meet them before the
        // English ones
        $lithuanian = $this->world->language('lt');
        $this->world->scenarioCategory($lithuanian, ['name' => 'Smishing']);
        $this->world->scenarioAttribute($lithuanian, ['name' => 'Spoofing']);
        $category = $this->world->scenarioCategory($this->en, ['name' => 'Smishing']);
        $attribute = $this->world->scenarioAttribute($this->en, ['name' => 'Spoofing']);

        $this->admin()->from('/admin-panel/admin_simulations')->post('/admin-panel/admin_simulations/import', [
            'lang_id' => $this->en,
            'doc'     => $this->simulationSheet(['C34' => 'Smishing', 'E34' => 'Spoofing']),
        ])->assertSessionHas('success');

        $scenario = DB::table('bei_scenarios')->where('descr', 'An urgent invoice arrives')->value('id');
        $this->assertTrue(DB::table('bei_scenarios_in_category')->where(['scenario_id' => $scenario, 'cat_id' => $category])->exists());
        $this->assertTrue(DB::table('bei_scenarios_in_attributes')->where(['scenario_id' => $scenario, 'attribute_id' => $attribute])->exists());
    }






    // -----------------------------------------------------------
    // testOnlySpreadsheetsAreImported
    // -----------------------------------------------------------
    //
    // Any extension but xlsx is refused and nothing is
    // created.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testOnlySpreadsheetsAreImported(): void
    {
        $this->admin()->from('/admin-panel/admin_simulations')->post('/admin-panel/admin_simulations/import', [
            'lang_id' => $this->en,
            'doc'     => $this->uploadedFile('simulation.csv', "level;situation\n1;Check\n"),
        ])->assertSessionHasErrors();

        $this->assertFalse(DB::table('bei_scenarios')->where('lang_id', $this->en)->exists());
    }






    // -----------------------------------------------------------
    // testSimulationIsEditedAndDeleted
    // -----------------------------------------------------------
    //
    // The edit form rewrites the description and a step's
    // situation and feedback; deleting removes the simulation,
    // its steps, its category links and the members' runs.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testSimulationIsEditedAndDeleted(): void
    {
        $category = $this->world->scenarioCategory($this->en);
        $scenario = $this->world->scenario($this->en, ['descr' => 'Old description'], [$category]);
        $step = $this->world->scenarioOption($scenario, 0, 1, 200, ['situation' => 'Old step']);
        $this->world->scenarioAttempt($this->world->member()->id, $scenario, $this->en);

        $this->admin()->get("/admin-panel/admin_simulations/edit/$scenario")->assertOk()->assertSee('Old step');
        $this->admin()->from("/admin-panel/admin_simulations/edit/$scenario")->post("/admin-panel/admin_simulations/update/$scenario", [
            'situation' => 'New description',
            'option'    => [$step => 'New step'],
            'feedback'  => [$step => 'New feedback'],
        ]);
        $this->assertSame('New description', DB::table('bei_scenarios')->where('id', $scenario)->value('descr'));
        $this->assertSame('New feedback', DB::table('bei_scenarios_options')->where('id', $step)->value('feedback'));

        $this->admin()->from('/admin-panel/admin_simulations')->post("/admin-panel/admin_simulations/delete/$scenario");
        $this->assertFalse(DB::table('bei_scenarios')->where('id', $scenario)->exists());
        $this->assertFalse(DB::table('bei_scenarios_options')->where('scenario_id', $scenario)->exists());
        $this->assertFalse(DB::table('bei_scenarios_in_category')->where('scenario_id', $scenario)->exists());
        $this->assertFalse(DB::table('bei_users_scenarios')->where('scenario_id', $scenario)->exists());
    }






    // -----------------------------------------------------------
    // testDescriptionOfASimulationWithoutStepsIsSaved
    // -----------------------------------------------------------
    //
    // Editing a simulation without steps — its edit page posts
    // no steps at all — saves the new description.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testDescriptionOfASimulationWithoutStepsIsSaved(): void
    {
        $scenario = $this->world->scenario($this->en, ['descr' => 'Description before the edit']);

        $this->admin()->from("/admin-panel/admin_simulations/edit/$scenario")
            ->post("/admin-panel/admin_simulations/update/$scenario", ['situation' => 'Description after the edit'])
            ->assertSessionHas('success');

        $this->assertSame('Description after the edit', DB::table('bei_scenarios')->where('id', $scenario)->value('descr'));
    }






    // -----------------------------------------------------------
    // admin
    // -----------------------------------------------------------
    //
    // The test signed in as its main admin.
    //
    // Used by:
    //   - the tests of this class
    //   - deleteLink (below)
    // -----------------------------------------------------------

    private function admin(): self
    {
        return $this->signInManager($this->admin);
    }






    // -----------------------------------------------------------
    // deleteLink
    // -----------------------------------------------------------
    //
    // The path the delete icon of a row opens — the pages hand
    // the icon's URL to Confirm_Dialog. Fails the test when
    // the page has no icon for the row.
    //
    // Used by:
    //   - testDeleteIconsDelete (above)
    // -----------------------------------------------------------

    private function deleteLink(string $page, int $id): string
    {
        $html = $this->admin()->get($page)->assertOk()->getContent();
        $this->assertSame(1, preg_match("#Confirm_Dialog\\('([^']*/delete/$id)'\\)#", $html, $link), "no delete icon for row $id on $page");
        return parse_url($link[1], PHP_URL_PATH);
    }
}
