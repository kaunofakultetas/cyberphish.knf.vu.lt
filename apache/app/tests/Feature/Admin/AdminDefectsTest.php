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
//    rich-text-body-over-packet — a news, information or
//      lesson body with a pasted photo over MySQL's 4 MB
//      packet is a 500, and nothing is saved
//    rich-text-edit-unescapes — the editors load the stored
//      HTML with one level of escaping undone, so an edit
//      and save corrupts it
//    news-delete-keeps-picture — deleting a news article
//      leaves its picture on disk
//    update-of-a-deleted-item — saving an article or page
//      deleted meanwhile says Updated! and keeps the new
//      picture
//    language-delete-orphans — deleting a language leaves
//      its members' rows behind
//    language-locale-taken — the language dialog takes a
//      locale another language has
//    selfeval-history-deleted-question — deleting a
//      question turns members' right answers wrong in the
//      panel's history
//    selfeval-history-duplicate-answer — the panel's
//      history counts an answer stored twice as wrong
//    question-import-encoding — a question file not in
//      UTF-8 is stored cut short, or not at all
//    question-import-bom — a question file with a byte
//      order mark loses its first question
//    simulation-import-level-text — a level written as text
//      with a space passes the check, then the save is a
//      500
//    simulation-import-unreadable — a file named .xlsx that
//      is no spreadsheet makes the import a 500
//    simulation-categories-gone — the categories dialog of
//      a deleted simulation files it under categories
//    simulation-delete-badge — taking a member's last
//      simulation off the offer awards no
//      badge_all_simulations
//    admin-knowledge-empty-label — the empty
//      knowledge-question list speaks of self-evaluation
//      questions
//    panel-login-bounces-members — a signed-in member can
//      neither open nor use the panel login
//    admin-form-list-500 — panel forms answer a field sent
//      as a list with a 500
//    admin-report-unknown-ids — a main admin's reports of
//      an unknown run or member open as blank pages
//    category-delete-ranking — a deleted category's
//      attempts still count in the ranking
//    admin-login-email-list-500 — the panel login page is a
//      500 after an email posted as a list
//
//  Used by:
//    - PHPUnit — the Feature suite (runTests.sh)
// -----------------------------------------------------------


namespace Tests\Feature\Admin;

