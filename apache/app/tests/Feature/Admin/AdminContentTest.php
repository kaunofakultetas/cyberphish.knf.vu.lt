<?php

// -----------------------------------------------------------
//  [*] AdminContentTest — what main admins publish
//
//  The content pages of the panel: languages
//  (LanguagesController), news with their pictures
//  (NewsController — the picture goes to storage/app/public
//  under a random name, which the site serves as
//  /upload/feat/…), information pages (InfoController) and
//  the settings of SiteSettingsController — the about text
//  (settings row 1), the self-evaluation length (row 2) and
//  the knowledge-test limits (row 3), saved into the rows
//  mysql/init.sql seeds — and the printout of every
//  simulation, each with its own categories. A language
//  name is unique and a language in use stays; an empty
//  body is saved as one. The panel's news pages reach the
//  panel, and removing a picture an article no longer has
//  is harmless.
//
//  Used by:
//    - PHPUnit — the Feature suite (runTests.sh)
// -----------------------------------------------------------


namespace Tests\Feature\Admin;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Tests\FeatureTestCase;








// -----------------------------------------------------------
// AdminContentTest
// -----------------------------------------------------------
//
// A main admin works the pages in every test; forms are
// posted from the page that holds them.
//
// Used by:
//   - PHPUnit — the Feature suite (runTests.sh)
// -----------------------------------------------------------

