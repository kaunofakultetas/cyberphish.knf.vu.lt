<?php

// -----------------------------------------------------------
//  [*] PlatformDefectsTest — known image defects, pinned
//
//  Each test states the behaviour the image should have and
//  pins the defect that breaks it today (KnownDefects): the
//  run reports them as incomplete, tagged with their ids,
//  until a fix makes one pass — then the test fails as the
//  cue to remove its pin and move it into its home file.
//
//    seed-admin-password  — mysql/init.sql seeds the main
//                           admin with the password "admin"
//    remoteip-trust-range — Apache trusts public addresses
//                           to name the visitor
//
//  Used by:
//    - PHPUnit — the Platform suite (runTests.sh)
// -----------------------------------------------------------


namespace Tests\Platform;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;








// -----------------------------------------------------------
// PlatformDefectsTest
// -----------------------------------------------------------
//
// One pinned defect per test, named in the header.
//
// Used by:
//   - PHPUnit — the Platform suite (runTests.sh)
// -----------------------------------------------------------

class PlatformDefectsTest extends TestCase
{

    // The virtual host the Dockerfile installs
    private const VHOST = '/etc/apache2/sites-available/000-default.conf';

    // The private address ranges a trusted proxy may sit in
    private const PRIVATE_RANGES = ['10.0.0.0/8', '172.16.0.0/12', '192.168.0.0/16'];






    // -----------------------------------------------------------
    // testSeededAdminHasNoKnownPassword
    // -----------------------------------------------------------
    //
    // init.sql gives the main admin admin@knf.vu.lt the
    // password "admin" — every fresh deployment opens with a
    // main admin anyone can guess. (The production database
    // predates it: none of its managers has the seeded hash.)
    // Wanted: no known password in the seed.
    //
    // Used by:
    //   - PHPUnit — the Platform suite (runTests.sh)
    // -----------------------------------------------------------

    public function testSeededAdminHasNoKnownPassword(): void
    {
        $hash = DB::table('bei_managers')->where('id', 1)->value('password');

        $this->pinDefect('seed-admin-password', function () use ($hash) {
            $this->assertFalse(Hash::check('admin', $hash), 'the seeded admin\'s password is "admin"');
        });
    }






    // -----------------------------------------------------------
    // testOnlyPrivateProxiesAreTrusted
    // -----------------------------------------------------------
    //
    // RemoteIPInternalProxy trusts 172.0.0.0/8 to name the
    // visitor in X-Forwarded-For, yet only 172.16.0.0/12 of it
    // is private: a client on a public 172.x address — Azure
    // has many — can put any address it likes into the access
    // log, the log the compromise review read. Wanted: every
    // trusted range inside the private ones.
    //
    // Used by:
    //   - PHPUnit — the Platform suite (runTests.sh)
    // -----------------------------------------------------------

    public function testOnlyPrivateProxiesAreTrusted(): void
    {
        preg_match_all('#^RemoteIPInternalProxy\s+(.+)$#m', file_get_contents(self::VHOST), $lines);
        $ranges = preg_split('/\s+/', trim(implode(' ', $lines[1])));
        $this->assertNotEmpty($ranges);

        $this->pinDefect('remoteip-trust-range', function () use ($ranges) {
            foreach ($ranges as $range) {
                $this->assertTrue($this->isPrivate($range), "$range is trusted but not private");
            }
        });
    }






    // -----------------------------------------------------------
    // isPrivate
    // -----------------------------------------------------------
    //
    // True when the CIDR range lies wholly inside one of the
    // PRIVATE_RANGES.
    //
    // Used by:
    //   - testOnlyPrivateProxiesAreTrusted (above)
    // -----------------------------------------------------------

    private function isPrivate(string $range): bool
    {
        list($address, $bits) = array_pad(explode('/', $range), 2, '32');
        foreach (self::PRIVATE_RANGES as $private) {
            list($network, $privateBits) = explode('/', $private);
            $mask = -1 << (32 - (int) $privateBits);
            if ((int) $bits >= (int) $privateBits && (ip2long($address) & $mask) === (ip2long($network) & $mask)) {
                return true;
            }
        }
        return false;
    }
}
