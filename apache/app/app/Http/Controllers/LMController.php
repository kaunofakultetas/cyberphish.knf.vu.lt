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

class LMController extends BaseController
{
    public function __construct()
    {
        parent::__construct();

    }

    public function index(){

        return view('learning-material');

    }

    public function content(Request $request, $lang=null, $slug, $uid){


        return view('lm-content',[
            'content'=>LMContent::where(['id'=>$uid])->get()->first()->toArray(),
            'additional_files'=>LMFiles::where(['content_id'=>$uid, 'embed'=>0])->get()->toArray(),
            'embeded_files'=>LMFiles::where(['content_id'=>$uid, 'embed'=>1])->get()->toArray(),
            'show_self_eval'=>$this->show_self_eval($uid),
            'cat_id'=>LMContent::where(['id'=>$uid])->pluck('cat_id')->first(),
            'uid'=>$uid
        ]);

    }

    public static function category_content_list(){

        $get_categories = LMCategory::where(['lang_id'=>Langs::getLangId(Session::get('lang'))])->get()->pluck('name', 'id')->toArray();

        $arr = array();
        foreach($get_categories as $k => $v){

            $arr[$k]['category_name'] = $v;

            $arr[$k]['items'] = LMContent::select("id", "title")->where(['cat_id'=>$k])->get()->pluck("title", "id")->toArray();

        }

        return $arr;



    }

    public function mark_completed(Request $request){

        $content_id = LMContent::get_id($request->get('ued'));
        $lang_id = LMContent::where(['id'=>$content_id])->get()->pluck('lang_id')->first();
        $user_id = Auth::guard('mem')->id();

        if($content_id !== false){
            LMUsers::firstOrCreate([
                'user_id'=>$user_id,
                'content_id'=>$content_id
            ]);

            $num = LMUsers::where(['user_id'=>$user_id])->count();

            if($num == 1){

                $badge = Badges::save_badge($lang_id, 'badge_topic');

                return back()->withSuccess(__('main.new_badge'));

            }

            if($this->show_self_eval($content_id) == true){

                $a = Badges::save_badge($lang_id, 'badge_category');

                if($a == true){

                    return back()->withSuccess(__('main.new_badge'));

                } else {

                    if(!in_array(0, LMController::check_if_all_presentations_finished($lang_id))){

                        $b = Badges::save_badge($lang_id, 'badge_all_presentations');

                        if($b == true){
                            return back()->withSuccess(__('main.new_badge'));
                        } else {
                            return back();
                        }

                    }

                    return back();

                }

            }

        }


        return back();

    }

    public static function check_if_all_presentations_finished($lang_id){

        $get_all_categories = LMCategory::where(['lang_id'=>$lang_id])->get()->toArray();

        $return = [];

        foreach($get_all_categories as $k => $v){

            $category_content = LMContent::where(['cat_id'=>$v['id']])->get()->toArray();

            foreach($category_content as $kk => $vv){

                $content_solved = LMController::show_self_eval($vv['id']);

                if($content_solved == true){
                    $return[] = 1;
                } else {
                    $return[] = 0;
                }
            }
        }

        return $return;

    }

    public static function show_self_eval($content_id){

        $user_id = Auth::guard('mem')->id();

        if($user_id !== null){

            $cat_id = LMContent::where(['id'=>$content_id])->pluck('cat_id')->first();

            $completed_category = LMContent::where(['cat_id'=>$cat_id])->get()->pluck('id')->toArray();

            foreach($completed_category as $kk => $vv){

                $content = LMUsers::where(['user_id'=>$user_id, 'content_id'=>$vv])->count();

                if($content == 0){
                    return false;
                }

            }

            return true;

        } else {

            return false;
        }


    }

    public function self_evaluation_test(Request $request, $lang=null, $cat_id){

        $p_id = LMController::gen_test_id();

        $addRecord = new SelfEvalUserTest;
        $addRecord->user_id = Auth::guard('mem')->id();
        $addRecord->cat_id = $cat_id;
        $addRecord->lang_id = Langs::getLangId(Session::get('lang'));
        $addRecord->public_id = $p_id;
        $addRecord->started = date("Y-m-d H:i:s");
        $addRecord->save();

        return redirect('/cp/se/'.$p_id.'/'.$cat_id);

    }

    public function self_eval_test(Request $request, $public_id, $cat_id){

        $cat_name = LMCategory::where(['id'=>$cat_id])->pluck('name')->first();

        $self_eval_limit = (int)Settings::where(['id'=>2])->pluck('val3')->first();

        $test_id = SelfEvalUserTest::select('id')->where(['public_id'=>$public_id])->get()->pluck('id')->first();

        $answered = SelfEvalUser::select("question_id")->where(['test_id'=>$test_id, 'user_id'=>Auth::guard('mem')->id()])->groupby('question_id')->get()->toArray();

        $next_count = SelfEval::selectraw("bei_selfeval_question.*")->where(['cat_id'=>$cat_id])->whereNotIn('bei_selfeval_question.id', $answered)->count();

        if($next_count > 0){
            $next_q = SelfEval::selectraw("bei_selfeval_question.*")->where(['cat_id'=>$cat_id])->whereNotIn('bei_selfeval_question.id', $answered)->inRandomOrder()->get()->first()->toArray();
            $answers = SelfEvalOption::where(['question_id'=>$next_q['id']])->inRandomOrder()->get()->toArray();
        } else {
            $next_q = [];
            $answers = [];
        }

        if(count($answered) == 0){
            $progress = 0;
        } else {
            $progress = round(count($answered) * 100 / $self_eval_limit);
        }

        return view('self-eval',[
            'category_name'=>$cat_name,
            'self_eval_limit'=>$self_eval_limit,
            'q'=>$next_q,
            'a'=>$answers,
            'progress'=>$progress
       ]);

    }

