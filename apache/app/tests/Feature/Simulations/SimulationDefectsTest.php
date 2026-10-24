<?php

// -----------------------------------------------------------
//  [*] SimulationDefectsTest — known simulation defects
//
//  Each test states the behaviour the simulations should
//  have and pins the defect that breaks it today
//  (KnownDefects): the run reports them as incomplete,
//  tagged with their ids, until a fix makes one pass — then
//  the test fails as the cue to remove its pin and move it
//  into SimulationsTest.
//
//    simulation-badge-unreachable  — badge_all_simulations
//                                    waits for simulations no
//                                    member page offers
//
//  Used by:
//    - PHPUnit — the Feature suite (runTests.sh)
// -----------------------------------------------------------


namespace Tests\Feature\Simulations;

use Illuminate\Support\Facades\DB;
use Tests\FeatureTestCase;
use Tests\Support\Flows;








// -----------------------------------------------------------
// SimulationDefectsTest
// -----------------------------------------------------------
//
// One pinned defect per test, named in the header; an
// English simulation of two levels in a category, and a
// member, for every test.
//
// Used by:
//   - PHPUnit — the Feature suite (runTests.sh)
// -----------------------------------------------------------

class SimulationDefectsTest extends FeatureTestCase
{

    // Starting a run and choosing at each step
    use Flows;

    // The English id, the category, the simulation, its two
    // choices and the member of the test running now
    private $en;
    private $category;
    private $scenario;
    private $first;
    private $last;
    private $member;






    // -----------------------------------------------------------
    // setUp
    // -----------------------------------------------------------
    //
    // A simulation in a category: a first choice of 200 that
    // leads to a last one of 100.
    //
    // Used by:
    //   - PHPUnit, before every test
    // -----------------------------------------------------------

    protected function setUp(): void
    {
        parent::setUp();
        $this->en = $this->world->language('en');
        $this->category = $this->world->scenarioCategory($this->en);
        $this->scenario = $this->world->scenario($this->en, [], [$this->category]);
        $this->first = $this->world->scenarioOption($this->scenario, 0, 1, 200);
        $this->last = $this->world->scenarioOption($this->scenario, $this->first, 2, 100);
        $this->member = $this->world->member();
    }






    // -----------------------------------------------------------
    // testBadgeCountsTheSimulationsMembersAreOffered
    // -----------------------------------------------------------
    //
    // Members reach a simulation only through a category of
    // its language (simulations_list), but
    // check_if_all_simulations_finished counts every
    // simulation of the language — one filed under no
    // category, which the panel allows, can never be finished
    // from the site, so badge_all_simulations, and with it
    // badge_finished_course, never comes. Production's Latvian
    // has six such. Wanted: finishing every simulation the
    // category pages offer earns the badge.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testBadgeCountsTheSimulationsMembersAreOffered(): void
    {
        $this->world->scenario($this->en, ['descr' => 'Filed under no category']);
        $this->signInMember($this->member)->get("/en/simulations/category/{$this->category}")
            ->assertOk()
            ->assertDontSee('Filed under no category');

        $this->finishTheRun('/en');

        $this->pinDefect('simulation-badge-unreachable', function () {
            $this->assertTrue($this->hasBadge($this->en), 'finishing every simulation on offer earned no badge_all_simulations');
        });
    }






    // -----------------------------------------------------------
    // finishTheRun
    // -----------------------------------------------------------
    //
    // Runs the simulation through both choices in English and
    // opens its end page under the given language prefix.
    //
    // Used by:
    //   - the tests of this class
    // -----------------------------------------------------------

    private function finishTheRun(string $prefix): void
    {
        $publicId = $this->startSimulation($this->member, $this->scenario);
        $this->chooseSimulationOption($this->member, $publicId, $this->first);
        $this->chooseSimulationOption($this->member, $publicId, $this->last);
        $this->signInMember($this->member)->get("$prefix/simulation/progress/$publicId")->assertOk();
    }






    // -----------------------------------------------------------
    // hasBadge
    // -----------------------------------------------------------
    //
    // Whether the member holds badge_all_simulations in the
    // language.
    //
    // Used by:
    //   - the tests of this class
    // -----------------------------------------------------------

    private function hasBadge(int $langId): bool
    {
        return DB::table('bei_users_badges')
            ->where(['user_id' => $this->member->id, 'lang_id' => $langId, 'name' => 'badge_all_simulations'])
            ->exists();
    }
}
