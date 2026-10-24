<?php

// -----------------------------------------------------------
//  [*] AdminDashboardTest — the figures of the panel's home
//
//  admin-panel/dashboard lists the members of the manager's
//  country in a tab per language they have begun, each row
//  with that language's course progress, simulations count
//  and knowledge results, and the members who have begun
//  nothing in a Not started tab. The page gathers the
//  figures for the whole country at once, and runs the
//  progress function only for a member and language with a
//  row the function counts — so these tests hold every row
//  to the member's own figures, worked out one member and
//  language at a time as the page once did, check who
//  stands in which tab, that a main admin reads every
//  country — and only a main admin, not a country manager
//  without a country — and that each row carries the text
//  the page's quick search matches, and check that more
//  members cost the page no more queries.
//
//  Used by:
//    - PHPUnit — the Feature suite (runTests.sh)
// -----------------------------------------------------------


namespace Tests\Feature\Admin;

use App\Http\Controllers\Admin\ManagerMainController;
use App\Models\LMUsers;
use Dom\HTMLDocument;
use Illuminate\Support\Facades\DB;
use Tests\FeatureTestCase;








// -----------------------------------------------------------
// AdminDashboardTest
// -----------------------------------------------------------
//
// A manager of country 2 opens the dashboard in every test,
// with English and Lithuanian on the site.
//
// Used by:
//   - PHPUnit — the Feature suite (runTests.sh)
// -----------------------------------------------------------

class AdminDashboardTest extends FeatureTestCase
{

    // The English and Lithuanian ids and the country manager
    // of the test running now
    private $en;
    private $lt;
    private $manager;






    // -----------------------------------------------------------
    // setUp
    // -----------------------------------------------------------
    //
    // The two languages and the manager every test shares.
    //
    // Used by:
    //   - PHPUnit, before every test
    // -----------------------------------------------------------

    protected function setUp(): void
    {
        parent::setUp();
        $this->en = $this->world->language('en');
        $this->lt = $this->world->language('lt');
        $this->manager = $this->world->manager(['main' => 0, 'country' => 2]);
    }






    // -----------------------------------------------------------
    // testEveryRowShowsTheMembersOwnFigures
    // -----------------------------------------------------------
    //
    // Four members of the country, each busy in a different
    // way — one with something of every kind in English, one
    // with a Lithuanian self-evaluation alone, one with only an
    // unfinished Lithuanian run and a failed and an unfinished
    // English test, one with nothing — and every row of every
    // tab shows what figuresOf works out for that member and
    // language. The rows are exactly the four member and
    // language pairs begun, the idle member is not started,
    // and three rows are pinned to their values, so the
    // comparison cannot pass on figures that are all 0.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testEveryRowShowsTheMembersOwnFigures(): void
    {
        $category = $this->world->lmCategory($this->en);
        $lesson = $this->world->lmContent($category, $this->en);
        $this->world->selfEvalQuestion($category, [['Right', true, 100], ['Wrong', false, 0]]);
        $ltCategory = $this->world->lmCategory($this->lt);
        $this->world->selfEvalQuestion($ltCategory, [['Right', true, 100], ['Wrong', false, 0]]);
        $scenario = $this->world->scenario($this->en);
        $ltScenario = $this->world->scenario($this->lt);

        $busy = $this->world->member(['country' => 2]);
        $this->world->lessonDone($busy->id, $lesson);
        $this->world->selfEvalAttempt($busy->id, $category, $this->en);
        $this->world->knowledgeAttempt($busy->id, $this->en, ['results' => 60]);
        $this->world->knowledgeAttempt($busy->id, $this->en, ['results' => 80]);
        $this->world->scenarioAttempt($busy->id, $scenario, $this->en);
        $this->world->scenarioAttempt($busy->id, $scenario, $this->en, ['finished' => 0]);

        $lithuanian = $this->world->member(['country' => 2]);
        $this->world->selfEvalAttempt($lithuanian->id, $ltCategory, $this->lt);

        $starter = $this->world->member(['country' => 2]);
        $this->world->scenarioAttempt($starter->id, $ltScenario, $this->lt, ['finished' => 0]);
        $this->world->knowledgeAttempt($starter->id, $this->en, ['results' => 40]);
        $this->world->knowledgeAttempt($starter->id, $this->en, ['results' => 90, 'finished' => 0]);

        $idle = $this->world->member(['country' => 2]);

        $page = $this->signInManager($this->manager)->get('/admin-panel/dashboard')->assertOk();
        $tabs = $page->viewData('recordlist');

        $rows = [];
        foreach ($tabs as $langId => $tab) {
            foreach ($tab as $row) {
                $shown = [$row['course_progress'], $row['simulations'], $row['knowledge_results']];
                $this->assertEquals($this->figuresOf($row['id'], $langId), $shown, "member {$row['id']} in language $langId");
                $rows[] = "{$row['id']}-$langId";
            }
        }
        $begun = ["{$busy->id}-{$this->en}", "{$lithuanian->id}-{$this->lt}", "{$starter->id}-{$this->en}", "{$starter->id}-{$this->lt}"];
        $this->assertEqualsCanonicalizing($begun, $rows);
        $this->assertSame([$idle->id], array_column($page->viewData('not_started'), 'id'));

        $this->assertEquals(100, $this->rowOf($tabs[$this->en], $busy->id)['course_progress']);
        $this->assertSame('60%, 80%', preg_replace('/<!--.*?-->/', '', $this->rowOf($tabs[$this->en], $busy->id)['knowledge_results']));
        $this->assertEquals(20, $this->rowOf($tabs[$this->lt], $lithuanian->id)['course_progress']);
        $this->assertSame('1 / 1', $this->rowOf($tabs[$this->lt], $starter->id)['simulations']);
    }






