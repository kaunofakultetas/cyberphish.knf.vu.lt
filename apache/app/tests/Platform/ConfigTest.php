<?php

// -----------------------------------------------------------
//  [*] ConfigTest — the app's configuration at run time
//
//  The app reads its settings from the environment
//  docker-compose.yml gives the container and ships no
//  .env file of its own. Debug mode is off — a debug page
//  shows the code around every error and, with Ignition
//  installed, opened the door to remote code execution —
//  and stays off when APP_DEBUG is missing. The container
//  runs read-only like the production one: the app's code
//  cannot be changed at run time, and only the storage
//  folders the tmpfs mounts provide take writes.
//
//  Used by:
//    - PHPUnit — the Platform suite (runTests.sh)
// -----------------------------------------------------------


namespace Tests\Platform;

use Tests\TestCase;








// -----------------------------------------------------------
// ConfigTest
// -----------------------------------------------------------
//
// Reads the booted app's configuration and the filesystem
// around it.
//
// Used by:
//   - PHPUnit — the Platform suite (runTests.sh)
// -----------------------------------------------------------

class ConfigTest extends TestCase
{

    // The places the app writes to: sessions, compiled views,
    // the cache, its log and the uploads — tmpfs mounts and
    // bind mounts in production
    private const WRITABLE = [
        'storage/framework/sessions',
        'storage/framework/views',
        'storage/framework/cache',
        'storage/logs',
        'storage/app/public',
    ];

    // The app's code, which nothing at run time may change
    private const READ_ONLY = ['app', 'bootstrap', 'config', 'public', 'resources', 'routes', 'vendor'];






    // -----------------------------------------------------------
    // testDebugModeIsOff
    // -----------------------------------------------------------
    //
    // APP_DEBUG=false switches debug off, and config/app.php
    // defaults to off when the variable is missing.
    //
    // Used by:
    //   - PHPUnit — the Platform suite (runTests.sh)
    // -----------------------------------------------------------

    public function testDebugModeIsOff(): void
    {
        $this->assertFalse(config('app.debug'));

        $config = file_get_contents(config_path('app.php'));
        $this->assertStringContainsString("'debug' => (bool) env('APP_DEBUG', false)", $config);
    }






    // -----------------------------------------------------------
    // testTranslationFilesHoldEachKeyOnce
    // -----------------------------------------------------------
    //
    // A key that appears twice in a translation file is
    // decided by PHP, which keeps the later entry silently: the
    // Greek sign-up button once read "Κανω ΕΓΓΡΑΦΗ" that way
    // instead of "Εγγραφείτε". Each file holds every key of its
    // top level once.
    //
    // Used by:
    //   - PHPUnit — the Platform suite (runTests.sh)
    // -----------------------------------------------------------

    public function testTranslationFilesHoldEachKeyOnce(): void
    {
        $twice = [];
        foreach (glob(resource_path('lang') . '/*/*.php') as $file) {
            preg_match_all("/^    '([^']+)'\\s*=>/m", file_get_contents($file), $keys);
            foreach (array_keys(array_filter(array_count_values($keys[1]), function (int $count) {
                return $count > 1;
            })) as $key) {
                $twice[] = basename(dirname($file)) . '/' . basename($file) . ': ' . $key;
            }
        }

        $this->assertSame([], $twice);
    }






    // -----------------------------------------------------------
    // testNoEnvFileShips
    // -----------------------------------------------------------
    //
    // Secrets come from the container's environment, never
    // from a file baked into the image.
    //
    // Used by:
    //   - PHPUnit — the Platform suite (runTests.sh)
    // -----------------------------------------------------------

    public function testNoEnvFileShips(): void
    {
        $this->assertFileDoesNotExist(base_path('.env'));
        $this->assertNotEmpty(config('app.key'));
    }






    // -----------------------------------------------------------
    // testSessionCookieIsHttpOnly
    // -----------------------------------------------------------
    //
    // Scripts in the page cannot read the session cookie, and
    // other sites' forms do not carry it (SameSite lax). Its
    // Secure flag, which Laravel sets on HTTPS requests, is
    // checked in SessionTest::testSessionCookieIsSecure.
    //
    // Used by:
    //   - PHPUnit — the Platform suite (runTests.sh)
    // -----------------------------------------------------------

    public function testSessionCookieIsHttpOnly(): void
    {
        $this->assertTrue(config('session.http_only'));
        $this->assertSame('lax', config('session.same_site'));
    }






    // -----------------------------------------------------------
    // testAppCodeIsReadOnly
    // -----------------------------------------------------------
    //
    // Nothing the app runs can rewrite its own code, the way
    // production's read-only container keeps it.
    //
    // Used by:
    //   - PHPUnit — the Platform suite (runTests.sh)
    // -----------------------------------------------------------

    public function testAppCodeIsReadOnly(): void
    {
        foreach (self::READ_ONLY as $folder) {
            $this->assertFalse(is_writable(base_path($folder)), "$folder/ is writable");
        }
    }






    // -----------------------------------------------------------
    // testStorageFoldersTakeWrites
    // -----------------------------------------------------------
    //
    // The folders the app writes to are writable.
    //
    // Used by:
    //   - PHPUnit — the Platform suite (runTests.sh)
    // -----------------------------------------------------------

    public function testStorageFoldersTakeWrites(): void
    {
        foreach (self::WRITABLE as $folder) {
            $this->assertTrue(is_writable(base_path($folder)), "$folder/ is not writable");
        }
    }
}
