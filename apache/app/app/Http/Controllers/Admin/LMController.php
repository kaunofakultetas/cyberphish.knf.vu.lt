<?php

// -----------------------------------------------------------
//  [*] Admin\LMController — the panel's learning material
//
//  The admin side of the course content: the categories of
//  each language, the lessons filed under them (title,
//  category, body, hours) and the files attached to a
//  lesson, kept in public/upload/lm under random names.
//  Every route sits behind auth.admin:man and perms, so
//  only a main admin gets here; a country manager gets a
//  404. Members read the same tables through
//  App\Http\Controllers\LMController.
//
//  Used by:
//    - routes/web_admin.php — the admin-panel/lm and
//      admin-panel/dialog/lm routes
// -----------------------------------------------------------


namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use App\Http\Controllers\SettingsController;
use App\Models\Langs as Langs;
use App\Models\LMCategory as LMCategory;
use App\Models\LMContent as LMContent;
use App\Models\LMFiles as LMFiles;
use App\Models\LMUsers as LMUsers;
use App\Models\SelfEval;
use App\Models\KnowledgeTest;
use Illuminate\Support\Facades\Validator;








// -----------------------------------------------------------
// LMController
// -----------------------------------------------------------
//
// One action per page, dialog and form of the panel's
// learning material. The list pages group their rows by
// language id, one tab per language, and a redirect whose
// anchor names a language id makes admn.js open that tab
// again. The dialogs are partial views that window.js
// loads into the panel's modal.
//
//   index, form, create, update, delete
//                    — the lessons
//   categories, categories_form, categories_create,
//   categories_update, categories_delete
//                    — the categories of each language
//   additional_files, files_form, files_create,
//   files_update, files_delete
//                    — the files of one lesson
//
// Production mounts public/upload read-only
// (docker-compose.yml): there files_create's move ends in a
// 500, while delete and files_delete leave the file on disk
// and remove its row all the same.
//
// Used by:
//   - routes/web_admin.php — every action
// -----------------------------------------------------------

class LMController extends BaseController
{






    // -----------------------------------------------------------
    // __construct
    // -----------------------------------------------------------
    //
    // BaseController's constructor and nothing else: the
    // auth:man guard on every action, and the session's main
    // and country flags shared with every view — the sidebar
    // shows the main admin's menu by them.
    //
    // Used by:
    //   - Laravel's router, for every route of this class
    // -----------------------------------------------------------

    public function __construct()
    {
        parent::__construct();
    }






    // -----------------------------------------------------------
    // index
    // -----------------------------------------------------------
    //
    // GET /admin-panel/lm/content
    //
    // The lessons page, admin-panel/lm_content.blade.php
    // (route name lm): every lesson, newest first, with its
    // category's name and its number of files, grouped by
    // language id for the tabs. It loads every lesson,
    // bodies included, once just to see whether there is
    // any. Main admins only (auth.admin:man, perms).
    //
    // Used by:
    //   - admin-panel/sidebar.blade.php — Learning Material,
    //     Content
    //   - admin-panel/lm_content_form.blade.php — the Content
    //     link above the lesson form
    //   - create (below) — redirects here
    // -----------------------------------------------------------

    public function index()
    {
        $langlist = Langs::all()->pluck('name', 'id')->toArray();

        if(count(LMContent::all()->toArray()) > 0){

            $arr1 = LMContent::selectraw("bei_lm_content.*, bei_lm_category.name AS category_name, COUNT(bei_lm_content_files.id) AS additional_files")
            ->leftJoin("bei_lm_category", 'bei_lm_category.id', '=', 'bei_lm_content.cat_id')
            ->leftJoin("bei_lm_content_files", 'bei_lm_content.id', '=', 'bei_lm_content_files.content_id')
            ->groupBy("bei_lm_content.id")
            ->orderByDesc('id')->get()->toArray();

            $arr = array();
            foreach($arr1 as $k => $v){
                $arr[$v['lang_id']][] = $arr1[$k];
            }

        } else {

            $arr = array();

        }

        return view('admin-panel.lm_content', ['recordlist' => $arr, 'langlist'=>$langlist]);
    }






