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
//    simulation-mode-session-only  — a run's mode lives in the
//                                    session alone, and a run
//                                    resumed without it shows
//                                    no feedback
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

    // The simulation, its two choices on the way to the end,
    // and the member of the test running now
    private $scenario;
    private $check;
    private $report;
    private $member;






    // -----------------------------------------------------------
    // setUp
    // -----------------------------------------------------------
    //
    // A simulation in a category: checking the sender (200,
    // with feedback) leads to reporting it (100, with
    // feedback of its own).
    //
    // Used by:
    //   - PHPUnit, before every test
    // -----------------------------------------------------------

    protected function setUp(): void
    {
        parent::setUp();
        $en = $this->world->language('en');
        $this->scenario = $this->world->scenario($en, [], [$this->world->scenarioCategory($en)]);
        $this->check = $this->world->scenarioOption($this->scenario, 0, 1, 200, [
            'situation' => 'Check the sender',
            'feedback'  => 'Good: the domain is wrong',
        ]);
        $this->report = $this->world->scenarioOption($this->scenario, $this->check, 2, 100, [
            'situation' => 'Report it to IT',
            'feedback'  => 'Right: IT can block the sender',
        ]);
        $this->member = $this->world->member();
    }






    // -----------------------------------------------------------
    // testSummaryModeOutlivesTheSession
    // -----------------------------------------------------------
    //
    // The mode chosen at the start — 2, every choice's
    // feedback listed at the end — is kept only in the
    // session, never on the run: a session that ends halfway
    // (120 idle minutes, or any restart of the container,
    // whose sessions live on a tmpfs) leaves a run that the
    // history resumes and finishes with no feedback at all.
    // Wanted: the end page lists the feedback of the mode the
    // run was started in.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testSummaryModeOutlivesTheSession(): void
    {
        $publicId = $this->startSimulation($this->member, $this->scenario, 2);
        $this->chooseSimulationOption($this->member, $publicId, $this->check);
        $this->flushSession();
        $this->chooseSimulationOption($this->member, $publicId, $this->report);

        $html = $this->signInMember($this->member)->get("/en/simulation/progress/$publicId")->assertOk()->getContent();

        $this->pinDefect('simulation-mode-session-only', function () use ($html) {
            $this->assertStringContainsString('Good: the domain is wrong', $html, 'the end page lists no feedback');
            $this->assertStringContainsString('Right: IT can block the sender', $html, 'the end page lists no feedback');
        });
    }
}
