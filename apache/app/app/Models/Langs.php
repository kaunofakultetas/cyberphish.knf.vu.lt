<?php

// -----------------------------------------------------------
//  [*] Langs — the languages of the site
//
//  One bei_lang row per site language: its name and its
//  locale, the code the URLs and the session carry (en, lt,
//  ee, lv, gr). The content tables key their rows by the
//  language's id, so nearly every page turns the locale it
//  has into that id first. mysql/init.sql seeds English, as
//  id 3; the panel adds the others.
//
//  Used by:
//    - nearly every member and admin controller
//      (Http/Controllers/)
//    - FinishedSe (Http/Middleware/)
//    - Badges, LoginLog (this folder)
// -----------------------------------------------------------


namespace App\Models;

use Illuminate\Database\Eloquent\Model;








// -----------------------------------------------------------
// Langs
// -----------------------------------------------------------
//
// Columns: name (unique) and locale.
//
//   getLangId — the id of a locale
//
// Used by:
//   - the callers of getLangId (below) — the language of
//     nearly every page
//   - Admin\LanguagesController — index, form, create,
//     update, delete
//   - Admin\InfoController, Admin\LMController,
//     Admin\NewsController, Admin\SimulationsController,
//     Admin\SiteSettingsController,
//     Admin\ManagerMainController — the language lists and
//     names of their pages
// -----------------------------------------------------------

class Langs extends Model
{

    // The table, which has no created_at / updated_at
    // columns; rememberTokenName belongs to the account
    // models, a plain model never reads it
    protected $table = "bei_lang";
    public $timestamps = false;
    protected $rememberTokenName = false;

    // The columns create() and fill() would accept; nothing
    // calls either on this model
    protected $fillable = [
        'id', 'name', 'locale'
    ];

    // Empty: toArray hands out every column
    protected $hidden = [
    ];

    // How the columns read back through the model
    protected $casts = [
        'id'=>'integer',
        'name'=>'string',
        'locale'=>'string'
    ];






    // -----------------------------------------------------------
    // getLangId
    // -----------------------------------------------------------
    //
    // The id of the language with that locale, or null when
    // no row has it. One query per call and nothing cached:
    // several actions call it more than once per request.
    //
    // Used by:
    //   - BaseController::__construct, BadgesController::index,
    //     CertController::download, HomeController::index,
    //     NewsController::index, RanksController,
    //     KnowledgeTestController, LMController,
    //     SimulationsController — the language of the page
    //   - FinishedSe::handle (Http/Middleware/)
    //   - Badges::get_badge, LoginLog::if_login_10 (this
    //     folder)
    // -----------------------------------------------------------

    public static function getLangId($locale)
    {
        return Langs::select('id')->where(['locale'=>$locale])->get()->pluck('id')->first();
    }
}
