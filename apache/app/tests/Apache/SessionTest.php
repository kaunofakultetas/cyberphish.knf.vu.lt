<?php

// -----------------------------------------------------------
//  [*] SessionTest — signing in the way a browser does
//
//  The session over HTTP: a visitor is sent to the login
//  page, a form posted without its CSRF token is refused,
//  a member signs in with the login form and keeps the
//  session in a cookie scripts cannot read, the session id
//  changes at sign-in so a planted id is worthless, and
//  signing out ends the session. The cookie is Secure as
//  well, since Laravel 13 marks it so on HTTPS requests.
//  The address recorded for the member is the one Apache
//  saw, whatever header the client sends.
//
//  Used by:
//    - PHPUnit — the Apache suite (runTests.sh)
// -----------------------------------------------------------


namespace Tests\Apache;

use App\Models\Members;
use Illuminate\Cookie\CookieValuePrefix;
use Illuminate\Support\Facades\Crypt;
use PHPUnit\Framework\Attributes\DataProvider;
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

    // The address a client claims to have — one from the
    // range RFC 5737 keeps for documentation
    private const CLAIMED_IP = '203.0.113.9';






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
    // The logout form of the member menu, posted with the
    // session's token, sends the member to the home page, and
    // the same cookie no longer opens the dashboard.
    //
    // Used by:
    //   - PHPUnit — the Apache suite (runTests.sh)
    // -----------------------------------------------------------

    public function testSignOutEndsTheSession(): void
    {
        $this->signInOverHttp($this->world->member());
        $token = $this->http->csrfToken('/cp/dashboard');

        $response = $this->http->post('/cp/logout', ['_token' => $token]);

        $this->assertSame(302, $response->getStatusCode());
        $this->assertSame('https://cyberphish.knf.vu.lt', $response->getHeaderLine('Location'));
        $this->assertSame(302, $this->http->get('/cp/dashboard')->getStatusCode());
    }






    // -----------------------------------------------------------
    // testSessionCookieIsSecure
    // -----------------------------------------------------------
    //
    // The session cookie carries the Secure flag, so a browser
    // never sends it over plain HTTP. config/session.php takes
    // the flag from SESSION_SECURE_COOKIE, which neither
    // docker-compose.yml nor run.sh sets; Laravel 13 then lets
    // the request decide, and behind the endpoint every request
    // arrives as HTTPS (X-Forwarded-Proto, believed by
    // TrustProxies). Laravel 7 sent the cookie without the
    // flag; this test was pinned as session-cookie-secure
    // until the upgrade released it.
    //
    // Used by:
    //   - PHPUnit — the Apache suite (runTests.sh)
    // -----------------------------------------------------------

    public function testSessionCookieIsSecure(): void
    {
        $this->http->get('/login');
        $cookie = $this->http->cookie(self::SESSION_COOKIE);

        $this->assertNotNull($cookie, 'the login page set no session cookie');
        $this->assertTrue($cookie->getSecure(), 'the session cookie is not Secure');
    }






    // -----------------------------------------------------------
    // testRecordedAddressIsTheOneApacheSaw
    // -----------------------------------------------------------
    //
    // A sign-in that carries a header claiming another
    // address still records the one Apache saw — 127.0.0.1 in
    // the test container — as the member's last_ip
    // (SettingsController::getUserIP).
    //
    // Used by:
    //   - PHPUnit — the Apache suite (runTests.sh)
    // -----------------------------------------------------------

    #[DataProvider('addressHeaders')]
    public function testRecordedAddressIsTheOneApacheSaw(string $header): void
    {
        $member = $this->world->member();

        $this->signInOverHttp($member, [$header => self::CLAIMED_IP]);

        $this->assertSame('127.0.0.1', Members::find($member->id)->last_ip, "the $header header chose the address");
    }






    // -----------------------------------------------------------
    // addressHeaders
    // -----------------------------------------------------------
    //
    // The two headers the app once took a client's word from:
    // Client-IP and Cloudflare's CF-Connecting-IP, though no
    // Cloudflare stands in front of the site.
    //
    // Used by:
    //   - testRecordedAddressIsTheOneApacheSaw (above)
    // -----------------------------------------------------------

    public static function addressHeaders(): array
    {
        return [
            'Client-IP'        => ['Client-IP'],
            'CF-Connecting-IP' => ['CF-Connecting-IP'],
        ];
    }






    // -----------------------------------------------------------
    // sessionId
    // -----------------------------------------------------------
    //
    // The session id inside the session cookie the client
    // holds: EncryptCookies encrypts it with APP_KEY, which the
    // tests share with Apache, behind a prefix that ties the
    // value to the cookie's name, and the cookie carries it
    // URL-encoded. The prefix is checked and removed, and the
    // id checked to have the shape of a Laravel session id, so
    // a decryption gone wrong cannot pass for a change.
    //
    // Used by:
    //   - testSignInGivesANewSessionId (above)
    // -----------------------------------------------------------

    private function sessionId(): string
    {
        $cookie = $this->http->cookie(self::SESSION_COOKIE);
        $this->assertNotNull($cookie, 'Apache set no session cookie');

        $value = Crypt::decrypt(rawurldecode($cookie->getValue()), false);
        $id = (string) CookieValuePrefix::validate(self::SESSION_COOKIE, $value, Crypt::getAllKeys());
        $this->assertMatchesRegularExpression('/^[A-Za-z0-9]{40}$/', $id);
        return $id;
    }
}