    // -----------------------------------------------------------
    // form
    // -----------------------------------------------------------
    //
    // GET /admin-panel/lm/content/{type}/{uid}
    //
    // The lesson form, admin-panel/lm_content_form.blade.php.
    // For type "new", uid is a language id: an empty form
    // with that language's categories, posting to create.
    // For type "edit", uid is a lesson id: the lesson's
    // fields, one query each, and the categories of its
    // language, posting to update. Any other type answers an
    // empty page. Main admins only (auth.admin:man, perms).
    //
    // Used by:
    //   - admin-panel/lm_content.blade.php — Create Content on
    //     each language tab (new), the edit icon of each
    //     lesson (edit)
    // -----------------------------------------------------------

    public function form(Request $request, $type, $uid)
    {
        $langlist = Langs::all()->pluck('name', 'id')->toArray();

        if($type == 'new'){

            $catlist = LMCategory::where(['lang_id'=>$uid])->pluck('name', 'id')->toArray();

            return view('admin-panel.lm_content_form', ['url'=>env('APP_URL').'/admin-panel/lm/content/create', 'langlist'=>$langlist, 'uid'=>$uid, 'catlist'=>$catlist]);

        } elseif($type == 'edit'){

            $catlist = LMCategory::where(['lang_id'=>LMContent::where(['id'=>$uid])->pluck('lang_id')->first()])->pluck('name', 'id')->toArray();

            return view('admin-panel.lm_content_form',
                [
                    'url'=>env('APP_URL').'/admin-panel/lm/content/update',
                    'uid'=>$uid,
                    'title'=>LMContent::where(['id'=>$uid])->pluck('title')->first(),
                    'cat_id'=>LMContent::where(['id'=>$uid])->pluck('cat_id')->first(),
                    'content'=>LMContent::where(['id'=>$uid])->pluck('content')->first(),
                    'hours'=>LMContent::where(['id'=>$uid])->pluck('hours')->first(),
                    'catlist'=>$catlist
                ]);

        }
    }






    // -----------------------------------------------------------
    // create
    // -----------------------------------------------------------
    //
    // POST /admin-panel/lm/content/create
    //
    // Stores a new lesson: the title must hold 1 to 255
    // characters, the category, body and hours are taken as
    // posted — an empty editor, NULL by then
    // (ConvertEmptyStringsToNull), as an empty body and empty
    // hours as 0, both columns being NOT NULL — and the
    // language is the form's hidden uid. Then back to the
    // lessons page on that language's tab, with "Created!".
    // Main admins only (auth.admin:man, perms).
    //
    // Used by:
    //   - admin-panel/lm_content_form.blade.php — the lesson
    //     form opened for a new lesson
    // -----------------------------------------------------------

    public function create(Request $request)
    {
        $validatedData = $request->validate([
            'title' => [ 'required', 'min:1', 'max:255' ],
        ]);

        $addRecord = new LMContent;
        $addRecord->cat_id = $request->get('cat_id');
        $addRecord->title = $request->get('title');
        $addRecord->content = $request->get('content') ?? '';
        $addRecord->hours = $request->get('hours') ?? 0;
        $addRecord->lang_id = $request->get('uid');
        $addRecord->save();

        return Redirect::to(route('lm') . "#a".$request->get('uid'))->withSuccess("Created!");
    }






    // -----------------------------------------------------------
    // update
    // -----------------------------------------------------------
    //
    // POST /admin-panel/lm/content/update
    //
    // Writes the lesson form over the lesson in the hidden
    // uid — category, title (1 to 255 characters), body and
    // hours — and goes back with "Updated!". An emptied body
    // or hours ends as in create, an empty body or 0 hours,
    // but through MySQL rather than the code: strict mode is
    // off (config/database.php), so MySQL's UPDATE stores the
    // column's implicit default instead of the NULL. Main
    // admins only (auth.admin:man, perms).
    //
    // Used by:
    //   - admin-panel/lm_content_form.blade.php — the lesson
    //     form opened on an existing lesson
    // -----------------------------------------------------------

