<?php

// -----------------------------------------------------------
//  [*] AdminAccessTest — who may open which admin page
//
//  Every admin-panel route but the login and the logout
//  sits behind the man guard, and every one carrying the
//  Perms middleware is for main admins only — a country
//  manager gets a 404 there and keeps the dashboard and
//  the member reports. These tests read the routes from the
//  live route table, so a route added tomorrow is checked
//  the same way. Country managers reading other countries'
//  members, and the panel's news pages the public news
//  routes answer instead, are pinned in AdminDefectsTest.
//
//  Used by:
//    - PHPUnit — the Feature suite (runTests.sh)
// -----------------------------------------------------------


namespace Tests\Feature\Admin;

use Illuminate\Routing\Route;
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

    // The panel's news pages that the public routes {lang}/news
    // and {lang}/news/{slug}/{uid} answer first — the
    // admin-news-shadowed defect of AdminDefectsTest; the
    // sweeps leave them to that test
    private const SHADOWED = [
        'GET admin-panel/news',
        'GET admin-panel/news/edit/{uid?}',
        'GET admin-panel/news/delete/{uid}',
        'GET admin-panel/news/delete_picture/{uid}',
    ];

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
    // adminRoutes
    // -----------------------------------------------------------
    //
    // Every admin route outside OPEN and SHADOWED, as method
    // and route pairs — HEAD left out, Laravel adds it to
    // every GET.
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
                if (!in_array($name, self::OPEN, true) && !in_array($name, self::SHADOWED, true)) {
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
