<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use App\Http\Controllers\SettingsController;
use App\Models\Langs;
use App\Models\LMCategory;
use App\Models\KnowledgeTest;
use App\Models\KnowledgeOption;
use App\Models\KnowledgeUserTest;
use App\Models\KnowledgeUserOptions;
use Illuminate\Support\Facades\Validator;

class KnowledgeController extends BaseController {

    public function __construct()
    {
        parent::__construct();
    }

    public function index(Request $request, $uid){

        $arr = KnowledgeTest::where(['cat_id'=>$uid])->get()->toArray();

        $name = LMCategory::where(['id'=>$uid])->pluck('name')->first();

        if (empty($request->get('page')) || ! is_numeric($request->get('page'))) {

            $curr_page = 1;
        } else {

            $curr_page = $request->get('page');
        }

        $perPage = 25;

        $currentItems = array_slice($arr, $perPage * ($curr_page - 1), $perPage);

        $paginator = new LengthAwarePaginator($currentItems, count($arr), $perPage, $curr_page, [
            'path' => url('admin-panel/lm/knowledge_test/'.$uid)
        ]);

        $paginator = $paginator->appends('page', request('page'));

        if (count($arr) == 0) {

            $arr = array();
            $paginator = array();
        }

        return view('admin-panel.knowledge', [
            'recordlist'=>$paginator,
            'name'=>$name,
            'uid'=>$uid]);

    }

    public function import_question_dialog(Request $request, $cat_id){

        return view('admin-panel.dialog.knowledge', ['cat_id'=>$cat_id]);

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

                        if($vv[0] == 'Q'){

                            $addQ = new KnowledgeTest;
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

                    }

                }


                foreach($q as $kk1 => $vv1){

                    if(!empty($vv1)){

                        if($vv1[0] == '='){

                            // Correct answer
                            $answer = trim(str_replace("=","", $vv1));

                            $addOption = new KnowledgeOption;
                            $addOption->question_id = $addQ->id;
                            $addOption->option = $answer;
                            $addOption->correct = 1;
                            $addOption->save();

                        }

                        if($vv1[0] == '~'){

                            // Wrong answer
                            $answer = trim(str_replace("~","", $vv1));

                            $addOption = new KnowledgeOption;
                            $addOption->question_id = $addQ->id;
                            $addOption->option = $answer;
                            $addOption->correct = 0;
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


        KnowledgeOption::where(['question_id'=>$uid])->delete();
        KnowledgeTest::where(['id'=>$uid])->delete();

        return back()->withSuccess("Question deleted!");

    }

    public function question_edit(Request $request, $uid){

        $question = KnowledgeTest::where(['id'=>$uid])->get()->pluck('question')->first();

        $answers = KnowledgeOption::where(['question_id'=>$uid])->get()->toArray();

        return view('admin-panel.knowledge_edit', ['uid'=>$uid, 'question'=>$question, 'answers'=>$answers]);

    }

    public function question_update(Request $request, $uid){

        $question = $request->get('q');

        $answers = $request->get('option');

        $correct = $request->get('correct');

        if(is_array($answers) && count($answers)>0){

            KnowledgeTest::where(['id'=>$uid])->update(['question'=>$question]);

            foreach($answers as $k => $v){

                KnowledgeOption::where(['id'=>$k])->update(['option'=>$v, 'correct'=>$correct[$k]]);

            }

        }


        return back()->withSuccess("Updated!");
    }




}



?>