<?php

// -----------------------------------------------------------
//  [*] Information — the information pages of the site
//
//  One bei_information row per information page, written in
//  the admin panel for one language: a title, the HTML body
//  and an alias, the slug the page's URL carries.
//
//  Used by:
//    - InfoController, Admin\InfoController
//      (Http/Controllers/)
//    - PageExists (Http/Middleware/)
// -----------------------------------------------------------


namespace App\Models;

use Illuminate\Database\Eloquent\Model;








// -----------------------------------------------------------
// Information
// -----------------------------------------------------------
//
// Columns: title, content (the HTML body), alias and
// lang_id (bei_lang). The public route carries the alias
// and the id, but a page is found by its id alone: the
// alias and the language in the URL are never compared with
// the row.
//
// Used by:
//   - InfoController::content — the public page
//   - PageExists::handle — the 404 for an unknown page id
//   - Admin\InfoController — index, form, create, update,
//     delete
// -----------------------------------------------------------

class Information extends Model
{

    // The table, which has no created_at / updated_at
    // columns; rememberTokenName belongs to the account
    // models, a plain model never reads it
    protected $table = "bei_information";
    public $timestamps = false;
    protected $rememberTokenName = false;

    // The columns create() and fill() would accept; nothing
    // calls either on this model
    protected $fillable = [
        'id', 'title', 'content', 'lang_id', 'alias'
    ];

    // Empty: toArray hands out every column
    protected $hidden = [
    ];

    // How the columns read back through the model. 'alias'
    // names no cast type: Laravel 7 leaves that column uncast
    // (it is text anyway), later Laravel versions throw on it
    protected $casts = [
        'id'=>'integer',
        'title'=>'string',
        'content'=>'string',
        'lang_id'=>'integer',
        'alias'=>'alias'
    ];
}
