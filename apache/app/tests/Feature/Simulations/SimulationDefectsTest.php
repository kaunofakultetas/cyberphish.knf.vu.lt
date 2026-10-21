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
//    simulation-foreign-option      — a step records any
//                                     option id posted, as
//                                     often as posted
//    simulation-results-mail-repeat — every visit to a
//                                     finished run mails the
//                                     result again
//    simulation-other-language      — a simulation of another
//                                     language is a 500
//    simulation-without-category    — a simulation filed
//                                     under no category is a
//                                     500
//
//  Used by:
//    - PHPUnit — the Feature suite (runTests.sh)
// -----------------------------------------------------------


namespace Tests\Feature\Simulations;

use App\Mail\SimulationsResults;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
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
    // testRunNeverScoresAboveItsBestPath
    // -----------------------------------------------------------
    //
    // SimulationsController::simulation_do_stuff_save stores
    // whatever option id is posted, without checking that the
    // current step offers it, so posting the 200-point choice
    // three times before the last one makes 700 points out of
    // a possible 300 — and the public ranking shows them.
    // Wanted: the run stays within 300.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testRunNeverScoresAboveItsBestPath(): void
    {
        $publicId = $this->startSimulation($this->member, $this->scenario);
        foreach ([$this->best, $this->best, $this->best, $this->last] as $option) {
            $this->chooseSimulationOption($this->member, $publicId, $option);
        }
        $this->signInMember($this->member)->get("/en/simulation/progress/$publicId");

        $this->pinDefect('simulation-foreign-option', function () use ($publicId) {
            $points = DB::table('bei_users_scenarios')->where('public_id', $publicId)->value('points');
            $this->assertLessThanOrEqual(300, $points, 'the run scored more than its best path is worth');
        });
    }






    // -----------------------------------------------------------
    // testFinishedRunMailsItsResultOnce
    // -----------------------------------------------------------
    //
    // The end page sends the SimulationsResults mail every
    // time it is shown, finished before or not, so a reload
    // or a revisit from the history mails the result again.
    // Wanted: one mail per run.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testFinishedRunMailsItsResultOnce(): void
    {
        $publicId = $this->startSimulation($this->member, $this->scenario);
        $this->chooseSimulationOption($this->member, $publicId, $this->best);
        $this->chooseSimulationOption($this->member, $publicId, $this->last);
        $this->signInMember($this->member)->get("/en/simulation/progress/$publicId");

        $this->signInMember($this->member)->get("/en/simulation/progress/$publicId");

        $this->pinDefect('simulation-results-mail-repeat', function () {
            Mail::assertSent(SimulationsResults::class, 1);
        });
    }






    // -----------------------------------------------------------
    // testSimulationOfAnotherLanguageIsNotFound
    // -----------------------------------------------------------
    //
    // R_Exists only checks that the simulation exists, then
    // simulation_start looks it up in the session's language
    // and calls toArray on nothing — a 500. Wanted: a 404.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testSimulationOfAnotherLanguageIsNotFound(): void
    {
        $lt = $this->world->language('lt');
        $lithuanian = $this->world->scenario($lt, [], [$this->world->scenarioCategory($lt)]);

        $this->pinDefect('simulation-other-language', function () use ($lithuanian) {
            $this->signInMember($this->member)->get("/en/simulation/$lithuanian")->assertNotFound();
        });
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
}
