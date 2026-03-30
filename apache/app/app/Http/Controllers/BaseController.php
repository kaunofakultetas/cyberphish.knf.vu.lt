<?php

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

class BaseController extends Controller
{
    public function __construct() {

        $this->middleware(function ($request, $next) {

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


            if(Auth::guard('mem')->check()){
                $user_email = Members::select('email')->where(['id'=>Auth::guard('mem')->id()])->get()->pluck('email')->first();
                $user_id = Auth::guard('mem')->id();
                $user_progress = LMUsers::get_users_progress(Auth::guard('mem')->id());
                $course = LMUsers::course_progress(Auth::guard('mem')->id(), Langs::getLangId(Session::get('lang')));
                $country_id = Members::select('country')->where(['id'=>Auth::guard('mem')->id()])->get()->pluck('country')->first();

                $badge_all_presentations = Badges::get_badge('badge_all_presentations');
                $badge_all_simulations = Badges::get_badge('badge_all_simulations');
                $badge_finished_course = Badges::get_badge('badge_finished_course');

                if($badge_all_presentations == true && $badge_all_simulations == true){

                    $bx = Badges::save_badge(Langs::getLangId(Session::get('lang')), 'badge_finished_course');

                    if($bx == true){
                        $request->session()->now('success', __('main.new_badge'));
                    } else {

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


           // if(Session::get('lang') === null){

            //    App::setLocale('en');
           //     Session::put('lang', 'en');

           // } else {

           //     if (! in_array(Session::get('lang'), ['en', 'lt'])) {
          //          abort(400);
          //      }

          //      App::setLocale(Session::get('lang'));
          //  }

            return $next($request);
        });

    }
}