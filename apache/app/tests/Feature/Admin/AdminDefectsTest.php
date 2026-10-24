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
//    dashboard-null-country       — a country manager without
//                                   a country sees every
//                                   country on the dashboard
//    admin-uid-script             — a URL segment reaches the
//                                   inline script of four pages
//    manager-edit-country         — the manager edit dialog
//                                   never shows the stored
//                                   country, so a save
//                                   overwrites it
//    language-delete-lessons      — deleting a language leaves
//                                   its uncategorised lessons
//                                   with no language
//    category-delete-hours        — lessons left without a
//                                   category still count in
//                                   the progress
//    simulation-import-blanks     — a blank description or step
//                                   level in the sheet is a 500
//    lesson-file-blank-name       — a file named with spaces
//                                   only is a 500, the upload
//                                   left behind
//    lesson-hours-comma           — hours typed with a decimal
//                                   comma are cut to the whole
//                                   number
//    question-import-no-file      — a question import without a
//                                   file is a 500
//
//  Used by:
//    - PHPUnit — the Feature suite (runTests.sh)
// -----------------------------------------------------------


namespace Tests\Feature\Admin;

use App\Models\LMUsers;
use Illuminate\Support\Facades\DB;
use Tests\FeatureTestCase;
use Tests\Support\Sheets;








// -----------------------------------------------------------
// AdminDefectsTest
// -----------------------------------------------------------
//
// One pinned defect per test, named in the header; a main
// admin and English for every test.
//
// Used by:
//   - PHPUnit — the Feature suite (runTests.sh)
// -----------------------------------------------------------

class AdminDefectsTest extends FeatureTestCase
{

    // The simulation spreadsheet the import reads
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
    // testCountryManagerWithoutACountrySeesNoOtherCountry
    // -----------------------------------------------------------
    //
    // The dashboard takes a null country for the main admin's
    // every country, so a country manager whose row holds no
    // country — the manager forms check none — sees every
    // country's members, Country column included, while
    // member_in_scope refuses it their reports. Wanted: no
    // other country's members.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testCountryManagerWithoutACountrySeesNoOtherCountry(): void
    {
        $lesson = $this->world->lmContent($this->world->lmCategory($this->en), $this->en);
        $lithuanian = $this->world->member(['country' => 1]);
        $this->world->lessonDone($lithuanian->id, $lesson);
        $estonian = $this->world->member(['country' => 2]);
        $manager = $this->world->manager(['main' => 0, 'country' => null]);

        $page = $this->signInManager($manager)->get('/admin-panel/dashboard')->assertOk();

        $this->pinDefect('dashboard-null-country', function () use ($page, $lithuanian, $estonian) {
            $this->assertNull($page->viewData('countries'), 'the page shows the Country column');
            $page->assertDontSee($lithuanian->email)->assertDontSee($estonian->email);
        });
    }






