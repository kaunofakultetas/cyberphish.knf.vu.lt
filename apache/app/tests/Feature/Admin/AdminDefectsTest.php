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
//    simulation-steps-order            — run reports and the
//                                       simulation edit page
//                                       list steps out of
//                                       sheet order
//    new-manager-dialog-preselects-own-country
//                                      — the new-manager dialog
//                                        opens on the signed-in
//                                        admin's own country
//    question-import-partial           — a question line
//                                       without its marker is
//                                       a 500, half the file
//                                       stored
//    selfeval-import-carryover         — a self-evaluation
//                                       block without a
//                                       question line adds its
//                                       options to the question
//                                       before it
//    selfeval-points-comma             — option points typed
//                                       with a decimal comma
//                                       are cut to whole
//                                       numbers
//    simulation-categories-clear       — unticking every
//                                       category of a
//                                       simulation changes
//                                       nothing
//    unchecked-hidden-ids              — forms for a gone
//                                       lesson or language are
//                                       a 500 or store rows no
//                                       page lists
//    simulation-share-counts-unoffered — the dashboard counts
//                                       simulations no member
//                                       page offers
//    admin-change-pass-array-500       — the panel's password
//                                       dialog posted with a
//                                       list is a 500
//
//  Used by:
//    - PHPUnit — the Feature suite (runTests.sh)
// -----------------------------------------------------------


namespace Tests\Feature\Admin;

use Illuminate\Support\Facades\DB;
use Tests\FeatureTestCase;
use Tests\Support\World;








// -----------------------------------------------------------
// AdminDefectsTest
// -----------------------------------------------------------
//
// One pinned defect per test, named in the header; English
// and a main admin for every test.
//
// Used by:
//   - PHPUnit — the Feature suite (runTests.sh)
// -----------------------------------------------------------

