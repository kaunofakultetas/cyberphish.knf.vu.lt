<?php

// -----------------------------------------------------------
//  [*] CertificateTest — the certificate of completion
//
//  GET cp/{lang}/certificate (CertController): a member
//  with a finished knowledge test above 74 in that language
//  and a full name gets the certificate as a PDF rendered
//  by dompdf; anyone else gets a 404. The template prints
//  the full name — which the member types in — as text, so
//  no markup reaches dompdf.
//
//  Used by:
//    - PHPUnit — the Feature suite (runTests.sh)
// -----------------------------------------------------------


namespace Tests\Feature\Knowledge;

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
}
