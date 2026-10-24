<?php

// -----------------------------------------------------------
//  [*] ApacheDefectsTest — known defects seen over HTTP
//
//  Each test states the behaviour the app over real HTTP
//  should have and pins the defect that breaks it today
//  (KnownDefects): the run reports them as incomplete,
//  tagged with their ids, until a fix makes one pass — then
//  the test fails as the cue to remove its pin and move it
//  into its home file.
//
//    lang-segment-line-break — a line break in a member
//      page's {lang} segment is a 500 over HTTP
//
//  Used by:
//    - PHPUnit — the Apache suite (runTests.sh)
// -----------------------------------------------------------


namespace Tests\Apache;

use Tests\ApacheTestCase;








// -----------------------------------------------------------
// ApacheDefectsTest
// -----------------------------------------------------------
//
// One pinned defect per test, named in the header.
//
// Used by:
//   - PHPUnit — the Apache suite (runTests.sh)
// -----------------------------------------------------------

class ApacheDefectsTest extends ApacheTestCase
{






    // -----------------------------------------------------------
    // testLineBreakInTheLanguageIsNoServerError
    // -----------------------------------------------------------
    //
    // {lang} takes any segment, and the member pages build
    // their redirects from it as it came: the knowledge test
    // page sends a member without the language's lessons to
    // /{lang}/cp/learning-material, and the answer forms of
    // the knowledge test and the simulations send every post
    // back to /{lang}/…/progress/…. A segment of %0A puts a
    // line break into that Location header; PHP refuses to
    // send such a header with a warning, the warning becomes
    // an exception in public/index.php, after the kernel, and
    // the member gets the 500 page, with the error written to
    // laravel.log at every request. In-process tests never
    // send headers, so only real HTTP shows it. Wanted: no
    // server error — the segment refused, or the redirect
    // built from a language that exists.
    //
    // Used by:
    //   - PHPUnit — the Apache suite (runTests.sh)
    // -----------------------------------------------------------

    public function testLineBreakInTheLanguageIsNoServerError(): void
    {
        $this->signInOverHttp($this->world->member());

        $response = $this->http->get('/%0A/knowledge_test');

        $this->pinDefect('lang-segment-line-break', function () use ($response) {
            $this->assertLessThan(500, $response->getStatusCode(), 'GET /%0A/knowledge_test answered with a server error');
        });
    }
}
