<?php

// -----------------------------------------------------------
//  [*] TemplatesTest — the templates and the tab icon as shipped
//
//  Every page the site and the panel show is built on one
//  of five layouts, and each links public/favicon.ico, the
//  round mark of the CyberPhish logo at 16, 32 and 48
//  pixels: the panel's layouts once linked a file that was
//  never there, the site's linked none, and the root icon
//  browsers fall back to was Laravel's empty placeholder,
//  so no tab showed an icon. The panel's buttons and icons
//  are mostly links to "#" whose onClick does the work:
//  opening a dialog, asking before a delete, posting a
//  status change. A link to "#" whose handler does not
//  return false still follows the link once the handler is
//  done, and the browser scrolls the page to its top —
//  behind the dialog that opens, or after a delete that was
//  cancelled.
//
//  Used by:
//    - PHPUnit — the Platform suite (runTests.sh)
// -----------------------------------------------------------


namespace Tests\Platform;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Tests\TestCase;








// -----------------------------------------------------------
// TemplatesTest
// -----------------------------------------------------------
//
// Reads the Blade files under resources/views and the icon
// as they are on disk; nothing here renders a page.
//
// Used by:
//   - PHPUnit — the Platform suite (runTests.sh)
// -----------------------------------------------------------

class TemplatesTest extends TestCase
{

    // The layouts every page of the site and the panel is
    // built on, under resources/views
    private const LAYOUTS = [
        'layouts/main.blade.php',
        'layouts/login.blade.php',
        'layouts/register.blade.php',
        'admin-panel/layouts/main.blade.php',
        'admin-panel/layouts/login.blade.php',
    ];






    // -----------------------------------------------------------
    // testHashLinksDoNotScrollToTheTop
    // -----------------------------------------------------------
    //
    // Every link to "#" with an onClick in the panel's
    // templates returns false from it. Fourteen dialog links
    // and fourteen delete icons once did not.
    //
    // Used by:
    //   - PHPUnit — the Platform suite (runTests.sh)
    // -----------------------------------------------------------

    public function testHashLinksDoNotScrollToTheTop(): void
    {
        $offenders = [];
        $checked = 0;
        $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(resource_path('views/admin-panel')));
        foreach ($files as $file) {
            if (!str_ends_with($file->getFilename(), '.blade.php')) {
                continue;
            }
            preg_match_all('/<a\b[^>]*>/i', file_get_contents($file->getPathname()), $tags);
            foreach ($tags[0] as $tag) {
                if (!str_contains($tag, 'href="#"') || !preg_match('/onclick="[^"]*"/i', $tag, $handler)) {
                    continue;
                }
                $checked++;
                if (!str_contains($handler[0], 'return false')) {
                    $offenders[] = $file->getFilename() . ': ' . $handler[0];
                }
            }
        }

        $this->assertGreaterThan(20, $checked, 'the scan found too few links');
        $this->assertSame([], $offenders);
    }






    // -----------------------------------------------------------
    // testEveryLayoutLinksTheTabIcon
    // -----------------------------------------------------------
    //
    // Each of the five layouts links /favicon.ico as the page's
    // icon, and no template still links the icon the panel's
    // layouts once named, which was never there.
    //
    // Used by:
    //   - PHPUnit — the Platform suite (runTests.sh)
    // -----------------------------------------------------------

    public function testEveryLayoutLinksTheTabIcon(): void
    {
        foreach (self::LAYOUTS as $layout) {
            $html = file_get_contents(resource_path('views/' . $layout));
            $this->assertMatchesRegularExpression('#<link rel="icon"[^>]*/favicon\.ico#', $html, "$layout links no tab icon");
        }

        $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(resource_path('views')));
        foreach ($files as $file) {
            if (str_ends_with($file->getFilename(), '.blade.php')) {
                $this->assertStringNotContainsString('assets/img/favicon', file_get_contents($file->getPathname()), $file->getFilename());
            }
        }
    }






    // -----------------------------------------------------------
    // testTabIconIsARealIcon
    // -----------------------------------------------------------
    //
    // public/favicon.ico is an icon file holding a PNG image
    // at 16 and at 32 pixels, the sizes a tab shows, where it
    // was once empty.
    //
    // Used by:
    //   - PHPUnit — the Platform suite (runTests.sh)
    // -----------------------------------------------------------

    public function testTabIconIsARealIcon(): void
    {
        $icon = file_get_contents(public_path('favicon.ico'));
        $this->assertGreaterThan(6, strlen($icon), 'the icon file is empty');

        $head = unpack('vreserved/vtype/vcount', $icon);
        $this->assertSame([0, 1], [$head['reserved'], $head['type']], 'not an icon file');

        $sizes = [];
        for ($i = 0; $i < $head['count']; $i++) {
            $entry = unpack('Cwidth/Cheight/x2/vplanes/vbits/Vlength/Voffset', $icon, 6 + 16 * $i);
            $this->assertSame("\x89PNG", substr($icon, $entry['offset'], 4), "image $i is not a PNG");
            $this->assertLessThanOrEqual(strlen($icon), $entry['offset'] + $entry['length']);
            $sizes[] = $entry['width'];
        }
        $this->assertContains(16, $sizes);
        $this->assertContains(32, $sizes);
    }
}
