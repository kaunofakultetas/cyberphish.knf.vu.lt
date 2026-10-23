<?php

// -----------------------------------------------------------
//  [*] AdminLearningTest — main admins building the course
//
//  The learning-material pages of the panel (Admin's
//  LMController, SelfevalController, KnowledgeController):
//  categories and lessons, the files attached to a lesson —
//  stored under the upload folder with a random name, only
//  for the extensions on the allow list — and the question
//  imports of both tests, which read a plain text file:
//  blocks separated by "::", a question line "Q\: text",
//  "=" before a correct option and "~" before a wrong one.
//  A self-evaluation question's correct options share 100
//  points, and the self-evaluation import recognises a
//  Greek question line; an option keeps every "=" and "~"
//  after its marker, and a question without options still
//  saves its text. A lesson goes with its file rows, its
//  file gone from disk or not; a category with questions is
//  refused. Deletes redirect to the row's language tab,
//  work on a read-only upload folder, and a file's delete
//  link opened twice is harmless. A file posted without its
//  Embed box is listed for download, and a post without a
//  file gets an error.
//
//  Used by:
//    - PHPUnit — the Feature suite (runTests.sh)
// -----------------------------------------------------------


namespace Tests\Feature\Admin;

use Illuminate\Support\Facades\DB;
use Tests\FeatureTestCase;








// -----------------------------------------------------------
// AdminLearningTest
// -----------------------------------------------------------
//
// A main admin and an English category for every test;
// forms are posted from the page that holds them.
//
// Used by:
//   - PHPUnit — the Feature suite (runTests.sh)
// -----------------------------------------------------------

class AdminLearningTest extends FeatureTestCase
{

    // Two questions in the import format: a single-choice one
    // and a multiple-choice one with two correct options
    private const QUESTIONS = "::\n"
        . "Q\\: Which link is safe to open?\n"
        . "=The address you typed yourself\n"
        . "~The link in an urgent mail\n"
        . "~A shortened link\n"
        . "::\n"
        . "Q\\: Which signs point to phishing?\n"
        . "=Urgency\n"
        . "=A sender domain that does not match\n"
        . "~Your name in the greeting\n";

    // The English id, its category and the main admin of the
    // test running now
    private $en;
    private $category;
    private $admin;






    // -----------------------------------------------------------
    // setUp
    // -----------------------------------------------------------
    //
    // English, the category "Phishing basics" and a main admin.
    //
    // Used by:
    //   - PHPUnit, before every test
    // -----------------------------------------------------------

    protected function setUp(): void
    {
        parent::setUp();
        $this->en = $this->world->language('en');
        $this->category = $this->world->lmCategory($this->en, ['name' => 'Phishing basics']);
        $this->admin = $this->world->manager();
    }






    // -----------------------------------------------------------
    // testCategoryIsCreatedRenamedAndDeleted
    // -----------------------------------------------------------
    //
    // A category of a language is created and renamed;
    // deleting it keeps its lessons, uncategorised.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testCategoryIsCreatedRenamedAndDeleted(): void
    {
        $this->admin()->from('/admin-panel/lm/categories')
            ->post('/admin-panel/dialog/lm/categories/create', ['name' => 'Social engineering', 'uid' => $this->en]);
        $id = DB::table('bei_lm_category')->where('name', 'Social engineering')->value('id');
        $this->assertEquals($this->en, DB::table('bei_lm_category')->where('id', $id)->value('lang_id'));

        $this->admin()->from('/admin-panel/lm/categories')
            ->post('/admin-panel/dialog/lm/categories/update', ['uid' => $id, 'name' => 'Manipulation']);
        $this->assertSame('Manipulation', DB::table('bei_lm_category')->where('id', $id)->value('name'));

        $lesson = $this->world->lmContent($id, $this->en);
        $this->admin()->from('/admin-panel/lm/categories')->post("/admin-panel/lm/categores/delete/$id");
        $this->assertFalse(DB::table('bei_lm_category')->where('id', $id)->exists());
        $this->assertNull(DB::table('bei_lm_content')->where('id', $lesson)->value('cat_id'));
    }






