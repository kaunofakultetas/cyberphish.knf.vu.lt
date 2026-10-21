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
//
//  Used by:
//    - PHPUnit — the Feature suite (runTests.sh)
// -----------------------------------------------------------


namespace Tests\Feature\Admin;

use App\Models\Members;
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
}
