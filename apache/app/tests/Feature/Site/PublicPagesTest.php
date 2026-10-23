<?php

// -----------------------------------------------------------
//  [*] PublicPagesTest — the pages anyone can open
//
//  The home page with its about text, the news list and
//  articles, the information pages, the two public ranking
//  tables and the language switch — the parts of the site a
//  visitor sees without an account. A language the about
//  text has no entry for gets an empty about block, and the
//  news list's page links lead to its pages. A ranking shows
//  each member's badges once, in the order earned, and costs
//  the same queries however many members it lists. No
//  response carries a CORS header. The home page's
//  latest-news section is switched off: welcome.blade.php
//  keeps it inside a Blade comment, though HomeController
//  still loads the three latest articles for it.
//  The language of a page comes from the {lang} part of its
//  URL when that is one of en, lt, ee, lv and gr, and from
//  the session otherwise (BaseController).
//
//  Used by:
//    - PHPUnit — the Feature suite (runTests.sh)
// -----------------------------------------------------------


namespace Tests\Feature\Site;

use Illuminate\Support\Facades\DB;
use Tests\FeatureTestCase;








// -----------------------------------------------------------
// PublicPagesTest
// -----------------------------------------------------------
//
// Every test is a visitor with no session; English is the
// seeded language (id 3), and setUp adds Lithuanian for
// every test, as the second language some tests need.
//
// Used by:
//   - PHPUnit — the Feature suite (runTests.sh)
// -----------------------------------------------------------

class PublicPagesTest extends FeatureTestCase
{

    // The ids of English and Lithuanian for the test running
    // now
    private $en;
    private $lt;






    // -----------------------------------------------------------
    // setUp
    // -----------------------------------------------------------
    //
    // English and Lithuanian for every test.
    //
    // Used by:
    //   - PHPUnit, before every test
    // -----------------------------------------------------------

    protected function setUp(): void
    {
        parent::setUp();
        $this->en = $this->world->language('en');
        $this->lt = $this->world->language('lt');
    }






    // -----------------------------------------------------------
    // testHomePageOpensOnAFreshInstall
    // -----------------------------------------------------------
    //
    // The home page renders with nothing but the seed rows of
    // mysql/init.sql and the Lithuanian row of setUp —
    // settings row 1 without an about text, and no news.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testHomePageOpensOnAFreshInstall(): void
    {
        $this->get('/')->assertOk()->assertSee('CYBERPHISH');
    }






    // -----------------------------------------------------------
    // testHomePageShowsTheAboutTextOfTheLanguage
    // -----------------------------------------------------------
    //
    // Settings row 1 holds the about text per language id;
    // the home page shows the one of the session's language.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testHomePageShowsTheAboutTextOfTheLanguage(): void
    {
        $this->world->setting(1, 'about', json_encode([
            $this->en => ['descr' => 'Training against phishing', 'link' => 'https://cyberphish.knf.vu.lt/en/page/about/1'],
            $this->lt => ['descr' => 'Mokymai prieš sukčiavimą', 'link' => 'https://cyberphish.knf.vu.lt/lt/page/apie/2'],
        ]));

        $this->get('/')->assertOk()->assertSee('Training against phishing')->assertDontSee('Mokymai prieš sukčiavimą');
    }






    // -----------------------------------------------------------
    // testHomePageOpensInALanguageWithoutAboutText
    // -----------------------------------------------------------
    //
    // A language the saved about text has no entry for — one
    // added after the text was last saved — gets the home page
    // with an empty about block.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testHomePageOpensInALanguageWithoutAboutText(): void
    {
        $this->world->setting(1, 'about', json_encode([$this->en => ['descr' => 'Training against phishing', 'link' => '']]));
        $this->get('/lang/lt')->assertRedirect('/');

        $this->get('/')->assertOk()->assertDontSee('Training against phishing');
    }






    // -----------------------------------------------------------
    // testNewsListShowsTheNewsOfTheLanguage
    // -----------------------------------------------------------
    //
    // The news list of a language holds that language's
    // articles only.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testNewsListShowsTheNewsOfTheLanguage(): void
    {
        $this->world->news($this->en, ['title' => 'English article']);
        $this->world->news($this->lt, ['title' => 'Lietuviškas straipsnis']);

        $this->get('/lt/news')->assertOk()->assertSee('Lietuviškas straipsnis')->assertDontSee('English article');
    }






    // -----------------------------------------------------------
    // testNewsListPagesByTwelve
    // -----------------------------------------------------------
    //
    // Twelve articles a page, in the order they were added:
    // the thirteenth is on page two.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testNewsListPagesByTwelve(): void
    {
        for ($i = 1; $i <= 13; $i++) {
            $this->world->news($this->en, ['title' => sprintf('Article number %02d', $i)]);
        }

        $this->get('/en/news')->assertSee('Article number 12')->assertDontSee('Article number 13');
        $this->get('/en/news?page=2')->assertSee('Article number 13')->assertDontSee('Article number 12');
    }






