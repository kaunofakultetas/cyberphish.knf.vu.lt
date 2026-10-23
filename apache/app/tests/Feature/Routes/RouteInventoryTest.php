<?php

// -----------------------------------------------------------
//  [*] RouteInventoryTest — every route open or guarded
//
//  Reads the live route table and holds each route to one
//  of two rules: it is on the list of routes open to
//  visitors by design (the public pages, signing up, in and
//  out, the reset and verification links), or a guard sits
//  in front of it — the mem guard for member pages, the man
//  guard for the panel. A route added without a decision
//  fails here. Every member route also sends a visitor to
//  the login form; AdminAccessTest does the same for the
//  panel.
//
//  Used by:
//    - PHPUnit — the Feature suite (runTests.sh)
// -----------------------------------------------------------


namespace Tests\Feature\Routes;

use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Route as Router;
use Tests\FeatureTestCase;








// -----------------------------------------------------------
// RouteInventoryTest
// -----------------------------------------------------------
//
// Routes are named "METHOD uri" here, HEAD left out — Laravel
// adds it to every GET. A route's middleware list also holds
// the closures the controllers register in their
// constructors; only the named middleware are compared.
//
// Used by:
//   - PHPUnit — the Feature suite (runTests.sh)
// -----------------------------------------------------------

class RouteInventoryTest extends FeatureTestCase
{

    // The routes open to visitors by design
    private const OPEN = [
        'GET /',
        'GET login',
        'POST login',
        'GET register',
        'POST register',
        'GET forgot-password',
        'POST forgot-password',
        'POST cp/logout',
        'GET verify/{verify_token}',
        'GET reset/{reset_token}',
        'POST reset/{reset_token}',
        'GET lang/{lang}',
        'GET {lang}/news',
        'GET {lang}/news/{slug}/{uid}',
        'GET {lang}/page/{slug}/{uid}',
        'GET {lang}/learn/{slug}/{uid}',
        'GET {lang}/ranks/self-evaluation',
        'GET {lang}/ranks/simulations',
        'GET admin-panel',
        'POST admin-panel',
        'POST admin-panel/logout',
    ];

    // The middleware that guards a route
    private const GUARDS = ['auth:mem', 'auth.admin:man', 'auth:man'];

    // A sample value for every route parameter of the member
    // routes
    private const SAMPLES = [
        'lang'      => 'en',
        'uid'       => '1',
        'cat_id'    => '1',
        'name'      => 'x',
        'public_id' => 'x',
    ];






    // -----------------------------------------------------------
    // testEveryRouteIsOpenByDesignOrGuarded
    // -----------------------------------------------------------
    //
    // No route outside OPEN goes without a guard, and every
    // route in OPEN still exists.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testEveryRouteIsOpenByDesignOrGuarded(): void
    {
        $unguarded = [];
        $seen = [];
        foreach ($this->routes() as list($name, $route)) {
            $seen[] = $name;
            $named = array_filter($route->gatherMiddleware(), 'is_string');
            $guarded = (bool) array_intersect(self::GUARDS, $named);
            if (!$guarded && !in_array($name, self::OPEN, true)) {
                $unguarded[] = $name;
            }
        }

        $this->assertSame([], $unguarded, 'routes with no guard that are not open by design');
        $this->assertSame([], array_values(array_diff(self::OPEN, $seen)), 'open routes that no longer exist');
    }






    // -----------------------------------------------------------
    // testMemberRoutesSendVisitorsToTheLogin
    // -----------------------------------------------------------
    //
    // A visitor gets a redirect to the login form from every
    // route behind the mem guard, whatever its method.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testMemberRoutesSendVisitorsToTheLogin(): void
    {
        $offenders = [];
        $checked = 0;
        foreach ($this->routes() as list($name, $route)) {
            if (!in_array('auth:mem', $route->gatherMiddleware(), true)) {
                continue;
            }
            $checked++;
            $method = strtok($name, ' ');
            $response = $this->call($method, $this->sampleUri($route));
            if (!$response->isRedirect(route('login'))) {
                $offenders[] = "$name answered " . $response->getStatusCode();
            }
            $this->flushSession();
        }

        $this->assertGreaterThan(20, $checked, 'the sweep found too few member routes');
        $this->assertSame([], $offenders);
    }






    // -----------------------------------------------------------
    // routes
    // -----------------------------------------------------------
    //
    // Every route as its "METHOD uri" name and the route.
    //
    // Used by:
    //   - the tests of this class
    // -----------------------------------------------------------

    private function routes(): array
    {
        $routes = [];
        foreach (Router::getRoutes() as $route) {
            foreach (array_diff($route->methods(), ['HEAD']) as $method) {
                $routes[] = ["$method {$route->uri()}", $route];
            }
        }
        return $routes;
    }






    // -----------------------------------------------------------
    // sampleUri
    // -----------------------------------------------------------
    //
    // The route's URI with every parameter filled from SAMPLES.
    //
    // Used by:
    //   - testMemberRoutesSendVisitorsToTheLogin (above)
    // -----------------------------------------------------------

    private function sampleUri(Route $route): string
    {
        return '/' . preg_replace_callback('/\{(\w+)\??\}/', function (array $match) {
            return self::SAMPLES[$match[1]];
        }, $route->uri());
    }
}
