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
//    manager-country-scope      — a country manager reads any
//                                 member's history and reports
//    admin-get-deletes          — deleting and switching off
//                                 answer a plain GET, so a
//                                 link can do it
//    admin-login-throttle       — the admin login takes any
//                                 number of password guesses
//    selfeval-greek-import      — the import never finds a
//                                 Greek question line
//    seed-settings-rows         — a fresh install has no
//                                 settings rows 1 and 3 to save
//                                 into
//    lesson-delete-missing-file — a lesson whose file is gone
//                                 from disk cannot be deleted
//    empty-body-500             — news, pages and lessons
//                                 saved with an empty body
//                                 are a 500
//    admin-news-shadowed        — the public news routes
//                                 answer the panel's news
//                                 list, edit and delete links
//    simulation-delete-links    — the delete icons of the
//                                 simulation categories and
//                                 attributes lead to a 404
//    admin-login-error-lost     — a failed admin login loses
//                                 its message
//    admin-pass-messages        — the password dialog answers
//                                 with raw translation keys
//    lesson-file-embed          — a lesson file saved without
//                                 its Embed box shows nowhere
//    simulation-import-partial  — a failed import leaves a
//                                 half-saved simulation behind
//    info-create-intended       — a new information page can
//                                 land the admin on a member
//                                 page instead of the list
//    import-markers             — the imports drop every "="
//                                 or "~" inside an option
//    question-update-no-options — a question with no options
//                                 never saves its text, yet
//                                 says "Updated!"
//    lm-delete-anchor           — deleting a lesson or a
//                                 category reopens the list
//                                 on the wrong language tab
//    category-delete-orphans    — a deleted category leaves
//                                 its questions behind
//    lesson-file-missing        — a file post without a file
//                                 still says "Created!"
//    lesson-file-delete-unknown — deleting a file that is
//                                 already gone is a 500
//    selfeval-form-route        — a self-evaluation route
//                                 names a method its
//                                 controller lacks: a 500
//    language-rules             — a name another language
//                                 has is a 500, not a form
//                                 error
//    language-delete-in-use     — deleting a language that
//                                 still has content is a 500
//    knowledge-report-unknown   — the panel's report of an
//                                 unknown attempt is a 500
//    manager-country-update     — editing a manager drops
//                                 the country
//    manager-update-main        — the manager dialog rewrites
//                                 a main admin's address and
//                                 password
//    simulation-update-descr    — a simulation without steps
//                                 loses its description edit
//    simulations-pdf-categories — every scenario of the
//                                 printout shows the last
//                                 one's categories
//    import-cross-language      — the import can file a
//                                 simulation under another
//                                 language's category
//
//  Used by:
//    - PHPUnit — the Feature suite (runTests.sh)
// -----------------------------------------------------------


namespace Tests\Feature\Admin;