    public function update(Request $request)
    {
        $validatedData = $request->validate([
            'title' => [ 'required', 'min:1', 'max:255' ],
        ]);

        $update = array(
            'cat_id'=>$request->get('cat_id'),
            'title'=>$request->get('title'),
            'content'=>$request->get('content'),
            'hours'=>$request->get('hours')
        );

        LMContent::where(['id' => $request->get('uid')])->update($update);

        return back()->withSuccess("Updated!");
    }






    // -----------------------------------------------------------
    // delete
    // -----------------------------------------------------------
    //
    // POST /admin-panel/lm/content_delete/{uid}
    //
    // Deletes a lesson: its files from disk with their rows,
    // the members' completions of it (bei_lm_users), then the
    // lesson itself; back to the previous page, on the
    // lesson's language tab, with "Removed!". Every file row
    // goes, as its foreign key would block the lesson's
    // delete, whether its file is still on disk or not; a
    // file on a read-only upload folder, as production mounts
    // it, stays on disk. Main admins only (auth.admin:man,
    // perms).
    //
    // Used by:
    //   - admin-panel/lm_content.blade.php — the delete icon
    //     of each lesson, after Confirm_Dialog (admn.js)
    // -----------------------------------------------------------

    public function delete(Request $request, $uid)
    {
        $lang_id = LMContent::where(['id'=>$uid])->pluck('lang_id')->first();

        $files = LMFiles::where(['content_id'=>$uid])->get()->toArray();
        if(count($files)>0){
            foreach($files as $k => $v){
                $file_path = public_path('upload/').$v['file_name'];
                if(is_file($file_path) && is_writable(dirname($file_path))){
                    unlink($file_path);
                }
                LMFiles::where(['id'=>$v['id']])->delete();
            }
        }

        LMUsers::where(['content_id'=>$uid])->delete();
        LMContent::destroy($uid);
        return Redirect::to(URL::previous() . "#a".$lang_id)->withSuccess("Removed!");
    }






    // -----------------------------------------------------------
    // categories
    // -----------------------------------------------------------
    //
    // GET /admin-panel/lm/categories
    //
    // The categories page, admin-panel/lm_categories.blade.php
    // (route name lm_categories): every category, newest
    // first, grouped by language id for the tabs, each with
    // links to its self-evaluation and knowledge-test
    // questions. Main admins only (auth.admin:man, perms).
    //
    // Used by:
    //   - admin-panel/sidebar.blade.php — Learning Material,
    //     Categories
    //   - admin-panel/self_eval.blade.php,
    //     admin-panel/knowledge.blade.php — their Content
    //     Categories link
    // -----------------------------------------------------------

    public function categories(Request $request)
    {
        $langlist = Langs::all()->pluck('name', 'id')->toArray();

        if(count(LMCategory::all()->toArray()) > 0){

            $arr1 = LMCategory::orderByDesc('id')->get()->toArray();

            $arr = array();
            foreach($arr1 as $k => $v){
                $arr[$v['lang_id']][] = $arr1[$k];
            }

        } else {

            $arr = array();

        }

        return view('admin-panel.lm_categories', ['recordlist' => $arr, 'langlist'=>$langlist]);
    }






    // -----------------------------------------------------------
    // categories_form
    // -----------------------------------------------------------
    //
    // GET /admin-panel/dialog/lm/categories/{type}/{uid}
    //
    // The category dialog for the panel's modal,
    // admin-panel/dialog/lm_category_form.blade.php. For type
    // "new", uid is a language id and the dialog posts to
    // categories_create; for "edit", uid is a category id,
    // and the dialog shows its name and posts to
    // categories_update. Any other type answers an empty
    // page. Main admins only (auth.admin:man, perms).
    //
    // Used by:
    //   - lm_categories_form in public/js/window.js — Create
    //     Category and the edit icons of
    //     admin-panel/lm_categories.blade.php
    // -----------------------------------------------------------

