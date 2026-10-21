<?php

// -----------------------------------------------------------
//  [*] AdminSimulationsTest — main admins building simulations
//
//  The simulation pages of the panel (Admin's
//  SimulationsController): categories and attributes per
//  language, and the import that turns the project's
//  simulation spreadsheet into a simulation — its texts in
//  C19 to C30, the categories and attributes marked "Yes"
//  in rows 34 to 53 (found by name), and its steps from row
//  58 down: level, situation, answer type (Incorrect,
//  Semi-incorrect, Semi-correct, Correct — 0, 50, 100 and
//  200 points) and feedback, each step hanging under the
//  last step one level up. Editing changes the texts;
//  deleting removes the simulation with everything that
//  hangs off it.
//
//  Used by:
//    - PHPUnit — the Feature suite (runTests.sh)
// -----------------------------------------------------------


namespace Tests\Feature\Admin;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\FeatureTestCase;








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

        $this->admin()->from($page)->get("/admin-panel/admin_simulations/categores/delete/$id");
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

        $this->admin()->from($page)->get("/admin-panel/admin_simulations/attributes/delete/$id");
        $this->assertFalse(DB::table('bei_scenarios_attributes')->where('id', $id)->exists());
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

        $this->admin()->from('/admin-panel/admin_simulations')->get("/admin-panel/admin_simulations/delete/$scenario");
        $this->assertFalse(DB::table('bei_scenarios')->where('id', $scenario)->exists());
        $this->assertFalse(DB::table('bei_scenarios_options')->where('scenario_id', $scenario)->exists());
        $this->assertFalse(DB::table('bei_scenarios_in_category')->where('scenario_id', $scenario)->exists());
        $this->assertFalse(DB::table('bei_users_scenarios')->where('scenario_id', $scenario)->exists());
    }






    // -----------------------------------------------------------
    // simulationSheet
    // -----------------------------------------------------------
    //
    // A simulation spreadsheet in the project's layout: the
    // texts, the category "Credential theft" and the attribute
    // "Urgency" marked Yes, and three steps — checking the
    // sender (Correct) with reporting it under it
    // (Semi-correct), and clicking the link (Incorrect).
    //
    // Used by:
    //   - testSpreadsheetBecomesASimulation (above)
    // -----------------------------------------------------------

    private function simulationSheet(): UploadedFile
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $cells = [
            'C19' => 'An urgent invoice arrives',
            'C20' => 'Spot the fake',
            'C21' => 'Finance clerk',
            'C22' => 'Email',
            'C26' => '1',
            'C27' => 'Phishing',
            'C30' => 'invoice',
            'B34' => 'Yes',
            'C34' => 'Credential theft',
            'D34' => 'Yes',
            'E34' => 'Urgency',
        ];
        foreach ($cells as $cell => $value) {
            $sheet->setCellValue($cell, $value);
        }
        $steps = [
            58 => [1, 'Check the sender', 'Correct', 'Good: the domain is wrong'],
            59 => [2, 'Report it to IT', 'Semi-correct', 'IT blocks the sender'],
            60 => [1, 'Click the link', 'Incorrect', 'That installs malware'],
        ];
        foreach ($steps as $row => list($level, $situation, $answer, $feedback)) {
            $sheet->setCellValue("A$row", $level);
            $sheet->setCellValue("C$row", $situation);
            $sheet->setCellValue("D$row", $answer);
            $sheet->setCellValue("E$row", $feedback);
        }

        $path = tempnam(sys_get_temp_dir(), 'sheet');
        (new Xlsx($spreadsheet))->save($path);
        return $this->uploadedFile('simulation.xlsx', file_get_contents($path));
    }






    // -----------------------------------------------------------
    // admin
    // -----------------------------------------------------------
    //
    // The test signed in as its main admin.
    //
    // Used by:
    //   - the tests of this class
    // -----------------------------------------------------------

    private function admin(): self
    {
        return $this->signInManager($this->admin);
    }
}