use App\Models\Managers;
use App\Models\Members;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\FeatureTestCase;
use Tests\Support\Sheets;
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
    // testCountryManagerSeesOnlyItsCountrysMembers
    // -----------------------------------------------------------
    //
    // The member reports a country manager may open —
    // user/self_evaluation_history, user/simulations_history,
    // user/simulation/report, user/knowledge_results — take
    // the member from the URL and check no country, so a
    // Latvian manager reads a Lithuanian member's address and
    // results by changing the id. Wanted: a 404.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testCountryManagerSeesOnlyItsCountrysMembers(): void
    {
        $manager = $this->world->manager(['main' => 0, 'country' => 2]);
        $foreigner = $this->world->member(['country' => 1]);

        $this->pinDefect('manager-country-scope', function () use ($manager, $foreigner) {
            $this->signInManager($manager)
                ->get("/admin-panel/user/simulations_history/{$this->en}/{$foreigner->id}")
                ->assertNotFound();
        });
    }






    // -----------------------------------------------------------
    // testDeletingAMemberNeedsMoreThanALink
    // -----------------------------------------------------------
    //
    // user/delete/{user_id} — like every delete and status
    // change of the panel — is a GET route, which the CSRF
    // check never covers and the session cookie (SameSite=lax)
    // follows on a top-level visit: a link a signed-in admin
    // opens from a mail deletes the member. Wanted: a GET
    // deletes nothing.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testDeletingAMemberNeedsMoreThanALink(): void
    {
        $member = $this->world->member();

        $this->signInManager($this->admin)->get("/admin-panel/user/delete/{$member->id}");

        $this->pinDefect('admin-get-deletes', function () use ($member) {
            $this->assertNotNull(Members::find($member->id), 'a GET request deleted the member');
        });
    }






    // -----------------------------------------------------------
    // testRepeatedWrongPasswordsLockTheAdminLogin
    // -----------------------------------------------------------
    //
    // AdminLoginController::login replaces Laravel's login
    // without ThrottlesLogins and has no reCAPTCHA: the panel
    // that runs the whole site takes unlimited guesses.
    // Wanted: after ten wrong passwords even the right one is
    // refused for a while.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testRepeatedWrongPasswordsLockTheAdminLogin(): void
    {
        $this->pinDefect('admin-login-throttle', function () {
            for ($guess = 1; $guess <= 10; $guess++) {
                $this->from('/admin-panel')->post('/admin-panel', ['email' => $this->admin->email, 'password' => 'Wrong-' . $guess]);
            }
            $this->from('/admin-panel')->post('/admin-panel', ['email' => $this->admin->email, 'password' => World::PASSWORD]);
            $this->assertGuest('man');
        });
    }






    // -----------------------------------------------------------
    // testGreekQuestionsAreImported
    // -----------------------------------------------------------
    //
    // SelfevalController::import_questions recognises a
    // question line by its first byte, and compares it with
    // the Greek capital epsilon — two bytes in UTF-8 — so a
    // Greek question line never matches and its options are
    // filed under nothing. Wanted: the Greek question is
    // imported.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testGreekQuestionsAreImported(): void
    {
        $category = $this->world->lmCategory($this->world->language('gr'));
        $file = $this->uploadedFile('questions.txt', "::\nΕ\\: Ποιος σύνδεσμος είναι ασφαλής;\n=Αυτός που πληκτρολόγησες\n~Ο σύνδεσμος του email\n");

        $this->signInManager($this->admin)->from("/admin-panel/lm/self_eval/$category")
            ->post('/admin-panel/lm/self_eval/import', ['cat_id' => $category, 'doc' => $file]);

        $this->pinDefect('selfeval-greek-import', function () use ($category) {
            $this->assertSame(1, DB::table('bei_selfeval_question')->where('cat_id', $category)->count());
        });
    }






    // -----------------------------------------------------------
    // testFreshInstallSavesItsAboutText
    // -----------------------------------------------------------
    //
    // mysql/init.sql seeds settings row 2 only, while the
    // settings page saves the about text into row 1 and the
    // knowledge-test limits into row 3 with an UPDATE — on a
    // fresh install both saves change nothing, and the
    // knowledge test cannot start without its limits. Wanted:
    // the about text is saved.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testFreshInstallSavesItsAboutText(): void
    {
        $this->signInManager($this->admin)->from('/admin-panel/settings/other')
            ->post('/admin-panel/settings/about_us_save', ['about' => [$this->en => ['descr' => 'About us', 'link' => '']]]);

        $this->pinDefect('seed-settings-rows', function () {
            $this->assertStringContainsString('About us', (string) DB::table('bei_settings')->where('id', 1)->value('val1'));
        });
    }






    // -----------------------------------------------------------
    // testLessonWithAMissingFileCanBeDeleted
    // -----------------------------------------------------------
    //
    // LMController::delete removes a file's row only after
    // unlinking the file, and the row's foreign key (NO
    // ACTION) then blocks deleting the lesson: a file gone
    // from disk — or a read-only upload mount, as production
    // has — makes the lesson undeletable, a 500. Wanted: the
    // lesson and its file rows go.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testLessonWithAMissingFileCanBeDeleted(): void
    {
        $lesson = $this->world->lmContent($this->world->lmCategory($this->en), $this->en);
        $this->world->lmFile($lesson, ['file_name' => 'lm/already-gone.pdf']);

        $this->signInManager($this->admin)->from('/admin-panel/lm/content')->get("/admin-panel/lm/content_delete/$lesson");

        $this->pinDefect('lesson-delete-missing-file', function () use ($lesson) {
            $this->assertFalse(DB::table('bei_lm_content')->where('id', $lesson)->exists(), 'the lesson is still there');
        });
    }






    // -----------------------------------------------------------
    // testNewsPagesOfThePanelReachThePanel
    // -----------------------------------------------------------
    //
    // routes/web_user.php is loaded before routes/web_admin.php
    // and {lang} takes any value, so {lang}/news answers
    // admin-panel/news — the public list, even for a visitor —
    // and {lang}/news/{slug}/{uid} answers the panel's edit,
    // delete and remove-picture links with the public article:
    // no admin can delete an article from the panel. Wanted: a
    // visitor is sent to the admin login, and the delete link
    // deletes.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testNewsPagesOfThePanelReachThePanel(): void
    {
        $article = $this->world->news($this->en);

        $this->pinDefect('admin-news-shadowed', function () use ($article) {
            $this->get('/admin-panel/news')->assertRedirect('/admin-panel');
            $this->signInManager($this->admin)->get("/admin-panel/news/delete/$article");
            $this->assertFalse(DB::table('bei_news')->where('id', $article)->exists(), 'the delete link did not delete');
        });
    }






    // -----------------------------------------------------------
    // testNewsWithAnEmptyBodyIsSaved
    // -----------------------------------------------------------
    //
    // Laravel's ConvertEmptyStringsToNull turns an empty
    // editor into NULL, and the content columns of news,
    // information pages and lessons are NOT NULL: saving one
    // with an empty body fails in the database — a 500
    // instead of either a saved article or a message. Wanted:
    // the article is saved.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testNewsWithAnEmptyBodyIsSaved(): void
    {
        $response = $this->signInManager($this->admin)->from('/admin-panel/news')->post('/admin-panel/news/create', [
            'title'   => 'Headline only',
            'content' => '',
            'lang_id' => $this->en,
        ]);

        $this->pinDefect('empty-body-500', function () use ($response) {
            $response->assertRedirect();
            $this->assertTrue(DB::table('bei_news')->where('title', 'Headline only')->exists());
        });
    }






    // -----------------------------------------------------------
    // testSimulationDeleteIconsDelete
    // -----------------------------------------------------------
    //
    // The delete icons of the simulation categories and
    // attributes pages hand Confirm_Dialog a URL under
    // /admin-panel/simulations/, while the routes live under
    // /admin-panel/admin_simulations/ (line 51 of
    // simulations_categories.blade.php and of
    // simulations_attributes.blade.php): no route matches, so
    // the icon lands on a 404 and the panel can delete
    // neither. AdminSimulationsTest calls the routes directly
    // and misses it. Wanted: each icon's link deletes its
    // row.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testSimulationDeleteIconsDelete(): void
    {
        $category = $this->world->scenarioCategory($this->en);
        $attribute = $this->world->scenarioAttribute($this->en);
        $categoryLink = $this->deleteLink('/admin-panel/admin_simulations/categories', $category);
        $attributeLink = $this->deleteLink('/admin-panel/admin_simulations/attributes', $attribute);

        $this->signInManager($this->admin)->get($categoryLink);
        $this->signInManager($this->admin)->get($attributeLink);

        $this->pinDefect('simulation-delete-links', function () use ($category, $attribute) {
            $this->assertFalse(DB::table('bei_scenarios_category')->where('id', $category)->exists(), 'the category is still there');
            $this->assertFalse(DB::table('bei_scenarios_attributes')->where('id', $attribute)->exists(), 'the attribute is still there');
        });
    }






    // -----------------------------------------------------------
    // testFailedAdminLoginShowsItsError
    // -----------------------------------------------------------
    //
    // AdminLoginController::login answers a failed attempt
    // through Laravel's intended redirect, which first takes
    // the page auth.admin parked in the session when it sent
    // the visitor to the form. That page sends the visitor
    // straight back, and the failure message is spent on the
    // way: the form returns without a word. Wanted: the form
    // again, with the message.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testFailedAdminLoginShowsItsError(): void
    {
        $this->get('/admin-panel/dashboard')->assertRedirect('/admin-panel');

        $response = $this->from('/admin-panel')->post('/admin-panel', ['email' => $this->admin->email, 'password' => 'Wrong-Password-1']);

        $this->pinDefect('admin-login-error-lost', function () use ($response) {
            $response->assertRedirect('/admin-panel');
            $response->assertSessionHasErrors();
        });
    }






    // -----------------------------------------------------------
    // testPasswordDialogAnswersInWords
    // -----------------------------------------------------------
    //
    // Admin\ManagerMainController::change_pass answers with
    // five main.msg_* keys that no resources/lang file
    // defines, and Laravel's translator hands a missing key
    // back as it is: the panel's password dialog shows the
    // key's name instead of a message. Wanted: a message in
    // words.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testPasswordDialogAnswersInWords(): void
    {
        $this->signInManager($this->admin)->from('/admin-panel/dashboard')->post('/admin-panel/change-pass', [
            'password'  => 'Not-The-Password-1',
            'newpass'   => 'Brand-New-Password-1',
            'renewpass' => 'Brand-New-Password-1',
        ])->assertSessionHasErrors();
        $message = session('errors')->first();

        $this->pinDefect('admin-pass-messages', function () use ($message) {
            $this->assertStringStartsNotWith('main.', $message, "the dialog says $message");
        });
    }






    // -----------------------------------------------------------
    // testFileWithoutEmbedShowsOnTheLesson
    // -----------------------------------------------------------
    //
    // The lesson-file form posts its Embed box only when it
    // is ticked, and LMController::files_create stores the
    // missing field as NULL, while the lesson page lists the
    // files with embed 0 (downloads) or 1 (slides) only: a
    // file saved with the box unticked shows nowhere for
    // members. files_update does the same when the box is
    // unticked. Wanted: the lesson page lists the file.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testFileWithoutEmbedShowsOnTheLesson(): void
    {
        $lesson = $this->world->lmContent($this->world->lmCategory($this->en), $this->en);

        $this->signInManager($this->admin)->from("/admin-panel/lm/files/$lesson")->post('/admin-panel/lm/file/create', [
            'uid'  => $lesson,
            'name' => 'Checklist for staff',
            'doc'  => $this->uploadedFile('checklist.pdf', "%PDF-1.4\n%test\n"),
        ])->assertSessionHas('success');

        $this->pinDefect('lesson-file-embed', function () use ($lesson) {
            $this->get("/en/learn/lesson/$lesson")->assertSee('Checklist for staff');
        });
    }






    // -----------------------------------------------------------
    // testFailedImportLeavesNothingBehind
    // -----------------------------------------------------------
    //
    // SimulationsController::simulations_import saves the
    // simulation row before its categories, attributes and
    // steps, with no transaction around them. A category
    // marked Yes whose name no row has stores a null id into
    // a NOT NULL column, and the import stops with a 500
    // after the simulation row is saved: a half-imported
    // simulation stays in the list, without its categories
    // and steps. Wanted: a failed import leaves nothing
    // behind.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testFailedImportLeavesNothingBehind(): void
    {
        $this->world->scenarioAttribute($this->en, ['name' => 'Urgency']);

        $this->signInManager($this->admin)->from('/admin-panel/admin_simulations')->post('/admin-panel/admin_simulations/import', [
            'lang_id' => $this->en,
            'doc'     => $this->simulationSheet(['C34' => 'A category nobody created']),
        ]);

        $this->pinDefect('simulation-import-partial', function () {
            $this->assertFalse(DB::table('bei_scenarios')->where('descr', 'An urgent invoice arrives')->exists(), 'the failed import left its simulation behind');
        });
    }






    // -----------------------------------------------------------
    // testNewInformationPageReturnsToTheList
    // -----------------------------------------------------------
    //
    // InfoController::create answers through Laravel's
    // intended redirect, which first takes any page an auth
    // middleware parked in the session — here a member page
    // the admin opened while not signed in as a member. The
    // page is saved, but the admin lands on that member page,
    // and from there on the member login, instead of on the
    // list. Wanted: the list of information pages.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testNewInformationPageReturnsToTheList(): void
    {
        $this->get('/cp/dashboard')->assertRedirect('/login');

        $response = $this->signInManager($this->admin)->from('/admin-panel/information/new')->post('/admin-panel/information/create', [
            'title'   => 'Opening hours',
            'content' => '<p>Weekdays from nine to five</p>',
            'lang_id' => $this->en,
        ]);
        $this->assertTrue(DB::table('bei_information')->where('title', 'Opening hours')->exists(), 'the page was not saved');

        $this->pinDefect('info-create-intended', function () use ($response) {
            $response->assertRedirect('/admin-panel/information');
        });
    }






    // -----------------------------------------------------------
    // testImportedOptionsKeepTheirText
    // -----------------------------------------------------------
    //
    // SelfevalController::import_questions and
    // KnowledgeController::import_questions take an option's
    // marker — "=" before a correct option, "~" before a
    // wrong one — out with str_replace, which removes every
    // "=" or "~" of the line, not only the first: an address
    // with a query string, or with a tilde in its path, is
    // stored changed by both imports. Wanted: each option
    // exactly as written after its marker.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testImportedOptionsKeepTheirText(): void
    {
        $category = $this->world->lmCategory($this->en);
        $right = 'https://portal.example.test/login?team=staff';
        $wrong = 'https://portal-example.test/~login';
        $questions = "::\nQ\\: Which link opens the staff portal?\n=$right\n~$wrong\n";

        $this->signInManager($this->admin)->from("/admin-panel/lm/self_eval/$category")
            ->post('/admin-panel/lm/self_eval/import', ['cat_id' => $category, 'doc' => $this->uploadedFile('questions.txt', $questions)]);
        $this->signInManager($this->admin)->from("/admin-panel/lm/knowledge_test/$category")
            ->post('/admin-panel/lm/knowledge_test/import', ['cat_id' => $category, 'doc' => $this->uploadedFile('questions.txt', $questions)]);
        $selfEval = DB::table('bei_selfeval_question')->where('cat_id', $category)->value('id');
        $knowledge = DB::table('bei_knowledge_test')->where('cat_id', $category)->value('id');
        $this->assertNotNull($selfEval, 'the self-evaluation import stored no question');
        $this->assertNotNull($knowledge, 'the knowledge-test import stored no question');

        $this->pinDefect('import-markers', function () use ($right, $wrong, $selfEval, $knowledge) {
            $this->assertSame([$right, $wrong], DB::table('bei_selfeval_option')->where('question_id', $selfEval)->orderBy('id')->pluck('option')->all(), 'the self-evaluation import changed the options');
            $this->assertSame([$right, $wrong], DB::table('bei_knowledge_option')->where('question_id', $knowledge)->orderBy('id')->pluck('option')->all(), 'the knowledge-test import changed the options');
        });
    }






    // -----------------------------------------------------------
    // testQuestionWithoutOptionsIsSaved
    // -----------------------------------------------------------
    //
    // SelfevalController::question_update and
    // KnowledgeController::question_update write the
    // question's text only when the form posted options. The
    // edit form of a question without options — an import
    // block with a question line but no option lines leaves
    // one — posts none, so its new text is dropped while the
    // page says "Updated!". Wanted: the new text is saved by
    // both forms.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testQuestionWithoutOptionsIsSaved(): void
    {
        $category = $this->world->lmCategory($this->en);
        $selfEval = $this->world->selfEvalQuestion($category, []);
        $knowledge = $this->world->knowledgeQuestion($category, []);
        $text = 'Who may ask for your password?';

        $this->signInManager($this->admin)->from("/admin-panel/lm/self_eval/edit/{$selfEval['question']}")
            ->post("/admin-panel/lm/self_eval/update/{$selfEval['question']}", ['q' => $text, 'q_type' => 1])
            ->assertSessionHas('success', 'Updated!');
        $this->signInManager($this->admin)->from("/admin-panel/lm/knowledge_test/edit/{$knowledge['question']}")
            ->post("/admin-panel/lm/knowledge_test/update/{$knowledge['question']}", ['q' => $text])
            ->assertSessionHas('success', 'Updated!');

        $this->pinDefect('question-update-no-options', function () use ($selfEval, $knowledge, $text) {
            $this->assertSame($text, DB::table('bei_selfeval_question')->where('id', $selfEval['question'])->value('question'), 'the self-evaluation question kept its old text');
            $this->assertSame($text, DB::table('bei_knowledge_test')->where('id', $knowledge['question'])->value('question'), 'the knowledge-test question kept its old text');
        });
    }






    // -----------------------------------------------------------
    // testDeletesReturnToTheLanguageTab
    // -----------------------------------------------------------
    //
    // The lessons and categories pages show one tab per
    // language, named by the language's id, and reopen the
    // tab a redirect's anchor names. LMController::delete and
    // LMController::categories_delete put the deleted row's
    // own id in that anchor, and the language id they read
    // goes unused: after a delete the list does not reopen
    // on the row's language. Wanted: both redirects name the
    // row's language.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testDeletesReturnToTheLanguageTab(): void
    {
        // Ids past English's own: a row with English's id would
        // hide the mix-up
        $category = $this->world->lmCategory($this->en, ['id' => max($this->world->nextId('bei_lm_category'), $this->en + 1)]);
        $lesson = $this->world->lmContent($category, $this->en, ['id' => max($this->world->nextId('bei_lm_content'), $this->en + 1)]);

        $lessonDeleted = $this->signInManager($this->admin)->from('/admin-panel/lm/content')->get("/admin-panel/lm/content_delete/$lesson");
        $categoryDeleted = $this->signInManager($this->admin)->from('/admin-panel/lm/categories')->get("/admin-panel/lm/categores/delete/$category");

        $this->pinDefect('lm-delete-anchor', function () use ($lessonDeleted, $categoryDeleted) {
            $lessonDeleted->assertRedirect("/admin-panel/lm/content#a{$this->en}");
            $categoryDeleted->assertRedirect("/admin-panel/lm/categories#a{$this->en}");
        });
    }






    // -----------------------------------------------------------
    // testDeletedCategoryLeavesNoQuestions
    // -----------------------------------------------------------
    //
    // LMController::categories_delete deletes the category
    // row alone, and the self-evaluation and knowledge-test
    // questions filed under it carry no foreign key to it:
    // they stay behind with their options, listed on no page
    // of the panel, and the knowledge test keeps drawing them
    // while the limits in settings row 3 name the category.
    // Wanted: no question or option outlives its category —
    // they go with it, or the delete is refused.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testDeletedCategoryLeavesNoQuestions(): void
    {
        $category = $this->world->lmCategory($this->en);
        $selfEval = $this->world->selfEvalQuestion($category, [['Report the mail', true, 100], ['Answer the mail', false, 0]]);
        $knowledge = $this->world->knowledgeQuestion($category, [['Report the mail', true], ['Answer the mail', false]]);

        $this->signInManager($this->admin)->from('/admin-panel/lm/categories')->get("/admin-panel/lm/categores/delete/$category");

        $this->pinDefect('category-delete-orphans', function () use ($category, $selfEval, $knowledge) {
            $kept = DB::table('bei_lm_category')->where('id', $category)->exists();
            $left = DB::table('bei_selfeval_question')->where('cat_id', $category)->count()
                + DB::table('bei_selfeval_option')->where('question_id', $selfEval['question'])->count()
                + DB::table('bei_knowledge_test')->where('cat_id', $category)->count()
                + DB::table('bei_knowledge_option')->where('question_id', $knowledge['question'])->count();
            $this->assertTrue($kept || $left === 0, "$left questions and options outlived their category");
        });
    }






    // -----------------------------------------------------------
    // testFilePostWithoutAFileGetsAnError
    // -----------------------------------------------------------
    //
    // LMController::files_create does all its work under a
    // check that a usable file came, and answers "Created!"
    // either way. A post without one — no file chosen, or one
    // PHP dropped for exceeding upload_max_filesize — stores
    // nothing, while the admin is told the file was added.
    // Wanted: back to the form with an error instead of
    // "Created!".
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testFilePostWithoutAFileGetsAnError(): void
    {
        $lesson = $this->world->lmContent($this->world->lmCategory($this->en), $this->en);

        $response = $this->signInManager($this->admin)->from("/admin-panel/lm/files/$lesson")->post('/admin-panel/lm/file/create', [
            'uid'  => $lesson,
            'name' => 'Checklist for staff',
        ]);
        $this->assertFalse(DB::table('bei_lm_content_files')->where('content_id', $lesson)->exists(), 'a row was stored without a file');

        $this->pinDefect('lesson-file-missing', function () use ($response) {
            $response->assertSessionMissing('success');
            $response->assertSessionHasErrors();
        });
    }






    // -----------------------------------------------------------
    // testRepeatedFileDeleteLinkIsHarmless
    // -----------------------------------------------------------
    //
    // LMController::files_delete builds the path to unlink
    // from the file name of the row the id names, without
    // checking that the row exists. For an id with no row — a
    // delete link opened twice, or one on a stale page — the
    // path is the upload folder itself, which exists, so
    // unlink runs on it and fails: a 500 instead of the list.
    // Wanted: the repeated link answers without a server
    // error.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testRepeatedFileDeleteLinkIsHarmless(): void
    {
        $lesson = $this->world->lmContent($this->world->lmCategory($this->en), $this->en);
        $file = $this->world->lmFile($lesson);
        $this->signInManager($this->admin)->from("/admin-panel/lm/files/$lesson")->get("/admin-panel/lm/files/delete/$file");
        $this->assertFalse(DB::table('bei_lm_content_files')->where('id', $file)->exists(), 'the first delete left the row');

        $repeated = $this->signInManager($this->admin)->from("/admin-panel/lm/files/$lesson")->get("/admin-panel/lm/files/delete/$file");

        $this->pinDefect('lesson-file-delete-unknown', function () use ($repeated) {
            $this->assertLessThan(500, $repeated->getStatusCode(), 'the repeated delete link is a ' . $repeated->getStatusCode());
        });
    }






    // -----------------------------------------------------------
    // testQuestionFormPathIsNotFound
    // -----------------------------------------------------------
    //
    // routes/web_admin.php sends GET
    // admin-panel/lm/self_eval/question_{type}/{uid} to
    // SelfevalController::form, a method the controller does
    // not have. Nothing links to the route, yet a main admin
    // who opens it gets a 500 rather than the panel's
    // not-found page. Wanted: a 404, as for any other path
    // the panel does not serve.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testQuestionFormPathIsNotFound(): void
    {
        $category = $this->world->lmCategory($this->en);

        $response = $this->signInManager($this->admin)->get("/admin-panel/lm/self_eval/question_new/$category");

        $this->pinDefect('selfeval-form-route', function () use ($response) {
            $response->assertNotFound();
        });
    }






    // -----------------------------------------------------------
    // testTakenLanguageNameIsRefused
    // -----------------------------------------------------------
    //
    // Admin\LanguagesController::create validates only that
    // the name is given and at most 255 characters, while
    // bei_lang keeps its names unique: a name another
    // language already has fails the INSERT on the unique
    // key, and the dialog's post ends in a 500 instead of a
    // form error. LanguagesController::update has the same
    // rules and breaks the same way on a rename. Wanted: the
    // languages page again, with an error.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testTakenLanguageNameIsRefused(): void
    {
        $taken = DB::table('bei_lang')->where('id', $this->en)->value('name');

        $response = $this->signInManager($this->admin)->from('/admin-panel/settings/languages')
            ->post('/admin-panel/dialog/lang/create', ['name' => $taken, 'locale' => 'xx']);

        $this->pinDefect('language-rules', function () use ($response) {
            $response->assertRedirect('/admin-panel/settings/languages');
            $response->assertSessionHasErrors();
        });
    }






    // -----------------------------------------------------------
    // testLanguageStillInUseIsRefusedWithAMessage
    // -----------------------------------------------------------
    //
    // Admin\LanguagesController::delete destroys the row
    // without looking at what still points at it: news,
    // information pages and learning-material categories
    // hold the language through NO ACTION foreign keys,
    // which refuse the DELETE, so a language that still has
    // any of them cannot go and the request ends in a 500
    // instead of a message. Wanted: the languages page
    // again, with a message saying why the language stays.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testLanguageStillInUseIsRefusedWithAMessage(): void
    {
        $latvian = $this->world->language('lv');
        $this->world->news($latvian);

        $response = $this->signInManager($this->admin)->from('/admin-panel/settings/languages')
            ->get("/admin-panel/settings/languages/delete/$latvian");

        $this->pinDefect('language-delete-in-use', function () use ($response) {
            $response->assertRedirect('/admin-panel/settings/languages');
            $response->assertSessionHasErrors();
        });
    }






    // -----------------------------------------------------------
    // testUnknownKnowledgeReportIsNotFound
    // -----------------------------------------------------------
    //
    // Admin\ManagerMainController::knowledge_report looks the
    // attempt up by the public id from the URL and goes on
    // whether or not one was found: for an unknown id the
    // question set decodes to null, and the foreach over it
    // stops with a 500. The member side fails the same way,
    // pinned as knowledge-unknown-attempt. Wanted: a 404.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testUnknownKnowledgeReportIsNotFound(): void
    {
        $response = $this->signInManager($this->admin)->get('/admin-panel/user/knowledge_results/no-such-attempt');

        $this->pinDefect('knowledge-report-unknown', function () use ($response) {
            $response->assertNotFound();
        });
    }






    // -----------------------------------------------------------
    // testManagerCountryChangeIsSaved
    // -----------------------------------------------------------
    //
    // Admin\ManagersController::manager_update saves only the
    // address and the password, though the manager dialog
    // asks for the country on an edit too: the posted
    // country is dropped while the panel says "Manager
    // updated!", and a manager's country can never change
    // once it is created. Wanted: the country posted is
    // saved.
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

        $this->pinDefect('manager-country-update', function () use ($manager) {
            $this->assertSame(4, Managers::find($manager->id)->country, 'the country posted was not saved');
        });
    }






    // -----------------------------------------------------------
    // testMainAdminIsNeverRewritten
    // -----------------------------------------------------------
    //
    // Admin\ManagersController::manager_status and
    // manager_delete pass over main admins, but
    // manager_update rewrites whichever row the posted uid
    // names: a post with a main admin's id replaces that
    // admin's address and password, though the managers page
    // never lists main admins. Wanted: a main admin's row
    // stays as it was.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testMainAdminIsNeverRewritten(): void
    {
        $other = $this->world->manager();

        $this->signInManager($this->admin)->from('/admin-panel/settings/managers')->post('/admin-panel/manager/update', [
            'uid'    => $other->id,
            'email'  => 'main-admin-rewritten@example.test',
            'pass'   => 'Rewritten-Pass-4',
            'repass' => 'Rewritten-Pass-4',
        ]);

        $this->pinDefect('manager-update-main', function () use ($other) {
            $row = Managers::find($other->id);
            $this->assertSame($other->email, $row->email, "the update rewrote a main admin's address");
            $this->assertTrue(Hash::check(World::PASSWORD, $row->password), "the update rewrote a main admin's password");
        });
    }






    // -----------------------------------------------------------
    // testDescriptionOfASimulationWithoutStepsIsSaved
    // -----------------------------------------------------------
    //
    // Admin\SimulationsController::simulation_update saves
    // the description only inside its check for posted
    // steps, and the edit page posts steps only for a
    // simulation that has some: editing a simulation without
    // steps drops the new description while the page says
    // "Updated!". Wanted: the description is saved.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testDescriptionOfASimulationWithoutStepsIsSaved(): void
    {
        $scenario = $this->world->scenario($this->en, ['descr' => 'Description before the edit']);

        $this->signInManager($this->admin)->from("/admin-panel/admin_simulations/edit/$scenario")
            ->post("/admin-panel/admin_simulations/update/$scenario", ['situation' => 'Description after the edit'])
            ->assertSessionHas('success');

        $this->pinDefect('simulation-update-descr', function () use ($scenario) {
            $descr = DB::table('bei_scenarios')->where('id', $scenario)->value('descr');
            $this->assertSame('Description after the edit', $descr, 'the edit of a simulation without steps was dropped');
        });
    }






    // -----------------------------------------------------------
    // testEachScenarioOfThePrintoutShowsItsOwnCategories
    // -----------------------------------------------------------
    //
    // Admin\SiteSettingsController::SimulationsDownloadPdf
    // keeps one list of categories and one of attributes per
    // language and overwrites both for every scenario, and
    // the printout shows that one list under each scenario:
    // every scenario of a language shows the categories and
    // attributes of the language's last scenario. Wanted:
    // each scenario shows its own.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testEachScenarioOfThePrintoutShowsItsOwnCategories(): void
    {
        $smishing = $this->world->scenarioCategory($this->en, ['name' => 'Smishing']);
        $curiosity = $this->world->scenarioAttribute($this->en, ['name' => 'Curiosity']);
        $vishing = $this->world->scenarioCategory($this->en, ['name' => 'Vishing']);
        $authority = $this->world->scenarioAttribute($this->en, ['name' => 'Authority']);
        $this->world->scenario($this->en, ['descr' => 'A parcel notice by text message'], [$smishing], [$curiosity]);
        $this->world->scenario($this->en, ['descr' => 'A call from the bank'], [$vishing], [$authority]);

        $page = $this->signInManager($this->admin)->get('/admin-panel/settings/simulations_download_pdf')
            ->assertOk()
            ->assertSee('A call from the bank')
            ->getContent();

        // The printout lists a language's scenarios by id, so
        // the page above the second one holds the first one
        $firstScenario = strstr($page, 'A call from the bank', true);

        $this->pinDefect('simulations-pdf-categories', function () use ($firstScenario) {
            $this->assertStringContainsString('Smishing', $firstScenario, "the first scenario shows the last one's categories");
            $this->assertStringContainsString('Curiosity', $firstScenario, "the first scenario shows the last one's attributes");
        });
    }






    // -----------------------------------------------------------
    // testImportFilesUnderTheCategoriesOfItsLanguage
    // -----------------------------------------------------------
    //
    // Admin\SimulationsController::simulations_import finds
    // the ticked categories and attributes by name through
    // ScenariosCategory::IdByCategory and
    // ScenariosAttributes::IdByAttr, which match the name in
    // every language and take the first row MySQL returns:
    // where another language has a category or attribute of
    // the same name, the imported simulation can be filed
    // under that language's category and tagged with its
    // attribute, and is then missing from its own category's
    // list. Wanted: the category and attribute of the
    // import's language.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testImportFilesUnderTheCategoriesOfItsLanguage(): void
    {
        // The Lithuanian rows go in first: a lookup that
        // ignores the language meets them before the English
        // ones
        $lithuanian = $this->world->language('lt');
        $this->world->scenarioCategory($lithuanian, ['name' => 'Smishing']);
        $this->world->scenarioAttribute($lithuanian, ['name' => 'Spoofing']);
        $category = $this->world->scenarioCategory($this->en, ['name' => 'Smishing']);
        $attribute = $this->world->scenarioAttribute($this->en, ['name' => 'Spoofing']);

        $this->signInManager($this->admin)->from('/admin-panel/admin_simulations')->post('/admin-panel/admin_simulations/import', [
            'lang_id' => $this->en,
            'doc'     => $this->simulationSheet(['C34' => 'Smishing', 'E34' => 'Spoofing']),
        ])->assertSessionHas('success');
        $scenario = DB::table('bei_scenarios')->where('descr', 'An urgent invoice arrives')->value('id');

        $this->pinDefect('import-cross-language', function () use ($scenario, $category, $attribute) {
            $filed = DB::table('bei_scenarios_in_category')->where(['scenario_id' => $scenario, 'cat_id' => $category])->exists();
            $this->assertTrue($filed, "the simulation is filed under the other language's category");
            $tagged = DB::table('bei_scenarios_in_attributes')->where(['scenario_id' => $scenario, 'attribute_id' => $attribute])->exists();
            $this->assertTrue($tagged, "the simulation is tagged with the other language's attribute");
        });
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
    //   - testSimulationDeleteIconsDelete (above)
    // -----------------------------------------------------------

    private function deleteLink(string $page, int $id): string
    {
        $html = $this->signInManager($this->admin)->get($page)->assertOk()->getContent();
        $found = preg_match("#Confirm_Dialog\\('([^']*/delete/$id)'\\)#", $html, $link);
        $this->assertSame(1, $found, "no delete icon for row $id on $page");
        return parse_url($link[1], PHP_URL_PATH);
    }
}