    // -----------------------------------------------------------
    // testTabsListTheMembersWhoBeganTheirLanguage
    // -----------------------------------------------------------
    //
    // A tab stands for each language a member of the country
    // has begun and lists only those members, its label
    // counting them, busiest first: two members began
    // Lithuanian alone — one only opened a self-evaluation —
    // one began both, one English alone, so Lithuanian (3)
    // comes before English (2) whatever the languages' ids. A
    // third language on the site that nobody began has no
    // tab, and the member who began nothing stands in Not
    // started (1). Every tab once listed every member, so most
    // tabs were the whole country at 0.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testTabsListTheMembersWhoBeganTheirLanguage(): void
    {
        $this->world->language('gr');
        $ltCategory = $this->world->lmCategory($this->lt);
        $enLesson = $this->world->lmContent($this->world->lmCategory($this->en), $this->en);
        $ltScenario = $this->world->scenario($this->lt);

        $ltOnly = $this->world->member(['country' => 2]);
        $this->world->scenarioAttempt($ltOnly->id, $ltScenario, $this->lt);
        $ltOpened = $this->world->member(['country' => 2]);
        $this->world->selfEvalAttempt($ltOpened->id, $ltCategory, $this->lt, ['finished' => 0]);
        $both = $this->world->member(['country' => 2]);
        $this->world->lessonDone($both->id, $enLesson);
        $this->world->knowledgeAttempt($both->id, $this->lt);
        $enOnly = $this->world->member(['country' => 2]);
        $this->world->lessonDone($enOnly->id, $enLesson);
        $idle = $this->world->member(['country' => 2]);

        $page = $this->signInManager($this->manager)->get('/admin-panel/dashboard')->assertOk();

        $tabs = $page->viewData('recordlist');
        $this->assertSame([$this->lt, $this->en], array_keys($tabs));
        $this->assertEqualsCanonicalizing([$ltOnly->id, $ltOpened->id, $both->id], array_column($tabs[$this->lt], 'id'));
        $this->assertEqualsCanonicalizing([$both->id, $enOnly->id], array_column($tabs[$this->en], 'id'));
        $this->assertSame([$idle->id], array_column($page->viewData('not_started'), 'id'));
        $page->assertSeeInOrder(['In Language lt (3)', 'In English (2)', 'Not started (1)']);
        $page->assertDontSee('In Language gr');
    }






