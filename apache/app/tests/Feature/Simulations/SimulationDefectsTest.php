<?php

// -----------------------------------------------------------
//  [*] SimulationDefectsTest — known simulation defects
//
//  Each test states the behaviour the simulations should
//  have and pins the defect that breaks it today
//  (KnownDefects): the run reports them as incomplete,
//  tagged with their ids, until a fix makes one pass — then
//  the test fails as the cue to remove its pin and move it
//  into its home file.
//
//    simulation-choice-json-true — a choice posted as JSON
//      true records option 1
//    progress-shown-before-the-close — the end page of a
//      run shows the progress from before it counted
//      (KnowledgeDefectsTest too)
//    simulations-list-last-ended — Last ended shows the run
//      started last, not the latest end
//
//  Used by:
//    - PHPUnit — the Feature suite (runTests.sh)
// -----------------------------------------------------------


namespace Tests\Feature\Simulations;

use App\Http\Controllers\SettingsController;
use Illuminate\Support\Facades\DB;
use Tests\FeatureTestCase;
use Tests\Support\Flows;








// -----------------------------------------------------------
// SimulationDefectsTest
// -----------------------------------------------------------
//
// One pinned defect per test, named in the header; the
// simulation SimulationsTest uses, and a member, for every
// test.
//
// Used by:
//   - PHPUnit — the Feature suite (runTests.sh)
// -----------------------------------------------------------

class SimulationDefectsTest extends FeatureTestCase
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
    // testChoicePostedAsTrueIsNotRecorded
    // -----------------------------------------------------------
    //
    // save_choice takes only a choice the current step offers,
    // checked with a loose in_array, and a JSON post hands the
    // a field over with its JSON type. true equals every id of
    // the step, so it passes the check; the lookups and the
    // insert then bind it as 1, and the run records option 1,
    // whichever simulation that belongs to, or none, with its
    // type and points. Here the simulation's own choices came
    // later than option 1, so it is none of them, and with
    // nothing under it in this simulation the next visit finds
    // no step left and closes the run as finished, scored on a
    // choice the simulation never offered. Wanted: true
    // records nothing and the run stays on its first step, as
    // for any id the step does not offer.
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
        $this->pinDefect('simulation-choice-json-true', function () use ($recorded, $page, $finished) {
            $this->assertSame([], $recorded, 'a choice posted as true was recorded as option ' . implode(', ', $recorded));
            $this->assertSame(0, $finished, 'the run closed after a choice posted as true');
            $page->assertSee('Ignore the notice')->assertSee('Track the parcel');
        });
    }






    // -----------------------------------------------------------
    // testEndPageShowsTheProgressItsRunCompletes
    // -----------------------------------------------------------
    //
    // BaseController works out the course progress before the
    // action runs, and the end page closes the run inside the
    // action, so the progress bar of parts/notif on that page
    // still shows the figure from before the run was counted.
    // The only simulation of the language finished, the
    // member's course stands at 25 per cent, but the page that
    // says "Simulation finished!" shows 0 per cent, and only
    // the next page shows 25. Wanted: the end page shows the
    // progress with its run counted.
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
        $this->pinDefect('progress-shown-before-the-close', function () use ($endPage) {
            preg_match('/style="width: ([0-9.]+)%"/', $endPage->getContent(), $shown);
            $this->assertSame('25', $shown[1] ?? null, 'the end page shows the course progress from before its run counted: ' . ($shown[1] ?? 'none') . ' per cent');
        });
    }






    // -----------------------------------------------------------
    // testSimulationsListShowsTheLatestEnd
    // -----------------------------------------------------------
    //
    // simulations_list takes the member's finished run with
    // the highest id, the one started last, for its "Last
    // ended" line. A run left open and finished later from the
    // history ends after a run started after it: begun three
    // days ago and finished an hour ago, beside one begun and
    // finished two days ago, the list says the simulation last
    // ended two days ago. Wanted: the latest end among the
    // finished runs.
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

        $this->pinDefect('simulations-list-last-ended', function () use ($page, $lastEnded) {
            preg_match('/' . preg_quote(__('main.last_ended'), '/') . ': ([0-9: -]+?) </', $page->getContent(), $shown);
            $this->assertSame($lastEnded, $shown[1] ?? null, 'the simulation is said to have last ended at ' . ($shown[1] ?? 'no time') . ', not at ' . $lastEnded);
        });
    }
}
