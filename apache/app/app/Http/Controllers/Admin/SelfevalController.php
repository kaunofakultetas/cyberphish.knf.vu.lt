<?php

// -----------------------------------------------------------
//  [*] SelfevalController — the self-evaluation questions
//
//  The admin side of the self-evaluations: the questions
//  of one learning-material category, imported from a
//  plain-text file, listed, edited and deleted. A question
//  is single-choice (q_type 1) or multiple-choice (2); its
//  options are correct or not and carry points. Every
//  route sits behind auth.admin:man and perms, so only a
//  main admin gets here. Members answer the questions
//  through App\Http\Controllers\LMController.
//
//  Used by:
//    - routes/web_admin.php — the admin-panel/lm/self_eval
//      and admin-panel/dialog/lm/self_eval routes
// -----------------------------------------------------------


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








// -----------------------------------------------------------
// SelfevalController
// -----------------------------------------------------------
//
// The question pages of one category, reached from the
// categories page of Admin\LMController.
//
//   index                  — the questions of a category
//   import_question_dialog,
//   import_questions       — the import from a text file
//   question_edit,
//   question_update        — one question and its options
//   question_delete        — a question goes, options and
//                            all
//
// Used by:
//   - routes/web_admin.php — every action
// -----------------------------------------------------------

class SelfevalController extends BaseController
{






    // -----------------------------------------------------------
    // __construct
    // -----------------------------------------------------------
    //
    // BaseController's constructor and nothing else: the
    // auth:man guard on every action, and the session's main
    // and country flags shared with every view — the sidebar
    // shows the main admin's menu by them.
    //
    // Used by:
    //   - Laravel's router, for every route of this class
    // -----------------------------------------------------------

    public function __construct()
    {
        parent::__construct();
    }






    // -----------------------------------------------------------
    // index
    // -----------------------------------------------------------
    //
    // GET /admin-panel/lm/self_eval/{uid}
    //
    // The self-evaluation questions of category uid,
    // admin-panel/self_eval.blade.php, under the category's
    // name: every question of the category is read and the
    // 25 of the page the query string names are sliced out,
    // anything empty or not numeric being page 1. With no
    // questions the view gets an empty array instead of a
    // paginator. Main admins only (auth.admin:man, perms).
    //
    // Used by:
    //   - admin-panel/lm_categories.blade.php — the Self
    //     Evaluation link of each category
    //   - admin-panel/self_eval.blade.php — its page links
    // -----------------------------------------------------------