class AdminContentTest extends FeatureTestCase
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
    // testLanguageIsAddedRenamedAndRemoved
    // -----------------------------------------------------------
    //
    // A language is created with its name and locale,
    // renamed, and deleted.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testLanguageIsAddedRenamedAndRemoved(): void
    {
        $this->admin()->from('/admin-panel/settings/languages')
            ->post('/admin-panel/dialog/lang/create', ['name' => 'Latviešu', 'locale' => 'lv'])
            ->assertSessionHas('success');
        $id = DB::table('bei_lang')->where('locale', 'lv')->value('id');
        $this->assertSame('Latviešu', DB::table('bei_lang')->where('id', $id)->value('name'));

        $this->admin()->from('/admin-panel/settings/languages')
            ->post('/admin-panel/dialog/lang/update', ['uid' => $id, 'name' => 'Latvian', 'locale' => 'lv']);
        $this->assertSame('Latvian', DB::table('bei_lang')->where('id', $id)->value('name'));

        $this->admin()->from('/admin-panel/settings/languages')->post("/admin-panel/settings/languages/delete/$id");
        $this->assertFalse(DB::table('bei_lang')->where('id', $id)->exists());
    }






    // -----------------------------------------------------------
    // testTakenLanguageNameIsRefused
    // -----------------------------------------------------------
    //
    // A language name another language already has goes back
    // to the languages page with an error.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testTakenLanguageNameIsRefused(): void
    {
        $taken = DB::table('bei_lang')->where('id', $this->en)->value('name');

        $this->admin()->from('/admin-panel/settings/languages')
            ->post('/admin-panel/dialog/lang/create', ['name' => $taken, 'locale' => 'xx'])
            ->assertRedirect('/admin-panel/settings/languages')
            ->assertSessionHasErrors('name');
    }






    // -----------------------------------------------------------
    // testLanguageStillInUseIsRefusedWithAMessage
    // -----------------------------------------------------------
    //
    // A language that still has news, information pages or
    // learning-material categories stays, and the languages
    // page says why.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testLanguageStillInUseIsRefusedWithAMessage(): void
    {
        $latvian = $this->world->language('lv');
        $this->world->news($latvian);

        $this->admin()->from('/admin-panel/settings/languages')
            ->post("/admin-panel/settings/languages/delete/$latvian")
            ->assertRedirect('/admin-panel/settings/languages')
            ->assertSessionHasErrors();

        $this->assertTrue(DB::table('bei_lang')->where('id', $latvian)->exists());
    }






    // -----------------------------------------------------------
    // testNewsIsPublishedInItsLanguage
    // -----------------------------------------------------------
    //
    // A news article gets a slug from its title and the
    // admin as its author, and appears on its language's news
    // page.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testNewsIsPublishedInItsLanguage(): void
    {
        $this->admin()->from('/admin-panel/news')->post('/admin-panel/news/create', [
            'title'   => 'Phishing season is here',
            'content' => '<p>Expect fake parcel notices.</p>',
            'lang_id' => $this->en,
        ])->assertSessionHas('success');

        $news = DB::table('bei_news')->where('title', 'Phishing season is here')->first();
        $this->assertSame('phishing-season-is-here', $news->alias);
        $this->assertEquals($this->admin->id, $news->created_by);
        $this->get('/en/news')->assertSee('Phishing season is here');
    }






    // -----------------------------------------------------------
    // testNewsPictureIsStoredForTheSite
    // -----------------------------------------------------------
    //
    // The picture is stored under storage/app/public/feat with
    // a random 40-character name and the news row points at
    // it.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testNewsPictureIsStoredForTheSite(): void
    {
        $this->publishWithPicture('With a picture');

        $picture = DB::table('bei_news')->where('title', 'With a picture')->value('feat_img');
        $this->assertMatchesRegularExpression('#^feat/[A-Za-z0-9]{40}\.png$#', $picture);
        $this->assertFileExists(storage_path('app/public/' . $picture));
    }






    // -----------------------------------------------------------
    // testNewsPictureMustBeAnImage
    // -----------------------------------------------------------
    //
    // A file that is not an image fails validation and no news
    // is created.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testNewsPictureMustBeAnImage(): void
    {
        $this->admin()->from('/admin-panel/news')->post('/admin-panel/news/create', [
            'title'   => 'Text pretending',
            'lang_id' => $this->en,
            'feat'    => UploadedFile::fake()->create('notes.php', 1, 'text/x-php'),
        ])->assertSessionHasErrors('feat');

        $this->assertFalse(DB::table('bei_news')->where('title', 'Text pretending')->exists());
    }






    // -----------------------------------------------------------
    // testNewsWithAnEmptyBodyIsSaved
    // -----------------------------------------------------------
    //
    // An article saved with an empty editor — NULL by the time
    // it arrives — is stored with an empty body.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testNewsWithAnEmptyBodyIsSaved(): void
    {
        $this->admin()->from('/admin-panel/news')->post('/admin-panel/news/create', [
            'title'   => 'Headline only',
            'content' => '',
            'lang_id' => $this->en,
        ])->assertRedirect();

        $this->assertSame('', DB::table('bei_news')->where('title', 'Headline only')->value('content'));
    }






    // -----------------------------------------------------------
    // testNewsPagesOfThePanelReachThePanel
    // -----------------------------------------------------------
    //
    // The public {lang}/news routes no longer answer the panel's
    // news pages: a visitor is sent to the admin login, and the
    // panel's delete deletes.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testNewsPagesOfThePanelReachThePanel(): void
    {
        $article = $this->world->news($this->en);

        $this->get('/admin-panel/news')->assertRedirect('/admin-panel');
        $this->admin()->from('/admin-panel/news')->post("/admin-panel/news/delete/$article");

        $this->assertFalse(DB::table('bei_news')->where('id', $article)->exists());
    }






    // -----------------------------------------------------------
    // testRemovingAMissingPictureIsHarmless
    // -----------------------------------------------------------
    //
    // Removing the picture of an article that has none — a
    // second click, a stale form — goes back to the form; the
    // picture folder itself is never unlinked.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testRemovingAMissingPictureIsHarmless(): void
    {
        $article = $this->world->news($this->en, ['feat_img' => null]);

        $this->admin()->from("/admin-panel/news/edit/$article")->post("/admin-panel/news/delete_picture/$article")
            ->assertRedirect("/admin-panel/news/edit/$article");

        $this->assertDirectoryExists(storage_path('app/public'));
    }






    // -----------------------------------------------------------
    // testNewsIsEdited
    // -----------------------------------------------------------
    //
    // The edit form's update — a POST, which no public route
    // shadows — saves the new title, body and slug.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testNewsIsEdited(): void
    {
        $id = $this->world->news($this->en, ['title' => 'Draft title']);

        $this->admin()->from("/admin-panel/news/edit/$id")->post('/admin-panel/news/update', [
            'uid'     => $id,
            'title'   => 'Final title',
            'content' => '<p>Final body</p>',
            'lang_id' => $this->en,
        ])->assertSessionHas('success');
        $this->assertSame('final-title', DB::table('bei_news')->where('id', $id)->value('alias'));
        $this->get("/en/news/final-title/$id")->assertSee('Final body');
    }






    // -----------------------------------------------------------
    // testInformationPageIsCreatedEditedAndDeleted
    // -----------------------------------------------------------
    //
    // A page is created with a slug, edited, opened by
    // visitors, and deleted.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testInformationPageIsCreatedEditedAndDeleted(): void
    {
        $this->admin()->from('/admin-panel/information')->post('/admin-panel/information/create', [
            'title'   => 'Cookie policy',
            'content' => '<p>We use session cookies.</p>',
            'lang_id' => $this->en,
        ]);
        $page = DB::table('bei_information')->where('title', 'Cookie policy')->first();
        $this->assertSame('cookie-policy', $page->alias);

        $this->admin()->from("/admin-panel/information/edit/{$page->id}")->post('/admin-panel/information/update', [
            'uid'     => $page->id,
            'title'   => 'Cookie policy',
            'content' => '<p>Only the session cookie.</p>',
            'lang_id' => $this->en,
        ]);
        $this->get("/en/page/cookie-policy/{$page->id}")->assertSee('Only the session cookie.');

        $this->admin()->from('/admin-panel/information')->post("/admin-panel/information/delete/{$page->id}");
        $this->assertFalse(DB::table('bei_information')->where('id', $page->id)->exists());
    }






    // -----------------------------------------------------------
    // testNewInformationPageReturnsToTheList
    // -----------------------------------------------------------
    //
    // A new information page lands the admin on the list of
    // pages, even when a member page is parked in the session
    // from an earlier visit.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testNewInformationPageReturnsToTheList(): void
    {
        $this->get('/cp/dashboard')->assertRedirect('/login');

        $this->admin()->from('/admin-panel/information/new')->post('/admin-panel/information/create', [
            'title'   => 'Opening hours',
            'content' => '<p>Weekdays from nine to five</p>',
            'lang_id' => $this->en,
        ])->assertRedirect('/admin-panel/information');

        $this->assertTrue(DB::table('bei_information')->where('title', 'Opening hours')->exists());
    }






    // -----------------------------------------------------------
    // testSettingsAreSaved
    // -----------------------------------------------------------
    //
    // The three forms store the about text the home page
    // shows, the self-evaluation length and the knowledge-test
    // limits. They save with an UPDATE, so this runs on the
    // rows mysql/init.sql seeds, as a fresh install would.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testSettingsAreSaved(): void
    {
        $category = $this->world->lmCategory($this->en);

        $this->admin()->from('/admin-panel/settings/other')->post('/admin-panel/settings/about_us_save', [
            'about' => [$this->en => ['descr' => 'We train staff to spot phishing', 'link' => 'https://example.test']],
        ]);
        $this->admin()->from('/admin-panel/settings/other')->post('/admin-panel/settings/self_eval_save', ['self_eval' => 4]);
        $this->admin()->from('/admin-panel/settings/other')->post('/admin-panel/settings/knowledge_test_settings_save', [
            'knowledge' => [$this->en => [$category => 5]],
        ]);

        $this->get('/')->assertSee('We train staff to spot phishing');
        $this->assertEquals(4, DB::table('bei_settings')->where('id', 2)->value('val3'));
        $limits = json_decode(DB::table('bei_settings')->where('id', 3)->value('val1'), true);
        $this->assertEquals(5, $limits[$this->en][$category]);
    }






    // -----------------------------------------------------------
    // testEachScenarioOfThePrintoutShowsItsOwnCategories
    // -----------------------------------------------------------
    //
    // The printout of every simulation shows under each
    // scenario its own categories and attributes, not those
    // of the language's last scenario.
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

        $page = $this->admin()->get('/admin-panel/settings/simulations_download_pdf')->assertOk()->getContent();

        // The printout lists a language's scenarios by id, so
        // the page above the second one holds the first one
        $firstScenario = strstr($page, 'A call from the bank', true);
        $this->assertStringContainsString('Smishing', $firstScenario);
        $this->assertStringContainsString('Curiosity', $firstScenario);
        $this->assertStringNotContainsString('Vishing', $firstScenario);
    }






    // -----------------------------------------------------------
    // testSvgNewsPictureIsRefused
    // -----------------------------------------------------------
    //
    // An SVG picture is refused and no article is saved. The
    // form's rule lists svg among the picture types, but
    // Laravel 13's image rule refuses SVG unless it is given
    // allow_svg — an SVG is a document that can carry script,
    // and the site serves pictures from its own origin. Under
    // Laravel 7 the SVG went through; this test was pinned as
    // news-svg-picture until the upgrade released it.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testSvgNewsPictureIsRefused(): void
    {
        $svg = $this->uploadedFile('cover.svg', '<?xml version="1.0" encoding="UTF-8"?>' . "\n"
            . '<svg xmlns="http://www.w3.org/2000/svg" width="60" height="40"><rect width="60" height="40" fill="#336699"/></svg>' . "\n");

        $this->admin()->from('/admin-panel/news')->post('/admin-panel/news/create', [
            'title'   => 'Vector cover',
            'content' => '<p>With a vector cover.</p>',
            'lang_id' => $this->en,
            'feat'    => $svg,
        ])->assertSessionHasErrors('feat');

        $this->assertFalse(DB::table('bei_news')->where('title', 'Vector cover')->exists(), 'the article was saved with an SVG picture');
    }






    // -----------------------------------------------------------
    // publishWithPicture
    // -----------------------------------------------------------
    //
    // Publishes an English article with a PNG picture.
    //
    // Used by:
    //   - testNewsPictureIsStoredForTheSite (above)
    // -----------------------------------------------------------

    private function publishWithPicture(string $title): void
    {
        $this->admin()->from('/admin-panel/news')->post('/admin-panel/news/create', [
            'title'   => $title,
            'content' => '<p>With a cover picture.</p>',
            'lang_id' => $this->en,
            'feat'    => UploadedFile::fake()->image('cover.png', 600, 400),
        ])->assertSessionHas('success');
    }






    // -----------------------------------------------------------
    // admin
    // -----------------------------------------------------------
    //
    // The test signed in as its main admin.
    //
    // Used by:
    //   - publishWithPicture (above) and the other tests of
    //     this class
    // -----------------------------------------------------------

    private function admin(): self
    {
        return $this->signInManager($this->admin);
    }
}