    public function categories_form(Request $request, $type, $uid)
    {
        if($type =='new'){

            return view('admin-panel.dialog.lm_category_form', ['url'=>env('APP_URL').'/admin-panel/dialog/lm/categories/create', 'uid'=>$uid]);

        } elseif($type =='edit'){

            return view('admin-panel.dialog.lm_category_form',
                [
                    'url'=>env('APP_URL').'/admin-panel/dialog/lm/categories/update',
                    'uid'=>$uid,
                    'name'=>LMCategory::where(['id'=>$uid])->pluck('name')->first()
                ]);

        }
    }






    // -----------------------------------------------------------
    // categories_create
    // -----------------------------------------------------------
    //
    // POST /admin-panel/dialog/lm/categories/create
    //
    // Stores a new category — its name 1 to 255 characters,
    // its language the dialog's hidden uid — and goes back to
    // the previous page on that language's tab, with
    // "Created!". Main admins only (auth.admin:man, perms).
    //
    // Used by:
    //   - admin-panel/dialog/lm_category_form.blade.php — the
    //     dialog opened for a new category
    // -----------------------------------------------------------

    public function categories_create(Request $request)
    {
        $validatedData = $request->validate([
            'name' => [ 'required', 'min:1', 'max:255' ],
        ]);

        $addRecord = new LMCategory;
        $addRecord->name = $validatedData['name'];
        $addRecord->lang_id = $request->get('uid');
        $addRecord->save();

        return Redirect::to(URL::previous() . "#a".$request->get('uid'))->withSuccess("Created!");
    }






    // -----------------------------------------------------------
    // categories_update
    // -----------------------------------------------------------
    //
    // POST /admin-panel/dialog/lm/categories/update
    //
    // Renames the category in the hidden uid (an integer; the
    // name 1 to 255 characters) and goes back to the previous
    // page on the category's language tab, with "Updated!".
    // Main admins only (auth.admin:man, perms).
    //
    // Used by:
    //   - admin-panel/dialog/lm_category_form.blade.php — the
    //     dialog opened on an existing category
    // -----------------------------------------------------------

    public function categories_update(Request $request)
    {
        $validatedData = $request->validate([
            'uid' => [ 'required', 'integer' ],
            'name' => [ 'required', 'min:1', 'max:255' ],
        ]);

        LMCategory::where(['id' => $validatedData['uid']])->update(['name'=>$validatedData['name']]);

        return Redirect::to(URL::previous() . "#a".LMCategory::where(['id'=>$validatedData['uid']])->pluck('lang_id')->first())->withSuccess("Updated!");
    }






    // -----------------------------------------------------------
    // categories_delete
    // -----------------------------------------------------------
    //
    // POST /admin-panel/lm/categores/delete/{uid}
    //
    // Deletes a category and goes back to the previous page,
    // on the category's language tab, with "Removed!"; its
    // lessons stay, without a category (the foreign key sets
    // their cat_id to NULL). A category that still holds
    // self-evaluation or knowledge-test questions is refused
    // with a message instead: they have no foreign key to it,
    // and would outlive it where no page lists them — the
    // knowledge test drawing them still. The route and the
    // page that links to it share the "categores" spelling.
    // Main admins only (auth.admin:man, perms).
    //
    // Used by:
    //   - admin-panel/lm_categories.blade.php — the delete
    //     icon of each category, after Confirm_Dialog
    //     (admn.js)
    // -----------------------------------------------------------

    public function categories_delete(Request $request, $uid)
    {
        $lang_id = LMCategory::where(['id'=>$uid])->pluck('lang_id')->first();

        if(SelfEval::where(['cat_id'=>$uid])->count() > 0 || KnowledgeTest::where(['cat_id'=>$uid])->count() > 0){
            return Redirect::to(URL::previous() . "#a".$lang_id)->withErrors("Category still has questions! Delete them first.");
        }

        LMCategory::destroy($uid);

        return Redirect::to(URL::previous() . "#a".$lang_id)->withSuccess("Removed!");
    }






