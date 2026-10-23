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
//    simulation-without-category    — a simulation filed
//                                     under no category is a
//                                     500
//    simulation-badge-message       — a badge held for long
//                                     is announced again on
//                                     each visit
//
//  Used by:
//    - PHPUnit — the Feature suite (runTests.sh)
// -----------------------------------------------------------


namespace Tests\Feature\Simulations;

use Tests\FeatureTestCase;
use Tests\Support\Flows;








// -----------------------------------------------------------
// SimulationDefectsTest
// -----------------------------------------------------------
//
// One pinned defect per test, named in the header; an
// English simulation of two levels and a member for every
// test.
//
// Used by:
//   - PHPUnit — the Feature suite (runTests.sh)
// -----------------------------------------------------------

class SimulationDefectsTest extends FeatureTestCase
{

    // Starting a run and choosing at each step
    use Flows;

    // The English id, the simulation, its choices and the
    // member of the test running now
    private $en;
    private $scenario;
    private $best;
    private $last;
    private $member;






    // -----------------------------------------------------------
    // setUp
    // -----------------------------------------------------------
    //
    // A simulation worth at most 300 points: a first choice of
    // 200 that leads to a last one of 100.
    //
    // Used by:
    //   - PHPUnit, before every test
    // -----------------------------------------------------------

    protected function setUp(): void
    {
        parent::setUp();
        $this->en = $this->world->language('en');
        $this->scenario = $this->world->scenario($this->en, [], [$this->world->scenarioCategory($this->en)]);
        $this->best = $this->world->scenarioOption($this->scenario, 0, 1, 200);
        $this->last = $this->world->scenarioOption($this->scenario, $this->best, 2, 100);
        $this->member = $this->world->member();
    }






    // -----------------------------------------------------------
    // testSimulationWithoutACategoryOpens
    // -----------------------------------------------------------
    //
    // simulation_start only sets the categories when the
    // simulation has some, and simulation_start.blade.php
    // counts them without the isset it gives the attributes —
    // an undefined variable, a 500. Wanted: the start page.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testSimulationWithoutACategoryOpens(): void
    {
        $uncategorised = $this->world->scenario($this->en, ['descr' => 'An uncategorised simulation']);

        $this->pinDefect('simulation-without-category', function () use ($uncategorised) {
            $this->signInMember($this->member)->get("/en/simulation/$uncategorised")
                ->assertOk()
                ->assertSee('An uncategorised simulation');
        });
    }






    // -----------------------------------------------------------
    // testHeldBadgeIsNotAnnouncedAgain
    // -----------------------------------------------------------
    //
    // Once every simulation of the language is finished,
    // SimulationsController::simulation_do_stuff calls
    // save_badge for badge_all_simulations on every end page
    // and flashes main.new_badge whatever save_badge answered
    // — false for a badge held already — so each later end
    // page, a reload or a visit from the history among them,
    // announces a new badge again. The one simulation of the
    // language is finished here, and its end page opened
    // twice. Wanted: only the visit that earned the badge
    // announces it.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testHeldBadgeIsNotAnnouncedAgain(): void
    {
        $publicId = $this->startSimulation($this->member, $this->scenario);
        $this->chooseSimulationOption($this->member, $publicId, $this->best);
        $this->chooseSimulationOption($this->member, $publicId, $this->last);
        $this->signInMember($this->member)->get("/en/simulation/progress/$publicId")
            ->assertSee(__('main.new_badge'), false);

        $revisit = $this->signInMember($this->member)->get("/en/simulation/progress/$publicId");

        $this->pinDefect('simulation-badge-message', function () use ($revisit) {
            $this->assertStringNotContainsString(__('main.new_badge'), $revisit->getContent(), 'the held badge was announced as new again');
        });
    }
}
