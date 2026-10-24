<?php

// -----------------------------------------------------------
//  [*] InfoController — the information pages of the panel
//
//  Main admins keep the site's information pages here, the
//  static pages visitors open at {lang}/page/{slug}/{uid}:
//  a list of 25 to a page, the form that writes a page —
//  its slug comes from the title — and the delete. Every
//  route sits behind auth.admin:man and perms: main admins
//  only.
//
//  Used by:
//    - routes/web_admin.php — the admin-panel/information
//      routes
// -----------------------------------------------------------


namespace App\Http\Controllers\Admin;

use Carbon\Carbon;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use App\Http\Controllers\SettingsController;
use App\Models\Information as Information;
use App\Models\Langs as Langs;
use Illuminate\Support\Facades\Validator;








// -----------------------------------------------------------
// InfoController
// -----------------------------------------------------------
//
// The panel side of bei_information; the public side, the
// page visitors read, is App\Http\Controllers\InfoController.
//
//   index          — the pages, 25 to a page
//   form           — the new-page and edit form
//   create, update — the form's two posts
//   delete         — removes a page
//
// Used by:
//   - routes/web_admin.php — the admin-panel/information
//     routes
// -----------------------------------------------------------

class InfoController extends BaseController
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
    // GET /admin-panel/information
    //
    // The list of information pages, newest first, each with
    // the name of its language, for
    // admin-panel/information.blade.php. The whole table is
    // read and cut into pages of 25 in PHP; a page number that
    // is not a whole number from 1 means the first page
    // (SettingsController::page_number), and an empty table
    // hands the view an empty array instead of a paginator.
    //
    // Used by:
    //   - admin-panel/sidebar.blade.php — the Information menu
    //     entry
    //   - admin-panel/information.blade.php — its page links
    //   - admin-panel/information_form.blade.php — the
    //     Information breadcrumb
    //   - create (below) — its redirect, by the route name
    //     information
    // -----------------------------------------------------------

    public function index(Request $request)
    {
        $langlist = Langs::all()->pluck('name', 'id')->toArray();

        if(count(Information::all()->toArray()) > 0){

            $arr = Information::select('bei_information.*', DB::raw('bei_lang.`name` AS lang_name'))
            ->leftJoin('bei_lang', 'bei_information.lang_id', '=', 'bei_lang.id')->orderByDesc('id')->get()->toArray();

        } else {

            $arr = array();

        }

        $perPage = 25;

        $curr_page = SettingsController::page_number($request->get('page'), count($arr), $perPage);

        $currentItems = array_slice($arr, $perPage * ($curr_page - 1), $perPage);

        $paginator = new LengthAwarePaginator($currentItems, count($arr), $perPage, $curr_page, [
            'path' => url('admin-panel/information')
        ]);

        $paginator = $paginator->appends('page', request('page'));

        if (count($arr) == 0) {

            $arr = array();
            $paginator = array();
        }

        return view('admin-panel.information', ['recordlist' => $paginator, 'langlist'=>$langlist]);
    }






    // -----------------------------------------------------------
    // form
    // -----------------------------------------------------------
    //
    // GET /admin-panel/information/new
    // GET /admin-panel/information/edit/{uid?}
    //
    // The page form, admin-panel/information_form.blade.php:
    // empty and posting to information/create without an id;
    // with one, filled from the page — a query per field —
    // and posting to information/update.
    //
    // Used by:
    //   - admin-panel/information.blade.php — the Create Info
    //     Page button and each row's edit icon
    // -----------------------------------------------------------

    public function form(Request $request, $uid=null)
    {
        $langlist = Langs::all()->pluck('name', 'id')->toArray();

        if($uid == null){

            return view('admin-panel.information_form', ['url'=>env('APP_URL').'/admin-panel/information/create', 'langlist'=>$langlist]);

        } else {

            return view('admin-panel.information_form',
                [
                    'url'=>env('APP_URL').'/admin-panel/information/update',
                    'uid'=>$uid,
                    'title'=>Information::where(['id'=>$uid])->pluck('title')->first(),
                    'content'=>Information::where(['id'=>$uid])->pluck('content')->first(),
                    'lang_id'=>Information::where(['id'=>$uid])->pluck('lang_id')->first(),
                    'alias'=>Information::where(['id'=>$uid])->pluck('alias')->first(),
                    'langlist'=>$langlist
                ]);

        }
    }






    // -----------------------------------------------------------
    // create
    // -----------------------------------------------------------
    //
    // POST /admin-panel/information/create
    //
    // Saves a new page: the title — required, at most 255
    // characters — and its slug, the body and the language as
    // posted. An empty editor arrives as NULL
    // (ConvertEmptyStringsToNull) and is stored as an empty
    // body, the NOT NULL column refusing NULL; a missing or
    // unknown language goes back with a form error, and so
    // does a title or body sent as a list, which once reached
    // the insert, a 500. Answers a redirect to the list — the
    // list itself, whatever page an auth middleware parked in
    // the session.
    //
    // Used by:
    //   - admin-panel/information_form.blade.php — the form,
    //     opened without an id
    // -----------------------------------------------------------

    public function create(Request $request)
    {
        $validatedData = $request->validate([
            'title' => [ 'required', 'string', 'min:1', 'max:255' ],
            'content' => [ 'nullable', 'string' ],
            'lang_id' => ['required', 'exists:bei_lang,id'],
        ]);

        $addRecord = new Information;
        $addRecord->title = $validatedData['title'];
        $addRecord->content = $request->get('content') ?? '';
        $addRecord->lang_id = $request->get('lang_id');
        $addRecord->alias = Str::slug($validatedData['title']);
        $addRecord->save();

        return redirect(route('information'))->withSuccess("Created!");
    }






    // -----------------------------------------------------------
    // update
    // -----------------------------------------------------------
    //
    // POST /admin-panel/information/update
    //
    // Saves the edit form over the page named by uid: the
    // title and its slug, the body and the language as posted
    // — a language that exists, checked as in create, or the
    // form comes back with the validator's message. A page
    // deleted meanwhile goes back with an error, where the
    // edit once answered "Updated!" over no row. Answers a
    // redirect back to the form. An empty body is stored as an
    // empty string, as in create, but by MySQL rather than the
    // code: config/database.php turns strict mode off, and
    // outside it an UPDATE setting a NOT NULL column to NULL
    // stores the column's default with a warning.
    //
    // Used by:
    //   - admin-panel/information_form.blade.php — the form,
    //     opened with an id
    // -----------------------------------------------------------

    public function update(Request $request)
    {
        $validatedData = $request->validate([
            'uid' => [ 'required', 'integer' ],
            'title' => [ 'required', 'string', 'min:1', 'max:255' ],
            'content' => [ 'nullable', 'string' ],
            'lang_id' => ['required', 'exists:bei_lang,id'],
        ]);

        if (Information::where(['id' => $validatedData['uid']])->count() == 0) {
            return back()->withErrors("The page no longer exists!");
        }

        $update = [
            'title'=>$validatedData['title'],
            'content'=>$request->get('content'),
            'alias'=>Str::slug($validatedData['title']),
            'lang_id'=>$validatedData['lang_id']
        ];

        Information::where(['id' => $validatedData['uid']])->update($update);

        return back()->withSuccess("Updated!");
    }






    // -----------------------------------------------------------
    // delete
    // -----------------------------------------------------------
    //
    // POST /admin-panel/information/delete/{uid}
    //
    // Deletes the page and answers a redirect back to the list.
    //
    // Used by:
    //   - admin-panel/information.blade.php — each row's
    //     delete icon, through Confirm_Dialog
    //     (public/js/admn.js)
    // -----------------------------------------------------------

    public function delete(Request $request, $uid)
    {
        Information::destroy($uid);

        return back()->withSuccess("Removed!");
    }
}
