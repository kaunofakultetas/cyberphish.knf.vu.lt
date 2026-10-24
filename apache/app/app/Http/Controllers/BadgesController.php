<?php

// -----------------------------------------------------------
//  [*] BadgesController — the member's badges page
//
//  The badges a member earned in the current language, as
//  one count per badge name, which the page turns into the
//  badge's picture or, while it is missing, the picture's
//  tr_ version. The badges themselves are awarded
//  elsewhere: for lessons, self-evaluations, simulations,
//  the knowledge test, ten days of logins in a row, and —
//  for the finished course — in BaseController's middleware.
//
//  Used by:
//    - routes/web_user.php — cp/badges
// -----------------------------------------------------------


namespace App\Http\Controllers;

use Session;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Badges;
use App\Models\SelfEvalUserTest;
use App\Models\SelfEval;
use App\Models\SelfEvalOption;
use App\Models\SelfEvalUser;
use App\Models\Settings;
use App\Models\LMContent;
use App\Models\Langs;
use App\Models\LMCategory;
use App\Models\LMFiles;
use App\Models\LMUsers;








// -----------------------------------------------------------
// BadgesController
// -----------------------------------------------------------
//
// One action. The language is the session's: the route
// carries no {lang}, so BaseController's middleware keeps
// the one of the member's last page.
//
// Used by:
//   - routes/web_user.php — cp/badges
// -----------------------------------------------------------

class BadgesController extends BaseController
{






    // -----------------------------------------------------------
    // __construct
    // -----------------------------------------------------------
    //
    // BaseController's constructor and nothing else: it
    // registers the middleware that sets the language and
    // shares the member's data with every view.
    //
    // Used by:
    //   - Laravel, when it builds the controller to dispatch
    //     its route
    // -----------------------------------------------------------

    public function __construct()
    {
        parent::__construct();
    }






    // -----------------------------------------------------------
    // index
    // -----------------------------------------------------------
    //
    // GET /cp/badges
    //
    // The member's badges page, behind auth:mem:
    // badges.blade.php with, for each badge name, how many
    // rows of it the member holds in the session language —
    // badge_login_10 in any language, being the member's own
    // rather than a course's — nine counts, one query each.
    // Its values replace the badge flags BaseController shares
    // under the same names. The count of badge_self_evaluation
    // is dead code: nothing awards that badge and the page
    // does not show it.
    //
    // Used by:
    //   - layouts/main.blade.php — "my badges" in the member
    //     menu of the top bar
    //   - parts/left-user-links.blade.php — the "my badges"
    //     button on the dashboard, badges and history pages
    //   - the new-badge message (main.new_badge in
    //     resources/lang) — its link, flashed when a badge is
    //     awarded
    // -----------------------------------------------------------

    public function index()
    {
        return view('badges',[
            'badge_topic'=>Badges::where(['lang_id'=>Langs::getLangId(Session::get('lang')), 'user_id'=>Auth::guard('mem')->id(), 'name'=>'badge_topic'])->count(),
            'badge_finished_course'=>Badges::where(['lang_id'=>Langs::getLangId(Session::get('lang')), 'user_id'=>Auth::guard('mem')->id(), 'name'=>'badge_finished_course'])->count(),
            // Nothing awards this badge and the page leaves it out
            'badge_self_evaluation'=>Badges::where(['lang_id'=>Langs::getLangId(Session::get('lang')), 'user_id'=>Auth::guard('mem')->id(), 'name'=>'badge_self_evaluation'])->count(),
            'badge_category'=>Badges::where(['lang_id'=>Langs::getLangId(Session::get('lang')), 'user_id'=>Auth::guard('mem')->id(), 'name'=>'badge_category'])->count(),
            'badge_all_presentations'=>Badges::where(['lang_id'=>Langs::getLangId(Session::get('lang')), 'user_id'=>Auth::guard('mem')->id(), 'name'=>'badge_all_presentations'])->count(),
            'badge_all_simulations'=>Badges::where(['lang_id'=>Langs::getLangId(Session::get('lang')), 'user_id'=>Auth::guard('mem')->id(), 'name'=>'badge_all_simulations'])->count(),
            'badge_self_evaluation_test'=>Badges::where(['lang_id'=>Langs::getLangId(Session::get('lang')), 'user_id'=>Auth::guard('mem')->id(), 'name'=>'badge_self_evaluation_test'])->count(),
            'badge_final_test'=>Badges::where(['lang_id'=>Langs::getLangId(Session::get('lang')), 'user_id'=>Auth::guard('mem')->id(), 'name'=>'badge_final_test'])->count(),
            // The member's own in any language (LoginLog::if_login_10)
            'badge_login_10'=>Badges::where(['user_id'=>Auth::guard('mem')->id(), 'name'=>'badge_login_10'])->count()
        ]);
    }
}
