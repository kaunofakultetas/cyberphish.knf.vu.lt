<?php

// -----------------------------------------------------------
//  [*] SimulationsTest — running a phishing simulation
//
//  The members-only simulations: the categories of a
//  language, the simulations of a category, a simulation's
//  start page, and a run — POST simulation_progress starts
//  it, each step offers the choices hanging under the
//  previous choice — only those count — and the run ends
//  when a choice has nothing under it: its points are added
//  up, the result is mailed once, and finishing every
//  simulation of the language earns badge_all_simulations.
//  In step-by-step mode (type 1) each choice's feedback
//  shows at the next step, in summary mode (type 2) all of
//  it at the end. A run is its member's alone, and a
//  simulation of another language a 404. The defects are
//  pinned in SimulationDefectsTest.
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
// SimulationsTest
// -----------------------------------------------------------
//
// One English simulation of two levels for every test: a
// first step of two choices — checking the sender (200
// points) or clicking the link (0) — each leading to a
// second step of its own.
//
// Used by:
//   - PHPUnit — the Feature suite (runTests.sh)
// -----------------------------------------------------------

class SimulationsTest extends FeatureTestCase
{

    // Starting a run and choosing at each step
    use Flows;

    // The English id, the category, the simulation, its
    // choices, and the member of the test running now
    private $en;
    private $category;
    private $scenario;
    private $check;
    private $click;
    private $report;
    private $delete;
    private $pay;
    private $member;






    // -----------------------------------------------------------
    // setUp
    // -----------------------------------------------------------
    //
    // The simulation "A fake invoice arrives" in the category
    // "Credential theft", tagged "Urgency", with its tree of
    // choices: check (200) leads to report (100) or delete
    // (50), click (0) leads to pay (0).
    //
    // Used by:
    //   - PHPUnit, before every test
    // -----------------------------------------------------------

    protected function setUp(): void
    {
        parent::setUp();
        $this->en = $this->world->language('en');
        $this->category = $this->world->scenarioCategory($this->en, ['name' => 'Credential theft']);
        $attribute = $this->world->scenarioAttribute($this->en, ['name' => 'Urgency']);
        $this->scenario = $this->world->scenario(
            $this->en,
            ['descr' => 'A fake invoice arrives', 'goal' => 'Spot the fake', 'actors' => 'Finance clerk'],
            [$this->category],
            [$attribute]
        );
        $this->check = $this->world->scenarioOption($this->scenario, 0, 1, 200, [
            'situation' => 'Check the sender',
            'feedback'  => 'Good: the domain is wrong',
        ]);
        $this->click = $this->world->scenarioOption($this->scenario, 0, 1, 0, ['situation' => 'Click the link']);
        $this->report = $this->world->scenarioOption($this->scenario, $this->check, 2, 100, [
            'situation' => 'Report it to IT',
            'feedback'  => 'Right: IT can block the sender',
        ]);
        $this->delete = $this->world->scenarioOption($this->scenario, $this->check, 2, 50, ['situation' => 'Delete it']);
        $this->pay = $this->world->scenarioOption($this->scenario, $this->click, 2, 0, ['situation' => 'Pay the invoice']);
        $this->member = $this->world->member();
    }






    // -----------------------------------------------------------
    // testSimulationsNeedAnAccount
    // -----------------------------------------------------------
    //
    // A visitor is sent to the login form.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testSimulationsNeedAnAccount(): void
    {
        $this->get('/en/simulations')->assertRedirect('/login');
        $this->get("/en/simulation/{$this->scenario}")->assertRedirect('/login');
    }






    // -----------------------------------------------------------
    // testCategoriesOfTheLanguageAreListed
    // -----------------------------------------------------------
    //
    // The language's simulation categories, never another
    // language's.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testCategoriesOfTheLanguageAreListed(): void
    {
        $this->world->scenarioCategory($this->world->language('lt'), ['name' => 'Tapatybės vagystė']);

        $this->signInMember($this->member)->get('/en/simulations')
            ->assertOk()
            ->assertSee('Credential theft')
            ->assertDontSee('Tapatybės vagystė');
    }






    // -----------------------------------------------------------
    // testCategoryListsItsSimulations
    // -----------------------------------------------------------
    //
    // A category lists the simulations filed under it — by id,
    // actors and type, linked to their start pages; an unknown
    // category is a 404.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testCategoryListsItsSimulations(): void
    {
        $this->signInMember($this->member)->get("/en/simulations/credential-theft/{$this->category}")
            ->assertOk()
            ->assertSee('Finance clerk')
            ->assertSee("en/simulation/{$this->scenario}", false);
        $this->signInMember($this->member)->get('/en/simulations/gone/' . ($this->category + 1000))
            ->assertNotFound();
    }






