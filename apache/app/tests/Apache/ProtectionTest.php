<?php

// -----------------------------------------------------------
//  [*] ProtectionTest — what Apache never hands out
//
//  The security half of the Apache setup, over HTTP:
//  hidden files are refused, nothing above public/ — the
//  code, the configuration, composer's files, the logs —
//  is reachable, Laravel's development tools are absent,
//  and no PHP ever runs in the upload folder. That last
//  rule is what keeps an uploaded script harmless, so the
//  tests plant real scripts there — under every name PHP
//  could answer to, and behind an .htaccess that tries to
//  switch PHP back on — and make sure none of them runs.
//  The upload folder is the symlink public/upload ->
//  storage/app/public, a tmpfs in the test container. No
//  response names the version of PHP or of Apache either.
//
//  Used by:
//    - PHPUnit — the Apache suite (runTests.sh)
// -----------------------------------------------------------


namespace Tests\Apache;

use Psr\Http\Message\ResponseInterface;
use Tests\ApacheTestCase;








// -----------------------------------------------------------
// ProtectionTest
// -----------------------------------------------------------
//
// Plain requests without a session; the planted files are
// removed after every test.
//
// Used by:
//   - PHPUnit — the Apache suite (runTests.sh)
// -----------------------------------------------------------

class ProtectionTest extends ApacheTestCase
{

    // What a planted script prints if PHP runs it — a string
    // that cannot appear in its source
    private const SCRIPT = '<?php echo "EXEC" . "UTED-" . (6 * 7);';
    private const RAN = 'EXECUTED-42';

    // The planted files of the test running now
    private $planted = [];






    // -----------------------------------------------------------
    // tearDown
    // -----------------------------------------------------------
    //
    // Removes the planted files before the rest of the
    // teardown.
    //
    // Used by:
    //   - PHPUnit, after every test
    // -----------------------------------------------------------

    protected function tearDown(): void
    {
        foreach ($this->planted as $path) {
            @unlink($path);
        }
        parent::tearDown();
    }






    // -----------------------------------------------------------
    // testHiddenFilesAreRefused
    // -----------------------------------------------------------
    //
    // Apache's own configuration refuses .ht files; a .env or
    // .git path finds no file in public/ and gets Laravel's
    // 404.
    //
    // Used by:
    //   - PHPUnit — the Apache suite (runTests.sh)
    // -----------------------------------------------------------

    public function testHiddenFilesAreRefused(): void
    {
        $this->assertSame(403, $this->http->get('/.htaccess')->getStatusCode());
        $this->assertSame(403, $this->http->get('/.htpasswd')->getStatusCode());
        $this->assertSame(404, $this->http->get('/.env')->getStatusCode());
        $this->assertSame(404, $this->http->get('/.git/HEAD')->getStatusCode());
    }






    // -----------------------------------------------------------
    // testCodeOutsidePublicIsUnreachable
    // -----------------------------------------------------------
    //
    // The files around public/ — the ones scanners ask for
    // daily — are not URLs: each is a 404, and nothing that
    // looks like source code comes back.
    //
    // Used by:
    //   - PHPUnit — the Apache suite (runTests.sh)
    // -----------------------------------------------------------

    public function testCodeOutsidePublicIsUnreachable(): void
    {
        $paths = [
            '/composer.json', '/composer.lock', '/artisan', '/server.php', '/.env.example',
            '/vendor/autoload.php', '/config/app.php', '/routes/web_user.php',
            '/app/Http/Kernel.php', '/storage/logs/laravel.log', '/bootstrap/cache/packages.php',
        ];
        foreach ($paths as $path) {
            $response = $this->http->get($path);
            $body = (string) $response->getBody();
            $this->assertSame(404, $response->getStatusCode(), $path);
            $this->assertStringNotContainsString('<?php', $body, $path);
            $this->assertStringNotContainsString('"require"', $body, $path);
        }
    }






    // -----------------------------------------------------------
    // testIgnitionIsAbsent
    // -----------------------------------------------------------
    //
    // Neither Ignition route exists — the health check nor
    // the execute-solution endpoint of CVE-2021-3129.
    //
    // Used by:
    //   - PHPUnit — the Apache suite (runTests.sh)
    // -----------------------------------------------------------

    public function testIgnitionIsAbsent(): void
    {
        $this->assertSame(404, $this->http->get('/_ignition/health-check')->getStatusCode());
        $this->assertSame(404, $this->http->post('/_ignition/execute-solution', ['solution' => 'x'])->getStatusCode());
    }






    // -----------------------------------------------------------
    // testUploadedScriptsAreRefused
    // -----------------------------------------------------------
    //
    // Every PHP extension the upload block knows is denied
    // outright, by its name alone.
    //
    // Used by:
    //   - PHPUnit — the Apache suite (runTests.sh)
    // -----------------------------------------------------------

    public function testUploadedScriptsAreRefused(): void
    {
        foreach (['probe.php', 'probe.phtml', 'probe.php5', 'probe.phar', 'probe.phps'] as $name) {
            $this->plant($name, self::SCRIPT);
            $response = $this->http->get("/upload/$name");
            $this->assertSame(403, $response->getStatusCode(), $name);
            $this->assertStringNotContainsString(self::RAN, (string) $response->getBody(), $name);
        }
    }






