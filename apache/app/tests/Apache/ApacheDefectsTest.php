<?php

// -----------------------------------------------------------
//  [*] ApacheDefectsTest — known defects over HTTP, pinned
//
//  Each test states the behaviour the site should have and
//  pins the defect that breaks it today (KnownDefects): the
//  run reports them as incomplete, tagged with their ids,
//  until a fix makes one pass — then the test fails as the
//  cue to remove its pin and move it into its home file.
//
//    expose-php            — every response names PHP's
//                            exact version
//    server-banner         — the Server header and Apache's
//                            own pages name Apache's version
//    session-cookie-secure — the session cookie has no
//                            Secure flag
//    client-ip-header      — a client picks the address the
//                            app records for it
//    trailing-slash-http   — the trailing-slash redirect
//                            leaves HTTPS
//    captcha-outage-500    — registration is a server error
//                            while Google is unreachable
//
//  Used by:
//    - PHPUnit — the Apache suite (runTests.sh)
// -----------------------------------------------------------


namespace Tests\Apache;

use App\Models\Members;
use Tests\ApacheTestCase;
use Tests\Support\World;








// -----------------------------------------------------------
// ApacheDefectsTest
// -----------------------------------------------------------
//
// One pinned defect per test, named in the header.
//
// Used by:
//   - PHPUnit — the Apache suite (runTests.sh)
// -----------------------------------------------------------

class ApacheDefectsTest extends ApacheTestCase
{

    // The address a client claims to have — one from the
    // range RFC 5737 keeps for documentation
    private const CLAIMED_IP = '203.0.113.9';






    // -----------------------------------------------------------
    // testResponsesDoNotNamePhp
    // -----------------------------------------------------------
    //
    // php.ini-production leaves expose_php on and the
    // Dockerfile builds php.ini from it as it is, so every
    // response of the app carries X-Powered-By with PHP's
    // exact version — 7.2.24, out of support since 2020 —
    // and the endpoint's Caddyfile does not remove it.
    // Wanted: no X-Powered-By header.
    //
    // Used by:
    //   - PHPUnit — the Apache suite (runTests.sh)
    // -----------------------------------------------------------

    public function testResponsesDoNotNamePhp(): void
    {
        $response = $this->http->get('/');

        $this->pinDefect('expose-php', function () use ($response) {
            $this->assertFalse($response->hasHeader('X-Powered-By'), 'X-Powered-By: ' . $response->getHeaderLine('X-Powered-By'));
        });
    }






    // -----------------------------------------------------------
    // testResponsesDoNotNameApachesVersion
    // -----------------------------------------------------------
    //
    // Debian's security.conf, which the image keeps, sets
    // ServerTokens OS and ServerSignature On: the Server
    // header names Apache 2.4.38 on Debian, and the pages
    // Apache writes itself — the 403 of a hidden file — sign
    // off with the same banner. Wanted: neither names a
    // version.
    //
    // Used by:
    //   - PHPUnit — the Apache suite (runTests.sh)
    // -----------------------------------------------------------

    public function testResponsesDoNotNameApachesVersion(): void
    {
        $server = $this->http->get('/')->getHeaderLine('Server');
        $refusal = (string) $this->http->get('/.htaccess')->getBody();

        $this->pinDefect('server-banner', function () use ($server, $refusal) {
            $this->assertNotRegExp('/\d/', $server, "Server: $server");
            $this->assertStringNotContainsString('Apache/2', $refusal, 'Apache signs its error page');
        });
    }






    // -----------------------------------------------------------
    // testSessionCookieIsSecure
    // -----------------------------------------------------------
    //
    // config/session.php takes the Secure flag from
    // SESSION_SECURE_COOKIE, which docker-compose.yml does not
    // set — and run.sh mirrors it — so the session cookie goes
    // out without the flag although the site is served over
    // HTTPS only. A browser then sends it along with any
    // plain-HTTP request to the host; the endpoint's
    // Strict-Transport-Security header prevents that only in
    // browsers that have already seen it. Wanted: the Secure
    // flag.
    //
    // Used by:
    //   - PHPUnit — the Apache suite (runTests.sh)
    // -----------------------------------------------------------