    // -----------------------------------------------------------
    // testStartPageDescribesTheSimulation
    // -----------------------------------------------------------
    //
    // The description, the goal, the categories and the
    // attributes; an unknown simulation is a 404.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testStartPageDescribesTheSimulation(): void
    {
        $this->signInMember($this->member)->get("/en/simulation/{$this->scenario}")
            ->assertOk()
            ->assertSee('A fake invoice arrives')
            ->assertSee('Spot the fake')
            ->assertSee('Credential theft')
            ->assertSee('Urgency');
        $this->signInMember($this->member)->get('/en/simulation/' . ($this->scenario + 1000))->assertNotFound();
    }






    // -----------------------------------------------------------
    // testStartingCreatesARun
    // -----------------------------------------------------------
    //
    // An open run of the member, in English, with no points
    // yet.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testStartingCreatesARun(): void
    {
        $publicId = $this->startSimulation($this->member, $this->scenario);

        $run = DB::table('bei_users_scenarios')->where('public_id', $publicId)->first();
        $this->assertEquals($this->member->id, $run->user_id);
        $this->assertEquals($this->scenario, $run->scenario_id);
        $this->assertEquals($this->en, $run->lang_id);
        $this->assertEquals(0, $run->finished);
        $this->assertEquals(0, $run->points);
    }






    // -----------------------------------------------------------
    // testFirstStepOffersTheLevelOneChoices
    // -----------------------------------------------------------
    //
    // The run opens on the two first-level choices.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testFirstStepOffersTheLevelOneChoices(): void
    {
        $publicId = $this->startSimulation($this->member, $this->scenario);

        $this->signInMember($this->member)->get("/en/simulation/progress/$publicId")
            ->assertOk()
            ->assertSee('Check the sender')
            ->assertSee('Click the link')
            ->assertDontSee('Report it to IT');
    }






    // -----------------------------------------------------------
    // testChoiceLeadsToItsOwnNextStep
    // -----------------------------------------------------------
    //
    // After checking the sender, the next step offers the
    // choices under that choice, not those under clicking.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testChoiceLeadsToItsOwnNextStep(): void
    {
        $publicId = $this->startSimulation($this->member, $this->scenario);
        $this->chooseSimulationOption($this->member, $publicId, $this->check)
            ->assertRedirect("/en/simulation/progress/$publicId");

        $this->signInMember($this->member)->get("/en/simulation/progress/$publicId")
            ->assertSee('Report it to IT')
            ->assertSee('Delete it')
            ->assertDontSee('Pay the invoice');
    }






    // -----------------------------------------------------------
    // testStepByStepModeShowsFeedbackAfterEachChoice
    // -----------------------------------------------------------
    //
    // In type 1 the chosen option's feedback greets the member
    // at the next step.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testStepByStepModeShowsFeedbackAfterEachChoice(): void
    {
        $publicId = $this->startSimulation($this->member, $this->scenario, 1);
        $this->chooseSimulationOption($this->member, $publicId, $this->check);

        $this->signInMember($this->member)->get("/en/simulation/progress/$publicId")
            ->assertSee('Good: the domain is wrong');
    }






    // -----------------------------------------------------------
    // testLastChoiceScoresTheRunAndMailsTheResult
    // -----------------------------------------------------------
    //
    // Checking (200) and reporting (100) end the run with 300
    // points: the run is closed, the result page and one
    // SimulationsResults mail show the points, and — the only
    // simulation of the language being done — the member earns
    // badge_all_simulations.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testLastChoiceScoresTheRunAndMailsTheResult(): void
    {
        $publicId = $this->startSimulation($this->member, $this->scenario);
        $this->chooseSimulationOption($this->member, $publicId, $this->check);
        $this->chooseSimulationOption($this->member, $publicId, $this->report);

        $this->signInMember($this->member)->get("/en/simulation/progress/$publicId")
            ->assertOk()
            ->assertSee(__('main.simulation_finished'))
            ->assertSee('300');

        $run = DB::table('bei_users_scenarios')->where('public_id', $publicId)->first();
        $this->assertEquals(1, $run->finished);
        $this->assertEquals(300, $run->points);
        $this->assertNotNull($run->ended);
        Mail::assertSent(SimulationsResults::class, function (SimulationsResults $mail) {
            return $mail->hasTo($this->member->email) && (int) $mail->data['points'] === 300;
        });
        $this->assertTrue(DB::table('bei_users_badges')
            ->where(['user_id' => $this->member->id, 'name' => 'badge_all_simulations'])->exists());
    }






