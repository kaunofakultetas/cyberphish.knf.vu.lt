<?php

// -----------------------------------------------------------
//  [*] InfoController — the public information pages
//
//  The static pages anyone can open — the contact page, the
//  cookie policy and the like — kept in bei_information and
//  written in the admin panel (Admin\InfoController). Built
//  on BaseController, whose middleware takes the language
//  from the URL's {lang} part.
//
//  Used by:
//    - routes/web_user.php — the information page route
// -----------------------------------------------------------


namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Information as Information;








// -----------------------------------------------------------
// InfoController
// -----------------------------------------------------------
//
// One action, content. Not the panel's controller of the
// same name, Admin\InfoController, which edits these pages.
//
// Used by:
//   - routes/web_user.php — the information page route
// -----------------------------------------------------------

class InfoController extends BaseController
{






    // -----------------------------------------------------------
    // __construct
    // -----------------------------------------------------------
    //
    // BaseController's middleware, nothing more.
    //
    // Used by:
    //   - Laravel's router, before content
    // -----------------------------------------------------------

    public function __construct()
    {
        parent::__construct();
    }






    // -----------------------------------------------------------
    // content
    // -----------------------------------------------------------
    //
    // GET /{lang}/page/{slug}/{uid}
    //
    // One information page, information.blade.php, found by
    // its id alone: PageExists (page.exists) answers an id no
    // bei_information row has with a 404, and the slug is
    // never read — {lang} only sets the language of the page
    // around it. The view gets the row as the model itself.
    //
    // Used by:
    //   - layouts/main.blade.php — the footer's contact link
    //     (page 6) and the cookie notice's policy link (page
    //     19, the English policy, under the language the
    //     visitor is in)
    // -----------------------------------------------------------

    public function content($lang, $slug, $uid)
    {
        return view('information', ['content'=>Information::where(['id'=>$uid])->get()->first()]);
    }
}
