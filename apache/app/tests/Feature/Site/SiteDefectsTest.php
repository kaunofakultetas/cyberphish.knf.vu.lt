<?php

// -----------------------------------------------------------
//  [*] SiteDefectsTest — known defects of the public pages
//
//  Each test states the behaviour the public pages should
//  have and pins the defect that breaks it today
//  (KnownDefects): the run reports them as incomplete,
//  tagged with their ids, until a fix makes one pass — then
//  the test fails as the cue to remove its pin and move it
//  into PublicPagesTest.
//
//    news-empty-picture-broken-image — a news article without
//                                     a picture shows a broken
//                                     image
//
//  Used by:
//    - PHPUnit — the Feature suite (runTests.sh)
// -----------------------------------------------------------


namespace Tests\Feature\Site;

use Tests\FeatureTestCase;








// -----------------------------------------------------------
// SiteDefectsTest
// -----------------------------------------------------------
//
// One pinned defect per test, named in the header.
//
// Used by:
//   - PHPUnit — the Feature suite (runTests.sh)
// -----------------------------------------------------------

class SiteDefectsTest extends FeatureTestCase
{






    // -----------------------------------------------------------
    // testNewsWithoutAPictureShowsNoBrokenImage
    // -----------------------------------------------------------
    //
    // The article page draws the picture whenever feat_img is
    // set at all, and the empty string the panel's
    // remove-picture link leaves is set; the news list draws
    // every card's picture unchecked, NULL included. An
    // article without a picture shows an image of the upload
    // folder itself — a broken image. Wanted: no picture, no
    // image.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testNewsWithoutAPictureShowsNoBrokenImage(): void
    {
        $en = $this->world->language('en');
        $removed = $this->world->news($en, ['title' => 'Its picture removed', 'feat_img' => '']);
        $this->world->news($en, ['title' => 'Never had a picture', 'feat_img' => null]);

        $pages = [
            'article' => $this->get("/en/news/its-picture-removed/$removed")->assertOk()->getContent(),
            'list'    => $this->get('/en/news')->assertOk()->getContent(),
        ];

        $this->pinDefect('news-empty-picture-broken-image', function () use ($pages) {
            foreach ($pages as $page => $html) {
                $this->assertStringNotContainsString('/upload/"', $html, "the $page shows a broken image");
            }
        });
    }
}
