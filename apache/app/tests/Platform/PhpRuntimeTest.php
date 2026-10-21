<?php

// -----------------------------------------------------------
//  [*] PhpRuntimeTest — the PHP the image ships
//
//  Pins the runtime of the production image: PHP 7.2.24
//  with the extensions the app needs, the production
//  php.ini, and the disable_functions list the Dockerfile
//  builds — the functions that run shell commands or read
//  files around the app stay off, and the ones the app
//  itself needs stay on (switching unlink, base64_encode
//  or preg_replace off once broke the certificate and every
//  page, as the error log of April 2024 shows). The test
//  runs in the same PHP and the same php.ini that Apache
//  loads; PhpProbeTest checks Apache's side.
//
//  Used by:
//    - PHPUnit — the Platform suite (runTests.sh)
// -----------------------------------------------------------


namespace Tests\Platform;

use Tests\TestCase;








// -----------------------------------------------------------
// PhpRuntimeTest
// -----------------------------------------------------------
//
// Reads the running PHP itself: version, loaded extensions,
// ini values and which functions exist.
//
// Used by:
//   - PHPUnit — the Platform suite (runTests.sh)
// -----------------------------------------------------------

class PhpRuntimeTest extends TestCase
{

    // The functions that start programs or open a shell — off,
    // so code that gets to run inside the app still cannot
    // run commands
    private const COMMAND_FUNCTIONS = ['exec', 'passthru', 'system', 'shell_exec', 'proc_open', 'popen', 'pcntl_exec'];

    // The file functions the Dockerfile switches off as well
    private const FILE_FUNCTIONS = ['readfile', 'symlink', 'link'];

    // Functions the app cannot work without — the certificate
    // download deletes its temporary file with unlink, and
    // Laravel's string helpers and session use the rest
    private const NEEDED_FUNCTIONS = ['unlink', 'mkdir', 'rmdir', 'base64_encode', 'preg_replace', 'file_put_contents'];

    // The extensions the app and its packages load
    private const EXTENSIONS = [
        'pdo_mysql',
        'gd',
        'zip',
        'mbstring',
        'openssl',
        'tokenizer',
        'xml',
        'ctype',
        'json',
        'fileinfo',
        'curl',
        'dom',
        'iconv',
    ];






    // -----------------------------------------------------------
    // testRunsPhp7224
    // -----------------------------------------------------------
    //
    // The image is pinned to php:7.2.24-apache; a version
    // change must be a decision, not an accident of a base
    // image tag.
    //
    // Used by:
    //   - PHPUnit — the Platform suite (runTests.sh)
    // -----------------------------------------------------------

    public function testRunsPhp7224(): void
    {
        $this->assertSame('7.2.24', PHP_VERSION);
    }






    // -----------------------------------------------------------
    // testLoadsTheExtensionsTheAppNeeds
    // -----------------------------------------------------------
    //
    // pdo_mysql for the database, gd and zip for dompdf and
    // phpspreadsheet, and the extensions Laravel, Guzzle and
    // the mailer rely on.
    //
    // Used by:
    //   - PHPUnit — the Platform suite (runTests.sh)
    // -----------------------------------------------------------

    public function testLoadsTheExtensionsTheAppNeeds(): void
    {
        foreach (self::EXTENSIONS as $extension) {
            $this->assertTrue(extension_loaded($extension), "extension $extension is not loaded");
        }
    }






    // -----------------------------------------------------------
    // testGdReadsJpegAndFreetype
    // -----------------------------------------------------------
    //
    // The Dockerfile builds gd with JPEG and FreeType, which
    // the certificate's background image and fonts need.
    //
    // Used by:
    //   - PHPUnit — the Platform suite (runTests.sh)
    // -----------------------------------------------------------

    public function testGdReadsJpegAndFreetype(): void
    {
        $gd = gd_info();
        $this->assertTrue($gd['JPEG Support']);
        $this->assertTrue($gd['FreeType Support']);
        $this->assertTrue($gd['PNG Support']);
    }






    // -----------------------------------------------------------
    // testRunsTheProductionIni
    // -----------------------------------------------------------
    //
    // php.ini is php.ini-production: errors are logged, never
    // printed into a page, and assertions are compiled out.
    //
    // Used by:
    //   - PHPUnit — the Platform suite (runTests.sh)
    // -----------------------------------------------------------

    public function testRunsTheProductionIni(): void
    {
        $this->assertFalse((bool) ini_get('display_errors'));
        $this->assertFalse((bool) ini_get('display_startup_errors'));
        $this->assertTrue((bool) ini_get('log_errors'));
        $this->assertSame('-1', ini_get('zend.assertions'));
    }






    // -----------------------------------------------------------
    // testCommandFunctionsAreDisabled
    // -----------------------------------------------------------
    //
    // None of the functions that start a program exists for
    // PHP code — PHP 7.2 reports a disabled function as
    // missing to function_exists.
    //
    // Used by:
    //   - PHPUnit — the Platform suite (runTests.sh)
    // -----------------------------------------------------------

    public function testCommandFunctionsAreDisabled(): void
    {
        foreach (self::COMMAND_FUNCTIONS as $function) {
            $this->assertFalse(function_exists($function), "$function() is not disabled");
        }
    }






    // -----------------------------------------------------------
    // testFileFunctionsTheDockerfileListsAreDisabled
    // -----------------------------------------------------------
    //
    // readfile, symlink and link are off too.
    //
    // Used by:
    //   - PHPUnit — the Platform suite (runTests.sh)
    // -----------------------------------------------------------

    public function testFileFunctionsTheDockerfileListsAreDisabled(): void
    {
        foreach (self::FILE_FUNCTIONS as $function) {
            $this->assertFalse(function_exists($function), "$function() is not disabled");
        }
    }






    // -----------------------------------------------------------
    // testFunctionsTheAppNeedsStayEnabled
    // -----------------------------------------------------------
    //
    // The hardening never reaches the functions the app calls
    // itself.
    //
    // Used by:
    //   - PHPUnit — the Platform suite (runTests.sh)
    // -----------------------------------------------------------

    public function testFunctionsTheAppNeedsStayEnabled(): void
    {
        foreach (self::NEEDED_FUNCTIONS as $function) {
            $this->assertTrue(function_exists($function), "$function() is disabled, but the app needs it");
        }
    }
}
