<?php

// -----------------------------------------------------------
//  [*] HomeController — the home page
//
//  The site's front page, open to everyone: the about text
//  in the session's language and the partner logos. Built
//  on BaseController, whose middleware sets the language
//  and shares the member's data with the layout.
//
//  Used by:
//    - routes/web_user.php — the home route
// -----------------------------------------------------------


namespace App\Http\Controllers;

use Session;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Models\News;
use App\Models\Settings;
use App\Models\Langs;








// -----------------------------------------------------------
// HomeController
// -----------------------------------------------------------
//
// One page. Its route is named home, the target of every
// redirect that means back to the start.
//
// Used by:
//   - routes/web_user.php — the home route
// -----------------------------------------------------------

class HomeController extends BaseController
{






    // -----------------------------------------------------------
    // __construct
    // -----------------------------------------------------------
    //
    // BaseController's middleware, nothing more.
    //
    // Used by:
    //   - Laravel's router, before index
    // -----------------------------------------------------------

    public function __construct()
    {
        parent::__construct();
    }






    // -----------------------------------------------------------
    // index
    // -----------------------------------------------------------
    //
    // GET /
    //
    // The home page, welcome.blade.php, for everyone. The view
    // gets the about text — settings row 1, whose val1 holds
    // JSON keyed by language id, each entry a description and
    // a link — with the id of the session's language to pick
    // from it; with no row 1 at all, as on a fresh install,
    // the view skips the lookup (as PHP 7 did by itself) and
    // the about block stays empty. It also gets the three
    // newest articles of that language, never shown: the view
    // keeps its news section inside a Blade comment.
    //
    // Known bug (low): the view reads the about entry of the
    // language id unchecked (fed by HomeController.php:110,
    // read at welcome.blade.php:22), so in a language the text
    // has no entry for — one added after the text was last
    // saved, or one with no bei_lang row — the home page is a
    // 500 until an admin saves the about text again. Fix: fall
    // back to an empty entry when the language has none.
    // Pinned as home-about-missing.
    //
    // Used by:
    //   - layouts/main.blade.php — the logo and the home entry
    //     of the menu
    //   - emails/usernewpass.blade.php — the site address in
    //     the new-password mail
    //   - LoginController::logout, SettingsController::
    //     lang_switch and guest:mem (RedirectIfAuthenticated)
    //     — their redirects home
    // -----------------------------------------------------------

    public function index()
    {
        return view('welcome', [
            'latest_news'=>News::latest_news(3, Langs::getLangId(Session::get('lang'))),
            'about'=>json_decode(Settings::select("val1")->where(['id'=>1])->get()->pluck("val1")->first(),1),
            'lang_id'=>Langs::getLangId(Session::get('lang'))
        ]);
    }
}
