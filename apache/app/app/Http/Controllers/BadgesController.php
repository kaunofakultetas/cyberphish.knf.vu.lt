<?php

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

class BadgesController extends BaseController
{
    public function __construct()
    {
        parent::__construct();

    }

    public function index(){

        return view('badges',[
            'badge_topic'=>Badges::where(['lang_id'=>Langs::getLangId(Session::get('lang')), 'user_id'=>Auth::guard('mem')->id(), 'name'=>'badge_topic'])->count(),
            'badge_finished_course'=>Badges::where(['lang_id'=>Langs::getLangId(Session::get('lang')), 'user_id'=>Auth::guard('mem')->id(), 'name'=>'badge_finished_course'])->count(),
            'badge_self_evaluation'=>Badges::where(['lang_id'=>Langs::getLangId(Session::get('lang')), 'user_id'=>Auth::guard('mem')->id(), 'name'=>'badge_self_evaluation'])->count(),
            'badge_category'=>Badges::where(['lang_id'=>Langs::getLangId(Session::get('lang')), 'user_id'=>Auth::guard('mem')->id(), 'name'=>'badge_category'])->count(),
            'badge_all_presentations'=>Badges::where(['lang_id'=>Langs::getLangId(Session::get('lang')), 'user_id'=>Auth::guard('mem')->id(), 'name'=>'badge_all_presentations'])->count(),
            'badge_all_simulations'=>Badges::where(['lang_id'=>Langs::getLangId(Session::get('lang')), 'user_id'=>Auth::guard('mem')->id(), 'name'=>'badge_all_simulations'])->count(),
            'badge_self_evaluation_test'=>Badges::where(['lang_id'=>Langs::getLangId(Session::get('lang')), 'user_id'=>Auth::guard('mem')->id(), 'name'=>'badge_self_evaluation_test'])->count(),
            'badge_final_test'=>Badges::where(['lang_id'=>Langs::getLangId(Session::get('lang')), 'user_id'=>Auth::guard('mem')->id(), 'name'=>'badge_final_test'])->count(),
            'badge_login_10'=>Badges::where(['lang_id'=>Langs::getLangId(Session::get('lang')), 'user_id'=>Auth::guard('mem')->id(), 'name'=>'badge_login_10'])->count()
        ]);

    }


}