    // -----------------------------------------------------------
    // testMainAdminSeesEveryCountry
    // -----------------------------------------------------------
    //
    // A main admin's dashboard lists the members of every
    // country, Other included, each row's Country cell naming
    // the member's; a country manager's lists only its own
    // country's, without the column. The dashboard once read
    // the main admin's own country, Other, and showed nobody
    // else.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testMainAdminSeesEveryCountry(): void
    {
        $lesson = $this->world->lmContent($this->world->lmCategory($this->en), $this->en);
        $lithuanian = $this->world->member(['country' => 1]);
        $this->world->lessonDone($lithuanian->id, $lesson);
        $greek = $this->world->member(['country' => 3]);
        $this->world->lessonDone($greek->id, $lesson);
        $other = $this->world->member(['country' => 0]);
        $this->world->lessonDone($other->id, $lesson);
        $maltese = $this->world->member(['country' => 5]);

        $page = $this->signInManager($this->world->manager())->get('/admin-panel/dashboard')->assertOk();

        $this->assertEqualsCanonicalizing([$lithuanian->id, $greek->id, $other->id], array_column($page->viewData('recordlist')[$this->en], 'id'));
        $this->assertSame([$maltese->id], array_column($page->viewData('not_started'), 'id'));
        $document = HTMLDocument::createFromString($page->getContent(), LIBXML_NOERROR);
        $rows = [
            [$lithuanian, "tab-a{$this->en}", 'Lithuania'],
            [$greek, "tab-a{$this->en}", 'Greece / Cyprus'],
            [$other, "tab-a{$this->en}", 'Other'],
            [$maltese, 'tab-not-started', 'Malta'],
        ];
        foreach ($rows as [$member, $pane, $country]) {
            $row = $document->querySelector("#$pane tr[data-search=\"{$member->username} {$member->email}\"]");
            $this->assertNotNull($row, "{$member->email} is not in $pane");
            $this->assertSame($country, trim($row->querySelectorAll('td')->item(2)->textContent));
        }

        $this->signInManager($this->manager)->get('/admin-panel/dashboard')->assertOk()
            ->assertDontSee('<th>Country</th>', false)
            ->assertDontSee($lithuanian->email);
    }






    // -----------------------------------------------------------
    // testCountryManagerWithoutACountrySeesNoOtherCountry
    // -----------------------------------------------------------
    //
    // Only the main flag opens every country: a country
    // manager whose row holds no country — the manager forms
    // check none — sees no other country's members, and no
    // Country column. The dashboard once took the null country
    // for the main admin's every country, while
    // member_in_scope refused that manager their reports.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testCountryManagerWithoutACountrySeesNoOtherCountry(): void
    {
        $lesson = $this->world->lmContent($this->world->lmCategory($this->en), $this->en);
        $lithuanian = $this->world->member(['country' => 1]);
        $this->world->lessonDone($lithuanian->id, $lesson);
        $estonian = $this->world->member(['country' => 2]);
        $manager = $this->world->manager(['main' => 0, 'country' => null]);

        $page = $this->signInManager($manager)->get('/admin-panel/dashboard')->assertOk();

        $this->assertNull($page->viewData('countries'), 'the page shows the Country column');
        $page->assertDontSee($lithuanian->email)->assertDontSee($estonian->email);
    }






    // -----------------------------------------------------------
    // testEveryMemberRowCarriesItsSearchText
    // -----------------------------------------------------------
    //
    // The quick search box stands above the tabs, with its
    // clear button, and every member's row — in a language
    // tab and in Not started alike — carries the user name
    // and email that User_Search of admn.js matches, while
    // each tab has the hidden row that answers a search
    // finding nobody there.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testEveryMemberRowCarriesItsSearchText(): void
    {
        $lesson = $this->world->lmContent($this->world->lmCategory($this->en), $this->en);
        $begun = $this->world->member(['country' => 2]);
        $this->world->lessonDone($begun->id, $lesson);
        $idle = $this->world->member(['country' => 2]);

        $html = $this->signInManager($this->manager)->get('/admin-panel/dashboard')->assertOk()->getContent();
        $document = HTMLDocument::createFromString($html, LIBXML_NOERROR);

        $this->assertNotNull($document->querySelector('.user-search #user-search'), 'the page has no search box');
        $this->assertNotNull($document->querySelector('.user-search .user-search-clear'), 'the search box has no clear button');
        $this->assertNull($document->querySelector('#TTAB #user-search'), 'the search box stands among the tabs');
        foreach (["tab-a{$this->en}" => $begun, 'tab-not-started' => $idle] as $pane => $member) {
            $rows = $document->querySelectorAll("#$pane tr[data-search]");
            $this->assertCount(1, $rows, "the tab $pane");
            $this->assertSame("{$member->username} {$member->email}", $rows->item(0)->getAttribute('data-search'));
            $this->assertCount(1, $document->querySelectorAll("#$pane tr.search-none"), "the tab $pane");
        }
    }






