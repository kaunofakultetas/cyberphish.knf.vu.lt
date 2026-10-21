<?php

// -----------------------------------------------------------
//  [*] LanguagesController — the site languages in the panel
//
//  Main admins keep the rows of bei_lang here, each a name
//  and a locale, through the languages page and its dialog.
//  The panel builds its language tabs from these rows, but
//  the public site switches only between the five locales
//  its BaseController and SettingsController::lang_switch
//  list — en, lt, ee, lv and gr — so a row with any other
//  locale gets tabs in the panel and no visitors. Every
//  route sits behind auth.admin:man and perms: main admins
//  only.
//
//  Used by:
//    - routes/web_admin.php — the languages page, its dialog
//      and their posts
// -----------------------------------------------------------


namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use App\Http\Controllers\SettingsController;
use App\Models\Langs as Langs;
use Illuminate\Support\Facades\Validator;








// -----------------------------------------------------------
// LanguagesController
// -----------------------------------------------------------
//
// The rows the content tables point at: news, information
// pages, learning-material categories and lessons carry a
// lang_id.
//
//   index          — the languages page
//   form           — the new and edit dialog
//   create, update — the dialog's two posts
//   delete         — removes a language
//
// Used by:
//   - routes/web_admin.php — the languages routes
// -----------------------------------------------------------

class LanguagesController extends BaseController
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
    // GET /admin-panel/settings/languages
    //
    // The languages page, admin-panel/langs.blade.php: every
    // language with its edit and delete links.
    //
    // Used by:
    //   - admin-panel/sidebar.blade.php — Languages, under
    //     Settings
    // -----------------------------------------------------------

    public function index(Request $request)
    {
        if(count(Langs::all()->toArray()) > 0){

            $arr = Langs::all()->toArray();

        } else {

            $arr = array();

        }

        return view('admin-panel.langs', ['recordlist' => $arr]);
    }






    // -----------------------------------------------------------
    // form
    // -----------------------------------------------------------
    //
    // GET /admin-panel/dialog/lang/new
    // GET /admin-panel/dialog/lang/edit/{uid?}
    //
    // The dialog of admin-panel/dialog/lang_form.blade.php,
    // loaded into the languages page's modal: empty and
    // posting to dialog/lang/create without an id; with one,
    // filled with the language's name and locale and posting
    // to dialog/lang/update.
    //
    // Used by:
    //   - public/js/window.js — lang_form, called by the
    //     Create Language button and each row's edit icon on
    //     admin-panel/langs.blade.php
    // -----------------------------------------------------------

    public function form(Request $request, $uid=null)
    {
        if($uid == null){

            return view('admin-panel.dialog.lang_form', ['url'=>env('APP_URL').'/admin-panel/dialog/lang/create']);

        } else {

            return view('admin-panel.dialog.lang_form',
                [
                    'url'=>env('APP_URL').'/admin-panel/dialog/lang/update',
                    'uid'=>$uid,
                    'name'=>Langs::where(['id'=>$uid])->pluck('name')->first(),
                    'locale'=>Langs::where(['id'=>$uid])->pluck('locale')->first()
                ]);

        }
    }






    // -----------------------------------------------------------
    // create
    // -----------------------------------------------------------
    //
    // POST /admin-panel/dialog/lang/create
    //
    // Adds a language: the name, required and at most 255
    // characters, and the locale as posted. Answers a redirect
    // back to the languages page.
    //
    // Known bug (low): the rules miss what bei_lang demands
    // (LanguagesController.php:190). A name another language
    // has breaks the table's unique key and an empty locale
    // its NOT NULL, both a 500, and a name longer than the
    // column's 45 characters is cut without a word (MySQL's
    // strict mode is off). Fix: limit the name to 45
    // characters and to names not taken, and require the
    // locale.
    // Pinned as language-rules.
    //
    // Used by:
    //   - admin-panel/dialog/lang_form.blade.php — the dialog,
    //     opened without an id
    // -----------------------------------------------------------

    public function create(Request $request)
    {
        $validatedData = $request->validate([
            'name' => [ 'required', 'min:1', 'max:255' ],
        ]);

        $addLangs = new Langs;
        $addLangs->name = $validatedData['name'];
        $addLangs->locale = $request->get('locale');
        $addLangs->save();

        return back()->withSuccess("Created!");
    }






    // -----------------------------------------------------------
    // update
    // -----------------------------------------------------------
    //
    // POST /admin-panel/dialog/lang/update
    //
    // Renames the language named by uid and sets its locale.
    // Answers a redirect back to the languages page.
    //
    // Known bug (low): as in create, a name another language
    // has is a 500 and one longer than 45 characters is cut
    // (LanguagesController.php:230); an empty locale is
    // stored as an empty string. Fix: the rules create needs.
    // Pinned as language-rules.
    //
    // Used by:
    //   - admin-panel/dialog/lang_form.blade.php — the dialog,
    //     opened with an id
    // -----------------------------------------------------------

    public function update(Request $request)
    {
        $validatedData = $request->validate([
            'uid' => [ 'required', 'integer' ],
            'name' => [ 'required', 'min:1', 'max:255' ],
        ]);

        Langs::where(['id' => $validatedData['uid']])->update(['name'=>$validatedData['name'], 'locale'=>$request->get('locale')]);

        return back()->withSuccess("Updated!");
    }






    // -----------------------------------------------------------
    // delete
    // -----------------------------------------------------------
    //
    // GET /admin-panel/settings/languages/delete/{uid}
    //
    // Deletes the language and answers a redirect back to
    // the languages page.
    //
    // Known bug (medium): the delete answers a plain GET
    // (LanguagesController.php:273), which the CSRF check
    // never covers, so a link a signed-in admin opens deletes
    // a language (pinned as admin-get-deletes, for every
    // delete of the panel). Fix: a POST route, sent with the
    // CSRF token.
    //
    // Known bug (low): a language that still has news,
    // information pages or learning-material categories
    // cannot go — their foreign keys (NO ACTION) refuse the
    // delete, and the request ends in a 500 instead of a
    // message. Fix: look for them first and say so.
    // Pinned as language-delete-in-use.
    //
    // Used by:
    //   - admin-panel/langs.blade.php — each row's delete
    //     icon, through Confirm_Dialog (public/js/admn.js)
    // -----------------------------------------------------------

    public function delete(Request $request, $uid)
    {
        Langs::destroy($uid);

        return back()->withSuccess("Removed!");
    }
}
