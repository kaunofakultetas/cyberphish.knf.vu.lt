<?php

// -----------------------------------------------------------
//  [*] AdminAccessTest — who may open which admin page
//
//  Every admin-panel route but the login and the logout
//  sits behind the man guard, and every one carrying the
//  Perms middleware is for main admins only — a country
//  manager gets a 404 there and keeps the dashboard and
//  the reports of its own country's members, another
//  country's member being a 404 too. The sweeps read the
//  routes from the live route table, so a route added
//  tomorrow is checked the same way — the panel's news
//  pages among them, which the public news routes no longer
//  answer first.
//
//  Used by:
//    - PHPUnit — the Feature suite (runTests.sh)
// -----------------------------------------------------------


namespace Tests\Feature\Admin;

use App\Models\Members;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route as Router;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\FeatureTestCase;








// -----------------------------------------------------------
// AdminAccessTest
// -----------------------------------------------------------
//
// The route sweeps fill every route parameter with a sample
// value; the guards and Perms answer before any controller
// reads them.
//
// Used by:
//   - PHPUnit — the Feature suite (runTests.sh)
// -----------------------------------------------------------

class AdminAccessTest extends FeatureTestCase
{

    // The admin routes open to visitors: signing in and out
    private const OPEN = ['GET admin-panel', 'POST admin-panel', 'GET admin-panel/logout'];

    // A sample value for every route parameter name the admin
    // routes use
    private const SAMPLES = [
        'uid'           => '1',
        'user_id'       => '1',
        'status_id'     => '1',
        'lang_id'       => '3',
        'simulation_id' => '1',
        'type'          => 'new',
        'public_id'     => 'x',
    ];






    // -----------------------------------------------------------
    // testEveryAdminRouteSendsVisitorsToTheAdminLogin
    // -----------------------------------------------------------
    //
    // A visitor gets a redirect to the admin login from every
    // admin route outside OPEN, whatever its method.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testEveryAdminRouteSendsVisitorsToTheAdminLogin(): void
    {
        $offenders = [];
        foreach ($this->adminRoutes() as list($method, $route)) {
            $response = $this->call($method, $this->sampleUri($route));
            if (!$response->isRedirect(route('admin_login'))) {
                $offenders[] = "$method {$route->uri()} answered " . $response->getStatusCode();
            }
            $this->flushSession();
        }

        $this->assertSame([], $offenders);
    }






    // -----------------------------------------------------------
    // testCountryManagerGetsNotFoundOnMainAdminPages
    // -----------------------------------------------------------
    //
    // Every route carrying the Perms middleware is a 404 for a
    // country manager.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testCountryManagerGetsNotFoundOnMainAdminPages(): void
    {
        $manager = $this->world->manager(['main' => 0, 'country' => 2]);
        $offenders = [];
        $checked = 0;
        foreach ($this->adminRoutes() as list($method, $route)) {
            if (!in_array('perms', $route->gatherMiddleware(), true)) {
                continue;
            }
            $checked++;
            $status = $this->signInManager($manager)->call($method, $this->sampleUri($route))->getStatusCode();
            if ($status !== 404) {
                $offenders[] = "$method {$route->uri()} answered $status";
            }
        }

        $this->assertGreaterThan(50, $checked, 'the sweep found too few Perms routes');
        $this->assertSame([], $offenders);
    }






    // -----------------------------------------------------------
    // testCountryManagerSeesItsCountryOnTheDashboard
    // -----------------------------------------------------------
    //
    // The dashboard lists the members of the manager's
    // country and nobody else.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testCountryManagerSeesItsCountryOnTheDashboard(): void
    {
        $manager = $this->world->manager(['main' => 0, 'country' => 2]);
        $ours = $this->world->member(['country' => 2]);
        $theirs = $this->world->member(['country' => 3]);

        $this->signInManager($manager)->get('/admin-panel/dashboard')
            ->assertOk()
            ->assertSee($ours->email)
            ->assertDontSee($theirs->email);
    }






    // -----------------------------------------------------------
    // testCountryManagerReadsOnlyItsCountrysReports
    // -----------------------------------------------------------
    //
    // The four member reports — the self-evaluation and the
    // simulation history, one run, one knowledge test — open
    // for a member of the manager's country and are a 404 for
    // a member of another, whatever id the URL carries.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testCountryManagerReadsOnlyItsCountrysReports(): void
    {
        $manager = $this->world->manager(['main' => 0, 'country' => 2]);

        foreach ([2 => 200, 1 => 404] as $country => $status) {
            foreach ($this->reportsOf($this->world->member(['country' => $country])) as $report) {
                $this->signInManager($manager)->get($report)->assertStatus($status);
            }
        }
    }






