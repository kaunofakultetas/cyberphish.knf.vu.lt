<?php

// -----------------------------------------------------------
//  [*] NewsController — the public news list and articles
//
//  The news pages anyone can open, in the language of the
//  session — which BaseController's middleware sets from the
//  URL's {lang} part when it is one of the five, and keeps
//  otherwise. The articles live in bei_news and are written
//  in the admin panel (Admin\NewsController). Nothing in
//  the site's pages links to the list any more: its only
//  link sat in the home page's news section, now inside a
//  Blade comment.
//
//  Used by:
//    - routes/web_user.php — the news list and news article
//      routes
// -----------------------------------------------------------


namespace App\Http\Controllers;

use Session;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use App\Models\Langs;
use App\Models\News;








// -----------------------------------------------------------
// NewsController
// -----------------------------------------------------------
//
// Not the panel's controller of the same name,
// Admin\NewsController, which writes the articles — though
// the routes here answer some of its URLs (see index and
// content).
//
//   index   — the list, twelve articles a page
//   content — one article
//
// Used by:
//   - routes/web_user.php — the news list and news article
//     routes
// -----------------------------------------------------------

class NewsController extends BaseController
{






    // -----------------------------------------------------------
    // __construct
    // -----------------------------------------------------------
    //
    // BaseController's middleware, nothing more.
    //
    // Used by:
    //   - Laravel's router, before index and content
    // -----------------------------------------------------------

    public function __construct()
    {
        parent::__construct();
    }






    // -----------------------------------------------------------
    // index
    // -----------------------------------------------------------
    //
    // GET /{lang}/news
    //
    // The news list, news.blade.php, for everyone: the
    // articles of the session's language in id order — the
    // oldest first, the query names no order — twelve a page,
    // sliced by hand from the full list into a paginator. A
    // missing or non-numeric page number means page one; an
    // empty list goes to the view as an empty array. The same
    // query runs twice, once to count and once to fetch.
    // Because web_user.php is loaded before web_admin.php and
    // {lang} takes any value, this route also answers the
    // panel's GET /admin-panel/news — pinned as
    // admin-news-shadowed, a defect of the route order rather
    // than of this method.
    //
    // Known bug (low): the paginator's path is the bare /news
    // (NewsController.php:142), a route web_user.php no
    // longer registers, so every page link under the list is
    // a 404 and articles past the first twelve can be reached
    // only by typing the page number into the address. Fix:
    // build the path from the language's own list URL.
    // Pinned as news-page-links.
    //
    // Used by:
    //   - nothing links to it at the moment: the only link,
    //     the more-news button of welcome.blade.php, sits in
    //     the Blade comment that switches the home page's
    //     news section off
    // -----------------------------------------------------------

    public function index(Request $request)
    {
        if(count(News::where(['lang_id'=>Langs::getLangId(Session::get('lang'))])->get()->toArray()) > 0){

            $arr = News::where(['lang_id'=>Langs::getLangId(Session::get('lang'))])->get()->toArray();

        } else {

            $arr = array();

        }

        if (empty($request->get('page')) || ! is_numeric($request->get('page'))) {

            $curr_page = 1;
        } else {

            $curr_page = $request->get('page');
        }

        $perPage = 12;

        $currentItems = array_slice($arr, $perPage * ($curr_page - 1), $perPage);

        // The page links lead to /news, which no route answers
        // (the Known bug above)
        $paginator = new LengthAwarePaginator($currentItems, count($arr), $perPage, $curr_page, [
            'path' => url('news')
        ]);

        $paginator = $paginator->appends('page', request('page'));

        if (count($arr) == 0) {

            $arr = array();
            $paginator = array();
        }

        return view('news', [
            'recordlist' => $paginator
        ]);
    }






    // -----------------------------------------------------------
    // content
    // -----------------------------------------------------------
    //
    // GET /{lang}/news/{slug}/{uid}
    //
    // One article, news_content.blade.php, found by its id
    // alone: NewsExists (news.exists) answers an id that is
    // not numeric or matches no bei_news row with a 404, and
    // neither {lang} nor the slug is read here — an article
    // opens under any language and any slug. The view gets
    // the row as an array. Like index, this route also
    // answers three of the panel's URLs — its edit, delete
    // and remove-picture links under /admin-panel/news —
    // with the public article (admin-news-shadowed, the route
    // order's defect).
    //
    // Used by:
    //   - news.blade.php — the read button of every article on
    //     the list; the home page's article cards in
    //     welcome.blade.php are switched off
    // -----------------------------------------------------------

    public function content($lang, $slug, $uid)
    {
        return view('news_content', [
            'content'=>News::where(['id'=>$uid])->get()->first()->toArray()
        ]);
    }
}