    // -----------------------------------------------------------
    // testLessonIsCreatedEditedAndDeleted
    // -----------------------------------------------------------
    //
    // A lesson is created in a category with its hours,
    // edited, and deleted together with the members'
    // completions of it.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testLessonIsCreatedEditedAndDeleted(): void
    {
        $this->admin()->from('/admin-panel/lm/content')->post('/admin-panel/lm/content/create', [
            'cat_id'  => $this->category,
            'title'   => 'Spotting fake senders',
            'content' => '<p>Look at the domain.</p>',
            'hours'   => 1.5,
            'uid'     => $this->en,
        ]);
        $lesson = DB::table('bei_lm_content')->where('title', 'Spotting fake senders')->first();
        $this->assertEquals($this->en, $lesson->lang_id);
        $this->assertEquals(1.5, $lesson->hours);

        $this->admin()->from("/admin-panel/lm/content/edit/{$lesson->id}")->post('/admin-panel/lm/content/update', [
            'uid'     => $lesson->id,
            'cat_id'  => $this->category,
            'title'   => 'Spotting fake senders',
            'content' => '<p>Look at the domain, then the links.</p>',
            'hours'   => 2,
        ]);
        $this->get("/en/learn/spotting-fake-senders/{$lesson->id}")->assertSee('then the links');

        $this->world->lessonDone($this->world->member()->id, $lesson->id);
        $this->admin()->from('/admin-panel/lm/content')->post("/admin-panel/lm/content_delete/{$lesson->id}");
        $this->assertFalse(DB::table('bei_lm_content')->where('id', $lesson->id)->exists());
        $this->assertFalse(DB::table('bei_lm_users')->where('content_id', $lesson->id)->exists());
    }






    // -----------------------------------------------------------
    // testLessonWithAMissingFileCanBeDeleted
    // -----------------------------------------------------------
    //
    // A lesson whose file is already gone from disk is deleted
    // with its file rows.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testLessonWithAMissingFileCanBeDeleted(): void
    {
        $lesson = $this->world->lmContent($this->category, $this->en);
        $this->world->lmFile($lesson, ['file_name' => 'lm/already-gone.pdf']);

        $this->admin()->from('/admin-panel/lm/content')->post("/admin-panel/lm/content_delete/$lesson");

        $this->assertFalse(DB::table('bei_lm_content')->where('id', $lesson)->exists());
        $this->assertFalse(DB::table('bei_lm_content_files')->where('content_id', $lesson)->exists());
    }






    // -----------------------------------------------------------
    // testCategoryWithQuestionsIsNotDeleted
    // -----------------------------------------------------------
    //
    // A category that still holds self-evaluation or
    // knowledge-test questions is refused with a message, and
    // keeps them: they have no foreign key to it and would
    // outlive it unlisted.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testCategoryWithQuestionsIsNotDeleted(): void
    {
        $category = $this->world->lmCategory($this->en);
        $this->world->selfEvalQuestion($category, [['Report the mail', true, 100], ['Answer the mail', false, 0]]);
        $this->world->knowledgeQuestion($category, [['Report the mail', true], ['Answer the mail', false]]);

        $this->admin()->from('/admin-panel/lm/categories')->post("/admin-panel/lm/categores/delete/$category")->assertSessionHasErrors();

        $this->assertTrue(DB::table('bei_lm_category')->where('id', $category)->exists());
        $this->assertSame(1, DB::table('bei_selfeval_question')->where('cat_id', $category)->count());
        $this->assertSame(1, DB::table('bei_knowledge_test')->where('cat_id', $category)->count());
    }






    // -----------------------------------------------------------
    // testDeletesReturnToTheLanguageTab
    // -----------------------------------------------------------
    //
    // The lessons and categories pages name a tab by its
    // language id, and both deletes redirect to the tab of the
    // deleted row's language.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testDeletesReturnToTheLanguageTab(): void
    {
        // Ids past English's own: a row with English's id would
        // hide a mix-up
        $category = $this->world->lmCategory($this->en, ['id' => max($this->world->nextId('bei_lm_category'), $this->en + 1)]);
        $lesson = $this->world->lmContent($category, $this->en, ['id' => max($this->world->nextId('bei_lm_content'), $this->en + 1)]);

        $this->admin()->from('/admin-panel/lm/content')->post("/admin-panel/lm/content_delete/$lesson")
            ->assertRedirect("/admin-panel/lm/content#a{$this->en}");
        $this->admin()->from('/admin-panel/lm/categories')->post("/admin-panel/lm/categores/delete/$category")
            ->assertRedirect("/admin-panel/lm/categories#a{$this->en}");
    }






