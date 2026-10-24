<?php

// -----------------------------------------------------------
//  [*] BaseController — the shared setup of every member page
//
//  The parent of the controllers on the member side of the
//  site; the admin panel has its own, Admin\BaseController.
//  Its constructor registers one middleware that runs
//  before each of their actions, for members and visitors
//  alike: it picks the page's language and hands every view
//  what the layout and the sidebars read — the member's
//  e-mail, course progress, finished lessons, country,
//  badges and questionnaire link, and the learning-material
//  menu of the language.
//
//  Used by:
//    - BadgesController, CertController, HomeController,
//      InfoController, KnowledgeTestController,
//      LMController, ManagerMainController, NewsController,
//      RanksController, SimulationsController — they
//      extend it
//    - Auth\LoginController, Auth\RegisterController — they
//      import it and extend it
// -----------------------------------------------------------


namespace App\Http\Controllers;

use Session;
use App\Http\Controllers\Controller;
use App\Http\Controllers\LMController;
use Illuminate\Http\Request;
use App\Models\Members;
use App\Models\Badges;
use App\Models\Langs;
use App\Models\LMUsers;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\App;
use View;








// -----------------------------------------------------------
// BaseController
// -----------------------------------------------------------
//
// Laravel runs a controller's own middleware after the
// route's, so behind auth:mem the member is signed in by
// the time this one runs; on the public pages it runs for
// visitors too, who get empty values.
//
//   __construct — registers the middleware
//
// Used by:
//   - the controllers named in the header — their
//     constructors call parent::__construct first
// -----------------------------------------------------------

class BaseController extends Controller
{






    // -----------------------------------------------------------
    // __construct
    // -----------------------------------------------------------
    //
    // Registers the middleware, a closure, for every action of
    // the controller. First the locale: the route's {lang}
    // when it is one of the five site languages, remembered in
    // the session; otherwise the session's language, or
    // English when there is none — an unknown {lang} still
    // reaches the action as it is. Then, for a signed-in
    // member, the e-mail, the finished lessons, the course
    // progress (the bei_user_course_progress function), the
    // country and three badges of the session language, and
    // the award of badge_finished_course once
    // badge_all_presentations and badge_all_simulations are
    // both held. The award can fall on any member page — the
    // simulations list, after the last run, most often — and
    // most pages show no messages, so its announcement waits
    // in the session, new_badge_waiting, for the first page
    // that does (parts/notif.blade.php, through
    // AppServiceProvider::boot); announced with the page that
    // earned it, it was lost on all of those. All of it, with
    // the learning-material menu and the questionnaire link of
    // the member's country, is shared with every view.
    //
    // Used by:
    //   - the constructors of the controllers named in the
    //     header, first thing
    // -----------------------------------------------------------

    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            // STEP 1: the locale — the route's {lang} when it is
            // a site language, else the session's, else English
            // ==================================================
            if ($request->route('lang') !== null && in_array($request->route('lang'), ['en', 'lt', 'ee', 'lv', 'gr'])) {

                App::setLocale($request->route('lang'));
                Session::put('lang', $request->route('lang'));

            } else {

                if(Session::get('lang') == null){

                    App::setLocale('en');
                    Session::put('lang', 'en');

                } else {

                    App::setLocale(Session::get('lang'));
                    Session::put('lang', Session::get('lang'));

                }
            }


            // STEP 2: the signed-in member's data; visitors get
            // empty values in the else branch
            // =================================================
            if(Auth::guard('mem')->check()){
                $user_email = Members::select('email')->where(['id'=>Auth::guard('mem')->id()])->get()->pluck('email')->first();
                $user_id = Auth::guard('mem')->id();
                $user_progress = LMUsers::get_users_progress(Auth::guard('mem')->id());
                $course = LMUsers::course_progress(Auth::guard('mem')->id(), Langs::getLangId(Session::get('lang')));
                $country_id = Members::select('country')->where(['id'=>Auth::guard('mem')->id()])->get()->pluck('country')->first();

                // Read before the award below, so the
                // questionnaire link that badge_finished_course
                // unlocks (parts/notif.blade.php) shows from the
                // next page on
                $badge_all_presentations = Badges::get_badge(Langs::getLangId(Session::get('lang')), 'badge_all_presentations');
                $badge_all_simulations = Badges::get_badge(Langs::getLangId(Session::get('lang')), 'badge_all_simulations');
                $badge_finished_course = Badges::get_badge(Langs::getLangId(Session::get('lang')), 'badge_finished_course');

                if($badge_all_presentations == true && $badge_all_simulations == true){

                    $bx = Badges::save_badge(Langs::getLangId(Session::get('lang')), 'badge_finished_course');

                    if($bx == true){
                        $request->session()->put('new_badge_waiting', true);
                    } else {
                        // Already held — nothing to announce
                    }

                }

            } else {
                $user_email = '';
                $user_progress = array();
                $course = 0;
                $user_id = 0;
                $country_id = 0;
                $badge_all_presentations = false;
                $badge_all_simulations = false;
                $badge_finished_course = false;
            }


            // STEP 3: what the layout, the sidebars and
            // parts/notif.blade.php read on every page
            // =========================================
            View::share('user_email', $user_email);
            View::share('category_content_list', LMController::category_content_list());
            View::share('user_progress', $user_progress);
            View::share('course', $course);
            View::share('user_id', $user_id);
            View::share('country_id', $country_id);
            View::share('pq_link', LMController::pc_questionare_link($country_id));
            View::share('badge_all_presentations', $badge_all_presentations);
            View::share('badge_all_simulations', $badge_all_simulations);
            View::share('badge_finished_course', $badge_finished_course);
            View::share('lang', Session::get('lang'));

            return $next($request);
        });
    }
}
