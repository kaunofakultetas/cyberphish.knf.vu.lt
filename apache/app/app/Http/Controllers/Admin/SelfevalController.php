<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use App\Http\Controllers\SettingsController;
use App\Models\Langs as Langs;
use App\Models\LMCategory;
use App\Models\SelfEval;
use App\Models\SelfEvalOption;
use App\Models\SelfEvalUser;
use Illuminate\Support\Facades\Validator;

class SelfevalController extends BaseController {

    public function __construct()
    {
        parent::__construct();
    }

    public function index(Request $request, $uid){

        $arr = SelfEval::where(['cat_id'=>$uid])->get()->toArray();

        $name = LMCategory::where(['id'=>$uid])->pluck('name')->first();

        if (empty($request->get('page')) || ! is_numeric($request->get('page'))) {

            $curr_page = 1;
        } else {

            $curr_page = $request->get('page');
        }

        $perPage = 25;

        $currentItems = array_slice($arr, $perPage * ($curr_page - 1), $perPage);

        $paginator = new LengthAwarePaginator($currentItems, count($arr), $perPage, $curr_page, [
            'path' => url('admin-panel/lm/self_eval/'.$uid)
        ]);

        $paginator = $paginator->appends('page', request('page'));

        if (count($arr) == 0) {

            $arr = array();
            $paginator = array();
        }

        return view('admin-panel.self_eval', [
            'recordlist'=>$paginator,
            'name'=>$name,
            'uid'=>$uid]);

    }

    public function import_question_dialog(Request $request, $cat_id){

        return view('admin-panel.dialog.self_eval', ['cat_id'=>$cat_id]);

    }

    public function import_questions(Request $request){

        $content = file_get_contents($request->file('doc'));
        $cat_id = $request->get('cat_id');

        $content = explode("::", $content);


        foreach($content as $k => $v){

            if(!empty(trim($v))){

                $q = explode("\n", $v);

                $correct_answers = 0;

                foreach($q as $kk => $vv){

                    if(!empty($vv)){

                        if($vv[0] == 'Q' || $vv[0] == 'K' || $vv[0] == 'Ε' || trim($vv[0]) == 'J'){

                                $addQ = new SelfEval;
                                $question = explode('\:', $vv);
                                $question = trim($question[1]);

                                $addQ->cat_id = $cat_id;
                                $addQ->question = $question;
                                $addQ->save();

                            }

                            if($vv[0] == '='){

                                // Correct answer
                                $correct_answers++;
                            }

                            if($correct_answers == 1){

                                SelfEval::where(['id'=>$addQ->id])->update(['q_type'=>1]);

                            } elseif($correct_answers > 1){

                                SelfEval::where(['id'=>$addQ->id])->update(['q_type'=>2]);

                            }



                    }

                }


                foreach($q as $kk1 => $vv1){

                    if(!empty($vv1)){

                        if($vv1[0] == '='){

                            // Correct answer
                            $answer = trim(str_replace("=","", $vv1));

                            $addOption = new SelfEvalOption;
                            $addOption->question_id = $addQ->id;
                            $addOption->option = $answer;
                            $addOption->correct = 1;
                            $addOption->points = round(100/$correct_answers, 2);
                            $addOption->save();

                        }

                        if($vv1[0] == '~'){

                            // Wrong answer
                            $answer = trim(str_replace("~","", $vv1));

                            $addOption = new SelfEvalOption;
                            $addOption->question_id = $addQ->id;
                            $addOption->option = $answer;
                            $addOption->correct = 0;
                            $addOption->points = 0;
                            $addOption->save();

                        }

                    }

            }

            $correct_answers = 0;

        }


        }


        return back()->withSuccess("Questions imported!");

    }

    public function question_delete(Request $request, $uid){


        SelfEvalOption::where(['question_id'=>$uid])->delete();
        SelfEval::where(['id'=>$uid])->delete();

        return back()->withSuccess("Question deleted!");

    }

    public function question_edit(Request $request, $uid){

        $question = SelfEval::where(['id'=>$uid])->get()->pluck('question')->first();

        $q_type = SelfEval::where(['id'=>$uid])->get()->pluck('q_type')->first();

        $answers = SelfEvalOption::where(['question_id'=>$uid])->get()->toArray();

        return view('admin-panel.self_eval_edit', ['uid'=>$uid, 'question'=>$question, 'answers'=>$answers, 'q_type'=>$q_type]);

    }

    public function question_update(Request $request, $uid){

        $question = $request->get('q');

        $answers = $request->get('option');

        $correct = $request->get('correct');

        $points = $request->get('points');

        if(is_array($answers) && count($answers)>0){

            SelfEval::where(['id'=>$uid])->update(['question'=>$question, 'q_type'=>$request->get('q_type')]);

            foreach($answers as $k => $v){

                SelfEvalOption::where(['id'=>$k])->update(['option'=>$v, 'correct'=>$correct[$k], 'points'=>$points[$k]]);

            }

        }


        return back()->withSuccess("Updated!");
    }


}



?>