    // -----------------------------------------------------------
    // testAllowedFileIsAttachedToTheLesson
    // -----------------------------------------------------------
    //
    // A PDF goes to the upload folder under a random name and
    // the lesson lists it.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testAllowedFileIsAttachedToTheLesson(): void
    {
        $lesson = $this->world->lmContent($this->category, $this->en);

        $this->admin()->from("/admin-panel/lm/files/$lesson")->post('/admin-panel/lm/file/create', [
            'uid'   => $lesson,
            'name'  => 'Checklist',
            'embed' => 0,
            'doc'   => $this->uploadedFile('checklist.pdf', "%PDF-1.4\n%test\n"),
        ])->assertSessionHas('success');

        $file = DB::table('bei_lm_content_files')->where('content_id', $lesson)->first();
        $this->assertMatchesRegularExpression('#^lm/[A-Za-z0-9]{40}\.pdf$#', $file->file_name);
        $this->assertFileExists(public_path('upload/' . $file->file_name));
    }






    // -----------------------------------------------------------
    // testFileWithoutEmbedIsListedForDownload
    // -----------------------------------------------------------
    //
    // A file posted with the Embed box unticked — the form then
    // posts no embed at all — is stored with embed 0 and
    // listed on the lesson page for download.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testFileWithoutEmbedIsListedForDownload(): void
    {
        $lesson = $this->world->lmContent($this->category, $this->en);

        $this->admin()->from("/admin-panel/lm/files/$lesson")->post('/admin-panel/lm/file/create', [
            'uid'  => $lesson,
            'name' => 'Checklist for staff',
            'doc'  => $this->uploadedFile('checklist.pdf', "%PDF-1.4\n%test\n"),
        ])->assertSessionHas('success');

        $this->assertEquals(0, DB::table('bei_lm_content_files')->where('content_id', $lesson)->value('embed'));
        $this->get("/en/learn/lesson/$lesson")->assertSee('Checklist for staff');
    }






    // -----------------------------------------------------------
    // testFilePostWithoutAFileGetsAnError
    // -----------------------------------------------------------
    //
    // A post without a usable file — none chosen, or one PHP
    // dropped for its size — goes back with an error, not
    // "Created!", and stores nothing.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testFilePostWithoutAFileGetsAnError(): void
    {
        $lesson = $this->world->lmContent($this->category, $this->en);

        $this->admin()->from("/admin-panel/lm/files/$lesson")->post('/admin-panel/lm/file/create', [
            'uid'  => $lesson,
            'name' => 'Checklist for staff',
        ])->assertSessionMissing('success')->assertSessionHasErrors();

        $this->assertFalse(DB::table('bei_lm_content_files')->where('content_id', $lesson)->exists());
    }






    // -----------------------------------------------------------
    // testScriptFileIsRefused
    // -----------------------------------------------------------
    //
    // An extension off the allow list — a PHP script — is
    // refused before anything is stored.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testScriptFileIsRefused(): void
    {
        $lesson = $this->world->lmContent($this->category, $this->en);

        $this->admin()->from("/admin-panel/lm/files/$lesson")->post('/admin-panel/lm/file/create', [
            'uid'   => $lesson,
            'name'  => 'Shell',
            'embed' => 0,
            'doc'   => $this->uploadedFile('shell.php', '<?php echo "x";'),
        ])->assertSessionHasErrors();

        $this->assertFalse(DB::table('bei_lm_content_files')->where('content_id', $lesson)->exists());
    }






