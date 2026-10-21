<?php

// -----------------------------------------------------------
//  [*] ProductionModeTest — what a visitor sees of a failure
//
//  Apache runs the app the way production does: no .env,
//  APP_DEBUG off, APP_ENV unset and so "production". A
//  server error must then show Laravel's generic error page
//  — no file paths, no stack trace, no Ignition, no secret
//  from the environment — and land in storage/logs, the
//  log the operators read. The error is a real one: a
//  member whose country has no questionnaire link breaks
//  every page of theirs (pinned in the Feature suite as
//  country-out-of-range).
//
//  Used by:
//    - PHPUnit — the Apache suite (runTests.sh)
// -----------------------------------------------------------


namespace Tests\Apache;

use Psr\Http\Message\ResponseInterface;
use Tests\ApacheTestCase;








// -----------------------------------------------------------
// ProductionModeTest
// -----------------------------------------------------------
//
// Each test signs a member of country 9 in over HTTP and
// opens the dashboard.
//
// Used by:
//   - PHPUnit — the Apache suite (runTests.sh)
// -----------------------------------------------------------

class ProductionModeTest extends ApacheTestCase
{

    // The log Laravel writes in production — a tmpfs in the
    // test container, as the bind mount is in production
    private const LOG = '/var/www/html/storage/logs/laravel.log';






    // -----------------------------------------------------------
    // testServerErrorShowsTheGenericPage
    // -----------------------------------------------------------
    //
    // The 500 page is Laravel's plain one, and nothing in it
    // tells a visitor how the server is built or what its
    // secrets are.
    //
    // Used by:
    //   - PHPUnit — the Apache suite (runTests.sh)
    // -----------------------------------------------------------

    public function testServerErrorShowsTheGenericPage(): void
    {
        $response = $this->openBrokenDashboard();
        $body = (string) $response->getBody();

        $this->assertSame(500, $response->getStatusCode());
        $this->assertStringContainsString('Server Error', $body);
        $leaks = [
            '/var/www/html',
            'Undefined offset',
            'Stack trace',
            'Whoops',
            'ignition',
            'vendor/laravel',
            getenv('APP_KEY'),
            getenv('DB_PASSWORD'),
        ];
        foreach ($leaks as $leak) {
            $this->assertStringNotContainsStringIgnoringCase($leak, $body);
        }
    }






    // -----------------------------------------------------------
    // testServerErrorIsLogged
    // -----------------------------------------------------------
    //
    // The error the visitor was spared is written to
    // laravel.log — the part of the log written during this
    // request names it.
    //
    // Used by:
    //   - PHPUnit — the Apache suite (runTests.sh)
    // -----------------------------------------------------------

    public function testServerErrorIsLogged(): void
    {
        clearstatcache();
        $offset = is_file(self::LOG) ? filesize(self::LOG) : 0;

        $this->openBrokenDashboard();

        clearstatcache();
        $written = (string) file_get_contents(self::LOG, false, null, $offset);
        $this->assertRegExp('/production\.ERROR: Undefined offset: 9/', $written);
    }






    // -----------------------------------------------------------
    // openBrokenDashboard
    // -----------------------------------------------------------
    //
    // Signs a member of country 9 in over HTTP and opens their
    // dashboard; the response is the server error.
    //
    // Used by:
    //   - the tests of this class
    // -----------------------------------------------------------

    private function openBrokenDashboard(): ResponseInterface
    {
        $member = $this->world->member(['country' => 9]);
        $this->signInOverHttp($member);

        return $this->http->get('/cp/dashboard');
    }
}
