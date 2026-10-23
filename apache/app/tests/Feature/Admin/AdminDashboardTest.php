<?php

// -----------------------------------------------------------
//  [*] AdminDashboardTest — the figures of the panel's home
//
//  admin-panel/dashboard lists the members of the manager's
//  country in a tab per language, each row with that
//  language's course progress, simulations count and
//  knowledge results. The page gathers them for the whole
//  country at once, and runs the progress function only
//  for a member and language with a row the function counts
//  — so these tests hold every row to the member's own
//  figures, worked out one member and language at a time as
//  the page once did, and check that more members cost the
//  page no more queries.
//
//  Used by:
//    - PHPUnit — the Feature suite (runTests.sh)
// -----------------------------------------------------------


namespace Tests\Feature\Admin;

use App\Http\Controllers\Admin\ManagerMainController;
use App\Models\LMUsers;
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
    // unfinished run and a failed and an unfinished test, one
    // with nothing — and every row of every tab shows what
    // figuresOf works out for that member and language. Three
    // rows are also pinned to their values, so the comparison
    // cannot pass on figures that are all 0.
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

        $this->world->member(['country' => 2]);

        $tabs = $this->signInManager($this->manager)->get('/admin-panel/dashboard')->assertOk()->viewData('recordlist');

        $rows = 0;
        foreach ($tabs as $langId => $tab) {
            foreach ($tab as $row) {
                $shown = [$row['course_progress'], $row['simulations'], $row['knowledge_results']];
                $this->assertEquals($this->figuresOf($row['id'], $langId), $shown, "member {$row['id']} in language $langId");
                $rows++;
            }
        }
        $this->assertSame(4 * DB::table('bei_lang')->count(), $rows);

        $this->assertEquals(100, $this->rowOf($tabs[$this->en], $busy->id)['course_progress']);
        $this->assertSame('60%, 80%', preg_replace('/<!--.*?-->/', '', $this->rowOf($tabs[$this->en], $busy->id)['knowledge_results']));
        $this->assertEquals(20, $this->rowOf($tabs[$this->lt], $lithuanian->id)['course_progress']);
        $this->assertSame('1 / 1', $this->rowOf($tabs[$this->lt], $starter->id)['simulations']);
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
