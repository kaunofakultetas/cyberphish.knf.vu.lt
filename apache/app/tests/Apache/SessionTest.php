<?php

// -----------------------------------------------------------
//  [*] SessionTest — signing in the way a browser does
//
//  The session over HTTP: a visitor is sent to the login
//  page, a form posted without its CSRF token is refused,
//  a member signs in with the login form and keeps the
//  session in a cookie scripts cannot read, the session id
//  changes at sign-in so a planted id is worthless, and
//  signing out ends the session. The cookie's missing
//  Secure flag is pinned in ApacheDefectsTest.
//
//  Used by:
//    - PHPUnit — the Apache suite (runTests.sh)
// -----------------------------------------------------------


namespace Tests\Apache;

use Illuminate\Support\Facades\Crypt;
use Tests\ApacheTestCase;
use Tests\Support\World;








// -----------------------------------------------------------
// SessionTest
// -----------------------------------------------------------
//
// Every test starts with an empty cookie jar.
//
// Used by:
//   - PHPUnit — the Apache suite (runTests.sh)
// -----------------------------------------------------------

class SessionTest extends ApacheTestCase
{






    // -----------------------------------------------------------
    // testVisitorIsSentToTheLoginPage
    // -----------------------------------------------------------
    //
    // A member page without a session redirects to the login
    // page, on the public HTTPS address the proxy headers
    // name.
    //
    // Used by:
    //   - PHPUnit — the Apache suite (runTests.sh)
    // -----------------------------------------------------------

    public function testVisitorIsSentToTheLoginPage(): void
    {
        $response = $this->http->get('/cp/dashboard');

        $this->assertSame(302, $response->getStatusCode());
        $this->assertSame('https://cyberphish.knf.vu.lt/login', $response->getHeaderLine('Location'));
    }






    // -----------------------------------------------------------
    // testFormWithoutCsrfTokenIsRefused
    // -----------------------------------------------------------
    //
    // A login posted with the right password but no CSRF
    // token — what a form on another site would send — gets
    // Laravel's 419 and signs nobody in.
    //
    // Used by:
    //   - PHPUnit — the Apache suite (runTests.sh)
    // -----------------------------------------------------------

    public function testFormWithoutCsrfTokenIsRefused(): void
    {
        $member = $this->world->member();

        $response = $this->http->post('/login', ['email' => $member->email, 'password' => World::PASSWORD]);

        $this->assertSame(419, $response->getStatusCode());
        $this->assertSame(302, $this->http->get('/cp/dashboard')->getStatusCode());
    }






    // -----------------------------------------------------------
    // testMemberSignsInWithTheLoginForm
    // -----------------------------------------------------------
    //
    // The login form signs the member in, and the session
    // cookie opens their dashboard — the page whose
    // change-username form holds their username.
    //
    // Used by:
    //   - PHPUnit — the Apache suite (runTests.sh)
    // -----------------------------------------------------------

    public function testMemberSignsInWithTheLoginForm(): void
    {
        $member = $this->world->member();

        $this->signInOverHttp($member);
        $response = $this->http->get('/cp/dashboard');

        $this->assertSame(200, $response->getStatusCode());
        $this->assertStringContainsString($member->username, (string) $response->getBody());
    }






    // -----------------------------------------------------------
    // testSessionCookieIsHiddenFromScripts
    // -----------------------------------------------------------
    //
    // The session cookie is HttpOnly, so no script on the page
    // can read it, and SameSite=Lax, so another site's forms
    // and requests go out without it.
    //
    // Used by:
    //   - PHPUnit — the Apache suite (runTests.sh)
    // -----------------------------------------------------------

    public function testSessionCookieIsHiddenFromScripts(): void
    {
        $this->http->get('/login');
        $cookie = $this->http->cookie(self::SESSION_COOKIE);

        $this->assertNotNull($cookie, 'the login page set no session cookie');
        $this->assertTrue($cookie->getHttpOnly());
        $this->assertSame('lax', strtolower(array_change_key_case($cookie->toArray())['samesite'] ?? ''));
    }






    // -----------------------------------------------------------
    // testSignInGivesANewSessionId
    // -----------------------------------------------------------
    //
    // The session the login page started is replaced at sign-
    // in: an id someone planted in the browser beforehand is
    // not the id of the signed-in session.
    //
    // Used by:
    //   - PHPUnit — the Apache suite (runTests.sh)
    // -----------------------------------------------------------

    public function testSignInGivesANewSessionId(): void
    {
        $member = $this->world->member();
        $this->http->get('/login');
        $before = $this->sessionId();

        $this->signInOverHttp($member);

        $this->assertNotSame($before, $this->sessionId());
    }






    // -----------------------------------------------------------
    // testSignOutEndsTheSession
    // -----------------------------------------------------------
    //
    // cp/logout sends the member to the home page, and the
    // same cookie no longer opens the dashboard.
    //
    // Used by:
    //   - PHPUnit — the Apache suite (runTests.sh)
    // -----------------------------------------------------------

    public function testSignOutEndsTheSession(): void
    {
        $this->signInOverHttp($this->world->member());

        $response = $this->http->get('/cp/logout');

        $this->assertSame(302, $response->getStatusCode());
        $this->assertSame('https://cyberphish.knf.vu.lt', $response->getHeaderLine('Location'));
        $this->assertSame(302, $this->http->get('/cp/dashboard')->getStatusCode());
    }






    // -----------------------------------------------------------
    // sessionId
    // -----------------------------------------------------------
    //
    // The session id inside the session cookie the client
    // holds: EncryptCookies encrypts it with APP_KEY, which the
    // tests share with Apache, and the cookie carries it URL-
    // encoded. Checked to have the shape of a Laravel session
    // id, so a decryption gone wrong cannot pass for a change.
    //
    // Used by:
    //   - testSignInGivesANewSessionId (above)
    // -----------------------------------------------------------

    private function sessionId(): string
    {
        $cookie = $this->http->cookie(self::SESSION_COOKIE);
        $this->assertNotNull($cookie, 'Apache set no session cookie');

        $id = Crypt::decrypt(rawurldecode($cookie->getValue()), false);
        $this->assertRegExp('/^[A-Za-z0-9]{40}$/', $id);
        return $id;
    }
}
