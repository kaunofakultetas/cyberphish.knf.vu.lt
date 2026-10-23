<?php

// -----------------------------------------------------------
//  [*] CertificateTest — the certificate of completion
//
//  GET cp/{lang}/certificate (CertController): a member
//  with a finished knowledge test above 74 in that language
//  and a full name gets the certificate as a PDF rendered
//  by dompdf; anyone else gets a 404. The template prints
//  the full name — which the member types in — as text, so
//  no markup reaches dompdf. A name keeps to the line drawn
//  for it, shrinking when it is too wide, and every letter
//  of a Lithuanian or a Greek name is drawn: a name the
//  script font cannot draw comes in a font that can.
//
//  Used by:
//    - PHPUnit — the Feature suite (runTests.sh)
// -----------------------------------------------------------


namespace Tests\Feature\Knowledge;

use App\Http\Controllers\CertController;
use DOMElement;
use FontLib\Font;
use Illuminate\Support\Facades\DB;
use Tests\FeatureTestCase;








// -----------------------------------------------------------
// CertificateTest
// -----------------------------------------------------------
//
// A member with a full name for every test; each test gives
// them the knowledge-test history it needs.
//
// Used by:
//   - PHPUnit — the Feature suite (runTests.sh)
// -----------------------------------------------------------

class CertificateTest extends FeatureTestCase
{

    // The English id and the member of the test running now
    private $en;
    private $member;






    // -----------------------------------------------------------
    // setUp
    // -----------------------------------------------------------
    //
    // English, and a member named Jonas Jonaitis.
    //
    // Used by:
    //   - PHPUnit, before every test
    // -----------------------------------------------------------

    protected function setUp(): void
    {
        parent::setUp();
        $this->en = $this->world->language('en');
        $this->member = $this->world->member(['fullname' => 'Jonas Jonaitis']);
    }






    // -----------------------------------------------------------
    // testCertificateNeedsAnAccount
    // -----------------------------------------------------------
    //
    // A visitor is sent to the login form.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testCertificateNeedsAnAccount(): void
    {
        $this->get('/cp/en/certificate')->assertRedirect('/login');
    }






    // -----------------------------------------------------------
    // testPassedMemberGetsThePdf
    // -----------------------------------------------------------
    //
    // A pass of 80 brings a PDF document.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testPassedMemberGetsThePdf(): void
    {
        $this->world->knowledgeAttempt($this->member->id, $this->en, ['results' => 80]);

        $response = $this->signInMember($this->member)->get('/cp/en/certificate')->assertOk();

        $this->assertStringContainsString('application/pdf', $response->headers->get('Content-Type'));
        $this->assertStringStartsWith('%PDF', $response->getContent());
    }






    // -----------------------------------------------------------
    // testPassMarkIsAbove74
    // -----------------------------------------------------------
    //
    // 74 is not enough, 75 is.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testPassMarkIsAbove74(): void
    {
        $attempt = $this->world->knowledgeAttempt($this->member->id, $this->en, ['results' => 74]);
        $this->signInMember($this->member)->get('/cp/en/certificate')->assertNotFound();

        DB::table('bei_users_knowledge_test')->where('id', $attempt)->update(['results' => 75]);
        $this->signInMember($this->member)->get('/cp/en/certificate')->assertOk();
    }






    // -----------------------------------------------------------
    // testMemberWithoutAPassGetsNothing
    // -----------------------------------------------------------
    //
    // No attempt at all, or a high result in an attempt that
    // never closed, is a 404.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testMemberWithoutAPassGetsNothing(): void
    {
        $this->signInMember($this->member)->get('/cp/en/certificate')->assertNotFound();

        $this->world->knowledgeAttempt($this->member->id, $this->en, ['results' => 95, 'finished' => 0]);
        $this->signInMember($this->member)->get('/cp/en/certificate')->assertNotFound();
    }






    // -----------------------------------------------------------
    // testCertificateNeedsAFullName
    // -----------------------------------------------------------
    //
    // The certificate carries the member's full name, so a
    // passed member without one gets a 404 until they enter
    // it.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testCertificateNeedsAFullName(): void
    {
        $nameless = $this->world->member();
        $this->world->knowledgeAttempt($nameless->id, $this->en, ['results' => 90]);

        $this->signInMember($nameless)->get('/cp/en/certificate')->assertNotFound();
    }






    // -----------------------------------------------------------
    // testCertificateIsPerLanguage
    // -----------------------------------------------------------
    //
    // A pass in Lithuanian unlocks the Lithuanian certificate
    // only.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testCertificateIsPerLanguage(): void
    {
        $this->world->knowledgeAttempt($this->member->id, $this->world->language('lt'), ['results' => 90]);

        $this->signInMember($this->member)->get('/cp/en/certificate')->assertNotFound();
        $this->signInMember($this->member)->get('/cp/lt/certificate')->assertOk();
    }






    // -----------------------------------------------------------
    // testTemplatePrintsTheNameAsText
    // -----------------------------------------------------------
    //
    // Markup typed into the full name reaches dompdf escaped,
    // so it cannot load a remote image or anything else.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testTemplatePrintsTheNameAsText(): void
    {
        $html = view('pdf.cert', [
            'fullname'       => '<img src=//x.yz/a>',
            'date'           => '05 October, 2026',
            'backgroundPath' => public_path('cert.png'),
        ])->render();

        $this->assertStringContainsString('&lt;img src=//x.yz/a&gt;', $html);
        $this->assertStringNotContainsString('<img src=//x.yz/a>', $html);
    }






