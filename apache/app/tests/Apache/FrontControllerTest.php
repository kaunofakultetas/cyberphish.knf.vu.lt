<?php

// -----------------------------------------------------------
//  [*] FrontControllerTest — how Apache hands requests over
//
//  The routing half of the Apache setup: public/.htaccess
//  sends every path that is not a file or a folder to
//  index.php, Laravel answers unknown paths with its 404
//  page, a trailing slash is redirected away — on https,
//  the scheme the vhost's ServerName gives Apache — the
//  static files of public/ are served by Apache itself with
//  their types, and a folder is never listed.
//
//  Used by:
//    - PHPUnit — the Apache suite (runTests.sh)
// -----------------------------------------------------------


namespace Tests\Apache;

use Tests\ApacheTestCase;








// -----------------------------------------------------------
// FrontControllerTest
// -----------------------------------------------------------
//
// Plain requests without a session.
//
// Used by:
//   - PHPUnit — the Apache suite (runTests.sh)
// -----------------------------------------------------------

class FrontControllerTest extends ApacheTestCase
{






    // -----------------------------------------------------------
    // testHomePageIsServedByTheApp
    // -----------------------------------------------------------
    //
    // A request for the root reaches Laravel through
    // index.php and comes back as the home page.
    //
    // Used by:
    //   - PHPUnit — the Apache suite (runTests.sh)
    // -----------------------------------------------------------

    public function testHomePageIsServedByTheApp(): void
    {
        $response = $this->http->get('/');

        $this->assertSame(200, $response->getStatusCode());
        $this->assertStringContainsString('text/html', $response->getHeaderLine('Content-Type'));
        $this->assertStringContainsString('CYBERPHISH', (string) $response->getBody());
    }






    // -----------------------------------------------------------
    // testIndexPhpIsTheHomePageToo
    // -----------------------------------------------------------
    //
    // The front controller itself, asked for by name, answers
    // with the home page.
    //
    // Used by:
    //   - PHPUnit — the Apache suite (runTests.sh)
    // -----------------------------------------------------------

    public function testIndexPhpIsTheHomePageToo(): void
    {
        $response = $this->http->get('/index.php');

        $this->assertSame(200, $response->getStatusCode());
        $this->assertStringContainsString('CYBERPHISH', (string) $response->getBody());
    }






    // -----------------------------------------------------------
    // testUnknownPathGetsTheAppsNotFoundPage
    // -----------------------------------------------------------
    //
    // A path no file and no route answers reaches Laravel,
    // which renders its own 404 page.
    //
    // Used by:
    //   - PHPUnit — the Apache suite (runTests.sh)
    // -----------------------------------------------------------

    public function testUnknownPathGetsTheAppsNotFoundPage(): void
    {
        $response = $this->http->get('/no/such/page');

        $this->assertSame(404, $response->getStatusCode());
        $this->assertStringContainsString('Not Found', (string) $response->getBody());
    }






    // -----------------------------------------------------------
    // testTrailingSlashIsRedirectedAway
    // -----------------------------------------------------------
    //
    // .htaccess redirects a path ending in a slash — when it
    // is not a folder — to the same path without it,
    // permanently. Apache writes the Location itself, and
    // keeps it on https: TLS ends at the endpoint, and the
    // vhost's ServerName names the scheme visitors use.
    //
    // Used by:
    //   - PHPUnit — the Apache suite (runTests.sh)
    // -----------------------------------------------------------

    public function testTrailingSlashIsRedirectedAway(): void
    {
        $response = $this->http->get('/login/');

        $this->assertSame(301, $response->getStatusCode());
        $this->assertSame('https://cyberphish.knf.vu.lt/login', $response->getHeaderLine('Location'));
    }






    // -----------------------------------------------------------
    // testStaticFilesAreServedByApache
    // -----------------------------------------------------------
    //
    // Files in public/ are sent as they are, with their media
    // types.
    //
    // Used by:
    //   - PHPUnit — the Apache suite (runTests.sh)
    // -----------------------------------------------------------

    public function testStaticFilesAreServedByApache(): void
    {
        $robots = $this->http->get('/robots.txt');
        $this->assertSame(200, $robots->getStatusCode());
        $this->assertStringStartsWith('text/plain', $robots->getHeaderLine('Content-Type'));

        $policy = $this->http->get('/privacy_policy.pdf');
        $this->assertSame(200, $policy->getStatusCode());
        $this->assertSame('application/pdf', $policy->getHeaderLine('Content-Type'));
        $this->assertStringStartsWith('%PDF', (string) $policy->getBody());
    }






    // -----------------------------------------------------------
    // testFoldersAreNeverListed
    // -----------------------------------------------------------
    //
    // Options -Indexes: the asset folders answer 403 instead of
    // a listing of their files.
    //
    // Used by:
    //   - PHPUnit — the Apache suite (runTests.sh)
    // -----------------------------------------------------------

    public function testFoldersAreNeverListed(): void
    {
        foreach (['/assets/', '/m_assets/', '/m_assets/vendor/', '/js/', '/css/'] as $folder) {
            $response = $this->http->get($folder);
            $this->assertSame(403, $response->getStatusCode(), $folder);
            $this->assertStringNotContainsString('Index of', (string) $response->getBody(), $folder);
        }
    }
}
