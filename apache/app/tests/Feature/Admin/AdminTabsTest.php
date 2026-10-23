<?php

// -----------------------------------------------------------
//  [*] AdminTabsTest — the tabs of the panel's pages
//
//  Eight pages of the panel show their content in tabs —
//  the dashboard, news, learning material and simulations a
//  tab per language, the settings a tab per section — and
//  admn.js keeps the open tab in the URL's hash, so a
//  reload, or a save that comes back to its language's tab,
//  opens the same tab. The browser scrolls to the element a
//  hash names, so no element may carry a tab's hash as its
//  id: when each pane did, every switch and every return
//  slid the page's title and tabs under the top bar. A tab
//  link's href is the hash, its data-target the pane.
//
//  Used by:
//    - PHPUnit — the Feature suite (runTests.sh)
// -----------------------------------------------------------


namespace Tests\Feature\Admin;

use Dom\HTMLDocument;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\FeatureTestCase;








// -----------------------------------------------------------
// AdminTabsTest
// -----------------------------------------------------------
//
// A main admin opens each tabbed page, which reaches all
// eight; the pages are read with PHP's HTML5 parser.
//
// Used by:
//   - PHPUnit — the Feature suite (runTests.sh)
// -----------------------------------------------------------

class AdminTabsTest extends FeatureTestCase
{






    // -----------------------------------------------------------
    // testTabHashNamesNoElement
    // -----------------------------------------------------------
    //
    // Every tab of the page's tab bar has a hash no element
    // of the page carries as its id, so neither opening the
    // tab nor arriving with its hash scrolls the page, and a
    // data-target that names a pane, which the tab opens.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    #[DataProvider('tabbedPages')]
    public function testTabHashNamesNoElement(string $page): void
    {
        $html = $this->signInManager($this->world->manager())->get($page)->assertOk()->getContent();
        $document = HTMLDocument::createFromString($html, LIBXML_NOERROR);

        $tabs = $document->querySelectorAll('#TTAB a[data-toggle="tab"]');
        $this->assertGreaterThan(0, count($tabs), 'the page shows no tabs');
        foreach ($tabs as $tab) {
            $hash = $tab->getAttribute('href');
            $this->assertNull($document->getElementById(substr($hash, 1)), "the hash $hash names an element, which the browser scrolls to");

            $pane = $document->getElementById(substr((string) $tab->getAttribute('data-target'), 1));
            $this->assertNotNull($pane, "the tab $hash names no pane to open");
            $this->assertStringContainsString('tab-pane', $pane->getAttribute('class'));
        }
    }






    // -----------------------------------------------------------
    // tabbedPages
    // -----------------------------------------------------------
    //
    // The eight pages of the panel with a tab bar.
    //
    // Used by:
    //   - testTabHashNamesNoElement (above)
    // -----------------------------------------------------------

    public static function tabbedPages(): array
    {
        $pages = [
            '/admin-panel/dashboard',
            '/admin-panel/news',
            '/admin-panel/lm/categories',
            '/admin-panel/lm/content',
            '/admin-panel/admin_simulations',
            '/admin-panel/admin_simulations/categories',
            '/admin-panel/admin_simulations/attributes',
            '/admin-panel/settings/other',
        ];
        return array_combine($pages, array_map(function (string $page) {
            return [$page];
        }, $pages));
    }
}
