<?php

// -----------------------------------------------------------
//  [*] SiteDefectsTest — known defects of the public pages
//
//  Each test states the behaviour the public pages should
//  have and pins the defect that breaks it today
//  (KnownDefects): the run reports them as incomplete,
//  tagged with their ids, until a fix makes one pass — then
//  the test fails as the cue to remove its pin and move it
//  into PublicPagesTest.
//
//    ranks-url-language  — the rankings read the URL's
//                          language, not the page's
//
//  Used by:
//    - PHPUnit — the Feature suite (runTests.sh)
// -----------------------------------------------------------


namespace Tests\Feature\Site;

use Tests\FeatureTestCase;








// -----------------------------------------------------------
// SiteDefectsTest
// -----------------------------------------------------------
//
// One pinned defect per test, named in the header.
//
// Used by:
//   - PHPUnit — the Feature suite (runTests.sh)
// -----------------------------------------------------------

class SiteDefectsTest extends FeatureTestCase
{






    // -----------------------------------------------------------
    // testRankingFollowsThePagesLanguage
    // -----------------------------------------------------------
    //
    // An unknown {lang} keeps the session's language for the
    // page, as the routes promise, but RanksController looks
    // the URL's code up itself: /xx/ranks/self-evaluation is
    // an English page whose ranking holds nobody. Wanted: the
    // ranking of the page's language.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testRankingFollowsThePagesLanguage(): void
    {
        $en = $this->world->language('en');
        $member = $this->world->member(['username' => 'RankedInEnglish']);
        $this->world->selfEvalAttempt($member->id, $this->world->lmCategory($en), $en, ['points' => 40]);
        $this->get('/en/ranks/self-evaluation')->assertOk()->assertSee('RankedInEnglish');

        $this->pinDefect('ranks-url-language', function () {
            $this->get('/xx/ranks/self-evaluation')->assertOk()->assertSee('RankedInEnglish');
        });
    }
}
