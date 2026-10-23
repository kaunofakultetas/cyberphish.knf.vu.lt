<?php

// -----------------------------------------------------------
//  [*] NewsController — the news articles of the admin panel
//
//  Main admins publish the site's news here: the articles
//  listed by language, the form that writes one, and the
//  deletes. An article's slug comes from its title and its
//  author is the signed-in manager; its picture is moved to
//  storage/app/public/feat under a random 40-character name
//  and served as /upload/feat/… through the public/upload
//  link the Dockerfile makes. Production mounts
//  storage/app/public read-only (docker-compose.yml), so
//  there a new picture cannot be stored — the move fails, a
//  500 — and removing one only empties feat_img, the file
//  staying. Every route sits behind auth.admin:man and
//  perms: main admins only.
//
//  RouteServiceProvider loads routes/web_admin.php before
//  the public {lang}/news routes, which would otherwise take
//  admin-panel for a language and answer the list and the
//  edit form with the public pages. The posts are safe
//  either way: the public routes answer GET only.
//
//  Used by:
//    - routes/web_admin.php — the admin-panel/news routes
// -----------------------------------------------------------


namespace App\Http\Controllers\Admin;

use Carbon\Carbon;
use App\Http\Controllers\Controller;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\URL;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use App\Http\Controllers\SettingsController;
use App\Models\Langs as Langs;
use App\Models\News as News;
use Illuminate\Support\Facades\Validator;








// -----------------------------------------------------------
// NewsController
// -----------------------------------------------------------
//
// The panel side of bei_news. The public side, the
// {lang}/news pages, is App\Http\Controllers\NewsController.
//
//   index          — the articles, one tab per language
//   form           — the new-article and edit form
//   create, update — the form's two posts
//   delete         — removes an article
//   delete_picture — removes an article's picture
//
// Used by:
//   - routes/web_admin.php — the admin-panel/news routes
// -----------------------------------------------------------

class NewsController extends BaseController
{






    // -----------------------------------------------------------
    // __construct
    // -----------------------------------------------------------
    //
    // Nothing of its own: BaseController's constructor adds
    // the auth:man middleware and shares the session's main
    // and country flags with every view of the panel.
    //
    // Used by:
    //   - Laravel's router, for every route of this controller
    // -----------------------------------------------------------

    public function __construct()
    {
        parent::__construct();
    }






    // -----------------------------------------------------------
    // index
    // -----------------------------------------------------------
    //
    // GET /admin-panel/news
    //
    // The news list: every article, newest first, grouped by
    // language id for admin-panel/news.blade.php, which shows
    // one tab per language with an edit and a delete link on
    // each row.
    //
    // Used by:
    //   - admin-panel/sidebar.blade.php — the News menu entry
    //   - admin-panel/news_form.blade.php — the News breadcrumb
    //   - create (below) — its redirect, by the route name news
    // -----------------------------------------------------------

    public function index(Request $request)
    {
        $langlist = Langs::all()->pluck('name', 'id')->toArray();

        if(count(News::all()->toArray()) > 0){

            $arr1 = News::orderByDesc('id')->get()->toArray();

            $arr = array();
            foreach($arr1 as $k => $v){
                $arr[$v['lang_id']][] = $arr1[$k];
            }

        } else {

            $arr = array();

        }

        return view('admin-panel.news', ['recordlist' => $arr, 'langlist'=>$langlist]);
    }






    // -----------------------------------------------------------
    // form
    // -----------------------------------------------------------
    //
    // GET /admin-panel/news/new
    // GET /admin-panel/news/edit/{uid?}
    //
    // The article form, admin-panel/news_form.blade.php:
    // empty and posting to news/create without an id; with
    // one, filled from the article — a query per field — and
    // posting to news/update. While the article has a picture
    // the form shows it with a remove link instead of the
    // upload field.
    //
    // Used by:
    //   - admin-panel/news.blade.php — the Create News Article
    //     button and each row's edit icon
    // -----------------------------------------------------------

    public function form(Request $request, $uid=null)
    {
        $langlist = Langs::all()->pluck('name', 'id')->toArray();

        if($uid == null){

            return view('admin-panel.news_form', ['url'=>env('APP_URL').'/admin-panel/news/create', 'langlist'=>$langlist, 'feat_img'=>'']);

        } else {

            return view('admin-panel.news_form',
                [
                    'url'=>env('APP_URL').'/admin-panel/news/update',
                    'uid'=>$uid,
                    'title'=>News::where(['id'=>$uid])->pluck('title')->first(),
                    'feat_img'=>News::where(['id'=>$uid])->pluck('feat_img')->first(),
                    'content'=>News::where(['id'=>$uid])->pluck('content')->first(),
                    'lang_id'=>News::where(['id'=>$uid])->pluck('lang_id')->first(),
                    'langlist'=>$langlist
                ]);

        }
    }






