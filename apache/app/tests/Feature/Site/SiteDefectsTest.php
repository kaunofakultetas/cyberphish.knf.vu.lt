<?php

// -----------------------------------------------------------
//  [*] SiteDefectsTest — known defects of the public pages
//
//  Each test states the behaviour the public pages should
//  have and pins the defect that breaks it today
//  (KnownDefects): the run reports them as incomplete,
//  tagged with their ids, until a fix makes one pass — then
//  the test fails as the cue to remove its pin and move it
//  into its home file.
//
//    cors-api-paths — every page under /api/ allows any
//      origin
//    ranks-tied-positions — equal totals get different
//      places, in an order MySQL picks
//    login-redirect-language — a member page's link under a
//      language opens the login form in English
//    cookie-notice-english — the cookie notice is English
//      in every language
//    html-lang-english — every page declares itself English
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
    // testApiPathsInviteNoOrigin
    // -----------------------------------------------------------
    //
    // config/cors.php gives Laravel's HandleCors the paths
    // api/* with every origin allowed, and its banner, like
    // Cors's, trusts that no route lives there. But {lang}
    // takes any first segment, so /api/news, /api/learn/…,
    // /api/ranks/… and, for a signed-in member,
    // /api/cp/learning-material are the site's own pages, and
    // each answers with Access-Control-Allow-Origin: * — the
    // invitation cors-wildcard took off every response. The
    // endpoint refuses a foreign Origin before Apache sees it,
    // so only Apache served directly shows it. Wanted: no
    // response names an allowed origin, under /api/ as on /.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testApiPathsInviteNoOrigin(): void
    {
        $member = $this->world->member();

        $news = $this->withHeaders(['Origin' => 'https://elsewhere.example'])->get('/api/news');
        $outline = $this->signInMember($member)->withHeaders(['Origin' => 'https://elsewhere.example'])->get('/api/cp/learning-material');

        $this->pinDefect('cors-api-paths', function () use ($news, $outline) {
            $news->assertHeaderMissing('Access-Control-Allow-Origin');
            $outline->assertHeaderMissing('Access-Control-Allow-Origin');
        });
    }






    // -----------------------------------------------------------
    // testTiedMembersShareTheirPlace
    // -----------------------------------------------------------
    //
    // Both public rankings number their rows with the loop's
    // counter, so members with the same total get different
    // places — one, two, three for three members who all hold
    // the best score — and which of them comes first is left
    // to MySQL, whose ORDER BY on the total breaks no tie: the
    // member who reached the score first can end up behind.
    // Ties are common: a simulation's best path is worth the
    // same to everyone who finds it, and so is a fully right
    // self-evaluation whose questions weigh alike. Wanted:
    // equal totals share a place, and a lower total comes
    // after them.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testTiedMembersShareTheirPlace(): void
    {
        $en = $this->world->language('en');
        $category = $this->world->lmCategory($en);
        $scenario = $this->world->scenario($en);
        $names = [];
        foreach (['first' => 80, 'second' => 80, 'behind' => 30] as $key => $points) {
            $member = $this->world->member();
            $names[$key] = $member->username;
            $this->world->selfEvalAttempt($member->id, $category, $en, ['points' => $points]);
            $this->world->scenarioAttempt($member->id, $scenario, $en, ['points' => $points * 5]);
        }

        // Each ranking's places by user name, read from its rows
        $places = [];
        foreach (['self-evaluation', 'simulations'] as $ranking) {
            preg_match_all('#<td>(\d+)</td>\s*<td>([^<]*)</td>#', $this->get("/en/ranks/$ranking")->assertOk()->getContent(), $rows, PREG_SET_ORDER);
            $places[$ranking] = [];
            foreach ($rows as $row) {
                $places[$ranking][trim($row[2])] = (int) $row[1];
            }
            $this->assertEqualsCanonicalizing(array_values($names), array_keys($places[$ranking]), "the $ranking ranking does not list the three members");
        }

        $this->pinDefect('ranks-tied-positions', function () use ($places, $names) {
            foreach ($places as $ranking => $place) {
                $this->assertSame($place[$names['first']], $place[$names['second']], "the $ranking ranking gives two members of equal points different places");
                $this->assertGreaterThan($place[$names['first']], $place[$names['behind']], "the $ranking ranking puts the lower total ahead");
            }
        });
    }






    // -----------------------------------------------------------
    // testLoginFormKeepsTheLanguageOfTheLinkFollowed
    // -----------------------------------------------------------
    //
    // The routes promise that a {lang} part naming a site
    // language switches the site to it, but BaseController
    // only does so once a controller runs, and auth:mem sends a
    // visitor to the login form before that. A visitor who
    // follows a Lithuanian link to a member page — a bookmark
    // of the learning material whose session has run out, a
    // link a teacher shared — gets the login form in English,
    // the language of a fresh session; only the page after the
    // sign-in turns Lithuanian. Wanted: the login form in the
    // language of the link that led there.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testLoginFormKeepsTheLanguageOfTheLinkFollowed(): void
    {
        $this->world->language('lt');

        $login = $this->followingRedirects()->get('/lt/cp/learning-material')->assertOk()->getContent();
        $this->assertStringContainsString('name="password"', $login, 'the member page did not lead to the login form');

        $this->pinDefect('login-redirect-language', function () use ($login) {
            $this->assertStringContainsString(e(trans('main.welcome_back', [], 'lt')), $login, 'the login form a Lithuanian link led to is not in Lithuanian');
        });
    }






    // -----------------------------------------------------------
    // testCookieNoticeSpeaksThePagesLanguage
    // -----------------------------------------------------------
    //
    // The cookie notice on every page of the main layout is
    // written into the layout's script in English — its
    // message, its policy link's label and the default
    // dismiss button — so a visitor of the Lithuanian,
    // Latvian, Estonian or Greek site meets it in English on
    // every page, the one text of the member side that skips
    // the translation files. Only its link follows the
    // visitor's language. Wanted: the notice in the page's
    // language.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testCookieNoticeSpeaksThePagesLanguage(): void
    {
        $notices = [];
        foreach (['en', 'lt', 'lv', 'ee', 'gr'] as $locale) {
            $html = $this->get("/$locale/news")->assertOk()->getContent();
            $found = preg_match('#["\']?message["\']?\s*:\s*"([^"]*)".*?["\']?link["\']?\s*:\s*"([^"]*)"#s', $html, $notice);
            $this->assertSame(1, $found, "the $locale news list has no cookie notice");
            $notices[$locale] = [$notice[1], $notice[2]];
        }

        $this->pinDefect('cookie-notice-english', function () use ($notices) {
            foreach (['lt', 'lv', 'ee', 'gr'] as $locale) {
                $this->assertNotSame($notices['en'], $notices[$locale], "the $locale pages show the English cookie notice");
            }
        });
    }






    // -----------------------------------------------------------
    // testPagesDeclareTheirLanguage
    // -----------------------------------------------------------
    //
    // The html tag of all three layouts — the main one, the
    // login form's and the sign-up form's — names English
    // whatever the session's language, so a Lithuanian,
    // Latvian, Estonian or Greek page tells screen readers to
    // read it with English pronunciation and browsers to offer
    // a translation from English. Wanted: the page's language,
    // by its ISO code — el for Greek and et for Estonian.
    //
    // Used by:
    //   - PHPUnit — the Feature suite (runTests.sh)
    // -----------------------------------------------------------

    public function testPagesDeclareTheirLanguage(): void
    {
        $tags = [];
        foreach (['lt', 'lv', 'ee', 'gr'] as $locale) {
            foreach (["/$locale/news" => 'news list', '/login' => 'login form', '/register' => 'sign-up form'] as $url => $page) {
                $this->assertSame(1, preg_match('#<html[^>]*>#', $this->get($url)->assertOk()->getContent(), $tag), "the $page of a $locale session has no html tag");
                $tags[$locale][$page] = $tag[0];
            }
        }

        $this->pinDefect('html-lang-english', function () use ($tags) {
            foreach (['lt' => 'lt', 'lv' => 'lv', 'ee' => 'et', 'gr' => 'el'] as $locale => $iso) {
                foreach ($tags[$locale] as $page => $tag) {
                    $this->assertMatchesRegularExpression('#\blang="' . $iso . '"#', $tag, "the $page of a $locale session declares another language");
                }
            }
        });
    }
}
