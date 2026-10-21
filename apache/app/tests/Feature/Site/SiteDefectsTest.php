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
//    home-about-missing     — the home page is a 500 in a
//                             language the about text has no
//                             entry for
//    news-page-links        — the page links under the news
//                             list lead to a 404
//    ranks-query-per-member — the ranking pages run one more
//                             query for every ranked member
//
//  Used by:
//    - PHPUnit — the Feature suite (runTests.sh)
// -----------------------------------------------------------


namespace Tests\Feature\Site;

use Illuminate\Support\Facades\DB;
use Tests\FeatureTestCase;








// -----------------------------------------------------------
// SiteDefectsTest
// -----------------------------------------------------------
//
// One pinned defect per test, named in the header; every
// test is a visitor without an account, on the English
// pages unless it switches the language.
//
// Used by:
//   - PHPUnit — the Feature suite (runTests.sh)
// -----------------------------------------------------------

class SiteDefectsTest extends FeatureTestCase
{

    // The English id for the test running now
    private $en;






    // -----------------------------------------------------------
    // setUp
    // -----------------------------------------------------------
    //
    // English for every test.
    //
    // Used by:
    //   - PHPUnit, before every test
    // -----------------------------------------------------------

    protected function setUp(): void
    {
        parent::setUp();
        $this->en = $this->world->language('en');
    }






    // -----------------------------------------------------------
    // testHomePageOpensInALanguageWithoutAboutText
    // -----------------------------------------------------------
    //
    // HomeController::index hands welcome.blade.php the about
    // text of settings row 1, kept per language id, with the
    // id of the session's language, and the view reads that
    // language's entry without checking it is there. In a
    // language the saved text has no entry for — one added
    // after the text was last saved — the read is an
    // undefined index, and the home page is a 500 for every
    // visitor who picks that language, until an admin saves
    // the about text again. Wanted: the home page opens.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testHomePageOpensInALanguageWithoutAboutText(): void
    {
        $this->world->language('lt');
        $this->world->setting(1, 'about', json_encode([$this->en => ['descr' => 'Training against phishing', 'link' => '']]));
        $this->get('/')->assertOk()->assertSee('Training against phishing');
        $this->get('/lang/lt')->assertRedirect('/');

        $this->pinDefect('home-about-missing', function () {
            $this->get('/')->assertOk();
        });
    }






    // -----------------------------------------------------------
    // testNewsPageLinkOpensPageTwo
    // -----------------------------------------------------------
    //
    // NewsController::index gives the news list's paginator
    // the bare /news as its path, which no route answers —
    // the list lives under {lang}/news — so every page link
    // under the list leads to a 404, and the articles past
    // the first twelve can be reached only by typing the page
    // number into the address. Wanted: the list's link to
    // page two opens page two, with the thirteenth article.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testNewsPageLinkOpensPageTwo(): void
    {
        for ($i = 1; $i <= 13; $i++) {
            $this->world->news($this->en, ['title' => sprintf('Article number %02d', $i)]);
        }
        $html = $this->get('/en/news')->assertOk()->getContent();
        $found = preg_match('#href="([^"]*[?&]page=2)"#', $html, $link);
        $this->assertSame(1, $found, 'the list has no link to page two');

        $this->pinDefect('news-page-links', function () use ($link) {
            $this->get($link[1])->assertOk()->assertSee('Article number 13');
        });
    }






    // -----------------------------------------------------------
    // testRankingQueriesDoNotGrowWithTheMembers
    // -----------------------------------------------------------
    //
    // RanksController::self_evaluation and ::simulations look
    // up the badges of each ranked member with a query of its
    // own, so both public ranking pages run one more query
    // for every member who ever finished a self-evaluation —
    // or a simulation — in the language, and grow slower as
    // the site fills up. The test counts each page's queries
    // with one ranked member and again with four. Wanted:
    // the same count both times.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testRankingQueriesDoNotGrowWithTheMembers(): void
    {
        $category = $this->world->lmCategory($this->en);
        $scenario = $this->world->scenario($this->en);
        $this->rankMembers(1, $category, $scenario);
        $few = $this->rankingQueries();
        $this->rankMembers(3, $category, $scenario);
        $more = $this->rankingQueries();

        $this->pinDefect('ranks-query-per-member', function () use ($few, $more) {
            foreach ($few as $page => $queries) {
                $this->assertSame($queries, $more[$page], "the $page ranking runs $queries queries for one member, {$more[$page]} for four");
            }
        });
    }






    // -----------------------------------------------------------
    // rankMembers
    // -----------------------------------------------------------
    //
    // Adds that many members to both English rankings: each
    // finishes one self-evaluation of the category and one run
    // of the simulation.
    //
    // Used by:
    //   - testRankingQueriesDoNotGrowWithTheMembers (above)
    // -----------------------------------------------------------

    private function rankMembers(int $count, int $category, int $scenario): void
    {
        for ($i = 0; $i < $count; $i++) {
            $member = $this->world->member();
            $this->world->selfEvalAttempt($member->id, $category, $this->en);
            $this->world->scenarioAttempt($member->id, $scenario, $this->en);
        }
    }






    // -----------------------------------------------------------
    // rankingQueries
    // -----------------------------------------------------------
    //
    // How many database queries each English ranking page runs
    // for a visitor, keyed by the last part of its path: the
    // connection's query log, emptied before each page.
    //
    // Used by:
    //   - testRankingQueriesDoNotGrowWithTheMembers (above)
    // -----------------------------------------------------------

    private function rankingQueries(): array
    {
        $queries = [];
        DB::enableQueryLog();
        foreach (['self-evaluation', 'simulations'] as $page) {
            DB::flushQueryLog();
            $this->get("/en/ranks/$page")->assertOk();
            $queries[$page] = count(DB::getQueryLog());
        }
        DB::disableQueryLog();
        return $queries;
    }
}
