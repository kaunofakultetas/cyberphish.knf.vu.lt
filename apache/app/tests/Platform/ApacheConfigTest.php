<?php

// -----------------------------------------------------------
//  [*] ApacheConfigTest — how the image configures Apache
//
//  The Apache half of the image, read from its files: the
//  virtual host serves public/ on port 8080, the upload
//  folder runs no PHP whatever a file is called, folder
//  listings are off, mod_rewrite and mod_remoteip are on,
//  and mod_php reads the production php.ini with nothing in
//  Apache's configuration loosening it. The Apache suite
//  proves the same rules over HTTP. The over-wide range of
//  trusted proxies is pinned in PlatformDefectsTest.
//
//  Used by:
//    - PHPUnit — the Platform suite (runTests.sh)
// -----------------------------------------------------------


namespace Tests\Platform;

use Tests\TestCase;








// -----------------------------------------------------------
// ApacheConfigTest
// -----------------------------------------------------------
//
// Every test reads the configuration files the Dockerfile
// puts in place.
//
// Used by:
//   - PHPUnit — the Platform suite (runTests.sh)
// -----------------------------------------------------------

class ApacheConfigTest extends TestCase
{

    // The virtual host the Dockerfile installs
    private const VHOST = '/etc/apache2/sites-available/000-default.conf';






    // -----------------------------------------------------------
    // testVirtualHostServesThePublicFolderOnPort8080
    // -----------------------------------------------------------
    //
    // Apache listens on 8080 — the port the Caddy endpoint
    // proxies to — and the enabled virtual host's document
    // root is public/, so nothing above it is ever a URL.
    //
    // Used by:
    //   - PHPUnit — the Platform suite (runTests.sh)
    // -----------------------------------------------------------

    public function testVirtualHostServesThePublicFolderOnPort8080(): void
    {
        $this->assertSame("Listen 8080\n", file_get_contents('/etc/apache2/ports.conf'));
        $this->assertFileExists('/etc/apache2/sites-enabled/000-default.conf');
        $this->assertMatchesRegularExpression('#^\s*DocumentRoot /var/www/html/public/?\s*$#m', $this->vhost());
    }






    // -----------------------------------------------------------
    // testUploadFolderRunsNoPhp
    // -----------------------------------------------------------
    //
    // The upload folder's block switches PHP's engine off with
    // php_admin_value — which no .htaccess can turn back on —
    // removes the PHP handlers and denies every PHP-like file
    // name outright.
    //
    // Used by:
    //   - PHPUnit — the Platform suite (runTests.sh)
    // -----------------------------------------------------------

    public function testUploadFolderRunsNoPhp(): void
    {
        $this->assertTrue((bool) preg_match('#<Directory /var/www/html/public/upload\s*>(.*?)</Directory>#s', $this->vhost(), $block));

        $this->assertMatchesRegularExpression('#php_admin_value engine Off#', $block[1]);
        $this->assertMatchesRegularExpression('#RemoveHandler \.php \.phtml \.phar#', $block[1]);
        $this->assertMatchesRegularExpression('#<FilesMatch "\\\\\.ph\(p\[3457s\]\?\|t\|tml\|ar\)\$">\s*Require all denied#', $block[1]);
    }






    // -----------------------------------------------------------
    // testFolderListingsAreOff
    // -----------------------------------------------------------
    //
    // The app folder's block turns Indexes off.
    //
    // Used by:
    //   - PHPUnit — the Platform suite (runTests.sh)
    // -----------------------------------------------------------

    public function testFolderListingsAreOff(): void
    {
        $this->assertMatchesRegularExpression('#<Directory /var/www/html\s*>\s*Options -Indexes#', $this->vhost());
    }






    // -----------------------------------------------------------
    // testRewriteAndRemoteIpAreEnabled
    // -----------------------------------------------------------
    //
    // public/.htaccess needs mod_rewrite to reach index.php,
    // and the access log needs mod_remoteip for the visitor's
    // address behind the proxies.
    //
    // Used by:
    //   - PHPUnit — the Platform suite (runTests.sh)
    // -----------------------------------------------------------

    public function testRewriteAndRemoteIpAreEnabled(): void
    {
        $this->assertFileExists('/etc/apache2/mods-enabled/rewrite.load');
        $this->assertFileExists('/etc/apache2/mods-enabled/remoteip.load');
        $this->assertMatchesRegularExpression('#^RemoteIPHeader X-Forwarded-For$#m', $this->vhost());
    }






    // -----------------------------------------------------------
    // testPhpReadsTheProductionIni
    // -----------------------------------------------------------
    //
    // PHP loads /usr/local/etc/php/php.ini — the file the
    // Dockerfile builds from php.ini-production — and no
    // Apache configuration file sets its own disable_functions
    // for mod_php.
    //
    // Used by:
    //   - PHPUnit — the Platform suite (runTests.sh)
    // -----------------------------------------------------------

    public function testPhpReadsTheProductionIni(): void
    {
        $this->assertSame('/usr/local/etc/php/php.ini', php_ini_loaded_file());

        $files = glob('/etc/apache2/{,*/}*.conf', GLOB_BRACE);
        $this->assertNotEmpty($files);
        foreach ($files as $file) {
            $this->assertStringNotContainsString('disable_functions', file_get_contents($file), $file);
        }
    }






    // -----------------------------------------------------------
    // vhost
    // -----------------------------------------------------------
    //
    // The virtual host's configuration text.
    //
    // Used by:
    //   - testVirtualHostServesThePublicFolderOnPort8080,
    //     testUploadFolderRunsNoPhp, testFolderListingsAreOff,
    //     testRewriteAndRemoteIpAreEnabled (above)
    // -----------------------------------------------------------

    private function vhost(): string
    {
        return file_get_contents(self::VHOST);
    }
}