    // -----------------------------------------------------------
    // testLatinNameKeepsTheScriptFont
    // -----------------------------------------------------------
    //
    // A name the template always drew well comes out as
    // before: Parisienne at 62px — 46.5 points — on one line.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testLatinNameKeepsTheScriptFont(): void
    {
        $drawn = $this->nameFrame('Jonas Jonaitis');

        $this->assertStringContainsString('parisienne', $drawn['font']);
        $this->assertEquals(46.5, $drawn['size']);
        $this->assertSame(1, $drawn['lines']);
    }






    // -----------------------------------------------------------
    // testNameStaysOnItsLine
    // -----------------------------------------------------------
    //
    // The line under the name is 620 points long. A name
    // wider than that at 62px — twenty capitals, which the
    // script font draws far wider than small letters, or the
    // form's longest name of 100 characters — is drawn smaller
    // until it fits, on one line, never wrapping over the
    // course's text; its block keeps the height of a name the
    // template draws at full size, so the date laid out under
    // it stays on its own line.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testNameStaysOnItsLine(): void
    {
        $template = $this->nameFrame('Jonas Jonaitis')['height'];
        foreach (['ALEKSANDRAS PETRAUSK', str_repeat('Wilhelmina ', 9) . 'Westhuizen'] as $name) {
            $drawn = $this->nameFrame($name);

            $this->assertSame(1, $drawn['lines'], "$name took {$drawn['lines']} lines");
            $this->assertLessThanOrEqual(620, $drawn['width'], "$name is {$drawn['width']} points wide");
            $this->assertEqualsWithDelta($template, $drawn['height'], 0.01, "the block of $name is {$drawn['height']} points tall");
        }
    }






    // -----------------------------------------------------------
    // testGreekNamePrintsInGreekLetters
    // -----------------------------------------------------------
    //
    // Greek is one of the five site languages, and Parisienne
    // has Latin letters only: a Greek name is drawn in a font
    // that has every one of its letters.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testGreekNamePrintsInGreekLetters(): void
    {
        $missing = $this->lettersTheNameFontLacks('Γιώργος Παπαδόπουλος');

        $this->assertSame('', $missing, "the name font lacks $missing");
    }






    // -----------------------------------------------------------
    // testLithuanianNamePrintsEveryLetter
    // -----------------------------------------------------------
    //
    // The full-name column keeps Lithuanian letters, so the
    // certificate gets them: each of the nine, small and
    // capital, is drawn in a font that has it.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testLithuanianNamePrintsEveryLetter(): void
    {
        $missing = $this->lettersTheNameFontLacks('Ąžuolė Čiurlionytė ąčęėįšųūž ĄČĘĖĮŠŲŪŽ');

        $this->assertSame('', $missing, "the name font lacks $missing");
    }






    // -----------------------------------------------------------
    // lettersTheNameFontLacks
    // -----------------------------------------------------------
    //
    // The letters of the full name that the font the
    // certificate draws it in has no glyph for, in the order
    // of the name; empty when every letter is drawn.
    //
    // Used by:
    //   - testGreekNamePrintsInGreekLetters,
    //     testLithuanianNamePrintsEveryLetter (above)
    // -----------------------------------------------------------

    private function lettersTheNameFontLacks(string $fullname): string
    {
        $font = Font::load($this->nameFrame($fullname)['font'] . '.ttf');
        $font->parse();
        $map = $font->getUnicodeCharMap();
        $letters = preg_split('//u', str_replace(' ', '', $fullname), -1, PREG_SPLIT_NO_EMPTY);

        return implode('', array_filter($letters, function (string $letter) use ($map) {
            return !isset($map[mb_ord($letter)]);
        }));
    }






    // -----------------------------------------------------------
    // nameFrame
    // -----------------------------------------------------------
    //
    // Draws the certificate for a full name the way the
    // download does (CertController::certificate) and returns
    // what dompdf made of the name: the file of its font,
    // without the extension, its size and number of lines,
    // the width of the text and the height of the block, both
    // in points. dompdf drops a
    // page's frames once the page is drawn, so they are read
    // in its end_frame callback.
    //
    // Used by:
    //   - the name tests of this class, the two on letters
    //     through lettersTheNameFontLacks (above)
    // -----------------------------------------------------------

    private function nameFrame(string $fullname): array
    {
        $dompdf = CertController::certificate($fullname, '05 October, 2026')->getDomPDF();
        $drawn = [];
        $dompdf->setCallbacks([['event' => 'end_frame', 'f' => function ($frame) use (&$drawn, $dompdf, $fullname) {
            $node = $frame->get_node();
            if ($node instanceof DOMElement && $node->getAttribute('id') === 'name') {
                $style = $frame->get_style();
                $drawn = [
                    'font'  => (string) $style->font_family,
                    'size'  => $style->font_size,
                    'lines' => count($frame->get_line_boxes()),
                    'height' => $frame->get_border_box()['h'],
                    'width' => $dompdf->getFontMetrics()->getTextWidth($fullname, $style->font_family, $style->font_size),
                ];
            }
        }]]);
        $dompdf->render();
        $this->assertNotSame([], $drawn, 'the certificate drew no name block');
        return $drawn;
    }
}
