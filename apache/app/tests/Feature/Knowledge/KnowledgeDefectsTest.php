<?php

// -----------------------------------------------------------
//  [*] KnowledgeDefectsTest — known knowledge-test defects
//
//  Each test states the behaviour the knowledge test should
//  have and pins the defect that breaks it today
//  (KnownDefects): the run reports them as incomplete,
//  tagged with their ids, until a fix makes one pass — then
//  the test fails as the cue to remove its pin and move it
//  into KnowledgeExamTest, or into CertificateTest for the
//  last pin, the certificate's own. The test unlocks the
//  certificate, so the first is the serious one: it lets a
//  member pass without the knowledge.
//
//    knowledge-skipped-answers  — a question answered with no
//                                 option drops out of the
//                                 result
//    knowledge-all-skipped      — skipping every question
//                                 divides by zero and the
//                                 test never closes
//    knowledge-limits-missing   — a language missing from the
//                                 limits makes every start a
//                                 500
//    knowledge-badge-later-pass — a pass on a later try never
//                                 earns the final-test badge
//    certificate-greek-name     — the certificate's name font
//                                 has no Greek letters
//
//  Used by:
//    - PHPUnit — the Feature suite (runTests.sh)
// -----------------------------------------------------------


namespace Tests\Feature\Knowledge;

use Barryvdh\DomPDF\Facade\Pdf;
use DOMElement;
use FontLib\Font;
use Illuminate\Support\Facades\DB;
use Tests\FeatureTestCase;
use Tests\Support\Flows;








// -----------------------------------------------------------
// KnowledgeDefectsTest
// -----------------------------------------------------------
//
// One pinned defect per test, named in the header; the same
// scene as KnowledgeExamTest — three questions, the first
// option of each correct, all three drawn — for every test.
// The certificate's pin draws the certificate on its own
// (nameFont).
//
// Used by:
//   - PHPUnit — the Feature suite (runTests.sh)
// -----------------------------------------------------------

class KnowledgeDefectsTest extends FeatureTestCase
{

    // Starting the test and answering its questions
    use Flows;

    // The English id, the category, its three questions with
    // their option ids, and the member of the test running now
    private $en;
    private $category;
    private $questions = [];
    private $member;






    // -----------------------------------------------------------
    // setUp
    // -----------------------------------------------------------
    //
    // Three questions in one English category, all drawn, and
    // a member.
    //
    // Used by:
    //   - PHPUnit, before every test
    // -----------------------------------------------------------

    protected function setUp(): void
    {
        parent::setUp();
        $this->en = $this->world->language('en');
        $this->category = $this->world->lmCategory($this->en);
        for ($i = 0; $i < 3; $i++) {
            $this->questions[] = $this->world->knowledgeQuestion($this->category, [['Right', true], ['Wrong', false]]);
        }
        $this->world->knowledgeLimits([$this->en => [$this->category => 3]]);
        $this->member = $this->world->member();
    }






    // -----------------------------------------------------------
    // testSkippedQuestionsCountAsWrong
    // -----------------------------------------------------------
    //
    // progress_save marks a question answered even when no
    // option comes with it, and calculate_results divides the
    // correct ANSWER ROWS by all answer rows, not by the
    // questions — one right answer and two skipped questions
    // score 100 and pass. Wanted: one of three, 33.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testSkippedQuestionsCountAsWrong(): void
    {
        $publicId = $this->finishWith([[0], [], []]);

        $this->pinDefect('knowledge-skipped-answers', function () use ($publicId) {
            $this->assertEquals(33, $this->results($publicId), 'skipped questions did not count against the result');
        });
    }






    // -----------------------------------------------------------
    // testTestWithEveryQuestionSkippedCloses
    // -----------------------------------------------------------
    //
    // With no answer rows at all calculate_results divides by
    // zero: the progress page is a 500, the attempt stays open,
    // and the test page keeps sending the member into it — the
    // knowledge test is closed to them for good. Wanted: the
    // attempt closes with 0.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testTestWithEveryQuestionSkippedCloses(): void
    {
        $publicId = $this->startKnowledgeTest($this->member);
        foreach ($this->questions as $question) {
            $this->answerKnowledgeQuestion($this->member, $publicId, $question['question'], []);
        }

        $this->pinDefect('knowledge-all-skipped', function () use ($publicId) {
            $this->signInMember($this->member)->get("/en/knowledge_test/progress/$publicId")->assertOk();
            $this->assertEquals(1, DB::table('bei_users_knowledge_test')->where('public_id', $publicId)->value('finished'));
        });
    }






    // -----------------------------------------------------------
    // testLanguageMissingFromTheLimitsStillStarts
    // -----------------------------------------------------------
    //
    // KnowledgeTestController::start takes the limits of the
    // test's language from settings row 3 without checking
    // that the language has an entry there. A language whose
    // first category came after the limits were last saved
    // has none: its test page offers the start form, and
    // every start is an undefined index — a 500. Here
    // Lithuanian gets a category and a question, while the
    // limits name English only. Wanted: the start answers
    // with a redirect.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testLanguageMissingFromTheLimitsStillStarts(): void
    {
        $lt = $this->world->language('lt');
        $this->world->knowledgeQuestion($this->world->lmCategory($lt), [['Right', true], ['Wrong', false]]);
        $this->signInMember($this->member)->get('/lt/knowledge_test')->assertOk()->assertSee('/lt/knowledge_start');

        $this->pinDefect('knowledge-limits-missing', function () {
            $this->signInMember($this->member)->post('/lt/knowledge_start')->assertRedirect();
        });
    }