    // -----------------------------------------------------------
    // additional_files
    // -----------------------------------------------------------
    //
    // GET /admin-panel/lm/files/{uid}
    //
    // The files page of lesson uid,
    // admin-panel/lm_additional_files.blade.php, under the
    // lesson's title: every file row of the lesson is read
    // and the 25 of the page the query string names are
    // sliced out, anything empty or not numeric being page 1.
    // With no files the view gets an empty array instead of
    // a paginator. Main admins only (auth.admin:man, perms).
    //
    // Used by:
    //   - admin-panel/lm_content.blade.php — the Additional
    //     files link of each lesson
    //   - admin-panel/lm_additional_files.blade.php — its
    //     page links
    // -----------------------------------------------------------

    public function additional_files(Request $request, $uid)
    {
        $arr = LMFiles::where(['content_id'=>$uid])->get()->toArray();

        $name = LMContent::where(['id'=>$uid])->pluck('title')->first();

        if (empty($request->get('page')) || ! is_numeric($request->get('page'))) {

            $curr_page = 1;
        } else {

            $curr_page = $request->get('page');
        }

        $perPage = 25;

        $currentItems = array_slice($arr, $perPage * ($curr_page - 1), $perPage);

        $paginator = new LengthAwarePaginator($currentItems, count($arr), $perPage, $curr_page, [
            'path' => url('admin-panel/lm/files/'.$uid)
        ]);

        $paginator = $paginator->appends('page', request('page'));

        if (count($arr) == 0) {

            $arr = array();
            $paginator = array();
        }

        return view('admin-panel.lm_additional_files', [
            'recordlist'=>$paginator,
            'name'=>$name,
            'uid'=>$uid
        ]);
    }






    // -----------------------------------------------------------
    // files_form
    // -----------------------------------------------------------
    //
    // GET /admin-panel/dialog/lm/file/{type}/{uid}
    //
    // The file dialog for the panel's modal,
    // admin-panel/dialog/lm_files_form.blade.php. For type
    // "new", uid is a lesson id and the dialog holds the file
    // chooser, posting to files_create; for "edit", uid is a
    // file id, and the dialog shows its name and Embed box
    // and posts to files_update. Any other type answers an
    // empty page. Main admins only (auth.admin:man, perms).
    //
    // Used by:
    //   - lm_files_form in public/js/window.js — Add file and
    //     the edit icons of
    //     admin-panel/lm_additional_files.blade.php
    // -----------------------------------------------------------

    public function files_form(Request $request, $type, $uid)
    {
        if($type =='new'){

            return view('admin-panel.dialog.lm_files_form', ['url'=>env('APP_URL').'/admin-panel/lm/file/create', 'uid'=>$uid, 'type'=>$type]);

        } elseif($type =='edit'){

            return view('admin-panel.dialog.lm_files_form',
                [
                    'url'=>env('APP_URL').'/admin-panel/lm/file/update',
                    'uid'=>$uid,
                    'name'=>LMFiles::where(['id'=>$uid])->pluck('name')->first(),
                    'embed'=>LMFiles::where(['id'=>$uid])->pluck('embed')->first(),
                    'type'=>$type
                ]);

        }
    }

    // The extensions files_create accepts, compared with the
    // lower-cased extension of the name the browser sent.
    // SVG and HTML are left out on purpose: served from
    // public/upload they could run script in the site's own
    // origin.
    private static $allowed_extensions = [
        'pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'odt', 'ods', 'odp',
        'jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp',
        'mp4', 'mp3', 'wav', 'avi', 'mov',
        'zip', 'rar', 'txt', 'csv', 'rtf',
    ];






