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
//  than a level below the step before it; a level written
//  as text counts as its number, and a file that is no
//  spreadsheet is refused with a message. Editing changes
//  the texts — the description of a simulation without
//  steps too; deleting removes the simulation with
//  everything that hangs off it, and the delete icons of
//  categories and attributes reach their routes. The
//  categories dialog of a deleted simulation stores
//  nothing, and taking the last simulation a member had
//  left off the offer awards badge_all_simulations.
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
    // testStepsAreListedInSheetOrder
    // -----------------------------------------------------------
    //
    // A member's run report, the simulation edit page and the
    // printout list the steps in sheet order, each indented
    // under its own parent. scenario_step_idx keys the steps
    // by simulation, parent and level, and with no order of
    // their own the pages once got them by that key — every
    // first step ahead, the second steps after them in a
    // block — as production did from the keys of 23 October
    // 2026 on (simulation-steps-order). Forty other
    // simulations make MySQL use the key, as it does on a
    // table of production's size.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testStepsAreListedInSheetOrder(): void
    {
        for ($i = 0; $i < 40; $i++) {
            $noise = $this->world->scenario($this->en);
            for ($j = 0; $j < 10; $j++) {
                $this->world->scenarioOption($noise, 0, 1, 0);
            }
        }
        $scenario = $this->world->scenario($this->en, [], [$this->world->scenarioCategory($this->en)]);
        $alpha = $this->world->scenarioOption($scenario, 0, 1, 200, ['situation' => 'Alpha']);
        $this->world->scenarioOption($scenario, $alpha, 2, 100, ['situation' => 'Bravo']);
        $this->world->scenarioOption($scenario, $alpha, 2, 50, ['situation' => 'Charlie']);
        $delta = $this->world->scenarioOption($scenario, 0, 1, 0, ['situation' => 'Delta']);
        $this->world->scenarioOption($scenario, $delta, 2, 0, ['situation' => 'Echo']);
        $run = $this->world->scenarioAttempt($this->world->member()->id, $scenario, $this->en);
        $publicId = DB::table('bei_users_scenarios')->where('id', $run)->value('public_id');

        $pages = [
            'run report' => $this->admin()->get("/admin-panel/user/simulation/report/$publicId")->assertOk()->getContent(),
            'edit page'  => $this->admin()->get("/admin-panel/admin_simulations/edit/$scenario")->assertOk()->getContent(),
            'printout'   => $this->admin()->get('/admin-panel/settings/simulations_download_pdf')->assertOk()->getContent(),
        ];

        foreach ($pages as $page => $html) {
            $positions = array_map(function ($situation) use ($html) {
                return strpos($html, $situation);
            }, ['Alpha', 'Bravo', 'Charlie', 'Delta', 'Echo']);
            $sorted = $positions;
            sort($sorted);
            $this->assertSame($sorted, $positions, "the $page lists the steps out of sheet order");
        }
    }






    // -----------------------------------------------------------
    // testCategoriesDialogStoresTheTickedCategoriesOfItsLanguage
    // -----------------------------------------------------------
    //
    // The categories dialog of a simulation replaces its
    // categories in the language with the ticked ones: every
    // box unticked takes it off them all, where nothing
    // changed once (simulation-categories-clear), and an id
    // of another language's category is not stored — the way
    // six Latvian simulations once ended up on no member page.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testCategoriesDialogStoresTheTickedCategoriesOfItsLanguage(): void
    {
        $first = $this->world->scenarioCategory($this->en);
        $second = $this->world->scenarioCategory($this->en);
        $foreign = $this->world->scenarioCategory($this->world->language('lt'));
        $scenario = $this->world->scenario($this->en, [], [$first]);
        $dialog = "/admin-panel/dialog/simulation/cat/{$this->en}/$scenario";
        $categories = function () use ($scenario) {
            return DB::table('bei_scenarios_in_category')->where('scenario_id', $scenario)->orderBy('cat_id')->pluck('cat_id')->map(function ($id) {
                return (int) $id;
            })->all();
        };

        $this->admin()->from('/admin-panel/admin_simulations')->post($dialog, ['cat' => [$second, $foreign]])->assertSessionHas('success');
        $this->assertSame([$second], $categories(), 'the ticked categories of the language');

        $this->admin()->from('/admin-panel/admin_simulations')->post($dialog, [])->assertSessionHas('success');
        $this->assertSame([], $categories(), 'unticking every category changed nothing');
    }






    // -----------------------------------------------------------
    // testFormsForAGoneLanguageStoreNothing
    // -----------------------------------------------------------
    //
    // A simulation category, an attribute or an import posted
    // for a language that no longer exists — a stale page, an
    // edited form — is refused, where the category and the
    // attribute were once stored under no language, on no tab,
    // to be neither edited nor deleted (unchecked-hidden-ids).
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testFormsForAGoneLanguageStoreNothing(): void
    {
        $posts = [
            '/admin-panel/dialog/simulations/categories/create' => ['uid' => 999999, 'name' => 'Nowhere'],
            '/admin-panel/dialog/simulations/attributes/create' => ['uid' => 999999, 'name' => 'Nowhere'],
            '/admin-panel/admin_simulations/import'             => ['lang_id' => 999999, 'doc' => $this->simulationSheet()],
        ];

        foreach ($posts as $route => $fields) {
            $this->admin()->from('/admin-panel/admin_simulations')->post($route, $fields)
                ->assertRedirect('/admin-panel/admin_simulations')
                ->assertSessionHasErrors();
        }

        foreach (['bei_scenarios_category', 'bei_scenarios_attributes', 'bei_scenarios'] as $table) {
            $this->assertFalse(DB::table($table)->where('lang_id', 999999)->exists(), "$table holds a row of no language");
        }
    }






    // -----------------------------------------------------------
    // testLevelWrittenAsTextIsImported
    // -----------------------------------------------------------
    //
    // A level typed as text with a space after it — "2 ", as
    // a copy from a document leaves it — counts as its
    // number, and the sheet imports with every step under
    // its own parent, one that climbs back up included. Such
    // a level once passed the import's check, PHP reading it
    // as the number, while the save filed each step under its
    // level as written and looked a parent up by the number:
    // a 500 halfway through the saves, and in a sheet that
    // mixed it with plain levels a step under the wrong
    // parent without a word (simulation-import-level-text).
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testLevelWrittenAsTextIsImported(): void
    {
        $this->world->scenarioCategory($this->en, ['name' => 'Credential theft']);
        $this->world->scenarioAttribute($this->en, ['name' => 'Urgency']);
        $sheet = $this->sheetWithSteps([
            58 => ['1 ', 'Check the sender', 'Correct'],
            59 => ['2 ', 'Look at the domain', 'Semi-correct'],
            60 => ['3 ', 'Report it to IT', 'Correct'],
            61 => ['2 ', 'Reply to ask', 'Incorrect'],
        ]);

        $response = $this->admin()->from('/admin-panel/admin_simulations')
            ->post('/admin-panel/admin_simulations/import', ['lang_id' => $this->en, 'doc' => $sheet]);
        $status = $response->getStatusCode();
        $scenario = DB::table('bei_scenarios')->where('descr', 'An urgent invoice arrives')->value('id');
        $steps = DB::table('bei_scenarios_options')->where('scenario_id', $scenario)->orderBy('id')->get(['id', 'parent_option_id', 'level'])->all();

        $this->assertSame(302, $status, 'the import answered with a server error');
        $this->assertCount(4, $steps, 'the steps were not saved');
        $this->assertEquals([1, 2, 3, 2], array_column($steps, 'level'));
        $this->assertEquals([0, $steps[0]->id, $steps[1]->id, $steps[0]->id], array_column($steps, 'parent_option_id'));
    }






    // -----------------------------------------------------------
    // testFileThatIsNoSpreadsheetIsRefusedWithAMessage
    // -----------------------------------------------------------
    //
    // A file named .xlsx that is no spreadsheet — an old .xls
    // renamed, a download cut short, a password-protected
    // workbook, an empty file — sends the import dialog back
    // with a message, and nothing is saved. The import checks
    // only the name's ending before PhpSpreadsheet opens the
    // file, and the reader's exception once reached the admin
    // as a 500 (simulation-import-unreadable).
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testFileThatIsNoSpreadsheetIsRefusedWithAMessage(): void
    {
        $response = $this->admin()->from('/admin-panel/admin_simulations')->post('/admin-panel/admin_simulations/import', [
            'lang_id' => $this->en,
            'doc'     => $this->uploadedFile('simulation.xlsx', "Level;Situation;Answer type\n1;Check the sender;Correct\n"),
        ]);
        $status = $response->getStatusCode();

        $this->assertSame(302, $status, 'the import of an unreadable file answered with a server error');
        $response->assertRedirect('/admin-panel/admin_simulations')->assertSessionHasErrors();
        $this->assertFalse(DB::table('bei_scenarios')->where('lang_id', $this->en)->exists());
    }






    // -----------------------------------------------------------
    // testCategoriesDialogOfADeletedSimulationStoresNothing
    // -----------------------------------------------------------
    //
    // The categories dialog of a simulation deleted since the
    // page was opened — in another tab, or by the other admin
    // — goes back with an error and stores nothing, as the
    // lesson and language forms do since
    // unchecked-hidden-ids, so a category page lists only
    // simulations that exist. The dialog once took the
    // simulation from its address without looking it up and
    // filed it under the ticked categories, where every
    // member found a button with no simulation behind it,
    // "ID:" and nothing else, leading to a 404
    // (simulation-categories-gone).
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testCategoriesDialogOfADeletedSimulationStoresNothing(): void
    {
        $category = $this->world->scenarioCategory($this->en);
        $kept = $this->world->scenario($this->en, [], [$category]);
        $gone = $this->world->scenario($this->en, [], [$category]);
        $this->admin()->from('/admin-panel/admin_simulations')->post("/admin-panel/admin_simulations/delete/$gone");

        $this->admin()->from('/admin-panel/admin_simulations')
            ->post("/admin-panel/dialog/simulation/cat/{$this->en}/$gone", ['cat' => [$category]]);
        $refused = session()->has('errors');
        $links = DB::table('bei_scenarios_in_category')->where('scenario_id', $gone)->count();
        $listed = $this->signInMember($this->world->member())->get("/en/simulations/category/$category")->assertOk()->getContent();

        $this->assertSame(0, $links, 'the deleted simulation was filed under the category');
        $this->assertTrue($refused, 'the dialog of a deleted simulation answered as a save');
        $this->assertStringNotContainsString('/en/simulation/"', $listed, 'the category page shows a button for no simulation');
        $this->assertStringContainsString("/en/simulation/$kept\"", $listed);
    }






    // -----------------------------------------------------------
    // testTakingTheLastSimulationLeftOffTheOfferAwardsTheBadge
    // -----------------------------------------------------------
    //
    // Taking the last simulation a member had left off the
    // offer — deleting it, unticking its categories, or
    // deleting the only category it was in — awards
    // badge_all_simulations of the language to every member
    // it completes, and to no member who still has a
    // simulation on offer unfinished there, as deleting the
    // last lesson a member had left awards
    // badge_all_presentations since lesson-delete-badge. The
    // badge was once awarded only on a run's end page, so a
    // member with every simulation on offer done and the
    // progress share full went without it — and without
    // badge_finished_course and the questionnaire link it
    // opens — until happening to open an end page again
    // (simulation-delete-badge).
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testTakingTheLastSimulationLeftOffTheOfferAwardsTheBadge(): void
    {
        $member = $this->world->member();
        $behind = $this->world->member();
        $languages = ['deleted' => $this->en, 'unticked' => $this->world->language('lt'), 'category deleted' => $this->world->language('lv')];
        $left = [];
        foreach ($languages as $how => $langId) {
            $done = $this->world->scenarioCategory($langId);
            $other = $this->world->scenarioCategory($langId);
            $this->world->scenarioAttempt($member->id, $this->world->scenario($langId, [], [$done]), $langId);
            $left[$how] = [$this->world->scenario($langId, [], [$how === 'category deleted' ? $other : $done]), $other];

            // One more on offer, finished by both members: the
            // member behind still lacks the first one
            $shared = $this->world->scenario($langId, [], [$done]);
            $this->world->scenarioAttempt($member->id, $shared, $langId);
            $this->world->scenarioAttempt($behind->id, $shared, $langId);
        }

        $page = '/admin-panel/admin_simulations';
        $this->admin()->from($page)->post("/admin-panel/admin_simulations/delete/{$left['deleted'][0]}");
        $this->admin()->from($page)->post("/admin-panel/dialog/simulation/cat/{$languages['unticked']}/{$left['unticked'][0]}", []);
        $this->admin()->from('/admin-panel/admin_simulations/categories')->post("/admin-panel/admin_simulations/categores/delete/{$left['category deleted'][1]}");

        $without = [];
        $awarded = [];
        foreach ($languages as $how => $langId) {
            if (!DB::table('bei_users_badges')->where(['user_id' => $member->id, 'lang_id' => $langId, 'name' => 'badge_all_simulations'])->exists()) {
                $without[] = $how;
            }
            if (DB::table('bei_users_badges')->where(['user_id' => $behind->id, 'lang_id' => $langId, 'name' => 'badge_all_simulations'])->exists()) {
                $awarded[] = $how;
            }
        }

        $this->assertSame([], $without, 'the member completed by the change holds no badge_all_simulations');
        $this->assertSame([], $awarded, 'the member with a simulation on offer still unfinished got badge_all_simulations');
    }






    // -----------------------------------------------------------
    // testOfferLeftEmptyAwardsNothing
    // -----------------------------------------------------------
    //
    // A change that leaves the language no simulation on
    // offer awards nothing: a member who finished one of the
    // two simulations of its only category gets no
    // badge_all_simulations when the category is deleted, nor
    // badge_finished_course on the next member page, as the
    // progress then counts no simulation share and a member
    // who completed none of the lessons gets no lessons badge.
    // Counted over every member with a finished run, the
    // award would have found each of them done with an empty
    // offer.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testOfferLeftEmptyAwardsNothing(): void
    {
        $category = $this->world->scenarioCategory($this->en);
        $member = $this->world->member();
        $this->world->scenarioAttempt($member->id, $this->world->scenario($this->en, [], [$category]), $this->en);
        $this->world->scenario($this->en, [], [$category]);
        $this->world->badge($member->id, $this->en, 'badge_all_presentations');

        $this->admin()->from('/admin-panel/admin_simulations/categories')
            ->post("/admin-panel/admin_simulations/categores/delete/$category")->assertRedirect();
        $this->signInMember($member)->get('/cp/badges')->assertOk();

        $this->assertSame([], DB::table('bei_users_badges')->where('user_id', $member->id)->whereIn('name', ['badge_all_simulations', 'badge_finished_course'])->pluck('name')->all());
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






    // -----------------------------------------------------------
    // sheetWithSteps
    // -----------------------------------------------------------
    //
    // A simulation spreadsheet with the texts, the category
    // and the attribute of Sheets::simulationSheet and the
    // given steps instead of its three: per sheet row the
    // level as given — a string stays text in the sheet — the
    // situation and the answer type. simulationSheet writes
    // its own steps over any cells given for their rows, so
    // it cannot carry these.
    //
    // Used by:
    //   - testLevelWrittenAsTextIsImported (above)
    // -----------------------------------------------------------

    private function sheetWithSteps(array $steps): UploadedFile
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
        foreach ($steps as $row => list($level, $situation, $answer)) {
            $sheet->setCellValue("A$row", $level);
            $sheet->setCellValue("C$row", $situation);
            $sheet->setCellValue("D$row", $answer);
        }

        $path = tempnam(sys_get_temp_dir(), 'sheet');
        (new Xlsx($spreadsheet))->save($path);
        return $this->uploadedFile('simulation.xlsx', file_get_contents($path));
    }
}
