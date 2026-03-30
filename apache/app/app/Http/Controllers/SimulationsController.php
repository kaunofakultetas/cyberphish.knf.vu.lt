<?php

namespace App\Http\Controllers;

use Mail;
use Session;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Mail\SimulationsResults;
use App\Models\Badges;
use App\Models\SelfEvalUserTest;
use App\Models\SelfEval;
use App\Models\SelfEvalOption;
use App\Models\SelfEvalUser;
use App\Models\Settings;
use App\Models\Langs;
use App\Models\Members;
use App\Models\Scenarios;
use App\Models\ScenariosAttributes;
use App\Models\ScenariosCategory;
use App\Models\ScenariosInAttributes;
use App\Models\ScenariosInCategory;
use App\Models\ScenariosOptions;
use App\Models\ScenariosUsers;
use App\Models\ScenariosUsersOptions;

class SimulationsController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
    }

    public function index(Request $request, $lang){


    }

    public function categories(){

        if(count(ScenariosCategory::where(['lang_id'=>Langs::getLangId(Session::get('lang'))])->get()->toArray()) > 0){

            $arr = ScenariosCategory::where(['lang_id'=>Langs::getLangId(Session::get('lang'))])->orderByDesc('id')->get()->toArray();

        } else {

            $arr = array();

        }

        return view('simulations_categories', ['recordlist'=>$arr]);
    }

    public function simulations_list(Request $request, $lang, $name, $uid){

        $cat_simulations = ScenariosInCategory::selectraw('bei_scenarios.*')
        ->where(['bei_scenarios_in_category.cat_id'=>$uid, 'bei_scenarios_in_category.lang_id'=>Langs::getLangId(Session::get('lang'))])
        ->leftJoin('bei_scenarios', 'bei_scenarios.id', 'bei_scenarios_in_category.scenario_id')
        ->get()->toArray();

        foreach($cat_simulations as $k => $v){

            $cc = ScenariosUsers::where(['scenario_id'=>$v['id'], 'lang_id'=>Langs::getLangId(Session::get('lang')), 'finished'=>1, 'user_id'=>Auth::guard('mem')->id()])->orderByDesc('id');

            if($cc->count()>0){

                $cat_simulations[$k]['history'] = $cc->get()->first()->toArray();

            } else {

                $cat_simulations[$k]['history'] = [];

            }
        }

        return view('simulations_list', [
            'name'=>ScenariosCategory::where(['id'=>$uid])->pluck('name')->first(),
            'recordlist'=>$cat_simulations
        ]);
    }

    public function simulation_start(Request $request, $lang, $uid){

        $scenario = Scenarios::where(['id'=>$uid, 'lang_id'=>Langs::getLangId(Session::get('lang'))]) ->get()->first()->toArray();

        $categories = ScenariosInCategory::where(['scenario_id'=>$scenario['id'], 'lang_id'=>Langs::getLangId(Session::get('lang'))])->get()->toArray();
        $attributes = ScenariosInAttributes::where(['scenario_id'=>$scenario['id'], 'lang_id'=>Langs::getLangId(Session::get('lang'))])->get()->toArray();

        foreach($categories as $k => $v){
            $scenario['categories'][$v['cat_id']] = ScenariosCategory::where(['id'=>$v['cat_id']])->get()->pluck('name')->first();
        }

        foreach($attributes as $k1 => $v1){
            $scenario['attributes'][$v1['attribute_id']] = ScenariosAttributes::where(['id'=>$v1['attribute_id']])->get()->pluck('name')->first();
        }

        return view('simulation_start', $scenario);
    }

    public function simulation_progress(Request $request, $lang, $uid){

        $public_id = SimulationsController::gen_test_id();

        $add = new ScenariosUsers;
        $add->public_id = $public_id;
        $add->user_id = Auth::guard('mem')->id();
        $add->scenario_id = $uid;
        $add->lang_id = Langs::getLangId($lang);
        $add->finished = 0;
        $add->started = date("Y-m-d H:i:s");
        $add->points = 0;
        $add->save();

        Session::put('simulation_type_'.$public_id, $request->get('simulation_type'));

        return redirect('/'.$lang.'/simulation/progress/'.$public_id);

    }

    private static function gen_test_id(){

        $p_id = SettingsController::random_string();

        $num = ScenariosUsers::where(['public_id'=>$p_id])->count();

        if($num == 0){
            return $p_id;
        } elseif($num > 0){
            return ScenariosUsers::gen_test_id();
        }

    }

    public function simulation_do_stuff(Request $request, $lang, $public_id){

        $if_any = ScenariosUsersOptions::where(['user_scenario_public_id'=>$public_id])->count();

        $scenario_id = ScenariosUsers::where(['public_id'=>$public_id])->get()->pluck('scenario_id')->first();

        $finished=0;

        $feedback_list = [];

        if($if_any == 0){

            $options = ScenariosOptions::where(['scenario_id'=>$scenario_id, 'parent_option_id'=>0, 'level'=>1])->get()->toArray();

        } else {

            $level = ScenariosUsersOptions::selectraw("bei_scenarios_options.level as level")
            ->where(['bei_users_scenarios_options.user_scenario_public_id'=>$public_id, 'bei_users_scenarios_options.user_id'=>Auth::guard('mem')->id()])
            ->leftJoin('bei_scenarios_options', 'bei_scenarios_options.id', 'bei_users_scenarios_options.option_id')
            ->orderByDesc('bei_users_scenarios_options.id')->get()->pluck('level')->first();

            $parent_option_id = ScenariosUsersOptions::where(['user_scenario_public_id'=>$public_id, 'user_id'=>Auth::guard('mem')->id()])
            ->orderByDesc('id')->get()->pluck('option_id')->first();

            $new_level = $level+1;

            $options = ScenariosOptions::where(['scenario_id'=>$scenario_id, 'parent_option_id'=>$parent_option_id, 'level'=>$new_level])->get()->toArray();

            if(count($options)==0){
                $finished=1;

                ScenariosUsers::where([
                        'public_id'=>$public_id,
                        'user_id'=>Auth::guard('mem')->id(),
                        'finished'=>0])->update(['ended'=>date("Y-m-d H:i:s"),
                        'finished'=>1,
                        'points'=>SimulationsController::simulation_total_points($public_id)]);
                        
                Mail::to(Members::where(['id'=>Auth::guard('mem')->id()])->pluck('email')->first())
                ->send(new SimulationsResults(['points'=>SimulationsController::simulation_total_points($public_id)]));

                if(!in_array(0, SimulationsController::check_if_all_simulations_finished(Langs::getLangId($lang)))){

                    Badges::save_badge(Langs::getLangId($lang), 'badge_all_simulations');

                    $request->session()->now('success', __('main.new_badge'));

                }



                if(Session::get('simulation_type_'.$public_id) == 2){

                    $feedback_list = ScenariosUsersOptions::selectraw('bei_users_scenarios_options.*, bei_scenarios_options.feedback, bei_scenarios_options.situation')
                    ->leftJoin('bei_scenarios_options', 'bei_users_scenarios_options.option_id', 'bei_scenarios_options.id')
                    ->where(['user_scenario_public_id'=>$public_id, 'user_id'=>Auth::guard('mem')->id()])
                    ->whereraw("bei_scenarios_options.feedback != ''")
                    ->get()
                    ->toArray();

                } else {



                }

            }

        }


        return view('simulation_do_stuff', [
                        'options'=>$options,
                        'public_id'=>$public_id,
                        'scenario_id'=>$scenario_id,
                        'finished'=>$finished,
                        'points'=>SimulationsController::simulation_total_points($public_id),
                        'feedback_list'=>$feedback_list
        ]);
    }

    public function simulation_do_stuff_save(Request $request, $lang, $public_id){

        $option_id = $request->get('a');

        $user_scenario_id = ScenariosUsers::where(['public_id'=>$public_id])->get()->pluck('id')->first();
        $scenario_id = ScenariosUsers::where(['public_id'=>$public_id])->get()->pluck('scenario_id')->first();

        $option_type = ScenariosOptions::where(['id'=>$option_id])->get()->pluck('option_type')->first();
        $points = ScenariosOptions::where(['id'=>$option_id])->get()->pluck('points')->first();
        $feedback = ScenariosOptions::where(['id'=>$option_id])->get()->pluck('feedback')->first();

                $add = new ScenariosUsersOptions;
                $add->user_id = Auth::guard('mem')->id();
                $add->user_scenario_id = $user_scenario_id;
                $add->user_scenario_public_id = $public_id;
                $add->scenario_id = $scenario_id;
                $add->option_id = $option_id;
                $add->option_type = $option_type;
                $add->points = $points;
                $add->save();

                if(Session::get('simulation_type_'.$public_id) == 1){

                    Session::put('feedback_'.$public_id, $feedback);

                } else {

                    Session::put('feedback_'.$public_id, '');
                }


        return redirect('/'.$lang.'/simulation/progress/'.$public_id);
    }

    public static function simulation_total_points($public_id){

        $sum = ScenariosUsersOptions::selectraw("SUM(points) as total_points")->where(['user_scenario_public_id'=>$public_id, 'user_id'=>Auth::guard('mem')->id()])->get()->pluck('total_points')->first();

        return $sum;

    }

    public function history(){


        $my_simulations = ScenariosUsers::selectraw("bei_scenarios.*, bei_users_scenarios.public_id, bei_users_scenarios.points, bei_users_scenarios.started, bei_users_scenarios.ended,  bei_users_scenarios.finished")->
        leftJoin('bei_scenarios', 'bei_scenarios.id', 'bei_users_scenarios.scenario_id')->
        where(['bei_users_scenarios.user_id'=>Auth::guard('mem')->id(), 'bei_users_scenarios.lang_id'=>Langs::getLangId(Session::get('lang'))])->get()->toArray();

        return view('simulations_history', ['my_simulations'=>$my_simulations]);
    }

    public static function check_if_all_simulations_finished($lang_id){

        $get_scenarios = Scenarios::where(['lang_id'=>$lang_id])->get()->toArray();

        $return = [];

        foreach($get_scenarios as $k => $v){

                $solved = ScenariosUsers::where(['user_id'=>Auth::guard('mem')->id(), 'scenario_id'=>$v['id'], 'finished'=>1])->count();

                if($solved > 0){
                    $return[] = 1;
                } else {
                    $return[] = 0;
                }

        }

        return $return;

    }

















}