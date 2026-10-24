<?php

// -----------------------------------------------------------
//  [*] ApacheTestCase — the base of the tests over HTTP
//
//  TestCase with a client for the real Apache that run.sh
//  starts on port 8080. Apache runs the app in processes of
//  its own, which see only committed rows, so these tests
//  get no transaction: the rows a test builds through its
//  World are committed, and removed again after the test.
//
//  Used by:
//    - the Apache suite (Apache/)
// -----------------------------------------------------------


namespace Tests;

use App\Models\Members;
use Tests\Support\ApacheClient;
use Tests\Support\World;








// -----------------------------------------------------------
// ApacheTestCase
// -----------------------------------------------------------
//
// One client per test, so cookies — the session, the CSRF
// token — never leak from one test into the next.
//
//   setUp          — the client
//   tearDown       — the World's rows go
//   signInOverHttp — a member signed in the browser's way
//
// Used by:
//   - the Apache suite (Apache/)
// -----------------------------------------------------------

abstract class ApacheTestCase extends TestCase
{

    // The session cookie's name: APP_NAME as a slug, plus
    // _session (config/session.php)
    protected const SESSION_COOKIE = 'cyberphish_session';

    // The client of the test running now
    protected $http;






    // -----------------------------------------------------------
    // setUp
    // -----------------------------------------------------------
    //
    // TestCase's setup, then a client with an empty cookie
    // jar.
    //
    // Used by:
    //   - PHPUnit, before every test
    // -----------------------------------------------------------

    protected function setUp(): void
    {
        parent::setUp();
        $this->http = new ApacheClient();
    }






    // -----------------------------------------------------------
    // tearDown
    // -----------------------------------------------------------
    //
    // Deletes the rows the test committed through its World,
    // and the rows the app wrote for the World's members,
    // before Laravel throws the app away.
    //
    // Used by:
    //   - PHPUnit, after every test
    // -----------------------------------------------------------

    protected function tearDown(): void
    {
        $this->world->removeAll();
        parent::tearDown();
    }






    // -----------------------------------------------------------
    // signInOverHttp
    // -----------------------------------------------------------
    //
    // Signs the member in the way a browser does: opens the
    // login page for its session and CSRF token, then posts
    // the form; the client keeps the session cookie for the
    // requests that follow. The member's password is the
    // World's.
    //
    // Used by:
    //   - FrontControllerTest, ProductionModeTest, SessionTest
    //     (Apache/)
    // -----------------------------------------------------------

    protected function signInOverHttp(Members $member, array $headers = []): void
    {
        $token = $this->http->csrfToken('/login');
        $this->assertNotSame('', $token, 'the login page has no CSRF token');

        $response = $this->http->post('/login', [
            '_token'   => $token,
            'email'    => $member->email,
            'password' => World::PASSWORD,
        ], $headers);
        $this->assertSame(302, $response->getStatusCode());
        $this->assertSame('https://cyberphish.knf.vu.lt/cp/dashboard', $response->getHeaderLine('Location'));
    }
}