    // -----------------------------------------------------------
    // testPassOnALaterTryEarnsTheBadge
    // -----------------------------------------------------------
    //
    // KnowledgeTestController::progress awards
    // badge_final_test only when the attempt it closes is the
    // member's first finished one, counted over every
    // language, so a member who fails the first try and
    // passes a later one never earns the badge. Wanted: a
    // pass on the second try earns badge_final_test.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testPassOnALaterTryEarnsTheBadge(): void
    {
        $this->world->knowledgeAttempt($this->member->id, $this->en, ['results' => 40]);
        $publicId = $this->finishWith([[0], [0], [0]]);
        $this->assertSame(100, $this->results($publicId));

        $this->pinDefect('knowledge-badge-later-pass', function () {
            $this->assertTrue(
                DB::table('bei_users_badges')->where(['user_id' => $this->member->id, 'name' => 'badge_final_test'])->exists(),
                'the pass on the second try earned no badge'
            );
        });
    }






    // -----------------------------------------------------------
    // testGreekNamePrintsInGreekLetters
    // -----------------------------------------------------------
    //
    // The certificate draws the full name in Parisienne
    // (storage/fonts), a script font with Latin letters only:
    // a Greek member's name cannot print, even once the
    // full-name column keeps it (today fullname-latin1 turns
    // it into question marks before the certificate is
    // drawn). Greek is one of the five site languages.
    // Wanted: the font the name is drawn in has every letter
    // of a Greek name.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testGreekNamePrintsInGreekLetters(): void
    {
        $name = 'Γιώργος Παπαδόπουλος';
        $font = Font::load($this->nameFont($name) . '.ttf');
        $font->parse();
        $map = $font->getUnicodeCharMap();
        $letters = preg_split('//u', str_replace(' ', '', $name), -1, PREG_SPLIT_NO_EMPTY);
        $missing = implode('', array_filter($letters, function (string $letter) use ($map) {
            return !isset($map[mb_ord($letter)]);
        }));

        $this->pinDefect('certificate-greek-name', function () use ($missing) {
            $this->assertSame('', $missing, "the name font lacks $missing");
        });
    }






    // -----------------------------------------------------------
    // finishWith
    // -----------------------------------------------------------
    //
    // Starts a test, answers question N with the options at
    // the positions given for it (0 correct, 1 wrong; an
    // empty list answers nothing) and opens the progress page,
    // which closes the attempt; returns its public id.
    //
    // Used by:
    //   - the scoring tests of this class
    //   - testPassOnALaterTryEarnsTheBadge (above)
    // -----------------------------------------------------------

    private function finishWith(array $answers): string
    {
        $publicId = $this->startKnowledgeTest($this->member);
        foreach ($this->questions as $i => $question) {
            $options = [];
            foreach ($answers[$i] as $position) {
                $options[] = $question['options'][$position];
            }
            $this->answerKnowledgeQuestion($this->member, $publicId, $question['question'], $options);
        }
        $this->signInMember($this->member)->get("/en/knowledge_test/progress/$publicId");
        return $publicId;
    }






    // -----------------------------------------------------------
    // results
    // -----------------------------------------------------------
    //
    // The attempt's result.
    //
    // Used by:
    //   - the scoring tests of this class
    //   - testPassOnALaterTryEarnsTheBadge (above)
    // -----------------------------------------------------------

    private function results(string $publicId): int
    {
        return (int) DB::table('bei_users_knowledge_test')->where('public_id', $publicId)->value('results');
    }






    // -----------------------------------------------------------
    // nameFont
    // -----------------------------------------------------------
    //
    // Draws pdf.cert for a full name the way
    // CertController::download does — A4 landscape, the
    // background image, a fixed date — and returns the file of
    // the font dompdf draws the name in, without its
    // extension. dompdf drops a page's frames once the page is
    // drawn, so the font is read in its end_frame callback.
    //
    // Used by:
    //   - testGreekNamePrintsInGreekLetters (above)
    // -----------------------------------------------------------

    private function nameFont(string $fullname): string
    {
        $pdf = Pdf::loadView('pdf.cert', [
            'fullname'       => $fullname,
            'date'           => '05 October, 2026',
            'backgroundPath' => public_path('cert.png'),
        ]);
        $pdf->setPaper('a4', 'landscape');
        $font = '';
        $dompdf = $pdf->getDomPDF();
        $dompdf->setCallbacks([['event' => 'end_frame', 'f' => function ($frame) use (&$font) {
            $node = $frame->get_node();
            if ($node instanceof DOMElement && $node->getAttribute('id') === 'name') {
                $font = (string) $frame->get_style()->font_family;
            }
        }]]);
        $dompdf->render();
        $this->assertNotSame('', $font, 'the certificate drew no name block');
        return $font;
    }
}