    public function testSessionCookieIsSecure(): void
    {
        $this->http->get('/login');
        $cookie = $this->http->cookie(self::SESSION_COOKIE);
        $this->assertNotNull($cookie, 'the login page set no session cookie');

        $this->pinDefect('session-cookie-secure', function () use ($cookie) {
            $this->assertTrue($cookie->getSecure(), 'the session cookie is not Secure');
        });
    }






    // -----------------------------------------------------------
    // testRecordedAddressIsTheOneApacheSaw
    // -----------------------------------------------------------
    //
    // SettingsController::getUserIP believes a
    // CF-Connecting-IP or Client-IP header before the address
    // Apache resolved — headers any client can send, and the
    // endpoint passes them on untouched (no Cloudflare stands
    // in front). Whatever address a client claims becomes its
    // member's last_ip, and BlockIpMiddleware's block list,
    // empty today, could be dodged the same way. Wanted: the
    // address Apache saw — 127.0.0.1 in the test container.
    //
    // Used by:
    //   - PHPUnit — the Apache suite (runTests.sh)
    // -----------------------------------------------------------

    /** @dataProvider addressHeaders */
    public function testRecordedAddressIsTheOneApacheSaw(string $header): void
    {
        $member = $this->world->member();

        $this->signInOverHttp($member, [$header => self::CLAIMED_IP]);

        $this->pinDefect('client-ip-header', function () use ($member, $header) {
            $this->assertSame('127.0.0.1', Members::find($member->id)->last_ip, "the $header header chose the address");
        });
    }






    // -----------------------------------------------------------
    // addressHeaders
    // -----------------------------------------------------------
    //
    // The two headers getUserIP takes a client's word from.
    //
    // Used by:
    //   - testRecordedAddressIsTheOneApacheSaw (above)
    // -----------------------------------------------------------

    public function addressHeaders(): array
    {
        return [
            'Client-IP'        => ['Client-IP'],
            'CF-Connecting-IP' => ['CF-Connecting-IP'],
        ];
    }






    // -----------------------------------------------------------
    // testTrailingSlashRedirectStaysOnHttps
    // -----------------------------------------------------------
    //
    // public/.htaccess redirects a trailing slash away with
    // mod_rewrite, and Apache makes the Location absolute
    // with the scheme it knows, plain HTTP — X-Forwarded-Proto
    // is read by Laravel, not by Apache. The redirect sends
    // an HTTPS visitor to http://, a plain-text request
    // wherever the browser has no Strict-Transport-Security
    // entry for the site yet. Wanted: the redirect stays on
    // https.
    //
    // Used by:
    //   - PHPUnit — the Apache suite (runTests.sh)
    // -----------------------------------------------------------

    public function testTrailingSlashRedirectStaysOnHttps(): void
    {
        $location = $this->http->get('/login/')->getHeaderLine('Location');

        $this->pinDefect('trailing-slash-http', function () use ($location) {
            $this->assertStringStartsWith('https://', $location);
        });
    }






    // -----------------------------------------------------------
    // testRegistrationSurvivesAGoogleOutage
    // -----------------------------------------------------------
    //
    // anhskohbo/no-captcha posts the reCAPTCHA token to Google
    // with no try/catch, so when Google cannot be reached —
    // in production whenever the exit proxy is down, here
    // always — the connection error escapes the validator,
    // and the registration form, like forgot-password,
    // answers with the 500 page; a hanging connection first
    // waits out the package's 30-second timeout. It fails
    // closed, nobody is registered, but the visitor is told
    // nothing. Wanted: the form back with an error.
    //
    // Used by:
    //   - PHPUnit — the Apache suite (runTests.sh)
    // -----------------------------------------------------------

    public function testRegistrationSurvivesAGoogleOutage(): void
    {
        $email = 'outage' . bin2hex(random_bytes(4)) . '@example.test';
        $token = $this->http->csrfToken('/register');

        $response = $this->http->post('/register', [
            '_token'               => $token,
            'email'                => $email,
            'password'             => World::PASSWORD,
            'password2'            => World::PASSWORD,
            'country'              => 1,
            'g-recaptcha-response' => 'a-token-google-never-sees',
        ]);

        $this->assertFalse(Members::where('email', $email)->exists(), 'the registration went through');
        $this->pinDefect('captcha-outage-500', function () use ($response) {
            $this->assertSame(302, $response->getStatusCode(), 'the registration is a server error');
        });
    }
}
