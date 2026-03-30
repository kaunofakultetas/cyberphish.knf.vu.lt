<?php

namespace App\Http\Controllers\Admin;

use Session;
use App\Http\Controllers\Controller;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use App\Models\LMUsers;
use App\Models\Langs;
use App\Models\Members;
use App\Models\Managers;
use App\Models\Scenarios;
use App\Models\ScenariosUsers;
use App\Models\ScenariosUsersOptions;
use App\Models\ScenariosOptions;
use App\Models\SelfEvalOption;
use App\Models\SelfEvalUser;
use App\Models\SelfEvalUserTest;
use App\Models\KnowledgeTest;
use App\Models\KnowledgeOption;
use App\Models\KnowledgeUserTest;
use App\Models\KnowledgeUserOptions;

class ManagerMainController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
    }

    public function dashboard()
    {
        $langlist = Langs::all()->pluck('name', 'id')->toArray();


        if(count(Members::where(['country'=>Managers::where(['id'=>Auth::guard('man')->id()])->get()->pluck('country')->first()])->get()->toArray()) > 0){

            $arr = array();
            foreach($langlist as $k => $v){
                $arr[$k] = Members::where(['country'=>Managers::where(['id'=>Auth::guard('man')->id()])->get()->pluck('country')->first()])
                ->orderByDesc('id')
                ->get()
                ->toArray();
            }

            foreach($arr as $kk => $vv){

                foreach($vv as $kkk => $vvv){

                    $arr[$kk][$kkk]['course_progress'] = LMUsers::course_progress($vvv['id'], $kk);
                    $arr[$kk][$kkk]['simulations'] = Scenarios::where(['lang_id'=>$kk])->count().' / '.ScenariosUsers::selectraw("*")->where(['user_id'=>$vvv['id'], 'lang_id'=>$kk])->distinct('scenario_id')->count();
                    $arr[$kk][$kkk]['knowledge_results'] = ManagerMainController::knowledge_results($vvv['id'], $kk);
                }

            }


        } else {

            $arr = array();

        }

        return view('admin-panel.dashboard', ['langlist'=>$langlist, 'recordlist'=>$arr, 'total_users'=>count(Members::where(['country'=>Managers::where(['id'=>Auth::guard('man')->id()])->get()->pluck('country')->first()])->get()->toArray())]);
    }

    public function change_pass(Request $request){

        if (!(Hash::check($request->get('password'), Auth::user()->password))) {

            return redirect()->back()->withErrors(__('main.msg_current_pass_wrong'));
        }

        if(strcmp($request->get('password'), $request->get('newpass')) == 0){

            return redirect()->back()->withErrors(__('main.msg_current_new_same'));
        }

        if(strcmp($request->get('newpass'), $request->get('renewpass')) <> 0){

            return redirect()->back()->withErrors(__('main.msg_new_pass_dont_match'));
        }

        $validator = Validator::make($request->all(), [
            'newpass' => 'required|min:6',
            'renewpass' => 'required|min:6'
        ]);

        if($validator->fails()){

            return redirect()->back()->withErrors(__('main.msg_pass_too_short', ['length'=>6]));

        }


        Managers::where('id', Auth::id())->update(['password' => Hash::make($request->get('newpass'))]);

        return redirect()->back()->withSuccess(__('main.msg_pass_changed'));


    }

    public function self_evaluation_history(Request $request, $lang_id, $user_id){

        if(count(SelfEvalUserTest::selectraw("bei_users_selfeval_test.*, bei_lm_category.name as category_name")
            ->leftJoin('bei_lm_category', 'bei_lm_category.id', 'bei_users_selfeval_test.cat_id')
            ->where([
                'bei_users_selfeval_test.user_id'=>$user_id,
                'bei_lm_category.lang_id'=>$lang_id
            ])->orderByDesc('points')->get()->toArray()) > 0){

                $arr = SelfEvalUserTest::selectraw("bei_users_selfeval_test.*, bei_lm_category.name as category_name, @correct:=0 AS correct")
                ->leftJoin('bei_lm_category', 'bei_lm_category.id', 'bei_users_selfeval_test.cat_id')
                ->where([
                    'bei_users_selfeval_test.user_id'=>$user_id,
                    'bei_lm_category.lang_id'=>$lang_id
                ])->orderByDesc('points')->get()->toArray();


        } else {

            $arr = array();

        }

        if (empty($request->get('page')) || ! is_numeric($request->get('page'))) {

            $curr_page = 1;
        } else {

            $curr_page = $request->get('page');
        }

        $perPage = 25;

        $currentItems = array_slice($arr, $perPage * ($curr_page - 1), $perPage);

        foreach($currentItems as $k => $v){

            $a = SelfEvalUser::where(['test_id'=>$v['id']])->groupBy('question_id')->get()->toArray();

          $c=0;
          foreach($a as $kk => $vv){


              if(ManagerMainController::question_correct($vv['question_id'], $vv['test_id'], $vv['user_id']) == true){
                    $c++;
                 }

           }

           $currentItems[$k]['correct'] = $c;
           $c=0;

        }

        $paginator = new LengthAwarePaginator($currentItems, count($arr), $perPage, $curr_page, [
            'path' => url('admin-panel/user/self_evaluation_history/'.$lang_id.'/'.$user_id)
        ]);

        $paginator = $paginator->appends('page', request('page'));

        if (count($arr) == 0) {

            $arr = array();
            $paginator = array();
        }
        return view('admin-panel.user_self_eval_history', ['recordlist' => $paginator, 'email'=>Members::where(['id'=>$user_id])->get()->pluck('email')->first(), 'language'=>Langs::where(['id'=>$lang_id])->get()->pluck('name')->first()]);

    }

    public function simulations_history(Request $request, $lang_id, $user_id){

        if(count(ScenariosUsers::selectraw("bei_scenarios.*, bei_users_scenarios.public_id, bei_users_scenarios.points, bei_users_scenarios.started, bei_users_scenarios.ended,  bei_users_scenarios.finished")->
            leftJoin('bei_scenarios', 'bei_scenarios.id', 'bei_users_scenarios.scenario_id')->
            where(['bei_users_scenarios.user_id'=>$user_id, 'bei_users_scenarios.lang_id'=>$lang_id])->get()->toArray()) > 0){

                $arr = ScenariosUsers::selectraw("bei_scenarios.*, bei_users_scenarios.public_id, bei_users_scenarios.points, bei_users_scenarios.started, bei_users_scenarios.ended,  bei_users_scenarios.finished")->
                leftJoin('bei_scenarios', 'bei_scenarios.id', 'bei_users_scenarios.scenario_id')->
                where(['bei_users_scenarios.user_id'=>$user_id, 'bei_users_scenarios.lang_id'=>$lang_id])->get()->toArray();


            } else {

                $arr = array();

            }

            if (empty($request->get('page')) || ! is_numeric($request->get('page'))) {

                $curr_page = 1;
            } else {

                $curr_page = $request->get('page');
            }

            $perPage = 25;

            $currentItems = array_slice($arr, $perPage * ($curr_page - 1), $perPage);

            $paginator = new LengthAwarePaginator($currentItems, count($arr), $perPage, $curr_page, [
                'path' => url('admin-panel/user/simulations_history/'.$lang_id.'/'.$user_id)
            ]);

            $paginator = $paginator->appends('page', request('page'));

            if (count($arr) == 0) {

                $arr = array();
                $paginator = array();
            }

            return view('admin-panel.user_simulations_history', ['recordlist' => $paginator, 'email'=>Members::where(['id'=>$user_id])->get()->pluck('email')->first(), 'language'=>Langs::where(['id'=>$lang_id])->get()->pluck('name')->first()]);

    }

    public static function question_correct($question_id, $test_id, $user_id){

        $pasirinkimai = SelfEvalUser::select('option_id')->where(['question_id'=>$question_id, 'test_id'=>$test_id, 'user_id'=>$user_id])->get()->pluck('option_id')->toArray();
        $question_options = SelfEvalOption::select('id')->where(['question_id'=>$question_id, 'correct'=>1])->get()->pluck('id')->toArray();


        if(count($pasirinkimai) == count($question_options)){

            $i=0;
            foreach($pasirinkimai as $k => $v){

                if(!in_array($v, $question_options)){
                    return false;
                }

            }

            foreach($question_options as $k => $v){

                if(!in_array($v, $pasirinkimai)){
                    return false;
                }

            }

            return true;

        } else {
            return false;
        }


    }

    public static function knowledge_results($user_id, $lang_id){

        $results = KnowledgeUserTest::select(['public_id', 'results'])->where(['user_id'=>$user_id, 'lang_id'=>$lang_id, 'finished'=>1])->get()->toArray();

        if(count($results)>0){
            foreach($results as $k => $v){
                $link[] = "<!--<a href=\"".env('APP_URL_ADMIN')."/user/knowledge_results/{$v['public_id']}\">-->{$v['results']}%<!--</a>-->";
            }
        } else {
            $link = [];
        }

        if(count($results)>0){
            return implode(", ", $link);
        } else {
            return '';
        }

    }

    public function simulation_report(Request $request, $public_id){

        $user_id = ScenariosUsers::where(['public_id'=>$public_id])->get()->pluck('user_id')->first();
        $lang_id = ScenariosUsers::where(['public_id'=>$public_id])->get()->pluck('lang_id')->first();
        $points = ScenariosUsers::where(['public_id'=>$public_id])->get()->pluck('points')->first();
        $scenario_id = ScenariosUsers::where(['public_id'=>$public_id])->get()->pluck('scenario_id')->first();

        $options = ScenariosOptions::where(['scenario_id'=>$scenario_id])->get()->toArray();

        $descr = Scenarios::where(['id'=>$scenario_id])->get()->pluck('descr')->first();

        $selected_options = ScenariosUsersOptions::where(['user_scenario_public_id'=>$public_id])->get()->pluck('option_id')->toArray();

            return view('admin-panel.simulation_report', [
                'email'=>Members::where(['id'=>$user_id])->get()->pluck('email')->first(),
                'language'=>Langs::where(['id'=>$lang_id])->get()->pluck('name')->first(),
                'descr'=>$descr,
                'options'=>$options,
                'points'=>$points,
                'selected_options'=>$selected_options
            ]);
    }

    public function knowledge_report(Request $request, $public_id){

        $user_id = KnowledgeUserTest::where(['public_id'=>$public_id])->get()->pluck('user_id')->first();
        $results = KnowledgeUserTest::where(['public_id'=>$public_id])->get()->pluck('results')->first();

        $email = Members::where(['id'=>$user_id])->get()->pluck('email')->first();

        $question_set = json_decode(KnowledgeUserTest::where(['public_id'=>$public_id])->get()->pluck('question_set')->first(), 1);

        $rez = [];
        foreach($question_set as $k => $v){

            $rez[$k]['question'] = KnowledgeTest::where(['id'=>$k])->get()->pluck('question')->first();
            $rez[$k]['answers'] = KnowledgeOption::select(['id', 'option'])->where(['question_id'=>$k])->get()->pluck('option', 'id')->toArray();
            $rez[$k]['is_correct'] = KnowledgeUserOptions::where(['public_id'=>$public_id, 'question_id'=>$k])->get()->pluck('correct')->first();
            $rez[$k]['correct_option_id'] = KnowledgeOption::select('id')->where(['question_id'=>$k, 'correct'=>1])->get()->pluck('id')->first();

        }

        return view('admin-panel.knowledge_results', [
            'email'=>$email,
            'results'=>$results
        ]);

    }

}