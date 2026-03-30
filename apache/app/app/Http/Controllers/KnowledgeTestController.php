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
use App\Models\KnowledgeTest;
use App\Models\KnowledgeOption;
use App\Models\KnowledgeUserTest;
use App\Models\KnowledgeUserOptions;

class KnowledgeTestController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
    }

    public function index(Request $request, $lang){

        $count = KnowledgeUserTest::where(['user_id'=>Auth::guard('mem')->id(), 'lang_id'=>Langs::getLangId($lang)])->count();

        $unfinished = KnowledgeUserTest::where(['finished'=>0, 'user_id'=>Auth::guard('mem')->id(), 'lang_id'=>Langs::getLangId($lang)])->count();

        $count_questions = KnowledgeTest::whereIn('cat_id', KnowledgeTestController::get_lang_cats($lang))->count();

        $passed = KnowledgeUserTest::select('ended')->where(['user_id'=>Auth::guard('mem')->id(), 'finished'=>1, 'lang_id'=>Langs::getLangId($lang)])->where('results', '>', 74)->count();

        if($unfinished == 0){

            return view('knowledge_test', [
                'total_tried'=>$count,
                'count_questions'=>$count_questions,
                'results'=>KnowledgeTestController::knowledge_results(Auth::guard('mem')->id(), Langs::getLangId($lang)),
                'passed'=>$passed,
                'fullname'=>Members::select('fullname')->where(['id'=>Auth::guard('mem')->id()])->get()->pluck('fullname')->first()
            ]);

        } else {

            $public_id = KnowledgeUserTest::where(['finished'=>0, 'user_id'=>Auth::guard('mem')->id(), 'lang_id'=>Langs::getLangId($lang)])->get()->pluck('public_id')->first();

            return redirect('/'.$lang.'/knowledge_test/progress/'.$public_id);

        }
    }

    public function start(Request $request, $lang){


        $unfinished = KnowledgeUserTest::where(['finished'=>0, 'user_id'=>Auth::guard('mem')->id(), 'lang_id'=>Langs::getLangId($lang)])->count();

        if($unfinished == 0){

        $public_id = KnowledgeTestController::gen_test_id();

        $knowledge_limits = json_decode(Settings::where(['id'=>3])->get()->pluck('val1')->first(),1);

        $lang_limits = $knowledge_limits[Langs::getLangId($lang)];

        $q = []; $qq = [];

        foreach($lang_limits as $k => $v){

            $questions = KnowledgeTest::where(['cat_id'=>$k])->inRandomOrder()->limit($v)->get()->pluck('id')->toArray();

            $q = array_merge($q, $questions);

        }

        foreach($q as $kk => $vv){
            $qq[$vv] = 0;
        }

        $q1 = json_encode($qq);

        $add = new KnowledgeUserTest;
        $add->public_id = $public_id;
        $add->user_id = Auth::guard('mem')->id();
        $add->lang_id = Langs::getLangId($lang);
        $add->finished = 0;
        $add->started = date("Y-m-d H:i:s");
        $add->question_set = $q1;
        $add->results = 0;
        $add->save();

        } else {

            $public_id = KnowledgeUserTest::where(['finished'=>0, 'user_id'=>Auth::guard('mem')->id(), 'lang_id'=>Langs::getLangId($lang)])->get()->pluck('public_id')->first();

        }


        return redirect('/'.$lang.'/knowledge_test/progress/'.$public_id);
    }

    private static function gen_test_id(){

        $p_id = SettingsController::random_string();

        $num = KnowledgeUserTest::where(['public_id'=>$p_id])->count();

        if($num == 0){
            return $p_id;
        } elseif($num > 0){
            return KnowledgeTestController::gen_test_id();
        }

    }

    public function progress(Request $request, $lang, $public_id){

        $lang_id = Langs::getLangId($lang);
        $id = KnowledgeUserTest::getPrivateId($public_id);

        $qids = KnowledgeUserTest::getQIds($id);

        $finished = 1;
        $answered = 0;
        foreach($qids as $k => $v){

            if($v == 0){
                $q_id = $k;
                $finished = 0;
                break;
            } else {
                $answered++;
            }
        }

        if($finished == 0){

            $options = KnowledgeOption::where(['question_id'=>$q_id])->inRandomOrder()->get()->toArray();

            $question = KnowledgeTest::getQuestion($q_id);

            $progress = round($answered * 100 / count($qids));

            $results = 0;

        } elseif($finished == 1){

            $progress = 100;

            $results = KnowledgeTestController::calculate_results($id);

            $q_id = 0;
            $question = '';
            $options = [];

            KnowledgeUserTest::where(['id'=>$id, 'user_id'=>Auth::guard('mem')->id()])->update(['finished'=>1, 'ended'=>date('Y-m-d H:i:s'), 'results'=>$results]);

            $finished_num = KnowledgeUserTest::where(['user_id'=>Auth::guard('mem')->id(), 'finished'=>1])->count();

            if($finished_num == 1){

                if($results > 74){

                    $a = Badges::save_badge($lang_id, 'badge_final_test');

                    if($a == true){
                        $request->session()->now('success', __('main.new_badge'));
                    }

                }

            }

        }

       return view('knowledge_progress', [
           'test_id'=>$id,
           'question_id'=>$q_id,
           'question'=>$question,
           'options'=>$options,
           'progress'=>$progress,
           'finished'=>$finished,
           'results'=>$results,
           'fullname'=>Members::select('fullname')->where(['id'=>Auth::guard('mem')->id()])->get()->pluck('fullname')->first()
       ]);

    }

    public function progress_save(Request $request, $lang, $public_id){

        $lang_id = Langs::getLangId($lang);
        $id = KnowledgeUserTest::getPrivateId($public_id);

        $question_id = KnowledgeTest::getDecryptedId($request->get('pbid'));

        $a = $request->get('a');

        if(is_array($a) && count($a)>0){
            foreach($a as $k => $v){

                $addRecord = new KnowledgeUserOptions;
                $addRecord->cat_id = KnowledgeTest::getCatId($id);
                $addRecord->test_id = $id;
                $addRecord->public_id = $public_id;
                $addRecord->user_id = Auth::guard('mem')->id();
                $addRecord->question_id = $question_id;
                $addRecord->correct = KnowledgeOption::select('correct')->where(['id'=>$v])->get()->pluck('correct')->first();
                $addRecord->save();

            }
        }

        $qids = KnowledgeUserTest::getQIds($id);
        foreach($qids as $k => $v){
            if($k == $question_id){
                $newqids[$k] = 1;
            } else {
                $newqids[$k] = $v;
            }
        }

        KnowledgeUserTest::where(['id'=>$id, 'user_id'=>Auth::guard('mem')->id()])->update(['question_set'=>json_encode($newqids,1)]);

        return redirect('/'.$lang.'/knowledge_test/progress/'.$public_id);

    }

    public static function calculate_results($test_id){

        $total_questions = KnowledgeUserOptions::where(['test_id'=>$test_id, 'user_id'=>Auth::guard('mem')->id()])->count();

        $correct_questions = KnowledgeUserOptions::where(['test_id'=>$test_id, 'user_id'=>Auth::guard('mem')->id(), 'correct'=>1])->count();

        $results = round($correct_questions * 100 / $total_questions);

        return $results;

    }

    public static function get_lang_cats($lang){

        $lang_id = Langs::getLangId($lang);

        $cats = LMCategory::select('id')->where(['lang_id'=>$lang_id])->get()->pluck('id')->toArray();

        return $cats;


    }

    public static function knowledge_results($user_id, $lang_id){

        $results = KnowledgeUserTest::select('results')->where(['user_id'=>$user_id, 'lang_id'=>$lang_id, 'finished'=>1])->get()->pluck('results')->toArray();

        if(count($results)>0){
            return __('main.results').' '. implode("%, ", $results).'%';
        } else {
            return '';
        }

    }

















}