    // -----------------------------------------------------------
    // testProgressMatchesTheFunctionAtHalfwayPoints
    // -----------------------------------------------------------
    //
    // LMUsers::country_progress repeats the stored function's
    // arithmetic in one statement; for members built to land
    // on halves and thirds — half the lessons, half the
    // simulations, a third or two of the self-evaluations, a
    // passed test, and their sums — every member and language
    // comes out exactly as bei_user_course_progress has it,
    // rounding included: half the lessons alone is 12, where
    // the double 12.5 rounds to the even 12. So do a lesson
    // stored twice, which counts once, and a lesson without a
    // category, which counts not at all.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testProgressMatchesTheFunctionAtHalfwayPoints(): void
    {
        $lesson = $this->world->lmContent($this->world->lmCategory($this->en), $this->en);
        $this->world->lmContent($this->world->lmCategory($this->en), $this->en);
        $uncategorised = $this->world->lmContent($this->world->lmCategory($this->en), $this->en, ['cat_id' => null]);
        $categories = [];
        for ($i = 0; $i < 3; $i++) {
            $categories[] = $category = $this->world->lmCategory($this->en);
            $this->world->selfEvalQuestion($category, [['Right', true, 100], ['Wrong', false, 0]]);
        }
        $scenario = $this->world->scenario($this->en);
        $this->world->scenario($this->en);

        $halfLessons = $this->world->member(['country' => 2]);
        $this->world->lessonDone($halfLessons->id, $lesson);
        $halfRuns = $this->world->member(['country' => 2]);
        $this->world->scenarioAttempt($halfRuns->id, $scenario, $this->en);
        $halves = $this->world->member(['country' => 2]);
        $this->world->lessonDone($halves->id, $lesson);
        $this->world->scenarioAttempt($halves->id, $scenario, $this->en);
        $third = $this->world->member(['country' => 2]);
        $this->world->selfEvalAttempt($third->id, $categories[0], $this->en);
        $this->world->scenarioAttempt($third->id, $scenario, $this->en);
        $passed = $this->world->member(['country' => 2]);
        $this->world->knowledgeAttempt($passed->id, $this->en, ['results' => 80]);
        $this->world->lessonDone($passed->id, $lesson);
        $twoThirds = $this->world->member(['country' => 2]);
        $this->world->selfEvalAttempt($twoThirds->id, $categories[0], $this->en);
        $this->world->selfEvalAttempt($twoThirds->id, $categories[1], $this->en);
        $this->world->lessonDone($twoThirds->id, $lesson);
        $this->world->scenarioAttempt($twoThirds->id, $scenario, $this->en);
        $idle = $this->world->member(['country' => 2]);
        $twice = $this->world->member(['country' => 2]);
        $this->world->lessonDone($twice->id, $lesson);
        $this->world->lessonDone($twice->id, $lesson);
        $unreachable = $this->world->member(['country' => 2]);
        $this->world->lessonDone($unreachable->id, $lesson);
        $this->world->lessonDone($unreachable->id, $uncategorised);

        $progress = LMUsers::country_progress(2, false);

        foreach ([$halfLessons, $halfRuns, $halves, $third, $passed, $twoThirds, $idle, $twice, $unreachable] as $member) {
            foreach ([$this->en, $this->lt] as $langId) {
                $this->assertEquals(LMUsers::course_progress($member->id, $langId), $progress[$langId][$member->id] ?? 0, "member {$member->id} in language $langId");
            }
        }
        $this->assertEquals(12, $progress[$this->en][$halfLessons->id]);
        $this->assertEquals(12, $progress[$this->en][$twice->id]);
        $this->assertEquals(12, $progress[$this->en][$unreachable->id]);
    }






    // -----------------------------------------------------------
    // testDashboardRunsNoProgressFunction
    // -----------------------------------------------------------
    //
    // The dashboard never calls bei_user_course_progress: once
    // per member and language, in one statement as before, it
    // kept the main admin's page waiting over a second.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testDashboardRunsNoProgressFunction(): void
    {
        $lesson = $this->world->lmContent($this->world->lmCategory($this->en), $this->en);
        $this->busyMembers(2, $lesson, $this->world->scenario($this->en));

        $manager = $this->signInManager($this->manager);
        DB::flushQueryLog();
        DB::enableQueryLog();
        $manager->get('/admin-panel/dashboard')->assertOk();
        $queries = array_column(DB::getQueryLog(), 'query');
        DB::disableQueryLog();

        $this->assertSame([], array_values(array_filter($queries, function (string $query) {
            return str_contains($query, 'bei_user_course_progress');
        })));
    }