    // -----------------------------------------------------------
    // files_create
    // -----------------------------------------------------------
    //
    // POST /admin-panel/lm/file/create
    //
    // Attaches an uploaded file to the lesson in the hidden
    // uid. An extension off the allow list goes back with an
    // error; otherwise the file moves into public/upload/lm
    // under 40 random characters and its extension, and its
    // row keeps the name given, that path, the MIME type read
    // from the content, the size and the Embed flag — an
    // embedded file shows in the lesson's PDF viewer, the
    // others are listed for download; an unticked box posts
    // nothing and stores 0, the lesson page lists only 0 and
    // 1. Back with "Created!". A request without a usable
    // file — none chosen, or one over PHP's
    // upload_max_filesize — goes back with an error and
    // stores nothing. Main admins only (auth.admin:man,
    // perms).
    //
    // Used by:
    //   - admin-panel/dialog/lm_files_form.blade.php — the
    //     dialog opened for a new file
    // -----------------------------------------------------------

    public function files_create(Request $request)
    {
        if ($request->hasFile('doc')) {

            $extension = strtolower($request->file('doc')->getClientOriginalExtension());
            if (!in_array($extension, self::$allowed_extensions)) {
                return back()->withErrors("File type '.$extension' is not allowed.");
            }

            $random_name = Str::random(40).'.'.$extension;
            $mimetype = $request->file('doc')->getmimetype();
            // Read before the move: PHP 8 gives no size for the
            // temporary file once it is gone
            $size = $request->file('doc')->getsize();

            $request->file('doc')->move(public_path('upload/lm/'), $random_name);

            $addRecord = new LMFiles;
            $addRecord->content_id = $request->get('uid');
            $addRecord->name = $request->get('name');
            $addRecord->file_name = 'lm/'.$random_name;
            $addRecord->mime_type = $mimetype;
            $addRecord->file_size = $size;
            $addRecord->embed = $request->get('embed') == 1 ? 1 : 0;
            $addRecord->save();

        } else {

            return back()->withErrors("No file was uploaded!");

        }

        return back()->withSuccess("Created!");
    }






    // -----------------------------------------------------------
    // files_update
    // -----------------------------------------------------------
    //
    // POST /admin-panel/lm/file/update
    //
    // Renames the file in the hidden uid and sets its Embed
    // flag — an unticked box posts nothing and stores 0, as
    // in files_create — then goes back with "Updated!". Main
    // admins only (auth.admin:man, perms).
    //
    // Used by:
    //   - admin-panel/dialog/lm_files_form.blade.php — the
    //     dialog opened on an existing file
    // -----------------------------------------------------------

    public function files_update(Request $request)
    {
        LMFiles::where(['id'=>$request->get('uid')])->update([
            'name'=>$request->get('name'),
            'embed'=>$request->get('embed') == 1 ? 1 : 0
        ]);

        return back()->withSuccess("Updated!");
    }






    // -----------------------------------------------------------
    // files_delete
    // -----------------------------------------------------------
    //
    // POST /admin-panel/lm/files/delete/{uid}
    //
    // Deletes one file of a lesson: from disk when it is
    // there and the upload folder is writable — production
    // mounts it read-only — then its row, whatever happened
    // on disk. An id with no row — a link opened
    // twice, or one on a stale page — finds nothing to delete
    // and gets the same answer. Back with "Updated!", not the
    // "Removed!" of the other deletes. Main admins only
    // (auth.admin:man, perms).
    //
    // Used by:
    //   - admin-panel/lm_additional_files.blade.php — the
    //     delete icon of each file, after Confirm_Dialog
    //     (admn.js)
    // -----------------------------------------------------------

    public function files_delete(Request $request, $uid)
    {
        $file = LMFiles::where(['id'=>$uid])->pluck('file_name')->first();

        // Without a row the path below would be the upload
        // folder itself, which unlink cannot remove
        if($file === null){
            return back()->withSuccess("Updated!");
        }

        $file_path = public_path('upload/').$file;

        // A read-only upload folder, as production mounts it,
        // keeps the file; the row goes all the same
        if(is_file($file_path) && is_writable(dirname($file_path))){
            unlink($file_path);
        }

        LMFiles::where(['id'=>$uid])->delete();

        return back()->withSuccess("Updated!");
    }
}