class AdminDefectsTest extends FeatureTestCase
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
    // testStepsAreListedInSheetOrder
    // -----------------------------------------------------------
    //
    // A member's run report, the simulation edit page and the
    // printout list a simulation's steps as the database
    // returns them, with no order, and indent each by its
    // level: since scenario_step_idx keys the steps by
    // simulation, parent and level, MySQL returns them sorted
    // by parent, so every first step comes first and the
    // second steps follow in a block, indented under the wrong
    // parent. Production returns them that way since the keys
    // of 23 October 2026. Wanted: the steps in the sheet's
    // order, each under its own parent.
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
            'run report' => $this->signInManager($this->admin)->get("/admin-panel/user/simulation/report/$publicId")->assertOk()->getContent(),
            'edit page'  => $this->signInManager($this->admin)->get("/admin-panel/admin_simulations/edit/$scenario")->assertOk()->getContent(),
        ];

        $this->pinDefect('simulation-steps-order', function () use ($pages) {
            foreach ($pages as $page => $html) {
                $positions = array_map(function ($situation) use ($html) {
                    return strpos($html, $situation);
                }, ['Alpha', 'Bravo', 'Charlie', 'Delta', 'Echo']);
                $sorted = $positions;
                sort($sorted);
                $this->assertSame($sorted, $positions, "the $page lists the steps out of sheet order");
            }
        });
    }






    // -----------------------------------------------------------
    // testNewManagerDialogOpensOnNoCountry
    // -----------------------------------------------------------
    //
    // The manager dialog selects $country, which the edit
    // dialog gets from the manager; the new-manager dialog
    // gets none, so the view falls back to the country
    // Admin\BaseController shares from the session — the
    // signed-in admin's own. The required select no longer
    // asks for a choice, and a main admin who leaves it
    // creates a manager of their own country, who reads that
    // country's members. Wanted: the new-manager dialog opens
    // on the placeholder.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testNewManagerDialogOpensOnNoCountry(): void
    {
        $greekAdmin = $this->world->manager(['country' => 3]);

        $html = $this->signInManager($greekAdmin)->get('/admin-panel/dialog/manager/new')->assertOk()->getContent();

        $this->pinDefect('new-manager-dialog-preselects-own-country', function () use ($html) {
            $this->assertDoesNotMatchRegularExpression('#<option value="\d+" selected="selected">#', $html, 'the new-manager dialog opens on a country');
        });
    }






    // -----------------------------------------------------------
    // testQuestionImportWithABadLineStoresNothing
    // -----------------------------------------------------------
    //
    // Both question imports take any line that starts with Q,
    // K, J or the Greek epsilon for a question line and read
    // its text after the first backslash-colon without
    // checking one is there — a typo, a wrapped line or an
    // option starting with such a letter is an undefined
    // array key, a 500, with the blocks before it already
    // stored, and the corrected file then stores them twice.
    // Wanted: the file is refused with a message and nothing
    // of it is stored.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testQuestionImportWithABadLineStoresNothing(): void
    {
        $category = $this->world->lmCategory($this->en);
        $file = "::\nQ\\: First question?\n=Yes\n~No\n::\nQ: Second question, without its marker?\n=Yes\n~No\n";
        $results = [];
        foreach (['self_eval' => 'bei_selfeval_question', 'knowledge_test' => 'bei_knowledge_test'] as $import => $table) {
            $status = $this->signInManager($this->admin)->from("/admin-panel/lm/$import/$category")->post("/admin-panel/lm/$import/import", [
                'cat_id' => $category,
                'doc'    => $this->uploadedFile('questions.txt', $file),
            ])->getStatusCode();
            $results[$import] = [$status < 500, DB::table($table)->where('cat_id', $category)->count()];
        }

        $this->pinDefect('question-import-partial', function () use ($results) {
            $this->assertSame(['self_eval' => [true, 0], 'knowledge_test' => [true, 0]], $results, 'an import answered with a server error, or stored part of the file');
        });
    }






    // -----------------------------------------------------------
    // testSelfEvaluationBlockWithoutAQuestionStoresNoOptions
    // -----------------------------------------------------------
    //
    // The self-evaluation import keeps the last question it
    // stored from block to block: a block whose question line
    // it does not recognise — a Latin E where the Greek
    // epsilon belongs, a leading space — adds its options to
    // the question before it, whose type turns to multiple
    // choice, and the import still says "Questions
    // imported!". The knowledge import starts every block
    // with no question (knowledge-import-markers). Wanted: a
    // block without a question line stores none of its
    // options.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testSelfEvaluationBlockWithoutAQuestionStoresNoOptions(): void
    {
        $category = $this->world->lmCategory($this->en);
        $file = "::\nQ\\: First question?\n=Right\n~Wrong\n::\nE\\: Second question, with a Latin E?\n=Right too\n=Also right\n~Wrong too\n";

        $this->signInManager($this->admin)->from("/admin-panel/lm/self_eval/$category")->post('/admin-panel/lm/self_eval/import', [
            'cat_id' => $category,
            'doc'    => $this->uploadedFile('questions.txt', $file),
        ]);
        $first = DB::table('bei_selfeval_question')->where('cat_id', $category)->orderBy('id')->first();

        $this->pinDefect('selfeval-import-carryover', function () use ($first) {
            $this->assertSame(2, DB::table('bei_selfeval_option')->where('question_id', $first->id)->count(), 'the first question took the next block\'s options');
            $this->assertEquals(1, $first->q_type);
        });
    }






    // -----------------------------------------------------------
    // testOptionPointsWithADecimalCommaAreNotCut
    // -----------------------------------------------------------
    //
    // The question edit form takes each option's points in a
    // plain text field and stores them as posted; MySQL, its
    // strict mode off, takes "33,33" for 33 with only a
    // warning, and three such right options score 99 again —
    // the hours of a lesson got the comma read as a point
    // (lesson-hours-comma). Wanted: the comma read as a
    // decimal point, or the value refused.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testOptionPointsWithADecimalCommaAreNotCut(): void
    {
        $question = $this->world->selfEvalQuestion($this->world->lmCategory($this->en), [
            ['First', true, 33.33], ['Second', true, 33.33], ['Third', true, 33.33], ['Wrong', false, 0],
        ]);
        list($first, $second, $third, $wrong) = $question['options'];

        $this->signInManager($this->admin)->from("/admin-panel/lm/self_eval/edit/{$question['question']}")->post("/admin-panel/lm/self_eval/update/{$question['question']}", [
            'q'       => 'Which three are right?',
            'q_type'  => 2,
            'option'  => [$first => 'First', $second => 'Second', $third => 'Third', $wrong => 'Wrong'],
            'correct' => [$first => 1, $second => 1, $third => 1, $wrong => 0],
            'points'  => [$first => '33,33', $second => '33,33', $third => '33,33', $wrong => '0'],
        ]);
        $points = DB::table('bei_selfeval_option')->whereIn('id', [$first, $second, $third])->orderBy('id')->pluck('points')->map(function ($value) {
            return (float) $value;
        })->all();

        $this->pinDefect('selfeval-points-comma', function () use ($points) {
            $this->assertSame([33.33, 33.33, 33.33], $points);
        });
    }






    // -----------------------------------------------------------
    // testUntickingEveryCategoryTakesTheSimulationOff
    // -----------------------------------------------------------
    //
    // The categories dialog replaces a simulation's categories
    // with the ticked ones only when one is ticked: with every
    // box unticked it answers "Updated!" and keeps them all,
    // so no simulation can be taken off every category — the
    // way to stop offering it to members without deleting
    // their runs. Wanted: unticking every category takes the
    // simulation off them.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testUntickingEveryCategoryTakesTheSimulationOff(): void
    {
        $scenario = $this->world->scenario($this->en, [], [$this->world->scenarioCategory($this->en)]);

        $this->signInManager($this->admin)->from('/admin-panel/admin_simulations')->post("/admin-panel/dialog/simulation/cat/{$this->en}/$scenario", [])
            ->assertSessionHas('success');

        $this->pinDefect('simulation-categories-clear', function () use ($scenario) {
            $this->assertFalse(DB::table('bei_scenarios_in_category')->where('scenario_id', $scenario)->exists(), 'unticking every category changed nothing');
        });
    }






    // -----------------------------------------------------------
    // testFormsForAGoneRowStoreNothing
    // -----------------------------------------------------------
    //
    // Several forms take their lesson or language from a
    // hidden field and check nothing: a file added on a stale
    // page of a lesson deleted meanwhile is moved into
    // upload/lm before its row's foreign key refuses it — a
    // 500, the file left behind — a lesson category for a
    // language that is gone is a 500, and a simulation
    // category for one is stored where no tab lists it, to be
    // neither edited nor deleted. Only the news and the
    // information pages check theirs (update-lang-unchecked).
    // Wanted: no server error, and nothing stored.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testFormsForAGoneRowStoreNothing(): void
    {
        $gone = $this->world->lmContent($this->world->lmCategory($this->en), $this->en);
        DB::table('bei_lm_content')->where('id', $gone)->delete();
        $before = glob(public_path('upload/lm/*')) ?: [];

        $statuses = [
            'file of a gone lesson'           => $this->signInManager($this->admin)->from("/admin-panel/lm/files/$gone")->post('/admin-panel/lm/file/create', [
                'uid' => $gone, 'name' => 'Checklist', 'embed' => 0, 'doc' => $this->uploadedFile('checklist.pdf', "%PDF-1.4\n%test\n"),
            ])->getStatusCode(),
            'lesson category of no language'  => $this->signInManager($this->admin)->from('/admin-panel/lm/categories')->post('/admin-panel/dialog/lm/categories/create', [
                'uid' => 999999, 'name' => 'Nowhere',
            ])->getStatusCode(),
            'simulation category of no language' => $this->signInManager($this->admin)->from('/admin-panel/admin_simulations/categories')->post('/admin-panel/dialog/simulations/categories/create', [
                'uid' => 999999, 'name' => 'Nowhere',
            ])->getStatusCode(),
        ];
        $added = array_diff(glob(public_path('upload/lm/*')) ?: [], $before);
        foreach ($added as $path) {
            @unlink($path);
        }
        $stored = DB::table('bei_lm_category')->where('lang_id', 999999)->count() + DB::table('bei_scenarios_category')->where('lang_id', 999999)->count();

        $this->pinDefect('unchecked-hidden-ids', function () use ($statuses, $added, $stored) {
            $this->assertSame([], array_keys(array_filter($statuses, function (int $status) {
                return $status >= 500;
            })), 'forms answered with a server error');
            $this->assertSame([], array_values($added), 'the upload was left behind');
            $this->assertSame(0, $stored, 'a category was stored under no language');
        });
    }






    // -----------------------------------------------------------
    // testDashboardCountsTheSimulationsMembersAreOffered
    // -----------------------------------------------------------
    //
    // A dashboard row's simulations figure sets the distinct
    // simulations the member began against every simulation
    // of the language, one filed under no category included —
    // a member who finished all there is to reach reads as
    // short of them, as the progress does
    // (LearningDefectsTest). Wanted: the simulations on offer.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testDashboardCountsTheSimulationsMembersAreOffered(): void
    {
        $offered = $this->world->scenario($this->en, [], [$this->world->scenarioCategory($this->en)]);
        $this->world->scenario($this->en, ['descr' => 'Filed under no category']);
        $member = $this->world->member(['country' => 1]);
        $this->world->scenarioAttempt($member->id, $offered, $this->en);

        $rows = $this->signInManager($this->admin)->get('/admin-panel/dashboard')->assertOk()->viewData('recordlist')[$this->en];
        $row = array_values(array_filter($rows, function ($row) use ($member) {
            return $row['id'] == $member->id;
        }))[0];

        $this->pinDefect('simulation-share-counts-unoffered', function () use ($row) {
            $this->assertSame('1 / 1', $row['simulations']);
        });
    }






    // -----------------------------------------------------------
    // testPasswordDialogTakesNoLists
    // -----------------------------------------------------------
    //
    // The main admin's own password dialog hands its fields
    // to Hash::check and strcmp as posted: a field posted as a
    // list is a TypeError, a 500 in the log, where the
    // member's password form refuses one with its message
    // (account-array-input). Wanted: refused without a server
    // error, the password unchanged.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testPasswordDialogTakesNoLists(): void
    {
        $posts = [
            'current password as a list' => ['password' => [World::PASSWORD], 'newpass' => 'Fresh-Admin-Pass-1', 'renewpass' => 'Fresh-Admin-Pass-1'],
            'new password as a list'     => ['password' => World::PASSWORD, 'newpass' => ['Fresh-Admin-Pass-1'], 'renewpass' => 'Fresh-Admin-Pass-1'],
        ];
        $statuses = [];
        foreach ($posts as $case => $fields) {
            $statuses[$case] = $this->signInManager($this->admin)->from('/admin-panel/dashboard')->post('/admin-panel/change-pass', $fields)->getStatusCode();
        }

        $this->pinDefect('admin-change-pass-array-500', function () use ($statuses) {
            $this->assertSame([], array_keys(array_filter($statuses, function (int $status) {
                return $status >= 500;
            })), 'the dialog answered with a server error');
        });
    }
}