    // -----------------------------------------------------------
    // testDashboardQueriesDoNotGrowWithTheMembers
    // -----------------------------------------------------------
    //
    // The page runs as many queries for four busy members as
    // for one: the figures come from queries over the whole
    // country, where it once asked again for every member in
    // every language.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testDashboardQueriesDoNotGrowWithTheMembers(): void
    {
        $category = $this->world->lmCategory($this->en);
        $lesson = $this->world->lmContent($category, $this->en);
        $scenario = $this->world->scenario($this->en);
        $this->busyMembers(1, $lesson, $scenario);
        $few = $this->dashboardQueries();
        $this->busyMembers(3, $lesson, $scenario);

        $this->assertSame($few, $this->dashboardQueries());
    }






    // -----------------------------------------------------------
    // figuresOf
    // -----------------------------------------------------------
    //
    // The course progress, simulations count and knowledge
    // results of one member in one language, asked one member
    // and language at a time: the progress straight from the
    // stored function, the language's simulations over the
    // distinct ones the member started, and the member's
    // finished tests, oldest first, in the dashboard's line.
    //
    // Used by:
    //   - testEveryRowShowsTheMembersOwnFigures (above)
    // -----------------------------------------------------------

    private function figuresOf(int $userId, int $langId): array
    {
        $simulations = DB::table('bei_scenarios')->where('lang_id', $langId)->count();
        $started = DB::table('bei_users_scenarios')->where(['user_id' => $userId, 'lang_id' => $langId])->distinct()->count('scenario_id');
        $results = DB::table('bei_users_knowledge_test')
            ->where(['user_id' => $userId, 'lang_id' => $langId, 'finished' => 1])
            ->orderBy('id')
            ->get(['public_id', 'results'])
            ->map(function ($result) {
                return (array) $result;
            })
            ->all();

        return [
            LMUsers::course_progress($userId, $langId),
            "$simulations / $started",
            ManagerMainController::knowledge_results($results),
        ];
    }






    // -----------------------------------------------------------
    // rowOf
    // -----------------------------------------------------------
    //
    // The member's row of one tab.
    //
    // Used by:
    //   - testEveryRowShowsTheMembersOwnFigures (above)
    // -----------------------------------------------------------

    private function rowOf(array $tab, int $userId): array
    {
        foreach ($tab as $row) {
            if ($row['id'] == $userId) {
                return $row;
            }
        }
        $this->fail("member $userId has no row");
    }






    // -----------------------------------------------------------
    // busyMembers
    // -----------------------------------------------------------
    //
    // That many members of the manager's country, each with
    // the lesson completed, a finished run of the simulation
    // and a passed knowledge test in English.
    //
    // Used by:
    //   - testDashboardQueriesDoNotGrowWithTheMembers (above)
    // -----------------------------------------------------------

    private function busyMembers(int $count, int $lesson, int $scenario): void
    {
        for ($i = 0; $i < $count; $i++) {
            $member = $this->world->member(['country' => 2]);
            $this->world->lessonDone($member->id, $lesson);
            $this->world->scenarioAttempt($member->id, $scenario, $this->en);
            $this->world->knowledgeAttempt($member->id, $this->en, ['results' => 90]);
        }
    }






    // -----------------------------------------------------------
    // dashboardQueries
    // -----------------------------------------------------------
    //
    // How many database queries the dashboard runs for the
    // manager: the connection's query log, emptied after the
    // sign-in, which reads the manager's row itself.
    //
    // Used by:
    //   - testDashboardQueriesDoNotGrowWithTheMembers (above)
    // -----------------------------------------------------------

    private function dashboardQueries(): int
    {
        $manager = $this->signInManager($this->manager);
        DB::flushQueryLog();
        DB::enableQueryLog();
        $manager->get('/admin-panel/dashboard')->assertOk();
        $queries = count(DB::getQueryLog());
        DB::disableQueryLog();
        return $queries;
    }
}