    // -----------------------------------------------------------
    // testNoScriptRunsUnderAnyName
    // -----------------------------------------------------------
    //
    // Names the deny rule does not match — upper case, a
    // second extension — come back as plain files, but PHP's
    // engine is off in the folder, so they never run.
    //
    // Used by:
    //   - PHPUnit — the Apache suite (runTests.sh)
    // -----------------------------------------------------------

    public function testNoScriptRunsUnderAnyName(): void
    {
        foreach (['probe.PHP', 'probe.php.jpg', 'probe.pHtml'] as $name) {
            $this->plant($name, self::SCRIPT);
            $this->assertInert($this->http->get("/upload/$name"), $name);
        }
    }






    // -----------------------------------------------------------
    // testUploadedHtaccessCannotTurnPhpOn
    // -----------------------------------------------------------
    //
    // An .htaccess dropped into the folder that maps .jpg to
    // PHP and turns the engine on cannot undo php_admin_value:
    // the "image" never runs. (Apache does read the file —
    // AllowOverride All reaches the folder — so the type it
    // sets is applied.)
    //
    // Used by:
    //   - PHPUnit — the Apache suite (runTests.sh)
    // -----------------------------------------------------------

    public function testUploadedHtaccessCannotTurnPhpOn(): void
    {
        $this->plant('.htaccess', "AddHandler application/x-httpd-php .jpg\nAddType application/x-httpd-php .jpg\nphp_flag engine on\n");
        $this->plant('photo.jpg', self::SCRIPT);

        $this->assertInert($this->http->get('/upload/photo.jpg'), 'photo.jpg');
    }






    // -----------------------------------------------------------
    // testUploadFolderIsNeverListed
    // -----------------------------------------------------------
    //
    // Opening the folder itself reveals none of its files.
    //
    // Used by:
    //   - PHPUnit — the Apache suite (runTests.sh)
    // -----------------------------------------------------------

    public function testUploadFolderIsNeverListed(): void
    {
        $this->plant('listed-name-check.pdf', '%PDF-1.4');

        $body = (string) $this->http->get('/upload/')->getBody();

        $this->assertStringNotContainsString('listed-name-check.pdf', $body);
    }






    // -----------------------------------------------------------
    // testUploadedDocumentIsServed
    // -----------------------------------------------------------
    //
    // The folder still does its job: a PDF in it is served
    // as a PDF.
    //
    // Used by:
    //   - PHPUnit — the Apache suite (runTests.sh)
    // -----------------------------------------------------------

    public function testUploadedDocumentIsServed(): void
    {
        $this->plant('handout.pdf', "%PDF-1.4\n%handout\n");

        $response = $this->http->get('/upload/handout.pdf');

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('application/pdf', $response->getHeaderLine('Content-Type'));
    }






    // -----------------------------------------------------------
    // testResponsesDoNotNamePhp
    // -----------------------------------------------------------
    //
    // No X-Powered-By header: the Dockerfile turns off
    // expose_php, which php.ini-production leaves on, and the
    // endpoint's Caddyfile would pass the header on.
    //
    // Used by:
    //   - PHPUnit — the Apache suite (runTests.sh)
    // -----------------------------------------------------------

    public function testResponsesDoNotNamePhp(): void
    {
        $response = $this->http->get('/');

        $this->assertFalse($response->hasHeader('X-Powered-By'), 'X-Powered-By: ' . $response->getHeaderLine('X-Powered-By'));
    }






    // -----------------------------------------------------------
    // testResponsesDoNotNameApachesVersion
    // -----------------------------------------------------------
    //
    // The Server header names no version, and the pages Apache
    // writes itself — the 403 of a hidden file — carry no
    // signature: 000-default.conf overrides the ServerTokens
    // OS and ServerSignature On of Debian's security.conf.
    //
    // Used by:
    //   - PHPUnit — the Apache suite (runTests.sh)
    // -----------------------------------------------------------

    public function testResponsesDoNotNameApachesVersion(): void
    {
        $server = $this->http->get('/')->getHeaderLine('Server');
        $refusal = (string) $this->http->get('/.htaccess')->getBody();

        $this->assertDoesNotMatchRegularExpression('/\d/', $server, "Server: $server");
        $this->assertStringNotContainsString('Apache/2', $refusal, 'Apache signs its error page');
    }






    // -----------------------------------------------------------
    // plant
    // -----------------------------------------------------------
    //
    // Writes a file into the upload folder and remembers it
    // for tearDown.
    //
    // Used by:
    //   - the upload tests of this class
    // -----------------------------------------------------------

    private function plant(string $name, string $content): void
    {
        $path = storage_path("app/public/$name");
        file_put_contents($path, $content);
        $this->planted[] = $path;
    }






    // -----------------------------------------------------------
    // assertInert
    // -----------------------------------------------------------
    //
    // The planted script came back refused, or as its own
    // source text — which shows both that Apache found the
    // file and that PHP did not run it. A refusal alone proves
    // less: the deny rule refuses a PHP-like name whether or
    // not the file exists.
    //
    // Used by:
    //   - testNoScriptRunsUnderAnyName,
    //     testUploadedHtaccessCannotTurnPhpOn (above)
    // -----------------------------------------------------------

    private function assertInert(ResponseInterface $response, string $name): void
    {
        $body = (string) $response->getBody();
        $this->assertStringNotContainsString(self::RAN, $body, "$name ran");
        if ($response->getStatusCode() !== 403) {
            $this->assertSame(self::SCRIPT, $body, "$name came back neither refused nor as its source");
        }
    }
}
