<?php

// -----------------------------------------------------------
//  [*] VendorTest — the packages the image ships
//
//  The Dockerfile installs composer.lock with --no-dev, so
//  the vendor folder holds the production packages only:
//  no PHPUnit, no Mockery, no Whoops, no Collision — and no
//  facade/ignition, whose execute-solution route is a remote
//  code execution (CVE-2021-3129) whenever debug mode is on;
//  the log review found it shipped until March 2026. Its
//  successor since Laravel 9, spatie/laravel-ignition, is
//  kept out the same way.
//  Laravel's package manifest, built at image time,
//  registers no development provider either.
//
//  Used by:
//    - PHPUnit — the Platform suite (runTests.sh)
// -----------------------------------------------------------


namespace Tests\Platform;

use Tests\TestCase;








// -----------------------------------------------------------
// VendorTest
// -----------------------------------------------------------
//
// Reads vendor/ and the composer metadata of the app — the
// test tools live in /opt/test-tools, outside it.
//
// Used by:
//   - PHPUnit — the Platform suite (runTests.sh)
// -----------------------------------------------------------

class VendorTest extends TestCase
{

    // The development and error-page packages of every
    // composer.json the app has had, which must never reach
    // the image
    private const DEV_PACKAGES = [
        'facade/ignition',
        'facade/flare-client-php',
        'filp/whoops',
        'fzaninotto/faker',
        'mockery/mockery',
        'nunomaduro/collision',
        'phpunit/phpunit',
        'spatie/ignition',
        'spatie/laravel-ignition',
    ];






    // -----------------------------------------------------------
    // testNoDevelopmentPackageIsInstalled
    // -----------------------------------------------------------
    //
    // None of the development packages has a folder in
    // vendor/, and composer recorded the install as one
    // without development packages.
    //
    // Used by:
    //   - PHPUnit — the Platform suite (runTests.sh)
    // -----------------------------------------------------------

    public function testNoDevelopmentPackageIsInstalled(): void
    {
        foreach (self::DEV_PACKAGES as $package) {
            $this->assertDirectoryDoesNotExist(base_path("vendor/$package"), "$package is installed");
        }

        $installed = json_decode(file_get_contents(base_path('vendor/composer/installed.json')), true);
        $this->assertFalse($installed['dev']);
        $names = array_column($installed['packages'], 'name');
        $this->assertSame([], array_values(array_intersect(self::DEV_PACKAGES, $names)));
    }






    // -----------------------------------------------------------
    // testPackageManifestRegistersNoDevelopmentProvider
    // -----------------------------------------------------------
    //
    // bootstrap/cache/packages.php, which Laravel's package
    // discovery wrote at build time, names no provider of a
    // development package — Ignition and Collision register
    // themselves this way.
    //
    // Used by:
    //   - PHPUnit — the Platform suite (runTests.sh)
    // -----------------------------------------------------------

    public function testPackageManifestRegistersNoDevelopmentProvider(): void
    {
        $manifest = require base_path('bootstrap/cache/packages.php');

        $this->assertSame([], array_values(array_intersect(self::DEV_PACKAGES, array_keys($manifest))));
        $this->assertFalse(class_exists('Facade\\Ignition\\IgnitionServiceProvider'));
    }






    // -----------------------------------------------------------
    // testInstalledVersionsAreTheLockedOnes
    // -----------------------------------------------------------
    //
    // Every package composer.lock pins is installed at exactly
    // the locked version — the image is built from the lock,
    // never from fresh resolution — and the framework is
    // Laravel 13.38.0, the version the suite was run on.
    //
    // Used by:
    //   - PHPUnit — the Platform suite (runTests.sh)
    // -----------------------------------------------------------

    public function testInstalledVersionsAreTheLockedOnes(): void
    {
        $lock = json_decode(file_get_contents(base_path('composer.lock')), true);
        $installed = json_decode(file_get_contents(base_path('vendor/composer/installed.json')), true);
        $versions = array_column($installed['packages'], 'version', 'name');

        foreach ($lock['packages'] as $package) {
            $this->assertSame($package['version'], $versions[$package['name']] ?? null, $package['name']);
        }
        $this->assertSame('v13.38.0', $versions['laravel/framework']);
    }
}
