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
//  simulation a category of the language offers earns
//  badge_all_simulations.
//  In step-by-step mode (type 1) each choice's feedback
//  shows at the next step, in summary mode (type 2) all of
//  it at the end. A run is its member's alone, and a
//  simulation of another language a 404; one filed under no
//  category still opens, and one without steps ends at
//  once. The badge is announced once, by the visit that
//  earns it, and goes to the run's language, whatever code
//  the URL of its end page carries.
//
//  Used by:
//    - PHPUnit — the Feature suite (runTests.sh)
// -----------------------------------------------------------


namespace Tests\Feature\Simulations;

use App\Http\Controllers\SettingsController;
use App\Mail\SimulationsResults;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\Mailer\Exception\TransportException;
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
    // testSimulationWithoutACategoryOpens
    // -----------------------------------------------------------
    //
    // A simulation filed under no category still has its start
    // page.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testSimulationWithoutACategoryOpens(): void
    {
        $uncategorised = $this->world->scenario($this->en, ['descr' => 'An uncategorised simulation']);

        $this->signInMember($this->member)->get("/en/simulation/$uncategorised")
            ->assertOk()
            ->assertSee('An uncategorised simulation');
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
    // testHeldBadgeIsNotAnnouncedAgain
    // -----------------------------------------------------------
    //
    // The end page that earns badge_all_simulations announces
    // it; opening that end page again — a reload, a visit
    // from the history — does not announce it a second time.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testHeldBadgeIsNotAnnouncedAgain(): void
    {
        $publicId = $this->startSimulation($this->member, $this->scenario);
        $this->chooseSimulationOption($this->member, $publicId, $this->check);
        $this->chooseSimulationOption($this->member, $publicId, $this->report);
        $this->signInMember($this->member)->get("/en/simulation/progress/$publicId")
            ->assertSee(__('main.new_badge'), false);

        $this->signInMember($this->member)->get("/en/simulation/progress/$publicId")
            ->assertDontSee(__('main.new_badge'), false);
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
    // testRunOfAnotherLanguagesSimulationIsNotStarted
    // -----------------------------------------------------------
    //
    // A start posted for a simulation of another language than
    // the URL's is a 404, as its start page is, and files no
    // run under the wrong language.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testRunOfAnotherLanguagesSimulationIsNotStarted(): void
    {
        $lt = $this->world->language('lt');
        $lithuanian = $this->world->scenario($lt, [], [$this->world->scenarioCategory($lt)]);

        $this->signInMember($this->member)->post("/en/simulation_progress/$lithuanian", ['simulation_type' => 1])->assertNotFound();

        $this->assertFalse(DB::table('bei_users_scenarios')->where('user_id', $this->member->id)->exists());
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
    // testChoiceStoredTwiceScoresOnce
    // -----------------------------------------------------------
    //
    // Two posts of one choice at the same moment once both
    // scored. The save now locks the run while it checks and
    // records; should a run hold such a pair from before, its
    // score counts one choice a level: 200 and 100, the best
    // path's 300.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testChoiceStoredTwiceScoresOnce(): void
    {
        $publicId = $this->startSimulation($this->member, $this->scenario);
        $this->chooseSimulationOption($this->member, $publicId, $this->check);
        $row = (array) DB::table('bei_users_scenarios_options')->where('user_scenario_public_id', $publicId)->first();
        unset($row['id']);
        DB::table('bei_users_scenarios_options')->insert($row);
        $this->chooseSimulationOption($this->member, $publicId, $this->report);

        $this->signInMember($this->member)->get("/en/simulation/progress/$publicId");

        $this->assertEquals(300, DB::table('bei_users_scenarios')->where('public_id', $publicId)->value('points'));
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






    // -----------------------------------------------------------
    // testMailOutageStillShowsTheEndPage
    // -----------------------------------------------------------
    //
    // A mail relay that cannot be reached when a run ends is
    // logged, and the member still gets the end page of the
    // closed run — it once answered with a 500.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testMailOutageStillShowsTheEndPage(): void
    {
        $publicId = $this->startSimulation($this->member, $this->scenario);
        $this->chooseSimulationOption($this->member, $publicId, $this->check);
        $this->chooseSimulationOption($this->member, $publicId, $this->report);
        Mail::shouldReceive('to')->andThrow(new TransportException('Connection to the mail relay refused'));

        $this->signInMember($this->member)->get("/en/simulation/progress/$publicId")->assertOk();

        $this->assertEquals(1, DB::table('bei_users_scenarios')->where('public_id', $publicId)->value('finished'));
    }






    // -----------------------------------------------------------
    // testSimulationWithoutStepsEnds
    // -----------------------------------------------------------
    //
    // A simulation without steps — the import lets one
    // through — offers no choice, so its run is over at once,
    // closed by its first visit. The run of one once stayed
    // open for good behind a Next button that posts nothing: a
    // run closed only after a choice.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testSimulationWithoutStepsEnds(): void
    {
        $empty = $this->world->scenario($this->en, [], [$this->category]);
        $publicId = $this->startSimulation($this->member, $empty);
        $this->signInMember($this->member)->post("/en/simulation/progress/$publicId", []);

        $this->signInMember($this->member)->get("/en/simulation/progress/$publicId")->assertOk();

        $this->assertEquals(1, DB::table('bei_users_scenarios')->where('public_id', $publicId)->value('finished'), 'the run of a simulation without steps is still open');
    }






    // -----------------------------------------------------------
    // testEndPageAwardsTheRunsLanguage
    // -----------------------------------------------------------
    //
    // A run keeps the language it was started in, and so does
    // its badge: an English run's end page opened under /lt/
    // earns the English badge_all_simulations and no
    // Lithuanian one. The URL's language once took it —
    // Lithuanian, without simulations, counting as all done.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testEndPageAwardsTheRunsLanguage(): void
    {
        $this->world->language('lt');
        $publicId = $this->startSimulation($this->member, $this->scenario);
        $this->chooseSimulationOption($this->member, $publicId, $this->check);
        $this->chooseSimulationOption($this->member, $publicId, $this->report);

        $this->signInMember($this->member)->get("/lt/simulation/progress/$publicId")->assertOk();

        $this->assertEquals([$this->en], DB::table('bei_users_badges')
            ->where(['user_id' => $this->member->id, 'name' => 'badge_all_simulations'])
            ->pluck('lang_id')->map(function ($id) {
                return (int) $id;
            })->all());
    }






    // -----------------------------------------------------------
    // testUnknownLanguageEndPageIsNoServerError
    // -----------------------------------------------------------
    //
    // Under a language code no language has, the end page
    // closes the run and awards the badge of the run's own
    // language. It once stored the badge with no language,
    // which the NOT NULL column refused: a 500, after the run
    // had closed, and the badge lost.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testUnknownLanguageEndPageIsNoServerError(): void
    {
        $publicId = $this->startSimulation($this->member, $this->scenario);
        $this->chooseSimulationOption($this->member, $publicId, $this->check);
        $this->chooseSimulationOption($this->member, $publicId, $this->report);

        $this->signInMember($this->member)->get("/xx/simulation/progress/$publicId")->assertOk();

        $this->assertTrue(DB::table('bei_users_badges')
            ->where(['user_id' => $this->member->id, 'lang_id' => $this->en, 'name' => 'badge_all_simulations'])->exists());
    }






    // -----------------------------------------------------------
    // testBadgeCountsTheSimulationsMembersAreOffered
    // -----------------------------------------------------------
    //
    // badge_all_simulations waits only for the simulations a
    // category of the language lists, as the category pages
    // do: one filed under no category, and one filed under
    // another language's category — the two ways production's
    // Latvian lost six of its 55 — are on no member's page,
    // and finishing every simulation on offer earns the badge.
    // Those two once held it, and badge_finished_course with
    // it, out of every member's reach.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testBadgeCountsTheSimulationsMembersAreOffered(): void
    {
        $lithuanianCategory = $this->world->scenarioCategory($this->world->language('lt'));
        $this->world->scenario($this->en, ['descr' => 'Filed under no category']);
        $this->world->scenario($this->en, ['descr' => 'Filed under a Lithuanian category'], [$lithuanianCategory]);
        $this->signInMember($this->member)->get("/en/simulations/category/{$this->category}")->assertOk()
            ->assertDontSee('Filed under no category')
            ->assertDontSee('Filed under a Lithuanian category');
        $publicId = $this->startSimulation($this->member, $this->scenario);
        $this->chooseSimulationOption($this->member, $publicId, $this->check);
        $this->chooseSimulationOption($this->member, $publicId, $this->report);

        $this->signInMember($this->member)->get("/en/simulation/progress/$publicId")->assertOk();

        $this->assertTrue(DB::table('bei_users_badges')
            ->where(['user_id' => $this->member->id, 'lang_id' => $this->en, 'name' => 'badge_all_simulations'])->exists(), 'finishing every simulation on offer earned no badge_all_simulations');
    }






    // -----------------------------------------------------------
    // testModeOutlivesTheSession
    // -----------------------------------------------------------
    //
    // A run keeps the mode it was started in on its own row
    // (simulation_type), so a session that ends halfway — 120
    // idle minutes, any restart of the container, whose
    // sessions live on a tmpfs — leaves a run that still
    // shows each choice's feedback at the next step in mode 1
    // and lists it all at the end in mode 2. Kept in the
    // session alone, the mode once went with it, and the run
    // resumed showed no feedback at all
    // (simulation-mode-session-only).
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testModeOutlivesTheSession(): void
    {
        $stepByStep = $this->startSimulation($this->member, $this->scenario, 1);
        $this->flushSession();
        $this->chooseSimulationOption($this->member, $stepByStep, $this->check);

        $this->signInMember($this->member)->get("/en/simulation/progress/$stepByStep")
            ->assertSee('Good: the domain is wrong');

        $summary = $this->startSimulation($this->member, $this->scenario, 2);
        $this->chooseSimulationOption($this->member, $summary, $this->check);
        $this->flushSession();
        $this->chooseSimulationOption($this->member, $summary, $this->report);

        $this->signInMember($this->member)->get("/en/simulation/progress/$summary")
            ->assertSeeInOrder(['Check the sender', 'Good: the domain is wrong', 'Report it to IT', 'Right: IT can block the sender']);
    }






    // -----------------------------------------------------------
    // testRunWithoutAKeptModeFallsBackOnTheSession
    // -----------------------------------------------------------
    //
    // A run started before runs kept their mode — its
    // simulation_type empty — takes the mode its session
    // still holds under simulation_type_ and the public id,
    // where every run kept it until then: here 2, the
    // feedback listed at the end, over the row's emptied 1.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testRunWithoutAKeptModeFallsBackOnTheSession(): void
    {
        $publicId = $this->startSimulation($this->member, $this->scenario, 1);
        DB::table('bei_users_scenarios')->where('public_id', $publicId)->update(['simulation_type' => null]);
        $this->withSession(['simulation_type_'.$publicId => 2]);

        $this->chooseSimulationOption($this->member, $publicId, $this->check);
        $this->chooseSimulationOption($this->member, $publicId, $this->report);

        $this->signInMember($this->member)->get("/en/simulation/progress/$publicId")
            ->assertSeeInOrder(['Check the sender', 'Good: the domain is wrong', 'Report it to IT', 'Right: IT can block the sender']);
    }






    // -----------------------------------------------------------
    // testChoicePostedAsTrueIsNotRecorded
    // -----------------------------------------------------------
    //
    // A choice posted as JSON true records nothing and leaves
    // the run on its first step, as any id the step does not
    // offer does, while a JSON number the step offers is
    // recorded: save_choice takes a whole number only and
    // matches it strictly against the step's ids. The loose
    // check once let true equal every id of the step, so the
    // run recorded option 1 — here none of the simulation's
    // own — and closed at the next visit, scored on a choice
    // it never offered (simulation-choice-json-true).
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testChoicePostedAsTrueIsNotRecorded(): void
    {
        $quiz = $this->world->scenario($this->en, ['descr' => 'A parcel notice arrives'], [$this->category]);
        $ignore = $this->world->scenarioOption($quiz, 0, 1, 50, ['situation' => 'Ignore the notice']);
        $track = $this->world->scenarioOption($quiz, 0, 1, 0, ['situation' => 'Track the parcel']);
        $publicId = $this->startSimulation($this->member, $quiz);

        $this->signInMember($this->member)->postJson("/en/simulation/progress/$publicId", ['a' => true]);

        $recorded = DB::table('bei_users_scenarios_options')->where('user_scenario_public_id', $publicId)->pluck('option_id')->map(function ($id) {
            return (int) $id;
        })->all();
        $page = $this->signInMember($this->member)->get("/en/simulation/progress/$publicId");
        $finished = (int) DB::table('bei_users_scenarios')->where('public_id', $publicId)->value('finished');

        $this->assertNotContains(1, [$ignore, $track]);
        $this->assertSame([], $recorded, 'a choice posted as true was recorded as option ' . implode(', ', $recorded));
        $this->assertSame(0, $finished, 'the run closed after a choice posted as true');
        $page->assertSee('Ignore the notice')->assertSee('Track the parcel');

        // A choice the step offers, posted the same way as a
        // number, is recorded
        $this->signInMember($this->member)->postJson("/en/simulation/progress/$publicId", ['a' => $ignore]);

        $this->assertEquals([$ignore], DB::table('bei_users_scenarios_options')->where('user_scenario_public_id', $publicId)->pluck('option_id')->all(), 'a choice posted as a JSON number the step offers was not recorded');
    }






    // -----------------------------------------------------------
    // testEndPageShowsTheProgressItsRunCompletes
    // -----------------------------------------------------------
    //
    // The end page of a run shows the course progress with its
    // run counted: the only simulation of the language
    // finished, the page that says so shows the member's 25
    // per cent, as the next page does. BaseController once
    // worked the progress out before the action closed the
    // run, so the end page showed 0 per cent and only the next
    // page 25 (progress-shown-before-the-close).
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testEndPageShowsTheProgressItsRunCompletes(): void
    {
        $publicId = $this->startSimulation($this->member, $this->scenario);
        $this->chooseSimulationOption($this->member, $publicId, $this->check);
        $this->chooseSimulationOption($this->member, $publicId, $this->report);

        $endPage = $this->signInMember($this->member)->get("/en/simulation/progress/$publicId");
        $nextPage = $this->signInMember($this->member)->get("/en/simulation/progress/$publicId");

        $endPage->assertOk()->assertSee(__('main.simulation_finished'));
        $nextPage->assertSee('style="width: 25%"', false);
        preg_match('/style="width: ([0-9.]+)%"/', $endPage->getContent(), $shown);
        $this->assertSame('25', $shown[1] ?? null, 'the end page shows the course progress from before its run counted: ' . ($shown[1] ?? 'none') . ' per cent');
    }






    // -----------------------------------------------------------
    // testSimulationsListShowsTheLatestEnd
    // -----------------------------------------------------------
    //
    // A category's list of simulations takes each one's Last
    // ended line from the member's finished run that ended
    // last: a run begun three days ago and finished an hour
    // ago, resumed from the history, outlasts one begun and
    // finished two days ago. The list once took the finished
    // run with the highest id, the one started last, and said
    // the simulation last ended two days ago
    // (simulations-list-last-ended).
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testSimulationsListShowsTheLatestEnd(): void
    {
        $resumed = $this->world->scenarioAttempt($this->member->id, $this->scenario, $this->en, [
            'started' => gmdate('Y-m-d H:i:s', time() - 3 * 86400),
            'ended'   => gmdate('Y-m-d H:i:s', time() - 3600),
        ]);
        $this->world->scenarioAttempt($this->member->id, $this->scenario, $this->en, [
            'started' => gmdate('Y-m-d H:i:s', time() - 2 * 86400),
            'ended'   => gmdate('Y-m-d H:i:s', time() - 2 * 86400 + 600),
        ]);
        $lastEnded = SettingsController::local_time(DB::table('bei_users_scenarios')->where('id', $resumed)->value('ended'));

        $page = $this->signInMember($this->member)->get("/en/simulations/credential-theft/{$this->category}")->assertOk();

        preg_match('/' . preg_quote(__('main.last_ended'), '/') . ': ([0-9: -]+?) </', $page->getContent(), $shown);
        $this->assertSame($lastEnded, $shown[1] ?? null, 'the simulation is said to have last ended at ' . ($shown[1] ?? 'no time') . ', not at ' . $lastEnded);
    }
}