    public function self_eval_test_save(Request $request, $public_id, $cat_id){

        $a = $request->get('a');

        $question_id = SelfEval::select('id')->whereRaw("MD5(MD5(id))=?", [$request->get('pbid')])->get()->pluck('id')->first();

        $test_id = SelfEvalUserTest::select('id')->where(['public_id'=>$public_id])->get()->pluck('id')->first();

        if(is_array($a) && count($a)>0){
            foreach($a as $k => $v){

                $addRecord = new SelfEvalUser;
                $addRecord->user_id = Auth::guard('mem')->id();
                $addRecord->question_id = $question_id;
                $addRecord->cat_id = $cat_id;
                $addRecord->test_id = $test_id;
                $addRecord->public_id = $public_id;
                $addRecord->option_id = $v;
                $addRecord->correct = SelfEvalOption::select('correct')->where(['id'=>$v])->get()->pluck('correct')->first();
                $addRecord->points = SelfEvalOption::select('points')->where(['id'=>$v])->get()->pluck('points')->first();
                $addRecord->save();

            }
        }

        return redirect('/cp/se/'.$public_id.'/'.$cat_id);

    }

    private static function gen_test_id(){

        $p_id = SettingsController::random_string();

        $num = SelfEvalUserTest::where(['public_id'=>$p_id])->count();

        if($num == 0){
            return $p_id;
        } elseif($num > 0){
            return SelfEvalUserTest::gen_test_id();
        }

    }

    public function self_eval_test_results(Request $request, $public_id){

        $test_id = SelfEvalUserTest::where(['public_id'=>$public_id, 'finished'=>1])->get()->pluck('id')->first();

        $cat_id = SelfEvalUserTest::where(['public_id'=>$public_id, 'finished'=>1])->get()->pluck('cat_id')->first();

        $points = SelfEvalUserTest::where(['public_id'=>$public_id, 'finished'=>1])->get()->pluck('points')->first();

        $question_ids = SelfEvalUser::select('question_id')->where(['test_id'=>$test_id, 'user_id'=>Auth::guard('mem')->id()])->groupby('question_id')->get()->pluck('question_id')->toArray();

        $results = []; $i=0;
        foreach($question_ids as $k => $v){

            $results[$i]['question'] = SelfEval::where(['id'=>$v])->get()->pluck('question')->first();

            $answ = SelfEvalOption::where(['question_id'=>$v])->get()->toArray();

            $results[$i]['answers'] = $answ;

            foreach($answ as $kk => $vv){

                $results[$i]['answers'][$kk]['selected'] = SelfEvalUser::where(['test_id'=>$test_id, 'user_id'=>Auth::guard('mem')->id(), 'option_id'=>$vv['id']])->count();

            }




            $i++;
        }

        return view('self_eval_results', [
            'category_name'=>LMCategory::where(['id'=>SelfEvalUserTest::where(['public_id'=>$public_id])->get()->pluck('cat_id')->first()])->get()->pluck('name')->first(),
            'started'=>SelfEvalUserTest::where(['public_id'=>$public_id])->get()->pluck('started')->first(),
            'ended'=>SelfEvalUserTest::where(['public_id'=>$public_id])->get()->pluck('ended')->first(),
            'points_collected'=>$points,
            'cat_id'=>$cat_id,
            'results'=>$results
        ]);

    }

    public function self_evaluation_history(){

        $my_tests = SelfEvalUserTest::selectraw("bei_users_selfeval_test.*, bei_lm_category.name as category_name")
        ->leftJoin('bei_lm_category', 'bei_lm_category.id', 'bei_users_selfeval_test.cat_id')
        ->where([
            'bei_users_selfeval_test.user_id'=>Auth::guard('mem')->id(),
            'bei_lm_category.lang_id'=>Langs::getLangId(Session::get('lang'))
        ])->orderByDesc('points')->get()->toArray();

        return view('self_eval_history', ['my_tests'=>$my_tests]);
    }

    public static function pc_questionare_link($country){

        $links = [
            0=>'',
            1=>'https://forms.gle/obu5qrFHEMu8FTkx6',
            2=>'https://forms.gle/JaeYxCyQVBy7GWax5',
            3=>'https://forms.gle/pxofi1kKf7432xez5',
            4=>'https://forms.gle/4ZEAtra2UHzLdqYm9',
            5=>'https://forms.gle/wz2YtVcwCnz7kjXq6'
        ];

        return $links[$country];

    }


}