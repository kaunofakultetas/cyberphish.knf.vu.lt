<?php

// -----------------------------------------------------------
//  [*] ProductionModeTest — what a visitor sees of a failure
//
//  Apache runs the app the way production does: no .env,
//  APP_DEBUG off, APP_ENV unset and so "production". A
//  server error must then show Laravel's generic error page
//  — no file paths, no SQL, no stack trace, no Ignition, no
//  secret from the environment — and land in storage/logs,
//  the log the operators read. The error is a real one: the
//  test hides a table every member page reads, as a
//  database without it would fail, and puts it back
//  afterwards. A failure outside the app is no server error
//  at all: while Google's reCAPTCHA service cannot be
//  reached — never, in the offline test container — the
//  sign-up and forgot-password forms come back with the
//  reCAPTCHA's error, and the failure is logged all the
//  same.
//
//  Used by:
//    - PHPUnit — the Apache suite (runTests.sh)
// -----------------------------------------------------------


namespace Tests\Apache;

use App\Models\Members;
use Illuminate\Support\Facades\DB;
use Psr\Http\Message\ResponseInterface;
use Tests\ApacheTestCase;
use Tests\Support\World;








// -----------------------------------------------------------
// ProductionModeTest
// -----------------------------------------------------------
//
// The server-error tests sign a member in over HTTP and
// open the dashboard with bei_users_badges hidden; the
// outage tests post a form with a reCAPTCHA token Google
// never sees.
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
            'SQLSTATE',
            'bei_users_badges',
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
        $written = $this->logWrittenBy(function () {
            $this->openBrokenDashboard();
        });

        $this->assertMatchesRegularExpression('/production\.ERROR: SQLSTATE\[42S02\].*bei_users_badges/', $written);
    }






    // -----------------------------------------------------------
    // testRegistrationSurvivesAGoogleOutage
    // -----------------------------------------------------------
    //
    // The sign-up form posted while Google cannot be reached
    // comes back to the form, and nobody is registered.
    //
    // Used by:
    //   - PHPUnit — the Apache suite (runTests.sh)
    // -----------------------------------------------------------

    public function testRegistrationSurvivesAGoogleOutage(): void
    {
        $email = 'outage' . bin2hex(random_bytes(4)) . '@example.test';

        $response = $this->signUpDuringTheOutage($email);

        $this->assertSame(302, $response->getStatusCode());
        $this->assertSame('https://cyberphish.knf.vu.lt/register', $response->getHeaderLine('Location'));
        $this->assertFalse(Members::where('email', $email)->exists());
    }






    // -----------------------------------------------------------
    // testResetRequestSurvivesAGoogleOutage
    // -----------------------------------------------------------
    //
    // The forgot-password form posted while Google cannot be
    // reached comes back to the form, and no reset link goes
    // out.
    //
    // Used by:
    //   - PHPUnit — the Apache suite (runTests.sh)
    // -----------------------------------------------------------

    public function testResetRequestSurvivesAGoogleOutage(): void
    {
        $member = $this->world->member();
        $token = $this->http->csrfToken('/forgot-password');
        $this->assertNotSame('', $token, 'the forgot-password page has no CSRF token');

        $response = $this->http->post('/forgot-password', [
            '_token'               => $token,
            'email'                => $member->email,
            'g-recaptcha-response' => 'a-token-google-never-sees',
        ]);

        $this->assertSame(302, $response->getStatusCode());
        $this->assertSame('https://cyberphish.knf.vu.lt/forgot-password', $response->getHeaderLine('Location'));
        $this->assertSame('', Members::find($member->id)->reset_token);
    }






    // -----------------------------------------------------------
    // testGoogleOutageIsLogged
    // -----------------------------------------------------------
    //
    // The visitor sees a form error, but the operators still
    // find the failed request to Google in laravel.log.
    //
    // Used by:
    //   - PHPUnit — the Apache suite (runTests.sh)
    // -----------------------------------------------------------

    public function testGoogleOutageIsLogged(): void
    {
        $written = $this->logWrittenBy(function () {
            $this->signUpDuringTheOutage('outage' . bin2hex(random_bytes(4)) . '@example.test');
        });

        $this->assertMatchesRegularExpression('/production\.ERROR: cURL error \d+: .*siteverify/', $written);
    }






    // -----------------------------------------------------------
    // openBrokenDashboard
    // -----------------------------------------------------------
    //
    // Signs a member in over HTTP, hides bei_users_badges —
    // the table BaseController reads the member's badges from
    // on every member page — and opens the dashboard; the
    // response is the server error. The table is back before
    // the method returns, whatever happened.
    //
    // Used by:
    //   - the server-error tests of this class
    // -----------------------------------------------------------

    private function openBrokenDashboard(): ResponseInterface
    {
        $member = $this->world->member();
        $this->signInOverHttp($member);

        DB::statement('RENAME TABLE bei_users_badges TO bei_users_badges_hidden');
        try {
            return $this->http->get('/cp/dashboard');
        } finally {
            DB::statement('RENAME TABLE bei_users_badges_hidden TO bei_users_badges');
        }
    }






    // -----------------------------------------------------------
    // signUpDuringTheOutage
    // -----------------------------------------------------------
    //
    // Opens the sign-up form for its session and CSRF token
    // and posts it for the address, complete but for a
    // reCAPTCHA token only Google could check.
    //
    // Used by:
    //   - testRegistrationSurvivesAGoogleOutage,
    //     testGoogleOutageIsLogged (above)
    // -----------------------------------------------------------

    private function signUpDuringTheOutage(string $email): ResponseInterface
    {
        $token = $this->http->csrfToken('/register');
        $this->assertNotSame('', $token, 'the sign-up page has no CSRF token');

        return $this->http->post('/register', [
            '_token'               => $token,
            'email'                => $email,
            'password'             => World::PASSWORD,
            'password2'            => World::PASSWORD,
            'country'              => 1,
            'g-recaptcha-response' => 'a-token-google-never-sees',
        ]);
    }






    // -----------------------------------------------------------
    // logWrittenBy
    // -----------------------------------------------------------
    //
    // Runs the requests and returns what Laravel added to
    // laravel.log meanwhile.
    //
    // Used by:
    //   - testServerErrorIsLogged, testGoogleOutageIsLogged
    //     (above)
    // -----------------------------------------------------------

    private function logWrittenBy(callable $requests): string
    {
        clearstatcache();
        $offset = is_file(self::LOG) ? filesize(self::LOG) : 0;

        $requests();

        clearstatcache();
        return (string) file_get_contents(self::LOG, false, null, $offset);
    }
}