    // -----------------------------------------------------------
    // create
    // -----------------------------------------------------------
    //
    // POST /admin-panel/news/create
    //
    // Saves a new article: the title — required, at most 255
    // characters — and its slug, the body and the language as
    // posted, the picture when one comes (jpeg, png, jpg, gif
    // or svg, at most 2048 KB), the time, and the signed-in
    // manager as its author. Answers a redirect to the news
    // route on the article's language tab — the panel's list,
    // the only route that keeps that name, as the public list
    // has none (web_user.php).
    //
    // An empty editor arrives as NULL
    // (ConvertEmptyStringsToNull) and is stored as an empty
    // body, the NOT NULL column refusing NULL; a missing or
    // unknown language goes back with a form error.
    //
    // The picture may not be an SVG, though svg is among the
    // allowed types (NewsController.php:226): Laravel 13's
    // image rule refuses SVG unless given allow_svg. Under
    // Laravel 7 SVGs went through (pinned as news-svg-picture
    // until the upgrade released it).
    //
    // Used by:
    //   - admin-panel/news_form.blade.php — the form, opened
    //     without an id
    // -----------------------------------------------------------

    public function create(Request $request)
    {
        $validatedData = $request->validate([
            'title' => [ 'required', 'min:1', 'max:255' ],
            'feat' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif,svg', 'max:2048'],
            'lang_id' => ['required', 'exists:bei_lang,id'],
        ]);

        $addRecord = new News;
        $addRecord->title = $validatedData['title'];
        $addRecord->content = $request->get('content') ?? '';
        $addRecord->lang_id = $request->get('lang_id');
        $addRecord->alias = Str::slug($validatedData['title']);

        if ($request->hasFile('feat')) {

            $imageName = Str::random(40).'.'.$request->feat->extension();
            $request->feat->move(storage_path('app/public/feat'), $imageName);
            $addRecord->feat_img = 'feat/'.$imageName;

        }

        $addRecord->created = Carbon::now();
        $addRecord->created_by = Auth::guard('man')->id();
        $addRecord->save();

        return Redirect::to(route('news') . "#a".$request->get('lang_id'))->withSuccess("Created!");
    }






    // -----------------------------------------------------------
    // update
    // -----------------------------------------------------------
    //
    // POST /admin-panel/news/update
    //
    // Saves the edit form over the article named by uid: the
    // title and its slug, the body and the language as posted,
    // and the picture when one comes; answers a redirect back
    // to the form. An empty body is stored as an empty string,
    // as in create, but by MySQL rather than the code:
    // config/database.php turns strict mode off, and outside
    // it an UPDATE that sets a NOT NULL column to NULL stores
    // the column's default with a warning.
    //
    // Used by:
    //   - admin-panel/news_form.blade.php — the form, opened
    //     with an id
    // -----------------------------------------------------------

    public function update(Request $request)
    {
        $validatedData = $request->validate([
            'uid' => [ 'required', 'integer' ],
            'title' => [ 'required', 'min:1', 'max:255' ],
            'feat' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif,svg', 'max:2048'],
        ]);

        $update = [
            'title'=>$validatedData['title'],
            'content'=>$request->get('content'),
            'lang_id'=>$request->get('lang_id'),
            'alias'=>Str::slug($validatedData['title']),
        ];

        if ($request->hasFile('feat')) {

            $imageName = Str::random(40).'.'.$request->feat->extension();
            $request->feat->move(storage_path('app/public/feat'), $imageName);
            $update['feat_img'] = 'feat/'.$imageName;

        }

        News::where(['id' => $validatedData['uid']])->update($update);

        return back()->withSuccess("Updated!");
    }






    // -----------------------------------------------------------
    // delete
    // -----------------------------------------------------------
    //
    // POST /admin-panel/news/delete/{uid}
    //
    // Deletes the article and answers a redirect to the page
    // before, on the article's language tab. Its picture file
    // stays in storage/app/public/feat.
    //
    // Used by:
    //   - admin-panel/news.blade.php — each row's delete icon,
    //     through Confirm_Dialog (public/js/admn.js)
    // -----------------------------------------------------------

    public function delete(Request $request, $uid)
    {
        $lang_id = News::where(['id'=>$uid])->pluck('lang_id')->first();

        News::destroy($uid);

        return Redirect::to(URL::previous() . "#a".$lang_id)->withSuccess("Removed!");
    }






    // -----------------------------------------------------------
    // delete_picture
    // -----------------------------------------------------------
    //
    // POST /admin-panel/news/delete_picture/{uid}
    //
    // Unlinks the article's picture from storage/app/public
    // and empties feat_img, so the form offers an upload
    // again; answers a redirect back to the form. An article
    // without a picture, or an unknown id, unlinks nothing,
    // and neither does a read-only folder, as production
    // mounts it — feat_img is emptied all the same.
    //
    // Used by:
    //   - admin-panel/news_form.blade.php — the remove icon
    //     under the picture, through Confirm_Dialog
    //     (public/js/admn.js)
    // -----------------------------------------------------------

    public function delete_picture($uid)
    {
        $feat_img = News::where(['id'=>$uid])->pluck('feat_img')->first();
        $picture = storage_path('app/public').'/'.$feat_img;

        // Without a picture the path is the folder itself
        if($feat_img != '' && is_file($picture) && is_writable(dirname($picture))){
            unlink($picture);
        }

        News::where(['id' => $uid])->update(['feat_img'=>'']);

        return back()->withSuccess("Removed!");
    }
}