    // -----------------------------------------------------------
    // testNewsPageLinkOpensPageTwo
    // -----------------------------------------------------------
    //
    // The page links under the list lead back to the list of
    // the language — the link to page two opens page two, with
    // the thirteenth article.
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
        $this->assertSame(1, preg_match('#href="([^"]*[?&]page=2)"#', $html, $link), 'the list has no link to page two');

        $this->get($link[1])->assertOk()->assertSee('Article number 13');
    }






    // -----------------------------------------------------------
    // testHugePageNumberOpensTheNewsList
    // -----------------------------------------------------------
    //
    // A page number past PHP's integers, a fraction or text
    // opens the list's first page; one past the end, however
    // large, an empty page. Such numbers once reached the
    // slicing as floats and answered with a 500.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testHugePageNumberOpensTheNewsList(): void
    {
        $this->world->news($this->en, ['title' => 'The only article']);

        $this->get('/en/news?page=99999999999999999999')->assertOk()->assertSee('The only article');
        $this->get('/en/news?page=9223372036854775807')->assertOk()->assertDontSee('The only article');
        $this->get('/en/news?page=1.5')->assertOk()->assertSee('The only article');
    }






    // -----------------------------------------------------------
    // testCookieNoticeKeepsTheLanguage
    // -----------------------------------------------------------
    //
    // The cookie notice links the policy under the language the
    // visitor is in, so following it no longer switches the
    // whole site to English.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testCookieNoticeKeepsTheLanguage(): void
    {
        $this->get('/lt/news')->assertOk()
            ->assertSee('/lt/page/cookie-policy/19', false)
            ->assertDontSee('/en/page/cookie-policy/19', false);
    }






    // -----------------------------------------------------------
    // testNewsArticleOpens
    // -----------------------------------------------------------
    //
    // An article opens by its id, with its title and body.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testNewsArticleOpens(): void
    {
        $id = $this->world->news($this->en, ['title' => 'Spotting a fake bank mail', 'content' => '<p>Check the sender.</p>']);

        $this->get("/en/news/spotting-a-fake-bank-mail/$id")
            ->assertOk()
            ->assertSee('Spotting a fake bank mail')
            ->assertSee('Check the sender.');
    }






    // -----------------------------------------------------------
    // testMissingNewsArticleIsNotFound
    // -----------------------------------------------------------
    //
    // NewsExists answers an unknown or non-numeric id with a
    // 404.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testMissingNewsArticleIsNotFound(): void
    {
        $id = $this->world->news($this->en);

        $this->get('/en/news/gone/' . ($id + 1000))->assertNotFound();
        $this->get('/en/news/gone/abc')->assertNotFound();
    }






    // -----------------------------------------------------------
    // testInformationPageOpens
    // -----------------------------------------------------------
    //
    // An information page opens by its id; an unknown id is a
    // 404 (PageExists).
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testInformationPageOpens(): void
    {
        $id = $this->world->information($this->en, ['title' => 'About the project', 'content' => '<p>Erasmus+ partners.</p>']);

        $this->get("/en/page/about-the-project/$id")->assertOk()->assertSee('Erasmus+ partners.');
        $this->get('/en/page/gone/' . ($id + 1000))->assertNotFound();
    }






    // -----------------------------------------------------------
    // testSelfEvaluationRankingAddsUpTheBestScores
    // -----------------------------------------------------------
    //
    // The ranking sums each member's best score per category
    // (view_max_points_per_selfeval_cat) and lists the highest
    // total first; unfinished attempts do not count.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testSelfEvaluationRankingAddsUpTheBestScores(): void
    {
        $first = $this->world->lmCategory($this->en);
        $second = $this->world->lmCategory($this->en);
        $leader = $this->world->member(['username' => 'TopScorer']);
        $runnerUp = $this->world->member(['username' => 'SecondBest']);
        $this->world->selfEvalAttempt($leader->id, $first, $this->en, ['points' => 30]);
        $this->world->selfEvalAttempt($leader->id, $first, $this->en, ['points' => 10]);
        $this->world->selfEvalAttempt($leader->id, $second, $this->en, ['points' => 40]);
        $this->world->selfEvalAttempt($runnerUp->id, $first, $this->en, ['points' => 50]);
        $this->world->selfEvalAttempt($runnerUp->id, $second, $this->en, ['points' => 90, 'finished' => 0]);

        $this->get('/en/ranks/self-evaluation')
            ->assertOk()
            ->assertSeeInOrder(['TopScorer', '70', 'SecondBest', '50']);
    }






    // -----------------------------------------------------------
    // testSimulationRankingAddsUpTheBestScores
    // -----------------------------------------------------------
    //
    // The same for simulations: each member's best run per
    // simulation, summed, highest first.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testSimulationRankingAddsUpTheBestScores(): void
    {
        $first = $this->world->scenario($this->en);
        $second = $this->world->scenario($this->en);
        $leader = $this->world->member(['username' => 'SimLeader']);
        $runnerUp = $this->world->member(['username' => 'SimRunnerUp']);
        $this->world->scenarioAttempt($leader->id, $first, $this->en, ['points' => 200]);
        $this->world->scenarioAttempt($leader->id, $second, $this->en, ['points' => 150]);
        $this->world->scenarioAttempt($runnerUp->id, $first, $this->en, ['points' => 300]);

        $this->get('/en/ranks/simulations')
            ->assertOk()
            ->assertSeeInOrder(['SimLeader', '350', 'SimRunnerUp', '300']);
    }






    // -----------------------------------------------------------
    // testRankingPrintsUserNamesAsText
    // -----------------------------------------------------------
    //
    // Members choose their user names, and the ranking shows
    // them to everyone: markup in a name is escaped, never
    // rendered.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testRankingPrintsUserNamesAsText(): void
    {
        $category = $this->world->lmCategory($this->en);
        $member = $this->world->member(['username' => '<b onclick="x()">Bold</b>']);
        $this->world->selfEvalAttempt($member->id, $category, $this->en, ['points' => 10]);

        $this->get('/en/ranks/self-evaluation')
            ->assertSee('&lt;b onclick=&quot;x()&quot;&gt;Bold&lt;/b&gt;', false)
            ->assertDontSee('<b onclick="x()">Bold</b>', false);
    }






    // -----------------------------------------------------------
    // testRankingShowsEachBadgeOnceInTheOrderEarned
    // -----------------------------------------------------------
    //
    // A badge stored twice shows once, and the badges beside a
    // name come in the order the member first earned them.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testRankingShowsEachBadgeOnceInTheOrderEarned(): void
    {
        $member = $this->world->member();
        $this->world->selfEvalAttempt($member->id, $this->world->lmCategory($this->en), $this->en, ['points' => 10]);
        foreach (['badge_login_10', 'badge_all_presentations', 'badge_login_10', 'badge_final_test'] as $badge) {
            $this->world->badge($member->id, $this->en, $badge);
        }

        $page = $this->get('/en/ranks/self-evaluation')
            ->assertOk()
            ->assertSeeInOrder(['badges/badge_login_10.png', 'badges/badge_all_presentations.png', 'badges/badge_final_test.png'], false)
            ->getContent();

        // Each badge's picture is named twice: its link and its
        // image
        $this->assertSame(2, substr_count($page, 'badges/badge_login_10.png'));
    }






    // -----------------------------------------------------------
    // testRankingQueriesDoNotGrowWithTheMembers
    // -----------------------------------------------------------
    //
    // Both rankings read the badges of all their members in
    // one query, so a page runs as many queries for four
    // ranked members as for one.
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

        $this->assertSame($few, $this->rankingQueries());
    }






    // -----------------------------------------------------------
    // testLanguageSwitchStoresTheChoice
    // -----------------------------------------------------------
    //
    // lang/{lang} keeps the language in the session and
    // returns to the home page.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testLanguageSwitchStoresTheChoice(): void
    {
        $this->get('/lang/lt')->assertRedirect('/')->assertSessionHas('lang', 'lt');
    }






    // -----------------------------------------------------------
    // testUnknownLanguageIsABadRequest
    // -----------------------------------------------------------
    //
    // A language outside the five the site speaks is a 400 and
    // leaves the session alone.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testUnknownLanguageIsABadRequest(): void
    {
        $this->get('/lang/xx')->assertStatus(400);

        $this->assertNotSame('xx', session('lang'));
    }






    // -----------------------------------------------------------
    // testLanguageInTheUrlSetsTheSession
    // -----------------------------------------------------------
    //
    // Opening a page under /lt/ makes Lithuanian the session's
    // language for the pages that follow.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testLanguageInTheUrlSetsTheSession(): void
    {
        $this->get('/lt/news')->assertOk()->assertSessionHas('lang', 'lt');
    }






    // -----------------------------------------------------------
    // testUnknownLanguageInTheUrlFallsBackToTheSession
    // -----------------------------------------------------------
    //
    // An unknown language in the URL is ignored: the page
    // opens in the session's language — English for a new
    // visitor.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testUnknownLanguageInTheUrlFallsBackToTheSession(): void
    {
        $this->get('/xx/news')->assertOk()->assertSessionHas('lang', 'en');
    }






    // -----------------------------------------------------------
    // testResponsesDoNotInviteEveryOrigin
    // -----------------------------------------------------------
    //
    // No response names an allowed origin or method: the
    // scripts of another site get no CORS permission to read
    // the pages. The no-cache Cache-Control stays.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testResponsesDoNotInviteEveryOrigin(): void
    {
        $this->get('/')
            ->assertHeaderMissing('Access-Control-Allow-Origin')
            ->assertHeaderMissing('Access-Control-Allow-Methods')
            ->assertHeader('Cache-Control', 'must-revalidate, no-cache, private');
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