    // -----------------------------------------------------------
    // testSummaryModeListsTheFeedbackAtTheEnd
    // -----------------------------------------------------------
    //
    // In type 2 a choice's feedback is held back during the
    // run and the end page lists every chosen option with its
    // feedback.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testSummaryModeListsTheFeedbackAtTheEnd(): void
    {
        $publicId = $this->startSimulation($this->member, $this->scenario, 2);
        $this->chooseSimulationOption($this->member, $publicId, $this->check);
        $this->signInMember($this->member)->get("/en/simulation/progress/$publicId")
            ->assertDontSee('Good: the domain is wrong');

        $this->chooseSimulationOption($this->member, $publicId, $this->report);

        $this->signInMember($this->member)->get("/en/simulation/progress/$publicId")
            ->assertSeeInOrder(['Check the sender', 'Good: the domain is wrong', 'Report it to IT', 'Right: IT can block the sender']);
    }






    // -----------------------------------------------------------
    // testAllSimulationsBadgeNeedsEverySimulation
    // -----------------------------------------------------------
    //
    // With a second simulation in the language, finishing the
    // first earns no badge.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testAllSimulationsBadgeNeedsEverySimulation(): void
    {
        $this->world->scenario($this->en, [], [$this->category]);
        $publicId = $this->startSimulation($this->member, $this->scenario);
        $this->chooseSimulationOption($this->member, $publicId, $this->click);
        $this->chooseSimulationOption($this->member, $publicId, $this->pay);

        $this->signInMember($this->member)->get("/en/simulation/progress/$publicId");

        $this->assertFalse(DB::table('bei_users_badges')
            ->where(['user_id' => $this->member->id, 'name' => 'badge_all_simulations'])->exists());
    }






    // -----------------------------------------------------------
    // testAnotherMembersRunIsNotFound
    // -----------------------------------------------------------
    //
    // A second member who learns the public id gets a 404 for
    // the step and for a choice, and nothing is recorded.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testAnotherMembersRunIsNotFound(): void
    {
        $publicId = $this->startSimulation($this->member, $this->scenario);
        $stranger = $this->world->member();

        $this->signInMember($stranger)->get("/en/simulation/progress/$publicId")->assertNotFound();
        $this->chooseSimulationOption($stranger, $publicId, $this->check)->assertNotFound();

        $this->assertFalse(DB::table('bei_users_scenarios_options')->where('user_scenario_public_id', $publicId)->exists());
    }






    // -----------------------------------------------------------
    // testHistoryListsTheRuns
    // -----------------------------------------------------------
    //
    // The member's simulation history lists the finished run
    // — its actors and points, linked to its end page.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testHistoryListsTheRuns(): void
    {
        $run = $this->world->scenarioAttempt($this->member->id, $this->scenario, $this->en, ['points' => 250]);
        $publicId = DB::table('bei_users_scenarios')->where('id', $run)->value('public_id');

        $this->signInMember($this->member)->get('/cp/simulations-history')
            ->assertOk()
            ->assertSee('Finance clerk')
            ->assertSee('250')
            ->assertSee("en/simulation/progress/$publicId", false);
    }






    // -----------------------------------------------------------
    // testSimulationOfAnotherLanguageIsNotFound
    // -----------------------------------------------------------
    //
    // A Lithuanian simulation opened under the English site is
    // a 404.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testSimulationOfAnotherLanguageIsNotFound(): void
    {
        $lt = $this->world->language('lt');
        $lithuanian = $this->world->scenario($lt, [], [$this->world->scenarioCategory($lt)]);

        $this->signInMember($this->member)->get("/en/simulation/$lithuanian")->assertNotFound();
    }






    // -----------------------------------------------------------
    // testRunNeverScoresAboveItsBestPath
    // -----------------------------------------------------------
    //
    // Only a choice the current step offers is recorded:
    // checking three times, choosing pay from the other branch
    // on the way, then reporting ends the run with the 300
    // points of its best path.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testRunNeverScoresAboveItsBestPath(): void
    {
        $publicId = $this->startSimulation($this->member, $this->scenario);
        foreach ([$this->check, $this->check, $this->pay, $this->check, $this->report] as $option) {
            $this->chooseSimulationOption($this->member, $publicId, $option);
        }
        $this->signInMember($this->member)->get("/en/simulation/progress/$publicId");

        $this->assertEquals(300, DB::table('bei_users_scenarios')->where('public_id', $publicId)->value('points'));
        $this->assertSame(2, DB::table('bei_users_scenarios_options')->where('user_scenario_public_id', $publicId)->count());
    }






    // -----------------------------------------------------------
    // testFinishedRunMailsItsResultOnce
    // -----------------------------------------------------------
    //
    // The visit that closes the run mails its result; a
    // reload of the end page, or a visit from the history,
    // mails nothing more.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testFinishedRunMailsItsResultOnce(): void
    {
        $publicId = $this->startSimulation($this->member, $this->scenario);
        $this->chooseSimulationOption($this->member, $publicId, $this->check);
        $this->chooseSimulationOption($this->member, $publicId, $this->report);
        $this->signInMember($this->member)->get("/en/simulation/progress/$publicId");

        $this->signInMember($this->member)->get("/en/simulation/progress/$publicId");

        Mail::assertSent(SimulationsResults::class, 1);
    }
}
