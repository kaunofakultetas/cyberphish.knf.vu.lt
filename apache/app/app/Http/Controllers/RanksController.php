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
use App\Models\Members;
use App\Models\Scenarios;
use App\Models\ScenariosAttributes;
use App\Models\ScenariosCategory;
use App\Models\ScenariosInAttributes;
use App\Models\ScenariosInCategory;
use App\Models\ScenariosOptions;
use App\Models\ScenariosUsers;
use App\Models\ScenariosUsersOptions;
use App\Models\ViewMaxPointsScenario;
use App\Models\ViewMaxPointsSelfEvalCat;

class RanksController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
    }

    public function self_evaluation(Request $request, $lang){

        $ranks = ViewMaxPointsSelfEvalCat::selectraw("id, username, SUM(max_points) as points")
        ->where(['lang_id'=>Langs::getLangId($lang)])
        ->groupby('id')
        ->orderbydesc('points')
        ->get()
        ->toArray();

        foreach($ranks as $k => $v){

            $ranks[$k]['badges'] = Badges::selectraw("DISTINCT(name) as name")->where(['user_id'=>$v['id']])->get()->toArray();

        }

        return view('rank_self_eval', ['ranking_table'=>$ranks]);
    }

    public function simulations(Request $request, $lang){

        $ranks = ViewMaxPointsScenario::selectraw("id, username, SUM(max_points) as points")
        ->where(['lang_id'=>Langs::getLangId($lang)])
        ->groupby('id')
        ->orderbydesc('points')
        ->get()
        ->toArray();

        foreach($ranks as $k => $v){

            $ranks[$k]['badges'] = Badges::selectraw("DISTINCT(name) as name")->where(['user_id'=>$v['id']])->get()->toArray();

        }


        return view('rank_simulations', ['ranking_table'=>$ranks]);
    }

}