    // -----------------------------------------------------------
    // testUrlSegmentNeverReachesThePagesScript
    // -----------------------------------------------------------
    //
    // The lesson files page and the self-evaluation and
    // knowledge question pages print the URL's id into an
    // onClick or a javascript: link; {{ }} escapes HTML, not
    // script, and MySQL reads the id's leading digits, so the
    // page looks normal while a crafted link's code runs on
    // the admin's next click. Wanted: the id never reaches the
    // script as written.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testUrlSegmentNeverReachesThePagesScript(): void
    {
        $category = $this->world->lmCategory($this->en);
        $lesson = $this->world->lmContent($category, $this->en);
        $pages = [
            "/admin-panel/lm/files/$lesson),alert(1),($lesson"              => "lm_files_form('new', $lesson),alert(1)",
            "/admin-panel/lm/self_eval/$category),alert(1),($category"      => "import_selfeval($category),alert(1)",
            "/admin-panel/lm/knowledge_test/$category),alert(1),($category" => "import_knowledge($category),alert(1)",
        ];
        $html = [];
        foreach ($pages as $page => $script) {
            $html[$page] = $this->signInManager($this->admin)->get($page)->getContent();
        }

        $this->pinDefect('admin-uid-script', function () use ($pages, $html) {
            foreach ($pages as $page => $script) {
                $this->assertStringNotContainsString($script, $html[$page], "$page runs the URL's code");
            }
        });
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






    // -----------------------------------------------------------
    // testLanguageWithLessonsIsNotDeleted
    // -----------------------------------------------------------
    //
    // The language delete refuses a language that still has
    // categories, but a lesson whose category was deleted
    // first is not looked for: the language goes, the lesson's
    // lang_id turns NULL, and the panel lists it nowhere to
    // edit or delete. Wanted: the delete is refused while the
    // language has lessons.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testLanguageWithLessonsIsNotDeleted(): void
    {
        $latvian = $this->world->language('lv');
        $category = $this->world->lmCategory($latvian);
        $lesson = $this->world->lmContent($category, $latvian);
        $this->signInManager($this->admin)->from('/admin-panel/lm/categories')->post("/admin-panel/lm/categores/delete/$category");
        $this->assertFalse(DB::table('bei_lm_category')->where('id', $category)->exists());

        $this->signInManager($this->admin)->from('/admin-panel/settings/languages')->post("/admin-panel/settings/languages/delete/$latvian");

        $this->pinDefect('language-delete-lessons', function () use ($lesson, $latvian) {
            $this->assertEquals($latvian, DB::table('bei_lm_content')->where('id', $lesson)->value('lang_id'), 'the lesson lost its language');
        });
    }






    // -----------------------------------------------------------
    // testLessonsMembersCannotReachDoNotCount
    // -----------------------------------------------------------
    //
    // Deleting a lesson category keeps its lessons with no
    // category, where no member page lists them, yet the
    // progress still divides by their hours: a member who
    // finished the one lesson left to reach is held at 12 of
    // the lessons' 25. Wanted: finishing every lesson on offer
    // gives the full share.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testLessonsMembersCannotReachDoNotCount(): void
    {
        $kept = $this->world->lmContent($this->world->lmCategory($this->en), $this->en);
        $doomed = $this->world->lmCategory($this->en);
        $this->world->lmContent($doomed, $this->en);
        $member = $this->world->member();
        $this->world->lessonDone($member->id, $kept);
        $this->signInManager($this->admin)->from('/admin-panel/lm/categories')->post("/admin-panel/lm/categores/delete/$doomed");
        $this->assertFalse(DB::table('bei_lm_category')->where('id', $doomed)->exists());

        $this->pinDefect('category-delete-hours', function () use ($member) {
            $this->assertEquals(25, LMUsers::course_progress($member->id, $this->en));
        });
    }






    // -----------------------------------------------------------
    // testSheetWithBlankCellsIsRefusedWithAMessage
    // -----------------------------------------------------------
    //
    // A sheet without a description stores NULL in a NOT NULL
    // column, and a step with a situation but no level reads
    // the parent of level -1: both are a 500 where the import
    // promises a message for a refused sheet. Wanted: no
    // server error.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testSheetWithBlankCellsIsRefusedWithAMessage(): void
    {
        $this->world->scenarioCategory($this->en, ['name' => 'Credential theft']);
        $this->world->scenarioAttribute($this->en, ['name' => 'Urgency']);
        $statuses = [];
        foreach (['no description' => ['C19' => null], 'a step without a level' => ['C61' => 'Forward it', 'D61' => 'Incorrect']] as $case => $cells) {
            $statuses[$case] = $this->signInManager($this->admin)->from('/admin-panel/admin_simulations')
                ->post('/admin-panel/admin_simulations/import', ['lang_id' => $this->en, 'doc' => $this->simulationSheet($cells)])
                ->getStatusCode();
        }

        $this->pinDefect('simulation-import-blanks', function () use ($statuses) {
            $this->assertSame([], array_keys(array_filter($statuses, function (int $status) {
                return $status >= 500;
            })), 'sheets answered with a server error');
        });
    }






    // -----------------------------------------------------------
    // testFileNamedWithSpacesOnlyIsRefused
    // -----------------------------------------------------------
    //
    // The file is moved into upload/lm before its name is
    // saved, and a name of spaces only reaches the NOT NULL
    // column as NULL — a 500, the file left on disk with no
    // row. Wanted: the post is refused and nothing is stored.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testFileNamedWithSpacesOnlyIsRefused(): void
    {
        $lesson = $this->world->lmContent($this->world->lmCategory($this->en), $this->en);
        $before = glob(public_path('upload/lm/*')) ?: [];

        $status = $this->signInManager($this->admin)->from("/admin-panel/lm/files/$lesson")->post('/admin-panel/lm/file/create', [
            'uid'   => $lesson,
            'name'  => '   ',
            'embed' => 0,
            'doc'   => $this->uploadedFile('checklist.pdf', "%PDF-1.4\n%test\n"),
        ])->getStatusCode();
        $added = array_diff(glob(public_path('upload/lm/*')) ?: [], $before);
        foreach ($added as $path) {
            @unlink($path);
        }

        $this->pinDefect('lesson-file-blank-name', function () use ($status, $added) {
            $this->assertLessThan(500, $status, 'the post was answered with a server error');
            $this->assertSame([], array_values($added), 'the upload was left behind');
        });
    }






    // -----------------------------------------------------------
    // testHoursWithADecimalCommaAreNotCut
    // -----------------------------------------------------------
    //
    // Hours are stored as posted from a text field, and with
    // strict mode off MySQL takes "0,5" for 0 with only a
    // warning — hours drive the lessons' share of the
    // progress. Wanted: the comma is refused or read as a
    // decimal point.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testHoursWithADecimalCommaAreNotCut(): void
    {
        $category = $this->world->lmCategory($this->en);

        $this->signInManager($this->admin)->from('/admin-panel/lm/content')->post('/admin-panel/lm/content/create', [
            'cat_id'  => $category,
            'title'   => 'Half an hour',
            'content' => '<p>Short.</p>',
            'hours'   => '0,5',
            'uid'     => $this->en,
        ]);
        $hours = DB::table('bei_lm_content')->where(['title' => 'Half an hour'])->value('hours');

        $this->pinDefect('lesson-hours-comma', function () use ($hours) {
            $this->assertTrue($hours === null || abs($hours - 0.5) < 0.01, "0,5 hours were stored as $hours");
        });
    }






    // -----------------------------------------------------------
    // testQuestionImportWithoutAFileIsRefused
    // -----------------------------------------------------------
    //
    // Both question imports read the upload with
    // file_get_contents without checking one came — a
    // scripted post, or a file PHP dropped as too large, is a
    // ValueError and a 500. Wanted: no server error.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testQuestionImportWithoutAFileIsRefused(): void
    {
        $category = $this->world->lmCategory($this->en);
        $statuses = [
            'self-evaluation' => $this->signInManager($this->admin)->from("/admin-panel/lm/self_eval/$category")
                ->post('/admin-panel/lm/self_eval/import', ['cat_id' => $category])->getStatusCode(),
            'knowledge test'  => $this->signInManager($this->admin)->from("/admin-panel/lm/knowledge_test/$category")
                ->post('/admin-panel/lm/knowledge_test/import', ['cat_id' => $category])->getStatusCode(),
        ];

        $this->pinDefect('question-import-no-file', function () use ($statuses) {
            $this->assertSame([], array_keys(array_filter($statuses, function (int $status) {
                return $status >= 500;
            })), 'imports answered with a server error');
        });
    }
}