use Dom\HTMLDocument;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\FeatureTestCase;
use Tests\Support\Flows;
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

    // Starting and answering self-evaluations and knowledge
    // tests
    use Flows;

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
    // testBodyWithAPastedPhotoIsNoServerError
    // -----------------------------------------------------------
    //
    // The news, information and lesson forms edit their body
    // in Summernote, which puts a picture chosen from a file
    // into the body itself, base64 inside an img tag, and
    // nothing caps the body's size. MySQL refuses a statement
    // over its max_allowed_packet — 4 MB on the stock 5.7
    // settings, which production and the test container both
    // run — and closes the connection: a body with a photo of
    // about 3 MB or more answers each of the three forms with
    // a 500, and nothing is saved. Wanted: such a body is
    // saved, or the form comes back with an error that says
    // why, never a server error. Each post runs on a
    // connection of its own, as the refused one dies with
    // its transaction.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testBodyWithAPastedPhotoIsNoServerError(): void
    {
        $en = $this->world->language('en');

        // A photo of about 3.4 MB as Summernote embeds it
        $body = '<p>Spot the fake parcel notice.</p><p><img src="data:image/jpeg;base64,' . str_repeat('QUJD', 1125000) . '"></p>';

        $statuses = [];
        $statuses['news'] = $this->bodyPostStatus('/admin-panel/news/new', '/admin-panel/news/create', [
            'title'   => 'Parcel notice with a photo',
            'content' => $body,
            'lang_id' => $en,
        ]);
        $statuses['information'] = $this->bodyPostStatus('/admin-panel/information/new', '/admin-panel/information/create', [
            'title'   => 'Parcel notice page with a photo',
            'content' => $body,
            'lang_id' => $en,
        ]);
        $statuses['lesson'] = $this->bodyPostStatus('/admin-panel/lm/content/new/' . $en, '/admin-panel/lm/content/create', [
            'title'   => 'Parcel notice lesson with a photo',
            'content' => $body,
            'uid'     => $en,
            'cat_id'  => $this->world->lmCategory($en),
            'hours'   => 1,
        ]);

        $this->pinDefect('rich-text-body-over-packet', function () use ($statuses) {
            $this->assertSame(['news' => 302, 'information' => 302, 'lesson' => 302], $statuses, 'the answers to a body with a pasted photo: ' . json_encode($statuses));
        });
    }






    // -----------------------------------------------------------
    // bodyPostStatus
    // -----------------------------------------------------------
    //
    // Posts a form as a fresh main admin and returns the
    // answer's status, then carries on over a fresh
    // connection (freshConnection).
    //
    // Used by:
    //   - testBodyWithAPastedPhotoIsNoServerError (above)
    // -----------------------------------------------------------

    private function bodyPostStatus(string $from, string $path, array $fields): int
    {
        $status = $this->signInManager($this->world->manager())->from($from)->post($path, $fields)->getStatusCode();
        $this->freshConnection();
        return $status;
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
    // testRichTextEditorsLoadTheStoredHtml
    // -----------------------------------------------------------
    //
    // The news, information and lesson forms hand the stored
    // HTML to Summernote through their content textarea, and
    // Summernote takes the textarea's text as the editor's
    // HTML. The Form builder writes that text with double
    // encoding off, so every entity already in the HTML comes
    // through decoded: angle brackets an admin typed as text,
    // which the editor stored as entities, reach the editor as
    // markup. A sender's address in angle brackets, as a
    // lesson on phishing mails shows one, turns into an
    // unknown element and vanishes from the editor, and from
    // the public page with the next save of the form; a
    // script or image tag typed as an example comes alive in
    // the admin's browser as the form opens. Wanted: each
    // editor gets exactly the HTML that is stored.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testRichTextEditorsLoadTheStoredHtml(): void
    {
        $en = $this->world->language('en');
        $admin = $this->world->manager();
        $category = $this->world->lmCategory($en);
        $html = '<p>From: "PayPal" &lt;service@paypa1.example&gt;</p>';
        $pages = [
            '/admin-panel/news/edit/' . $this->world->news($en, ['content' => $html]),
            '/admin-panel/information/edit/' . $this->world->information($en, ['content' => $html]),
            '/admin-panel/lm/content/edit/' . $this->world->lmContent($category, $en, ['content' => $html]),
        ];

        $loaded = [];
        foreach ($pages as $page) {
            $document = HTMLDocument::createFromString($this->signInManager($admin)->get($page)->assertOk()->getContent(), LIBXML_NOERROR);
            $loaded[$page] = $document->querySelector('textarea#summernote')->textContent;
        }

        $this->pinDefect('rich-text-edit-unescapes', function () use ($loaded, $html) {
            foreach ($loaded as $page => $text) {
                $this->assertSame($html, $text, "$page hands its editor other HTML than the stored");
            }
        });
    }






    // -----------------------------------------------------------
    // testDeletedArticleTakesItsPictureAlong
    // -----------------------------------------------------------
    //
    // Deleting a news article removes its row only: the
    // picture stays in storage/app/public/feat, still served
    // at its old address under /upload/feat, while the
    // article form's remove link and a lesson's delete both
    // take their files off the disk. Every deleted article
    // with a picture leaves one behind for good. Wanted: the
    // picture goes with its article, where the folder is
    // writable.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testDeletedArticleTakesItsPictureAlong(): void
    {
        $en = $this->world->language('en');
        $admin = $this->world->manager();
        $this->signInManager($admin)->from('/admin-panel/news/new')->post('/admin-panel/news/create', [
            'title'   => 'Article with a cover',
            'content' => '<p>With a cover picture.</p>',
            'lang_id' => $en,
            'feat'    => UploadedFile::fake()->image('cover.png', 600, 400),
        ])->assertSessionHas('success');
        $article = DB::table('bei_news')->where('title', 'Article with a cover')->first();
        $picture = storage_path('app/public/' . $article->feat_img);
        $this->assertFileExists($picture);

        $this->signInManager($admin)->from('/admin-panel/news')->post("/admin-panel/news/delete/{$article->id}")->assertRedirect();
        $this->assertFalse(DB::table('bei_news')->where('id', $article->id)->exists());

        $left = is_file($picture);
        @unlink($picture);

        $this->pinDefect('news-delete-keeps-picture', function () use ($left) {
            $this->assertFalse($left, 'the deleted article\'s picture is still on disk and served');
        });
    }






    // -----------------------------------------------------------
    // testEditOfADeletedItemIsRefused
    // -----------------------------------------------------------
    //
    // The news and information edit forms update whatever row
    // their hidden id names and answer "Updated!" when none
    // does: a form left open while its article or page was
    // deleted — in another tab, or by another admin — reports
    // a save that stored nothing, and the news form first
    // moves its new picture into storage/app/public/feat,
    // where no row points at it. Wanted: the edit of an item
    // that is gone comes back with an error and leaves
    // nothing on disk, as the forms that add a file to a
    // lesson, or a category or a lesson to a language, refuse
    // a parent that is gone (unchecked-hidden-ids).
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testEditOfADeletedItemIsRefused(): void
    {
        $en = $this->world->language('en');
        $admin = $this->world->manager();
        $article = $this->world->news($en);
        $page = $this->world->information($en);
        DB::table('bei_news')->where('id', $article)->delete();
        DB::table('bei_information')->where('id', $page)->delete();
        $before = glob(storage_path('app/public/feat/*')) ?: [];

        $this->signInManager($admin)->from("/admin-panel/news/edit/$article")->post('/admin-panel/news/update', [
            'uid'     => $article,
            'title'   => 'Edited after its delete',
            'content' => '<p>Edited.</p>',
            'lang_id' => $en,
            'feat'    => UploadedFile::fake()->image('cover.png', 600, 400),
        ]);
        $newsAnswer = session('success');
        $this->signInManager($admin)->from("/admin-panel/information/edit/$page")->post('/admin-panel/information/update', [
            'uid'     => $page,
            'title'   => 'Edited after its delete',
            'content' => '<p>Edited.</p>',
            'lang_id' => $en,
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
        $this->pinDefect('update-of-a-deleted-item', function () use ($problems) {
            $this->assertSame([], $problems, implode('; ', $problems));
        });
    }






    // -----------------------------------------------------------
    // testDeletedLanguageLeavesNothingOfItsMembers
    // -----------------------------------------------------------
    //
    // A language whose content is all gone can be deleted,
    // but rows its members earned in it stay behind: badges,
    // knowledge tests and self-evaluations. The content goes
    // through the panel's own deletes first, as an admin
    // retiring a language would do it; a simulation's delete
    // takes its members' runs along, while a category's
    // delete leaves its self-evaluations. MySQL 5.7 gives the
    // highest id out again after a restart, so a language
    // added later can take the deleted one's id and inherit
    // those rows — a passed knowledge test, and with it the
    // certificate, in a language the member never studied.
    // Wanted: the members' rows of the language go with it,
    // or the delete is refused while they exist, as it is
    // for content.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testDeletedLanguageLeavesNothingOfItsMembers(): void
    {
        $admin = $this->world->manager();
        $maltese = $this->world->language('mt');
        $member = $this->world->member(['country' => 5]);
        $this->world->badge($member->id, $maltese, 'badge_final_test');
        $this->world->knowledgeAttempt($member->id, $maltese, ['results' => 90]);
        $category = $this->world->lmCategory($maltese);
        $this->world->selfEvalAttempt($member->id, $category, $maltese, ['points' => 80]);
        $scenario = $this->world->scenario($maltese);
        $this->world->scenarioAttempt($member->id, $scenario, $maltese, ['points' => 400]);

        // The content goes first, through the panel's own deletes
        $this->signInManager($admin)->from('/admin-panel/lm/categories')
            ->post("/admin-panel/lm/categores/delete/$category");
        $this->signInManager($admin)->from('/admin-panel/admin_simulations')
            ->post("/admin-panel/admin_simulations/delete/$scenario");
        $this->assertFalse(DB::table('bei_lm_category')->where('id', $category)->exists(), 'the category was not deleted');
        $this->assertFalse(DB::table('bei_scenarios')->where('id', $scenario)->exists(), 'the simulation was not deleted');

        $this->signInManager($admin)->from('/admin-panel/settings/languages')
            ->post("/admin-panel/settings/languages/delete/$maltese")
            ->assertRedirect('/admin-panel/settings/languages');

        $gone = !DB::table('bei_lang')->where('id', $maltese)->exists();
        $left = [];
        foreach (['bei_users_badges', 'bei_users_knowledge_test', 'bei_users_selfeval_test', 'bei_users_scenarios'] as $table) {
            if (DB::table($table)->where('lang_id', $maltese)->exists()) {
                $left[] = $table;
            }
        }

        $this->pinDefect('language-delete-orphans', function () use ($gone, $left) {
            $this->assertFalse($gone && $left !== [], 'the deleted language left its members\' rows in ' . implode(', ', $left));
        });
    }






    // -----------------------------------------------------------
    // testLocaleOfAnotherLanguageIsRefused
    // -----------------------------------------------------------
    //
    // The language dialog keeps the name unique, as bei_lang's
    // key does, but takes any locale, one another language
    // has included, on adding a language and on editing one.
    // The site finds a language by its locale and takes the
    // first row that has it (Langs::getLangId), so of two
    // languages sharing a locale the other one's news,
    // lessons, tests and simulations reach no visitor, while
    // the panel gives it tabs of its own. Wanted: a locale
    // another language has is refused, on both posts.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testLocaleOfAnotherLanguageIsRefused(): void
    {
        $admin = $this->world->manager();
        $this->signInManager($admin)->from('/admin-panel/settings/languages')
            ->post('/admin-panel/dialog/lang/create', ['name' => 'English (UK)', 'locale' => 'en']);
        $afterCreate = DB::table('bei_lang')->where('locale', 'en')->count();

        $latvian = $this->world->language('lv');
        $this->signInManager($admin)->from('/admin-panel/settings/languages')
            ->post('/admin-panel/dialog/lang/update', ['uid' => $latvian, 'name' => 'Latvian', 'locale' => 'en']);
        $afterUpdate = DB::table('bei_lang')->where('locale', 'en')->count();

        $this->pinDefect('language-locale-taken', function () use ($afterCreate, $afterUpdate) {
            $this->assertSame([1, 1], [$afterCreate, $afterUpdate], 'languages with the locale en after the new language and after the edit');
        });
    }






    // -----------------------------------------------------------
    // testSelfEvaluationHistoryKeepsTheRightAnswersOfADeletedQuestion
    // -----------------------------------------------------------
    //
    // A member answers both questions of a self-evaluation
    // right, and the history shows 2/2. Deleting a question in
    // the panel removes its options and keeps the members'
    // answers to it, each stored with its correct flag; the
    // history's question_correct compares those answers with
    // the question's options as they are now, none, and calls
    // the question wrong, so the same attempt, its points
    // unchanged, reads 1/2. Wanted: deleting a question turns
    // no right answer of an earlier attempt into a wrong one —
    // the history reads it from the stored flags, or leaves
    // the deleted question out.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testSelfEvaluationHistoryKeepsTheRightAnswersOfADeletedQuestion(): void
    {
        $en = $this->world->language('en');
        $this->world->setting(2, 'self_eval_pertime', null, null, 2);
        $category = $this->world->lmCategory($en);
        $kept = $this->world->selfEvalQuestion($category, [['Right', true, 100], ['Wrong', false, 0]]);
        $doomed = $this->world->selfEvalQuestion($category, [['Right', true, 100], ['Wrong', false, 0]]);
        $member = $this->world->member();
        $publicId = $this->startSelfEvaluation($member, $category);
        foreach ([$kept, $doomed] as $question) {
            $this->answerSelfEvaluation($member, $publicId, $category, $question['question'], [$question['options'][0]]);
        }
        $this->signInMember($member)->get("/cp/se/$publicId/$category");
        $this->assertSame(1, (int) DB::table('bei_users_selfeval_test')->where('public_id', $publicId)->value('finished'));
        $admin = $this->world->manager();
        $history = "/admin-panel/user/self_evaluation_history/$en/{$member->id}";
        $before = $this->signInManager($admin)->get($history)->assertOk()->viewData('recordlist')->items()[0];
        $this->assertSame([2, 2], [$before['correct'], $before['questions']]);

        $this->signInManager($admin)->from("/admin-panel/lm/self_eval/$category")
            ->post("/admin-panel/lm/self_eval/delete/{$doomed['question']}")->assertRedirect();
        $after = $this->signInManager($admin)->get($history)->assertOk()->viewData('recordlist')->items()[0];

        $this->pinDefect('selfeval-history-deleted-question', function () use ($after) {
            $this->assertSame(0, $after['questions'] - $after['correct'], "the attempt reads {$after['correct']}/{$after['questions']} after the delete");
        });
    }






    // -----------------------------------------------------------
    // testSelfEvaluationHistoryCountsAnAnswerStoredTwiceOnce
    // -----------------------------------------------------------
    //
    // Two answers to one question that arrived together were
    // each stored before the attempt's row lock came in, so an
    // attempt can hold its right option twice. The member's
    // points count it once and the member's results page marks
    // it chosen once, but question_correct, behind the
    // panel's self-evaluation history, compares the number of
    // option rows with the number of correct options, two
    // against one, and calls the question wrong: the history
    // shows 0/1 for an attempt that scored full points.
    // Wanted: the history counts each chosen option once, 1/1.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testSelfEvaluationHistoryCountsAnAnswerStoredTwiceOnce(): void
    {
        $member = $this->world->member();
        $en = $this->world->language('en');
        $category = $this->world->lmCategory($en);
        $attempt = $this->world->selfEvalAttempt($member->id, $category, $en, ['points' => 100]);
        $publicId = DB::table('bei_users_selfeval_test')->where('id', $attempt)->value('public_id');
        $question = $this->world->selfEvalQuestion($category, [['Right', true, 100], ['Wrong', false, 0]]);
        foreach ([1, 2] as $copy) {
            DB::table('bei_users_selfeval')->insert([
                'cat_id'      => $category,
                'test_id'     => $attempt,
                'public_id'   => $publicId,
                'user_id'     => $member->id,
                'question_id' => $question['question'],
                'option_id'   => $question['options'][0],
                'correct'     => 1,
                'points'      => 100,
            ]);
        }

        $row = $this->signInManager($this->world->manager())->get("/admin-panel/user/self_evaluation_history/$en/{$member->id}")
            ->assertOk()->viewData('recordlist')->items()[0];

        $this->pinDefect('selfeval-history-duplicate-answer', function () use ($row) {
            $this->assertSame([1, 1], [$row['correct'], $row['questions']], 'right answers out of the questions answered');
        });
    }






    // -----------------------------------------------------------
    // testFileInAnotherEncodingIsReadOrRefused
    // -----------------------------------------------------------
    //
    // The question imports store the file's bytes as they
    // come, and the connection's strict mode is off, so MySQL
    // keeps what it cannot read as UTF-8 only with a warning.
    // A Lithuanian file saved as Windows-1257, the Baltic
    // "ANSI" of Windows editors, keeps its options only up to
    // their first Lithuanian letter, and a file saved as UTF-16,
    // Notepad's "Unicode", stores nothing at all — both under
    // "Questions imported!". Wanted: the texts read as written,
    // or the file goes back with a message and nothing stored.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testFileInAnotherEncodingIsReadOrRefused(): void
    {
        $question = "K\\: Kuri nuoroda saugi?\n=Ta, kurią įvedėte patys\n~Nuoroda iš laiško\n";
        $files = [
            'Windows-1257' => "K\\: Kuri nuoroda saugi?\n=Ta, kuri\xE0 \xE1ved\xEBte patys\n~Nuoroda i\xF0 lai\xF0ko\n",
            'UTF-16'       => "\xFF\xFE" . mb_convert_encoding($question, 'UTF-16LE', 'UTF-8'),
        ];
        $outcomes = [];

        foreach ($files as $encoding => $content) {
            $category = $this->world->lmCategory($this->world->language('lt'));
            $response = $this->signInManager($this->admin)->from("/admin-panel/lm/self_eval/$category")->post('/admin-panel/lm/self_eval/import', [
                'cat_id' => $category,
                'doc'    => $this->uploadedFile('klausimai.txt', $content),
            ]);
            $questions = DB::table('bei_selfeval_question')->where('cat_id', $category)->pluck('id');
            $options = DB::table('bei_selfeval_option')->whereIn('question_id', $questions)->orderBy('id')->pluck('option')->all();

            if (session()->has('errors') && count($questions) === 0) {
                $outcomes[$encoding] = 'refused';
            } elseif ($options === ['Ta, kurią įvedėte patys', 'Nuoroda iš laiško']) {
                $outcomes[$encoding] = 'read as written';
            } else {
                $outcomes[$encoding] = count($questions) . ' question(s) stored, options ' . json_encode($options, JSON_UNESCAPED_UNICODE);
            }
        }

        $this->pinDefect('question-import-encoding', function () use ($outcomes) {
            foreach ($outcomes as $encoding => $outcome) {
                $this->assertContains($outcome, ['refused', 'read as written'], "the $encoding file: $outcome");
            }
        });
    }






    // -----------------------------------------------------------
    // testFileWithAByteOrderMarkKeepsItsFirstQuestion
    // -----------------------------------------------------------
    //
    // Windows editors save UTF-8 with a byte order mark, three
    // bytes before the first line. Both question imports take
    // the file as it comes, so a file that opens on its first
    // question line reads that line as starting with the mark,
    // not with Q: no question is opened, its options are
    // skipped, the line check does not see it, and the import
    // says "Questions imported!" one question short. Wanted:
    // the mark is no part of the text, and both questions are
    // imported, in both imports.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testFileWithAByteOrderMarkKeepsItsFirstQuestion(): void
    {
        $file = "\xEF\xBB\xBFQ\\: Who may ask for your password?\n=Nobody\n~Your bank\n"
            . "::\nQ\\: Which link is safe to open?\n=The address you typed yourself\n~The link in an urgent mail\n";
        $imported = [];

        foreach (['self_eval' => 'bei_selfeval_question', 'knowledge_test' => 'bei_knowledge_test'] as $import => $table) {
            $category = $this->world->lmCategory($this->en);
            $this->signInManager($this->admin)->from("/admin-panel/lm/$import/$category")->post("/admin-panel/lm/$import/import", [
                'cat_id' => $category,
                'doc'    => $this->uploadedFile('questions.txt', $file),
            ])->assertSessionHas('success');
            $imported[$import] = DB::table($table)->where('cat_id', $category)->orderBy('id')->pluck('question')->all();
        }

        $this->pinDefect('question-import-bom', function () use ($imported) {
            $both = ['Who may ask for your password?', 'Which link is safe to open?'];
            $this->assertSame(['self_eval' => $both, 'knowledge_test' => $both], $imported, 'the first question of the file was lost');
        });
    }






    // -----------------------------------------------------------
    // testLevelWrittenAsTextIsImported
    // -----------------------------------------------------------
    //
    // A level typed as text with a space after it — "2 ", as a
    // copy from a document leaves it — passes the import's
    // check, PHP reading it as the number 2. The save then
    // files each step under its level as written, and finds
    // the parent of a step that climbs back up by the number:
    // the key "1 " is not 1, a 500 halfway through the saves.
    // A sheet that mixes such a level with plain ones files
    // the step under the wrong parent without a word. Wanted:
    // the level counts as its number, and the sheet imports
    // with its steps in place.
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

        $response = $this->signInManager($this->admin)->from('/admin-panel/admin_simulations')
            ->post('/admin-panel/admin_simulations/import', ['lang_id' => $this->en, 'doc' => $sheet]);
        $status = $response->getStatusCode();
        $scenario = DB::table('bei_scenarios')->where('descr', 'An urgent invoice arrives')->value('id');
        $steps = DB::table('bei_scenarios_options')->where('scenario_id', $scenario)->orderBy('id')->get(['id', 'parent_option_id', 'level'])->all();

        $this->pinDefect('simulation-import-level-text', function () use ($status, $steps) {
            $this->assertSame(302, $status, 'the import answered with a server error');
            $this->assertCount(4, $steps, 'the steps were not saved');
            $this->assertEquals([1, 2, 3, 2], array_column($steps, 'level'));
            $this->assertEquals([0, $steps[0]->id, $steps[1]->id, $steps[0]->id], array_column($steps, 'parent_option_id'));
        });
    }






    // -----------------------------------------------------------
    // sheetWithSteps
    // -----------------------------------------------------------
    //
    // A simulation spreadsheet with the texts, the category
    // and the attribute of Sheets::simulationSheet and the
    // given steps instead of its three: per sheet row the
    // level as given — a string stays text in the sheet — the
    // situation and the answer type.
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






    // -----------------------------------------------------------
    // testFileThatIsNoSpreadsheetIsRefusedWithAMessage
    // -----------------------------------------------------------
    //
    // The simulation import checks only that the file's name
    // ends in .xlsx before PhpSpreadsheet opens it. A file
    // that is none — an old .xls renamed, a download cut
    // short, a password-protected workbook, an empty file —
    // makes the reader throw, and the admin gets a 500. Wanted:
    // the dialog goes back with a message, and nothing is
    // saved.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testFileThatIsNoSpreadsheetIsRefusedWithAMessage(): void
    {
        $response = $this->signInManager($this->admin)->from('/admin-panel/admin_simulations')->post('/admin-panel/admin_simulations/import', [
            'lang_id' => $this->en,
            'doc'     => $this->uploadedFile('simulation.xlsx', "Level;Situation;Answer type\n1;Check the sender;Correct\n"),
        ]);
        $status = $response->getStatusCode();

        $this->pinDefect('simulation-import-unreadable', function () use ($status, $response) {
            $this->assertSame(302, $status, 'the import of an unreadable file answered with a server error');
            $response->assertRedirect('/admin-panel/admin_simulations')->assertSessionHasErrors();
            $this->assertFalse(DB::table('bei_scenarios')->where('lang_id', $this->en)->exists());
        });
    }






    // -----------------------------------------------------------
    // testCategoriesDialogOfADeletedSimulationStoresNothing
    // -----------------------------------------------------------
    //
    // The categories dialog takes the simulation from its
    // address and never looks it up. Opened from a page that
    // still lists a simulation deleted since — another tab, the
    // other admin — it files the deleted simulation under the
    // ticked categories, and every member who opens such a
    // category finds a button with no simulation behind it,
    // "ID:" and nothing else, leading to a 404. Wanted: the
    // dialog of a simulation that is gone goes back with an
    // error and stores nothing, as the lesson and language
    // forms do since unchecked-hidden-ids.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testCategoriesDialogOfADeletedSimulationStoresNothing(): void
    {
        $category = $this->world->scenarioCategory($this->en);
        $kept = $this->world->scenario($this->en, [], [$category]);
        $gone = $this->world->scenario($this->en, [], [$category]);
        $this->signInManager($this->admin)->from('/admin-panel/admin_simulations')->post("/admin-panel/admin_simulations/delete/$gone");

        $this->signInManager($this->admin)->from('/admin-panel/admin_simulations')
            ->post("/admin-panel/dialog/simulation/cat/{$this->en}/$gone", ['cat' => [$category]]);
        $refused = session()->has('errors');
        $links = DB::table('bei_scenarios_in_category')->where('scenario_id', $gone)->count();
        $listed = $this->signInMember($this->world->member())->get("/en/simulations/category/$category")->assertOk()->getContent();

        $this->pinDefect('simulation-categories-gone', function () use ($refused, $links, $listed, $kept) {
            $this->assertSame(0, $links, 'the deleted simulation was filed under the category');
            $this->assertTrue($refused, 'the dialog of a deleted simulation answered as a save');
            $this->assertStringNotContainsString('/en/simulation/"', $listed, 'the category page shows a button for no simulation');
            $this->assertStringContainsString("/en/simulation/$kept\"", $listed);
        });
    }






    // -----------------------------------------------------------
    // testTakingTheLastSimulationLeftOffTheOfferAwardsTheBadge
    // -----------------------------------------------------------
    //
    // badge_all_simulations is awarded only on a run's end
    // page. A member who has finished every simulation of a
    // language but one, which an admin then takes off the
    // offer — deleting it, unticking its categories, or
    // deleting the only category it was in — has every
    // simulation on offer done, the progress shows the share
    // full, and still no badge, nor badge_finished_course and
    // the questionnaire link it opens, until the member
    // happens to open a run's end page again, a new run or a
    // finished one from the history, with nothing on any page
    // to say so. Deleting the last lesson a member had left
    // awards badge_all_presentations since lesson-delete-badge.
    // Wanted: each of the three awards badge_all_simulations
    // of the language to the members it completes.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testTakingTheLastSimulationLeftOffTheOfferAwardsTheBadge(): void
    {
        $member = $this->world->member();
        $languages = ['deleted' => $this->en, 'unticked' => $this->world->language('lt'), 'category deleted' => $this->world->language('lv')];
        $left = [];
        foreach ($languages as $how => $langId) {
            $done = $this->world->scenarioCategory($langId);
            $other = $this->world->scenarioCategory($langId);
            $this->world->scenarioAttempt($member->id, $this->world->scenario($langId, [], [$done]), $langId);
            $left[$how] = [$this->world->scenario($langId, [], [$how === 'category deleted' ? $other : $done]), $other];
        }

        $page = '/admin-panel/admin_simulations';
        $this->signInManager($this->admin)->from($page)->post("/admin-panel/admin_simulations/delete/{$left['deleted'][0]}");
        $this->signInManager($this->admin)->from($page)->post("/admin-panel/dialog/simulation/cat/{$languages['unticked']}/{$left['unticked'][0]}", []);
        $this->signInManager($this->admin)->from('/admin-panel/admin_simulations/categories')->post("/admin-panel/admin_simulations/categores/delete/{$left['category deleted'][1]}");

        $without = [];
        foreach ($languages as $how => $langId) {
            if (!DB::table('bei_users_badges')->where(['user_id' => $member->id, 'lang_id' => $langId, 'name' => 'badge_all_simulations'])->exists()) {
                $without[] = $how;
            }
        }

        $this->pinDefect('simulation-delete-badge', function () use ($without) {
            $this->assertSame([], $without, 'the member completed by the change holds no badge_all_simulations');
        });
    }






    // -----------------------------------------------------------
    // testEmptyKnowledgeQuestionListSpeaksOfKnowledgeQuestions
    // -----------------------------------------------------------
    //
    // The panel's knowledge questions page of a category
    // without questions, titled "… - Knowledge Questions",
    // says "No self evaluation questions for" the category:
    // admin-panel/knowledge.blade.php carries, word for word,
    // the empty-list line of the self-evaluation page. An
    // admin who opens a new category's knowledge questions is
    // told it has no self-evaluation questions. Wanted: the
    // empty list speaks of knowledge questions.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testEmptyKnowledgeQuestionListSpeaksOfKnowledgeQuestions(): void
    {
        $category = $this->world->lmCategory($this->world->language('en'), ['name' => 'Phishing basics']);

        $html = $this->signInManager($this->world->manager())->get("/admin-panel/lm/knowledge_test/$category")
            ->assertOk()
            ->assertSee('Phishing basics - Knowledge Questions')
            ->getContent();

        $this->pinDefect('admin-knowledge-empty-label', function () use ($html) {
            $this->assertStringNotContainsString('No self evaluation questions', $html, 'the empty knowledge-question list speaks of self-evaluation questions');
            $this->assertStringContainsString('No knowledge questions for Phishing basics', $html);
        });
    }






    // -----------------------------------------------------------
    // testPanelLoginOpensForASignedInMember
    // -----------------------------------------------------------
    //
    // AdminLoginController puts guest:mem in front of the
    // panel's login form and its post, so anyone signed in on
    // the course site — an organiser who also takes the course,
    // most likely — is sent to the course's home page from
    // /admin-panel, with no word why, and the right manager
    // password signs nobody in. The two sessions are meant to
    // live side by side: the member login lets a signed-in
    // manager in, and the member logout keeps a manager signed
    // in on the same browser. Only the order decides today:
    // panel first works, course first does not. Wanted: the
    // panel's login form opens for a signed-in member, and the
    // manager's password signs the manager in and lands on the
    // dashboard, the member staying signed in.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testPanelLoginOpensForASignedInMember(): void
    {
        $member = $this->world->member();
        $manager = $this->world->manager(['main' => 0, 'country' => 1]);

        $this->pinDefect('panel-login-bounces-members', function () use ($member, $manager) {
            $this->signInMember($member)->get('/admin-panel')->assertOk();

            $this->post('/admin-panel', ['email' => $manager->email, 'password' => World::PASSWORD])
                ->assertRedirect('/admin-panel/dashboard');
            $this->assertAuthenticated('man');
            $this->assertSame($manager->id, (int) $this->app['auth']->guard('man')->id());
            $this->assertAuthenticated('mem');
        });
    }






    // -----------------------------------------------------------
    // testPanelFormsRefuseFieldsOfTheWrongShape
    // -----------------------------------------------------------
    //
    // The panel's forms read their text fields straight from
    // the request, and their rules — required, min and max —
    // let a list through, counting its items. A list then
    // reaches Str::slug, the unique rule's query or the insert
    // itself, and a plain value posted where the question
    // editors expect one value per option is indexed like a
    // list: each is a 500. That holds for the language dialog's
    // create and update, the news and information forms, the
    // new lesson, the new learning-material category, the new
    // simulation category and attribute, and the self-
    // evaluation and knowledge-test question editors. The
    // manager dialog and the password dialog already refuse a
    // list. Wanted: every post refused without a server error.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testPanelFormsRefuseFieldsOfTheWrongShape(): void
    {
        $en = $this->world->language('en');
        $news = $this->world->news($en);
        $info = $this->world->information($en);
        $category = $this->world->lmCategory($en);
        $selfEval = $this->world->selfEvalQuestion($category, [['Right', true, 100], ['Wrong', false, 0]]);
        $knowledge = $this->world->knowledgeQuestion($category, [['Right', true], ['Wrong', false]]);
        $admin = $this->world->manager();

        $posts = [
            'new language, name as a list'            => ['/admin-panel/dialog/lang/create', ['name' => ['Listed'], 'locale' => 'xx']],
            'language update, id as a list'           => ['/admin-panel/dialog/lang/update', ['uid' => [(string) $en], 'name' => 'English', 'locale' => 'en']],
            'new article, title as a list'            => ['/admin-panel/news/create', ['title' => ['Title'], 'content' => 'Body', 'lang_id' => (string) $en]],
            'article update, title as a list'         => ['/admin-panel/news/update', ['uid' => (string) $news, 'title' => ['Title'], 'content' => 'Body', 'lang_id' => (string) $en]],
            'new page, body as a list'                => ['/admin-panel/information/create', ['title' => 'Title', 'content' => ['Body'], 'lang_id' => (string) $en]],
            'page update, title as a list'            => ['/admin-panel/information/update', ['uid' => (string) $info, 'title' => ['Title'], 'content' => 'Body', 'lang_id' => (string) $en]],
            'new lesson, hours as a list'             => ['/admin-panel/lm/content/create', ['title' => 'Lesson', 'uid' => (string) $en, 'cat_id' => (string) $category, 'content' => 'Body', 'hours' => ['1']]],
            'new category, name as a list'            => ['/admin-panel/dialog/lm/categories/create', ['name' => ['Category'], 'uid' => (string) $en]],
            'new simulation category, name as a list' => ['/admin-panel/dialog/simulations/categories/create', ['name' => ['Category'], 'uid' => (string) $en]],
            'new attribute, name as a list'           => ['/admin-panel/dialog/simulations/attributes/create', ['name' => ['Attribute'], 'uid' => (string) $en]],
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
            $statuses[$label] = $this->signInManager($admin)->from('/admin-panel/dashboard')->post($path, $body)->getStatusCode();
        }

        $this->pinDefect('admin-form-list-500', function () use ($statuses) {
            $this->assertSame([], array_keys(array_filter($statuses, function (int $status) {
                return $status >= 500;
            })), 'posts answered with a server error');
        });
    }






    // -----------------------------------------------------------
    // testReportsOfUnknownIdsAreNotFound
    // -----------------------------------------------------------
    //
    // Admin\ManagerMainController checks only the manager's
    // reach (member_in_scope), which lets a main admin through
    // for any id, so the report of a run that no longer
    // exists — its simulation deleted while the history page
    // stood open — and the two histories of a member id no
    // member has open as blank pages: "Simulations History -
    // - " with no steps and an empty total, and lists headed by
    // no address. A country manager gets a 404 for the same
    // URLs, and the knowledge-test report answers an unknown
    // public id with a 404 for everyone (knowledge-report-
    // unknown). Wanted: the same 404 for a main admin.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testReportsOfUnknownIdsAreNotFound(): void
    {
        $en = $this->world->language('en');
        $admin = $this->world->manager();
        $nobody = $this->world->nextId('bei_users');

        $statuses = [];
        foreach ([
            '/admin-panel/user/simulation/report/no-such-run',
            "/admin-panel/user/self_evaluation_history/$en/$nobody",
            "/admin-panel/user/simulations_history/$en/$nobody",
        ] as $report) {
            $statuses[$report] = $this->signInManager($admin)->get($report)->getStatusCode();
        }

        $this->pinDefect('admin-report-unknown-ids', function () use ($statuses) {
            $this->assertSame([], array_keys(array_filter($statuses, function (int $status) {
                return $status !== 404;
            })), 'reports of unknown ids that open');
        });
    }






    // -----------------------------------------------------------
    // testRankingAgreesWithTheHistoryAfterACategoryIsDeleted
    // -----------------------------------------------------------
    //
    // Deleting a learning-material category leaves its
    // self-evaluation attempts behind. The member's history,
    // like the panel's, reads the attempts through their
    // category, so the deleted category's attempts drop out
    // of it, and the course progress no longer counts them;
    // the public ranking reads them through
    // view_max_points_per_selfeval_cat, which never looks at
    // the category, and goes on adding their best points to
    // the member's total. Wanted: the ranking and the history
    // agree — each member's total is the sum of the best
    // finished attempt per category the history lists.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testRankingAgreesWithTheHistoryAfterACategoryIsDeleted(): void
    {
        $en = $this->world->language('en');
        $gone = $this->world->lmCategory($en);
        $kept = $this->world->lmCategory($en);
        $member = $this->world->member();
        $this->world->selfEvalAttempt($member->id, $gone, $en, ['points' => 70]);
        $this->world->selfEvalAttempt($member->id, $kept, $en, ['points' => 30]);

        $this->signInManager($this->admin)->from('/admin-panel/lm/categories')->post("/admin-panel/lm/categores/delete/$gone");
        $this->assertFalse(DB::table('bei_lm_category')->where('id', $gone)->exists());

        $best = [];
        foreach ($this->signInMember($member)->get('/cp/self-evaluation-history')->assertOk()->viewData('my_tests') as $attempt) {
            if ((int) $attempt['finished'] === 1) {
                $best[$attempt['cat_id']] = max($best[$attempt['cat_id']] ?? 0, (int) $attempt['points']);
            }
        }
        $ranking = $this->get('/en/ranks/self-evaluation')->assertOk()->viewData('ranking_table');
        $total = 0;
        foreach ($ranking as $row) {
            if ((int) $row['id'] === $member->id) {
                $total = (int) $row['points'];
            }
        }

        $this->pinDefect('category-delete-ranking', function () use ($best, $total) {
            $this->assertSame(array_sum($best), $total, 'the ranking adds points the member\'s history does not list');
        });
    }






    // -----------------------------------------------------------
    // testPanelLoginFormSurvivesAnEmailPostedAsAList
    // -----------------------------------------------------------
    //
    // An email posted as a list fails the panel login's email
    // rule, and Laravel sends the form back with the input
    // flashed, the list included. The login form prints the
    // flashed email straight into the field's value, which
    // hands the list to htmlspecialchars: a TypeError, and the
    // next load of /admin-panel is the 500 page. The member
    // forms take their email through the Form builder, which
    // reads a list's first item. Wanted: the form comes back,
    // with its error, and no server error.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testPanelLoginFormSurvivesAnEmailPostedAsAList(): void
    {
        $this->from('/admin-panel')->post('/admin-panel', ['email' => ['manager@example.test'], 'password' => 'Some-Password-1'])
            ->assertRedirect('/admin-panel');

        $status = $this->get('/admin-panel')->getStatusCode();

        $this->pinDefect('admin-login-email-list-500', function () use ($status) {
            $this->assertSame(200, $status, 'the login form answered with a server error');
        });
    }
}