    // -----------------------------------------------------------
    // testRepeatedFileDeleteLinkIsHarmless
    // -----------------------------------------------------------
    //
    // A delete link opened a second time — or one on a stale
    // page — finds no row and answers like the first: back to
    // the list, not a server error.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testRepeatedFileDeleteLinkIsHarmless(): void
    {
        $lesson = $this->world->lmContent($this->category, $this->en);
        $file = $this->world->lmFile($lesson);
        $this->admin()->from("/admin-panel/lm/files/$lesson")->post("/admin-panel/lm/files/delete/$file");
        $this->assertFalse(DB::table('bei_lm_content_files')->where('id', $file)->exists());

        $this->admin()->from("/admin-panel/lm/files/$lesson")->post("/admin-panel/lm/files/delete/$file")
            ->assertRedirect("/admin-panel/lm/files/$lesson")
            ->assertSessionHas('success');
    }






    // -----------------------------------------------------------
    // testSelfEvaluationQuestionsAreImported
    // -----------------------------------------------------------
    //
    // Both questions land in the category with their options;
    // the single-choice question's correct option is worth
    // 100, the two correct options of the other 50 each.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testSelfEvaluationQuestionsAreImported(): void
    {
        $this->admin()->from("/admin-panel/lm/self_eval/{$this->category}")->post('/admin-panel/lm/self_eval/import', [
            'cat_id' => $this->category,
            'doc'    => $this->uploadedFile('questions.txt', self::QUESTIONS),
        ])->assertSessionHas('success');

        $questions = DB::table('bei_selfeval_question')->where('cat_id', $this->category)->orderBy('id')->get();
        $this->assertCount(2, $questions);
        $this->assertSame('Which link is safe to open?', $questions[0]->question);
        $this->assertEquals(1, $questions[0]->q_type);
        $this->assertEquals(2, $questions[1]->q_type);
        $this->assertSame([
            ['The address you typed yourself', 1, 100.0],
            ['The link in an urgent mail', 0, 0.0],
            ['A shortened link', 0, 0.0],
        ], $this->optionRows('bei_selfeval_option', $questions[0]->id, true));
        $this->assertSame([
            ['Urgency', 1, 50.0],
            ['A sender domain that does not match', 1, 50.0],
            ['Your name in the greeting', 0, 0.0],
        ], $this->optionRows('bei_selfeval_option', $questions[1]->id, true));
    }






    // -----------------------------------------------------------
    // testGreekQuestionsAreImported
    // -----------------------------------------------------------
    //
    // A question line opened by the Greek capital epsilon — two
    // bytes in UTF-8 — is recognised like a Q line.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testGreekQuestionsAreImported(): void
    {
        $category = $this->world->lmCategory($this->world->language('gr'));
        $file = $this->uploadedFile('questions.txt', "::\nΕ\\: Ποιος σύνδεσμος είναι ασφαλής;\n=Αυτός που πληκτρολόγησες\n~Ο σύνδεσμος του email\n");

        $this->admin()->from("/admin-panel/lm/self_eval/$category")
            ->post('/admin-panel/lm/self_eval/import', ['cat_id' => $category, 'doc' => $file]);

        $this->assertSame(1, DB::table('bei_selfeval_question')->where('cat_id', $category)->count());
    }






    // -----------------------------------------------------------
    // testImportedOptionsKeepTheirText
    // -----------------------------------------------------------
    //
    // Both imports cut an option's marker only: an address with
    // a query string or a tilde in its path is stored exactly
    // as written after the marker.
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

        $this->admin()->from("/admin-panel/lm/self_eval/$category")
            ->post('/admin-panel/lm/self_eval/import', ['cat_id' => $category, 'doc' => $this->uploadedFile('questions.txt', $questions)]);
        $this->admin()->from("/admin-panel/lm/knowledge_test/$category")
            ->post('/admin-panel/lm/knowledge_test/import', ['cat_id' => $category, 'doc' => $this->uploadedFile('questions.txt', $questions)]);

