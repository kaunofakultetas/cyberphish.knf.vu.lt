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
//  simulation, each with its own categories. A language's
//  name and locale are its own, a language in use stays —
//  one with lessons too — and a deleted one takes its
//  members' rows along. An empty body is saved as one, a
//  body with a pasted photo whole, and the editors load the
//  HTML as stored. The panel's news pages reach the panel,
//  a deleted or replaced picture leaves no file behind,
//  removing a picture an article no longer has is harmless,
//  and the article form's script gets its id only as a
//  number. An edit of a deleted article or page is refused,
//  and so is a field of the wrong shape, without a server
//  error.
//
//  Used by:
//    - PHPUnit — the Feature suite (runTests.sh)
// -----------------------------------------------------------


namespace Tests\Feature\Admin;

use Dom\HTMLDocument;
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
    // testLanguageWithSimulationsIsNotDeleted
    // -----------------------------------------------------------
    //
    // A language that still has simulations stays too, with
    // its message: no foreign key holds them, and the delete
    // once left them under a language that was gone.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testLanguageWithSimulationsIsNotDeleted(): void
    {
        $latvian = $this->world->language('lv');
        $this->world->scenario($latvian, [], [$this->world->scenarioCategory($latvian)]);

        $this->admin()->from('/admin-panel/settings/languages')
            ->post("/admin-panel/settings/languages/delete/$latvian")
            ->assertRedirect('/admin-panel/settings/languages')
            ->assertSessionHasErrors();

        $this->assertSame('The language still has simulations, or their categories or attributes!', session('errors')->first());
        $this->assertTrue(DB::table('bei_lang')->where('id', $latvian)->exists());
    }






    // -----------------------------------------------------------
    // testLanguageWithLessonsIsNotDeleted
    // -----------------------------------------------------------
    //
    // A language that still has lessons stays, with its
    // message — lessons whose category was deleted first
    // included. Their foreign key lets the delete through and
    // empties their language, so a lesson with neither once
    // stood on no panel page, to be edited or deleted.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testLanguageWithLessonsIsNotDeleted(): void
    {
        $latvian = $this->world->language('lv');
        $category = $this->world->lmCategory($latvian);
        $lesson = $this->world->lmContent($category, $latvian);
        $this->admin()->from('/admin-panel/lm/categories')->post("/admin-panel/lm/categores/delete/$category");
        $this->assertFalse(DB::table('bei_lm_category')->where('id', $category)->exists());

        $this->admin()->from('/admin-panel/settings/languages')
            ->post("/admin-panel/settings/languages/delete/$latvian")
            ->assertRedirect('/admin-panel/settings/languages')
            ->assertSessionHasErrors();

        $this->assertSame('The language still has lessons!', session('errors')->first());
        $this->assertTrue(DB::table('bei_lang')->where('id', $latvian)->exists());
        $this->assertEquals($latvian, DB::table('bei_lm_content')->where('id', $lesson)->value('lang_id'), 'the lesson lost its language');
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
    // testUpdatesNeedALanguageThatExists
    // -----------------------------------------------------------
    //
    // An edited information page or news article keeps its
    // language unless a language that exists is posted: a
    // missing or unknown one comes back with the validator's
    // message, as on create, where it was once stored as it
    // came and hid the page from every language.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testUpdatesNeedALanguageThatExists(): void
    {
        $page = $this->world->information($this->en);
        $article = $this->world->news($this->en);

        foreach ([[], ['lang_id' => 999999]] as $language) {
            $this->admin()->from('/admin-panel/information')->post('/admin-panel/information/update', ['uid' => $page, 'title' => 'Renamed page'] + $language)
                ->assertSessionHasErrors('lang_id');
            $this->admin()->from('/admin-panel/news')->post('/admin-panel/news/update', ['uid' => $article, 'title' => 'Renamed article'] + $language)
                ->assertSessionHasErrors('lang_id');
        }

        $this->assertEquals($this->en, DB::table('bei_information')->where('id', $page)->value('lang_id'));
        $this->assertEquals($this->en, DB::table('bei_news')->where('id', $article)->value('lang_id'));
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
    // testSelfEvaluationLengthBelowOneIsRefused
    // -----------------------------------------------------------
    //
    // A self-evaluation needs at least one question: a length
    // of 0 comes back with the validator's message and the
    // setting stays as it was.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testSelfEvaluationLengthBelowOneIsRefused(): void
    {
        $before = DB::table('bei_settings')->where('id', 2)->value('val3');

        $this->admin()->from('/admin-panel/settings/other')->post('/admin-panel/settings/self_eval_save', ['self_eval' => 0])
            ->assertSessionHasErrors('self_eval');

        $this->assertEquals($before, DB::table('bei_settings')->where('id', 2)->value('val3'));
    }






    // -----------------------------------------------------------
    // testBlankKnowledgeLimitIsStoredAsZero
    // -----------------------------------------------------------
    //
    // A knowledge-test limit left blank is stored as the 0 the
    // page shows for it, so the test draws nothing from that
    // category.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testBlankKnowledgeLimitIsStoredAsZero(): void
    {
        $category = $this->world->lmCategory($this->en);

        $this->admin()->from('/admin-panel/settings/other')->post('/admin-panel/settings/knowledge_test_settings_save', [
            'knowledge' => [$this->en => [$category => '']],
        ]);

        $limits = json_decode(DB::table('bei_settings')->where('id', 3)->value('val1'), true);
        $this->assertSame('0', $limits[$this->en][$category]);
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
    // testNewsIdNeverReachesThePagesScript
    // -----------------------------------------------------------
    //
    // The article form hands the id of its URL to the remove
    // link of the picture as a number. It once printed the
    // segment as written into the link's onClick, where the
    // quote {{ }} turns into an entity is a quote again: a
    // crafted link closed the string and ran its own code on
    // the admin's click.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testNewsIdNeverReachesThePagesScript(): void
    {
        $news = $this->world->news($this->en, ['feat_img' => 'feat/cover.png']);

        $html = $this->admin()->get("/admin-panel/news/edit/$news');alert(1);('")->assertOk()->getContent();

        $this->assertStringContainsString("Confirm_Dialog('" . env('APP_URL_ADMIN') . "/news/delete_picture/$news')", $html);
        $this->assertStringNotContainsString("delete_picture/$news&#039;", $html);
    }






    // -----------------------------------------------------------
    // testBodyWithAPastedPhotoIsNoServerError
    // -----------------------------------------------------------
    //
    // The news, information and lesson forms save a body
    // with a pasted photo, whole. Summernote puts a picture
    // chosen from a file into the body itself, base64 inside
    // an img tag, and nothing caps the body's size; MySQL
    // refuses a statement over its max_allowed_packet and
    // closes the connection, and the limit is production's
    // 64 MB in the test container too. Each post runs on a
    // connection of its own, as a refused one dies with its
    // transaction, and the stored body is compared by its
    // length, not its megabytes. On the stock 4 MB a body
    // with a photo of about 3 MB or more was once a 500 in
    // each of the three forms, and nothing was saved
    // (rich-text-body-over-packet).
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testBodyWithAPastedPhotoIsNoServerError(): void
    {
        // A photo of about 3.4 MB as Summernote embeds it
        $body = '<p>Spot the fake parcel notice.</p><p><img src="data:image/jpeg;base64,' . str_repeat('QUJD', 1125000) . '"></p>';

        $answers = [];
        $answers['news'] = $this->bodyPostStatus('/admin-panel/news/new', '/admin-panel/news/create', 'bei_news', [
            'title'   => 'Parcel notice with a photo',
            'content' => $body,
            'lang_id' => $this->en,
        ]);
        $answers['information'] = $this->bodyPostStatus('/admin-panel/information/new', '/admin-panel/information/create', 'bei_information', [
            'title'   => 'Parcel notice page with a photo',
            'content' => $body,
            'lang_id' => $this->en,
        ]);
        $answers['lesson'] = $this->bodyPostStatus('/admin-panel/lm/content/new/' . $this->en, '/admin-panel/lm/content/create', 'bei_lm_content', [
            'title'   => 'Parcel notice lesson with a photo',
            'content' => $body,
            'uid'     => $this->en,
            'cat_id'  => $this->world->lmCategory($this->en),
            'hours'   => 1,
        ]);

        $whole = [302, strlen($body)];
        $this->assertSame(['news' => $whole, 'information' => $whole, 'lesson' => $whole], $answers, 'the answers to a body with a pasted photo, with the length of the body stored: ' . json_encode($answers));
    }






    // -----------------------------------------------------------
    // testRichTextEditorsLoadTheStoredHtml
    // -----------------------------------------------------------
    //
    // The news, information and lesson forms hand their
    // editor exactly the HTML that is stored, and a refused
    // edit gets back exactly the body it posted. Summernote
    // takes the content textarea's text as the editor's HTML,
    // so the forms write that text escaped once: angle
    // brackets an admin typed as text, which the editor
    // stores as entities, reach it as text — a sender's
    // address in angle brackets, as a lesson on phishing
    // mails shows one. The Form builder once wrote the text
    // with double encoding off, so such an address reached
    // the editor as an unknown element and vanished with the
    // next save, and a script tag typed as an example ran in
    // the admin's browser as the form opened
    // (rich-text-edit-unescapes).
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testRichTextEditorsLoadTheStoredHtml(): void
    {
        $category = $this->world->lmCategory($this->en);
        $html = '<p>From: "PayPal" &lt;service@paypa1.example&gt;</p>';
        $pages = [
            '/admin-panel/news/edit/' . $this->world->news($this->en, ['content' => $html]),
            '/admin-panel/information/edit/' . $this->world->information($this->en, ['content' => $html]),
            '/admin-panel/lm/content/edit/' . $this->world->lmContent($category, $this->en, ['content' => $html]),
        ];

        $loaded = [];
        foreach ($pages as $page) {
            $document = HTMLDocument::createFromString($this->admin()->get($page)->assertOk()->getContent(), LIBXML_NOERROR);
            $loaded[$page] = $document->querySelector('textarea#summernote')->textContent;
        }

        foreach ($loaded as $page => $text) {
            $this->assertSame($html, $text, "$page hands its editor other HTML than the stored");
        }

        // An edit refused for its empty title comes back with
        // the body it posted, not the one stored
        $article = $this->world->news($this->en);
        $this->admin()->from("/admin-panel/news/edit/$article")->post('/admin-panel/news/update', [
            'uid'     => $article,
            'title'   => '',
            'content' => $html,
            'lang_id' => $this->en,
        ])->assertSessionHasErrors('title');
        $document = HTMLDocument::createFromString($this->admin()->get("/admin-panel/news/edit/$article")->assertOk()->getContent(), LIBXML_NOERROR);
        $this->assertSame($html, $document->querySelector('textarea#summernote')->textContent, 'the refused edit lost the body it posted');
    }






    // -----------------------------------------------------------
    // testDeletedArticleTakesItsPictureAlong
    // -----------------------------------------------------------
    //
    // Deleting a news article takes its picture file along,
    // where the folder is writable, as the article form's
    // remove link and a lesson's delete take theirs. The
    // picture once stayed in storage/app/public/feat, still
    // served at its old address under /upload/feat — one
    // left behind for good with every deleted article
    // (news-delete-keeps-picture).
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testDeletedArticleTakesItsPictureAlong(): void
    {
        $this->publishWithPicture('Article with a cover');
        $article = DB::table('bei_news')->where('title', 'Article with a cover')->first();
        $picture = storage_path('app/public/' . $article->feat_img);
        $this->assertFileExists($picture);

        $this->admin()->from('/admin-panel/news')->post("/admin-panel/news/delete/{$article->id}")->assertRedirect();
        $this->assertFalse(DB::table('bei_news')->where('id', $article->id)->exists());

        $left = is_file($picture);
        @unlink($picture);

        $this->assertFalse($left, 'the deleted article\'s picture is still on disk and served');
    }






    // -----------------------------------------------------------
    // testReplacingAPictureRemovesTheOldFile
    // -----------------------------------------------------------
    //
    // A picture posted with the edit form of an article that
    // has one replaces the file too: the new picture is
    // stored and served, and the old file goes, as it goes
    // with a deleted article. The replaced file once stayed
    // in storage/app/public/feat, still public
    // (news-delete-keeps-picture).
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testReplacingAPictureRemovesTheOldFile(): void
    {
        $this->publishWithPicture('Article with a new cover');
        $article = DB::table('bei_news')->where('title', 'Article with a new cover')->first();
        $old = storage_path('app/public/' . $article->feat_img);
        $this->assertFileExists($old);

        $this->admin()->from("/admin-panel/news/edit/{$article->id}")->post('/admin-panel/news/update', [
            'uid'     => $article->id,
            'title'   => 'Article with a new cover',
            'content' => '<p>With a cover picture.</p>',
            'lang_id' => $this->en,
            'feat'    => UploadedFile::fake()->image('new-cover.png', 600, 400),
        ])->assertSessionHas('success');
        $new = storage_path('app/public/' . DB::table('bei_news')->where('id', $article->id)->value('feat_img'));

        $stored = $new !== $old && is_file($new);
        $left = is_file($old);
        @unlink($old);
        @unlink($new);

        $this->assertTrue($stored, 'the new picture was not stored');
        $this->assertFalse($left, 'the replaced picture is still on disk and served');
    }






    // -----------------------------------------------------------
    // testEditOfADeletedItemIsRefused
    // -----------------------------------------------------------
    //
    // The news and information edit forms refuse an article
    // or page deleted while the form stood open — in another
    // tab, or by another admin — with an error, store
    // nothing and leave no new picture on disk, as the forms
    // that add a file to a lesson, or a category or a lesson
    // to a language, refuse a parent that is gone
    // (unchecked-hidden-ids). They once updated whatever row
    // their hidden id named, answered "Updated!" when none
    // did, and the news form first moved its new picture
    // into storage/app/public/feat, where no row pointed at
    // it (update-of-a-deleted-item).
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testEditOfADeletedItemIsRefused(): void
    {
        $article = $this->world->news($this->en);
        $page = $this->world->information($this->en);
        DB::table('bei_news')->where('id', $article)->delete();
        DB::table('bei_information')->where('id', $page)->delete();
        $before = glob(storage_path('app/public/feat/*')) ?: [];

        $this->admin()->from("/admin-panel/news/edit/$article")->post('/admin-panel/news/update', [
            'uid'     => $article,
            'title'   => 'Edited after its delete',
            'content' => '<p>Edited.</p>',
            'lang_id' => $this->en,
            'feat'    => UploadedFile::fake()->image('cover.png', 600, 400),
        ]);
        $newsAnswer = session('success');
        $this->admin()->from("/admin-panel/information/edit/$page")->post('/admin-panel/information/update', [
            'uid'     => $page,
            'title'   => 'Edited after its delete',
            'content' => '<p>Edited.</p>',
            'lang_id' => $this->en,
        ]);
        $pageAnswer = session('success');

        $added = array_values(array_diff(glob(storage_path('app/public/feat/*')) ?: [], $before));
        foreach ($added as $path) {
            @unlink($path);
        }

        $problems = [];
        if ($added !== []) {
            $problems[] = 'the picture was left on disk';
        }
        if ($newsAnswer !== null) {
            $problems[] = "the news edit answered \"$newsAnswer\"";
        }
        if ($pageAnswer !== null) {
            $problems[] = "the information edit answered \"$pageAnswer\"";
        }
        $this->assertSame([], $problems, implode('; ', $problems));
    }






    // -----------------------------------------------------------
    // testDeletedLanguageLeavesNothingOfItsMembers
    // -----------------------------------------------------------
    //
    // A language whose content is all gone takes its
    // members' rows along when it is deleted — badges,
    // knowledge tests, self-evaluations and simulation runs —
    // and the same member's rows of another language stay.
    // The content goes through the panel's own deletes
    // first, as an admin retiring a language would do it.
    // MySQL 5.7 gives the highest id out again after a
    // restart, so a language added later can take the
    // deleted one's id. The rows once stayed behind for such
    // a language to inherit — a passed knowledge test, and
    // with it the certificate, in a language the member
    // never studied (language-delete-orphans).
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testDeletedLanguageLeavesNothingOfItsMembers(): void
    {
        $maltese = $this->world->language('mt');
        $member = $this->world->member(['country' => 5]);
        $this->world->badge($member->id, $maltese, 'badge_final_test');
        $this->world->knowledgeAttempt($member->id, $maltese, ['results' => 90]);
        $category = $this->world->lmCategory($maltese);
        $this->world->selfEvalAttempt($member->id, $category, $maltese, ['points' => 80]);
        $scenario = $this->world->scenario($maltese);
        $this->world->scenarioAttempt($member->id, $scenario, $maltese, ['points' => 400]);

        // A badge of the same member in English, which stays
        $english = $this->world->badge($member->id, $this->en, 'badge_final_test');

        // The content goes first, through the panel's own deletes
        $this->admin()->from('/admin-panel/lm/categories')
            ->post("/admin-panel/lm/categores/delete/$category");
        $this->admin()->from('/admin-panel/admin_simulations')
            ->post("/admin-panel/admin_simulations/delete/$scenario");
        $this->assertFalse(DB::table('bei_lm_category')->where('id', $category)->exists(), 'the category was not deleted');
        $this->assertFalse(DB::table('bei_scenarios')->where('id', $scenario)->exists(), 'the simulation was not deleted');

        $this->admin()->from('/admin-panel/settings/languages')
            ->post("/admin-panel/settings/languages/delete/$maltese")
            ->assertRedirect('/admin-panel/settings/languages');

        $gone = !DB::table('bei_lang')->where('id', $maltese)->exists();
        $left = [];
        foreach (['bei_users_badges', 'bei_users_knowledge_test', 'bei_users_selfeval_test', 'bei_users_scenarios'] as $table) {
            if (DB::table($table)->where('lang_id', $maltese)->exists()) {
                $left[] = $table;
            }
        }

        $this->assertTrue($gone, 'the language was not deleted');
        $this->assertSame([], $left, 'the deleted language left its members\' rows in ' . implode(', ', $left));
        $this->assertTrue(DB::table('bei_users_badges')->where('id', $english)->exists(), 'the member\'s English badge went with the other language');
    }






    // -----------------------------------------------------------
    // testDeletedLanguageLeavesTheLoginBadge
    // -----------------------------------------------------------
    //
    // badge_login_10 stays when the language it was filed
    // under is deleted, and the badges page goes on showing it
    // as held: the badge is the member's in every language,
    // filed under the session's language when it was earned,
    // which need not be one the member studies.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testDeletedLanguageLeavesTheLoginBadge(): void
    {
        $maltese = $this->world->language('mt');
        $member = $this->world->member();
        $this->world->badge($member->id, $maltese, 'badge_login_10');

        $this->admin()->from('/admin-panel/settings/languages')
            ->post("/admin-panel/settings/languages/delete/$maltese")
            ->assertRedirect('/admin-panel/settings/languages');

        $this->assertFalse(DB::table('bei_lang')->where('id', $maltese)->exists(), 'the language was not deleted');
        $this->signInMember($member)->get('/cp/badges')->assertOk()->assertSee('/badges/badge_login_10.png', false);
    }






    // -----------------------------------------------------------
    // testLocaleOfAnotherLanguageIsRefused
    // -----------------------------------------------------------
    //
    // The language dialog refuses a locale another language
    // has, on adding a language and on editing one, as it
    // refuses a name another has, while an edit that keeps
    // the language's own locale saves. The site finds a
    // language by its locale and takes the first row that
    // has it (Langs::getLangId). The dialog once took any
    // locale, so of two languages sharing one the other's
    // news, lessons, tests and simulations reached no
    // visitor, while the panel gave it tabs of its own
    // (language-locale-taken).
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testLocaleOfAnotherLanguageIsRefused(): void
    {
        $this->admin()->from('/admin-panel/settings/languages')
            ->post('/admin-panel/dialog/lang/create', ['name' => 'English (UK)', 'locale' => 'en']);
        $afterCreate = DB::table('bei_lang')->where('locale', 'en')->count();

        $latvian = $this->world->language('lv');
        $this->admin()->from('/admin-panel/settings/languages')
            ->post('/admin-panel/dialog/lang/update', ['uid' => $latvian, 'name' => 'Latvian', 'locale' => 'en']);
        $afterUpdate = DB::table('bei_lang')->where('locale', 'en')->count();

        $this->assertSame([1, 1], [$afterCreate, $afterUpdate], 'languages with the locale en after the new language and after the edit');

        // The same edit with the language's own locale saves
        $this->admin()->from('/admin-panel/settings/languages')
            ->post('/admin-panel/dialog/lang/update', ['uid' => $latvian, 'name' => 'Latvian', 'locale' => 'lv'])
            ->assertSessionHas('success');
        $this->assertSame('Latvian', DB::table('bei_lang')->where('id', $latvian)->value('name'));
    }






    // -----------------------------------------------------------
    // testPanelFormsRefuseFieldsOfTheWrongShape
    // -----------------------------------------------------------
    //
    // The panel's forms refuse a field of the wrong shape
    // without a server error: a list posted where they read
    // a text, and a plain value where the question editors
    // expect one value per option. That holds for the
    // language dialog's create and update, the news and
    // information forms, the new lesson, the new
    // learning-material category, the new simulation category
    // and attribute, and the self-evaluation and
    // knowledge-test question editors, as it does for the
    // manager and password dialogs. Their rules — required,
    // min and max — once let a list through, counting its
    // items, to Str::slug, the unique rule's query or the
    // insert itself, and the editors indexed a plain value
    // like a list, a 500 each (admin-form-list-500).
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testPanelFormsRefuseFieldsOfTheWrongShape(): void
    {
        $news = $this->world->news($this->en);
        $info = $this->world->information($this->en);
        $category = $this->world->lmCategory($this->en);
        $selfEval = $this->world->selfEvalQuestion($category, [['Right', true, 100], ['Wrong', false, 0]]);
        $knowledge = $this->world->knowledgeQuestion($category, [['Right', true], ['Wrong', false]]);

        $posts = [
            'new language, name as a list'            => ['/admin-panel/dialog/lang/create', ['name' => ['Listed'], 'locale' => 'xx']],
            'language update, id as a list'           => ['/admin-panel/dialog/lang/update', ['uid' => [(string) $this->en], 'name' => 'English', 'locale' => 'en']],
            'new article, title as a list'            => ['/admin-panel/news/create', ['title' => ['Title'], 'content' => 'Body', 'lang_id' => (string) $this->en]],
            'article update, title as a list'         => ['/admin-panel/news/update', ['uid' => (string) $news, 'title' => ['Title'], 'content' => 'Body', 'lang_id' => (string) $this->en]],
            'new page, body as a list'                => ['/admin-panel/information/create', ['title' => 'Title', 'content' => ['Body'], 'lang_id' => (string) $this->en]],
            'page update, title as a list'            => ['/admin-panel/information/update', ['uid' => (string) $info, 'title' => ['Title'], 'content' => 'Body', 'lang_id' => (string) $this->en]],
            'new lesson, hours as a list'             => ['/admin-panel/lm/content/create', ['title' => 'Lesson', 'uid' => (string) $this->en, 'cat_id' => (string) $category, 'content' => 'Body', 'hours' => ['1']]],
            'new category, name as a list'            => ['/admin-panel/dialog/lm/categories/create', ['name' => ['Category'], 'uid' => (string) $this->en]],
            'new simulation category, name as a list' => ['/admin-panel/dialog/simulations/categories/create', ['name' => ['Category'], 'uid' => (string) $this->en]],
            'new attribute, name as a list'           => ['/admin-panel/dialog/simulations/attributes/create', ['name' => ['Attribute'], 'uid' => (string) $this->en]],
            'self-evaluation question, one value'     => ['/admin-panel/lm/self_eval/update/' . $selfEval['question'], [
                'q' => 'Question', 'q_type' => '1',
                'option' => [$selfEval['options'][0] => 'Right', $selfEval['options'][1] => 'Wrong'],
                'correct' => '1',
                'points' => [$selfEval['options'][0] => '100', $selfEval['options'][1] => '0'],
            ]],
            'knowledge question, one value'           => ['/admin-panel/lm/knowledge_test/update/' . $knowledge['question'], [
                'q' => 'Question',
                'option' => [$knowledge['options'][0] => 'Right', $knowledge['options'][1] => 'Wrong'],
                'correct' => '1',
            ]],
        ];

        $statuses = [];
        foreach ($posts as $label => list($path, $body)) {
            $statuses[$label] = $this->admin()->from('/admin-panel/dashboard')->post($path, $body)->getStatusCode();
        }

        $this->assertSame([], array_keys(array_filter($statuses, function (int $status) {
            return $status >= 500;
        })), 'posts answered with a server error');
    }






    // -----------------------------------------------------------
    // publishWithPicture
    // -----------------------------------------------------------
    //
    // Publishes an English article with a PNG picture.
    //
    // Used by:
    //   - testNewsPictureIsStoredForTheSite,
    //     testDeletedArticleTakesItsPictureAlong and
    //     testReplacingAPictureRemovesTheOldFile (above)
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
    // bodyPostStatus
    // -----------------------------------------------------------
    //
    // Posts a form as a fresh main admin — the class's own
    // goes with the first connection dropped — and returns
    // the answer's status with the length of the body the
    // table then holds under the posted title, or null when
    // it holds none. Both are read before the test carries
    // on over a fresh connection (freshConnection), which
    // takes the stored row away again.
    //
    // Used by:
    //   - testBodyWithAPastedPhotoIsNoServerError (above)
    // -----------------------------------------------------------

    private function bodyPostStatus(string $from, string $path, string $table, array $fields): array
    {
        $status = $this->signInManager($this->world->manager())->from($from)->post($path, $fields)->getStatusCode();

        // A server error may have taken the connection along,
        // and a query on it would end the test in an error
        $stored = $status < 500 ? DB::table($table)->where('title', $fields['title'])->value('content') : null;
        $this->freshConnection();
        return [$status, $stored === null ? null : strlen($stored)];
    }






    // -----------------------------------------------------------
    // freshConnection
    // -----------------------------------------------------------
    //
    // MySQL closes the connection over a packet larger than
    // its max_allowed_packet, and the test's transaction goes
    // with it. This drops the connection — what its
    // transaction held is rolled back — and opens a new one
    // inside a new transaction, which the suite's tearDown
    // rolls back. The guards are built again too, as their
    // user providers hold the connection they were made with.
    //
    // Used by:
    //   - bodyPostStatus (above)
    // -----------------------------------------------------------

    private function freshConnection(): void
    {
        DB::purge();
        DB::beginTransaction();
        $this->app['auth']->forgetGuards();
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