    // -----------------------------------------------------------
    // testMainAdminReadsEveryCountrysReports
    // -----------------------------------------------------------
    //
    // A main admin opens the reports of a member of any
    // country, its own country (0) aside.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testMainAdminReadsEveryCountrysReports(): void
    {
        $admin = $this->world->manager();

        foreach ($this->reportsOf($this->world->member(['country' => 1])) as $report) {
            $this->signInManager($admin)->get($report)->assertOk();
        }
    }






    // -----------------------------------------------------------
    // testUnknownKnowledgeReportIsNotFound
    // -----------------------------------------------------------
    //
    // The knowledge report of a public id no attempt has is a
    // 404, even for a main admin.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testUnknownKnowledgeReportIsNotFound(): void
    {
        $this->signInManager($this->world->manager())->get('/admin-panel/user/knowledge_results/no-such-attempt')->assertNotFound();
    }






    // -----------------------------------------------------------
    // testMainAdminOpensThePanelPages
    // -----------------------------------------------------------
    //
    // Each page of the panel's menu opens for a main admin.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    #[DataProvider('panelPages')]
    public function testMainAdminOpensThePanelPages(string $page): void
    {
        $this->signInManager($this->world->manager())->get($page)->assertOk();
    }






    // -----------------------------------------------------------
    // panelPages
    // -----------------------------------------------------------
    //
    // The pages of the panel's menu and its new-item forms.
    //
    // Used by:
    //   - testMainAdminOpensThePanelPages (above)
    // -----------------------------------------------------------

    public static function panelPages(): array
    {
        $pages = [
            '/admin-panel/dashboard',
            '/admin-panel/users',
            '/admin-panel/news',
            '/admin-panel/news/new',
            '/admin-panel/information',
            '/admin-panel/information/new',
            '/admin-panel/lm/categories',
            '/admin-panel/lm/content',
            '/admin-panel/admin_simulations',
            '/admin-panel/admin_simulations/categories',
            '/admin-panel/admin_simulations/attributes',
            '/admin-panel/settings/languages',
            '/admin-panel/settings/managers',
            '/admin-panel/settings/other',
            '/admin-panel/settings/simulations_download_pdf',
            '/admin-panel/dialog/lang/new',
            '/admin-panel/dialog/manager/new',
            '/admin-panel/change-pass',
        ];
        return array_combine($pages, array_map(function (string $page) {
            return [$page];
        }, $pages));
    }






    // -----------------------------------------------------------
    // reportsOf
    // -----------------------------------------------------------
    //
    // The paths of the member's four reports in English, after
    // giving the member a finished simulation run and a
    // finished knowledge test for the two that show one.
    //
    // Used by:
    //   - testCountryManagerReadsOnlyItsCountrysReports,
    //     testMainAdminReadsEveryCountrysReports (above)
    // -----------------------------------------------------------

    private function reportsOf(Members $member): array
    {
        $en = $this->world->language('en');
        $run = $this->world->scenarioAttempt($member->id, $this->world->scenario($en), $en);
        $attempt = $this->world->knowledgeAttempt($member->id, $en);

        return [
            "/admin-panel/user/self_evaluation_history/$en/{$member->id}",
            "/admin-panel/user/simulations_history/$en/{$member->id}",
            '/admin-panel/user/simulation/report/' . DB::table('bei_users_scenarios')->where('id', $run)->value('public_id'),
            '/admin-panel/user/knowledge_results/' . DB::table('bei_users_knowledge_test')->where('id', $attempt)->value('public_id'),
        ];
    }






    // -----------------------------------------------------------
    // adminRoutes
    // -----------------------------------------------------------
    //
    // Every admin route outside OPEN, as method and route
    // pairs — HEAD left out, Laravel adds it to every GET.
    //
    // Used by:
    //   - the route sweeps of this class
    // -----------------------------------------------------------

    private function adminRoutes(): array
    {
        $routes = [];
        foreach (Router::getRoutes() as $route) {
            if (strpos($route->uri(), 'admin-panel') !== 0) {
                continue;
            }
            foreach (array_diff($route->methods(), ['HEAD']) as $method) {
                $name = "$method {$route->uri()}";
                if (!in_array($name, self::OPEN, true)) {
                    $routes[] = [$method, $route];
                }
            }
        }
        $this->assertNotEmpty($routes);
        return $routes;
    }






    // -----------------------------------------------------------
    // sampleUri
    // -----------------------------------------------------------
    //
    // The route's URI with every parameter filled from SAMPLES.
    //
    // Used by:
    //   - the route sweeps of this class
    // -----------------------------------------------------------

    private function sampleUri(Route $route): string
    {
        return '/' . preg_replace_callback('/\{(\w+)\??\}/', function (array $match) {
            return self::SAMPLES[$match[1]];
        }, $route->uri());
    }
}