        $selfEval = DB::table('bei_selfeval_question')->where('cat_id', $category)->value('id');
        $knowledge = DB::table('bei_knowledge_test')->where('cat_id', $category)->value('id');
        $this->assertSame([$right, $wrong], DB::table('bei_selfeval_option')->where('question_id', $selfEval)->orderBy('id')->pluck('option')->all());
        $this->assertSame([$right, $wrong], DB::table('bei_knowledge_option')->where('question_id', $knowledge)->orderBy('id')->pluck('option')->all());
    }






    // -----------------------------------------------------------
    // testKnowledgeQuestionsAreImported
    // -----------------------------------------------------------
    //
    // The same file fills the knowledge test: both questions
    // with their options and which are correct.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testKnowledgeQuestionsAreImported(): void
    {
        $this->admin()->from("/admin-panel/lm/knowledge_test/{$this->category}")->post('/admin-panel/lm/knowledge_test/import', [
            'cat_id' => $this->category,
            'doc'    => $this->uploadedFile('questions.txt', self::QUESTIONS),
        ])->assertSessionHas('success');

        $questions = DB::table('bei_knowledge_test')->where('cat_id', $this->category)->orderBy('id')->get();
        $this->assertCount(2, $questions);
        $this->assertSame('Which signs point to phishing?', $questions[1]->question);
        $this->assertSame([
            ['Urgency', 1],
            ['A sender domain that does not match', 1],
            ['Your name in the greeting', 0],
        ], $this->optionRows('bei_knowledge_option', $questions[1]->id, false));
    }






    // -----------------------------------------------------------
    // testSelfEvaluationQuestionIsEditedAndDeleted
    // -----------------------------------------------------------
    //
    // The edit form updates the question and its options; the
    // delete link removes it with them.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testSelfEvaluationQuestionIsEditedAndDeleted(): void
    {
        $question = $this->world->selfEvalQuestion($this->category, [['Old right', true, 100], ['Old wrong', false, 0]]);
        list($right, $wrong) = $question['options'];

        $this->admin()->get("/admin-panel/lm/self_eval/edit/{$question['question']}")->assertOk();
        $this->admin()->from("/admin-panel/lm/self_eval/edit/{$question['question']}")
            ->post("/admin-panel/lm/self_eval/update/{$question['question']}", [
                'q'       => 'Edited question',
                'q_type'  => 1,
                'option'  => [$right => 'New right', $wrong => 'New wrong'],
                'correct' => [$right => 1, $wrong => 0],
                'points'  => [$right => 80, $wrong => 0],
            ]);
        $this->assertSame('Edited question', DB::table('bei_selfeval_question')->where('id', $question['question'])->value('question'));
        $this->assertEquals(80, DB::table('bei_selfeval_option')->where('id', $right)->value('points'));

        $this->admin()->from("/admin-panel/lm/self_eval/{$this->category}")
            ->post("/admin-panel/lm/self_eval/delete/{$question['question']}");
        $this->assertFalse(DB::table('bei_selfeval_question')->where('id', $question['question'])->exists());
        $this->assertFalse(DB::table('bei_selfeval_option')->where('question_id', $question['question'])->exists());
    }






    // -----------------------------------------------------------
    // testKnowledgeQuestionIsEditedAndDeleted
    // -----------------------------------------------------------
    //
    // The same for a knowledge-test question.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testKnowledgeQuestionIsEditedAndDeleted(): void
    {
        $question = $this->world->knowledgeQuestion($this->category, [['Old right', true], ['Old wrong', false]]);
        list($right, $wrong) = $question['options'];

        $this->admin()->from("/admin-panel/lm/knowledge_test/edit/{$question['question']}")
            ->post("/admin-panel/lm/knowledge_test/update/{$question['question']}", [
                'q'       => 'Edited knowledge question',
                'option'  => [$right => 'Now wrong', $wrong => 'Now right'],
                'correct' => [$right => 0, $wrong => 1],
            ]);
        $this->assertEquals(1, DB::table('bei_knowledge_option')->where('id', $wrong)->value('correct'));

        $this->admin()->from("/admin-panel/lm/knowledge_test/{$this->category}")
            ->post("/admin-panel/lm/knowledge_test/delete/{$question['question']}");
        $this->assertFalse(DB::table('bei_knowledge_test')->where('id', $question['question'])->exists());
        $this->assertFalse(DB::table('bei_knowledge_option')->where('question_id', $question['question'])->exists());
    }






    // -----------------------------------------------------------
    // testQuestionFormPathIsNotFound
    // -----------------------------------------------------------
    //
    // admin-panel/lm/self_eval/question_{type}/{uid} is served
    // no more — its route named a method SelfevalController
    // never had, and nothing linked to it — so the path is the
    // panel's 404, like any other the panel does not serve.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testQuestionFormPathIsNotFound(): void
    {
        $this->admin()->get("/admin-panel/lm/self_eval/question_new/{$this->category}")->assertNotFound();
    }






    // -----------------------------------------------------------
    // testQuestionWithoutOptionsIsSaved
    // -----------------------------------------------------------
    //
    // A question without options — an import block with a
    // question line but no option lines leaves one — saves
    // its edited text in both forms.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testQuestionWithoutOptionsIsSaved(): void
    {
        $selfEval = $this->world->selfEvalQuestion($this->category, []);
        $knowledge = $this->world->knowledgeQuestion($this->category, []);
        $text = 'Who may ask for your password?';

        $this->admin()->from("/admin-panel/lm/self_eval/edit/{$selfEval['question']}")
            ->post("/admin-panel/lm/self_eval/update/{$selfEval['question']}", ['q' => $text, 'q_type' => 1])
            ->assertSessionHas('success', 'Updated!');
        $this->admin()->from("/admin-panel/lm/knowledge_test/edit/{$knowledge['question']}")
            ->post("/admin-panel/lm/knowledge_test/update/{$knowledge['question']}", ['q' => $text])
            ->assertSessionHas('success', 'Updated!');

        $this->assertSame($text, DB::table('bei_selfeval_question')->where('id', $selfEval['question'])->value('question'));
        $this->assertSame($text, DB::table('bei_knowledge_test')->where('id', $knowledge['question'])->value('question'));
    }






    // -----------------------------------------------------------
    // testDeletesWorkOnAReadOnlyUploadFolder
    // -----------------------------------------------------------
    //
    // Production mounts the upload folder read-only, made so
    // here for the test: deleting a lesson file and deleting
    // a lesson with files both remove their rows — the files
    // stay where the mount keeps them.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testDeletesWorkOnAReadOnlyUploadFolder(): void
    {
        $kept = $this->world->lmContent($this->category, $this->en);
        $gone = $this->world->lmContent($this->category, $this->en);
        $file = $this->world->lmFile($kept, ['file_name' => 'lm/readonly-a.pdf']);
        $this->world->lmFile($gone, ['file_name' => 'lm/readonly-b.pdf']);
        $folder = public_path('upload/lm');
        @mkdir($folder, 0755, true);
        file_put_contents("$folder/readonly-a.pdf", '%PDF-1.4');
        file_put_contents("$folder/readonly-b.pdf", '%PDF-1.4');
        chmod($folder, 0555);
        try {
            $this->admin()->from("/admin-panel/lm/files/$kept")->post("/admin-panel/lm/files/delete/$file")->assertRedirect();
            $this->admin()->from('/admin-panel/lm/content')->post("/admin-panel/lm/content_delete/$gone")->assertRedirect();
        } finally {
            chmod($folder, 0755);
            @unlink("$folder/readonly-a.pdf");
            @unlink("$folder/readonly-b.pdf");
        }

        $this->assertFalse(DB::table('bei_lm_content_files')->where('id', $file)->exists());
        $this->assertFalse(DB::table('bei_lm_content')->where('id', $gone)->exists());
    }






    // -----------------------------------------------------------
    // optionRows
    // -----------------------------------------------------------
    //
    // A question's options in their order, as text and
    // correctness — and points when asked for.
    //
    // Used by:
    //   - the import tests of this class
    // -----------------------------------------------------------

    private function optionRows(string $table, int $questionId, bool $withPoints): array
    {
        $rows = [];
        foreach (DB::table($table)->where('question_id', $questionId)->orderBy('id')->get() as $option) {
            $row = [$option->option, (int) $option->correct];
            if ($withPoints) {
                $row[] = (float) $option->points;
            }
            $rows[] = $row;
        }
        return $rows;
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