    public function index(Request $request, $uid)
    {
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






    // -----------------------------------------------------------
    // import_question_dialog
    // -----------------------------------------------------------
    //
    // GET /admin-panel/dialog/lm/self_eval/import/{uid}
    //
    // The import dialog for the panel's modal,
    // admin-panel/dialog/self_eval.blade.php: a file chooser
    // and the category id — the route's uid, which Laravel
    // hands over by position as cat_id — posting to
    // import_questions. Main admins only (auth.admin:man,
    // perms).
    //
    // Used by:
    //   - import_selfeval in public/js/window.js — Import
    //     Questions on admin-panel/self_eval.blade.php
    // -----------------------------------------------------------

    public function import_question_dialog(Request $request, $cat_id)
    {
        return view('admin-panel.dialog.self_eval', ['cat_id'=>$cat_id]);
    }






    // -----------------------------------------------------------
    // import_questions
    // -----------------------------------------------------------
    //
    // POST /admin-panel/lm/self_eval/import
    //
    // Imports the questions of a plain-text file into the
    // category in cat_id. The file is cut into blocks at
    // "::". In a block, a line starting with Q, K, J or the
    // Greek capital epsilon — the initial of the word for
    // question in the site's languages — opens a question
    // whose text follows its first backslash-colon, up to
    // the next one — the first character is compared whole,
    // so the two-byte epsilon matches too; a line starting
    // with "=" is a correct option, "~" a wrong one, its text
    // everything after that marker, and other lines are
    // skipped.
    // One correct option makes a single-choice question
    // (q_type 1), more make a multiple-choice one (2); the
    // correct options share 100 points and the wrong ones
    // get 0. Back with "Questions imported!". Main admins
    // only (auth.admin:man, perms).
    //
    // Nothing runs in a transaction, and broken input ends
    // in a 500 with the blocks before it already stored: a
    // question line without a backslash-colon is an
    // undefined offset, and an option before any question
    // was stored meets an undefined $addQ. A block whose
    // question line is not recognised files its options and
    // its type under the question of an earlier block.
    //
    // Used by:
    //   - admin-panel/dialog/self_eval.blade.php — the import
    //     dialog's form
    // -----------------------------------------------------------

    public function import_questions(Request $request)
    {
        // STEP 1: the uploaded file, read whole and cut into
        // blocks at "::"
        // ==================================================
        $content = file_get_contents($request->file('doc'));
        $cat_id = $request->get('cat_id');

        $content = explode("::", $content);

        foreach($content as $k => $v){

            if(!empty(trim($v))){

                $q = explode("\n", $v);

                $correct_answers = 0;


                // STEP 2: first pass over the block's lines — the
                // question is stored, its correct options counted
                // and its type set
                // ===============================================
                foreach($q as $kk => $vv){

                    if(!empty($vv)){

                        if($vv[0] == 'Q' || $vv[0] == 'K' || mb_substr($vv, 0, 1) == 'Ε' || trim($vv[0]) == 'J'){

                            $addQ = new SelfEval;
                            $question = explode('\:', $vv);
                            $question = trim($question[1]);

                            $addQ->cat_id = $cat_id;
                            $addQ->question = $question;
                            $addQ->save();

                        }

                        if($vv[0] == '='){

                            // "=" opens a correct option
                            $correct_answers++;
                        }

                        // Set again after every line once a correct
                        // option was counted, so the last count wins
                        if($correct_answers == 1){

                            SelfEval::where(['id'=>$addQ->id])->update(['q_type'=>1]);

                        } elseif($correct_answers > 1){

                            SelfEval::where(['id'=>$addQ->id])->update(['q_type'=>2]);

                        }

                    }

                }


                // STEP 3: second pass — the options, the correct
                // ones sharing 100 points
                // ==============================================
                foreach($q as $kk1 => $vv1){

                    if(!empty($vv1)){

                        if($vv1[0] == '='){

                            // A correct option: the text after the marker
                            $answer = trim(substr($vv1, 1));

                            $addOption = new SelfEvalOption;
                            $addOption->question_id = $addQ->id;
                            $addOption->option = $answer;
                            $addOption->correct = 1;
                            $addOption->points = round(100/$correct_answers, 2);
                            $addOption->save();

                        }

                        if($vv1[0] == '~'){

                            // A wrong option, the same way
                            $answer = trim(substr($vv1, 1));

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






    // -----------------------------------------------------------
    // question_delete
    // -----------------------------------------------------------
    //
    // POST /admin-panel/lm/self_eval/delete/{uid}
    //
    // Deletes the question's options, then the question, and
    // goes back with "Question deleted!". The members'
    // recorded answers to it stay in bei_users_selfeval.
    // Main admins only (auth.admin:man, perms).
    //
    // Used by:
    //   - admin-panel/self_eval.blade.php — the delete icon of
    //     each question, after Confirm_Dialog (admn.js)
    // -----------------------------------------------------------

    public function question_delete(Request $request, $uid)
    {
        SelfEvalOption::where(['question_id'=>$uid])->delete();
        SelfEval::where(['id'=>$uid])->delete();

        return back()->withSuccess("Question deleted!");
    }






    // -----------------------------------------------------------
    // question_edit
    // -----------------------------------------------------------
    //
    // GET /admin-panel/lm/self_eval/edit/{uid}
    //
    // The edit page of one question,
    // admin-panel/self_eval_edit.blade.php: its text, its
    // type (1 single-choice, 2 multiple-choice) and its
    // options with their correctness and points. Main admins
    // only (auth.admin:man, perms).
    //
    // Used by:
    //   - admin-panel/self_eval.blade.php — the edit icon of
    //     each question
    // -----------------------------------------------------------

    public function question_edit(Request $request, $uid)
    {
        $question = SelfEval::where(['id'=>$uid])->get()->pluck('question')->first();

        $q_type = SelfEval::where(['id'=>$uid])->get()->pluck('q_type')->first();

        $answers = SelfEvalOption::where(['question_id'=>$uid])->get()->toArray();

        return view('admin-panel.self_eval_edit', ['uid'=>$uid, 'question'=>$question, 'answers'=>$answers, 'q_type'=>$q_type]);
    }






    // -----------------------------------------------------------
    // question_update
    // -----------------------------------------------------------
    //
    // POST /admin-panel/lm/self_eval/update/{uid}
    //
    // Saves the edit page: the question's text and type,
    // then every posted option, keyed by its id, with its
    // text, correctness and points. Nothing checks that the
    // options belong to this question. A question without
    // options — an import block can leave one — saves its
    // text and type all the same. Back with "Updated!". Main
    // admins only (auth.admin:man, perms).
    //
    // Used by:
    //   - admin-panel/self_eval_edit.blade.php — the question
    //     form
    // -----------------------------------------------------------

    public function question_update(Request $request, $uid)
    {
        $question = $request->get('q');

        $answers = $request->get('option');

        $correct = $request->get('correct');

        $points = $request->get('points');

        SelfEval::where(['id'=>$uid])->update(['question'=>$question, 'q_type'=>$request->get('q_type')]);

        if(is_array($answers) && count($answers)>0){

            foreach($answers as $k => $v){

                SelfEvalOption::where(['id'=>$k])->update(['option'=>$v, 'correct'=>$correct[$k], 'points'=>$points[$k]]);

            }

        }

        return back()->withSuccess("Updated!");
    }
}
