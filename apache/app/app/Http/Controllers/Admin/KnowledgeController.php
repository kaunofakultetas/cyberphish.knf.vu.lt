<?php

// -----------------------------------------------------------
//  [*] KnowledgeController — the knowledge-test questions
//
//  The admin side of the knowledge test, the exam that
//  unlocks the certificate: the questions of one
//  learning-material category, imported from a plain-text
//  file, listed, edited and deleted. Each option is
//  correct or not; how many questions of each category a
//  test draws is set on the settings page (settings row
//  3). Every route sits behind auth.admin:man and perms,
//  so only a main admin gets here. Members take the test
//  through KnowledgeTestController.
//
//  Used by:
//    - routes/web_admin.php — the
//      admin-panel/lm/knowledge_test and
//      admin-panel/dialog/lm/knowledge_test routes
// -----------------------------------------------------------


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








// -----------------------------------------------------------
// KnowledgeController
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

class KnowledgeController extends BaseController
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
    // GET /admin-panel/lm/knowledge_test/{uid}
    //
    // The knowledge-test questions of category uid,
    // admin-panel/knowledge.blade.php, under the category's
    // name: every question of the category is read and the
    // 25 of the page the query string names are sliced out,
    // anything empty or not numeric being page 1. With no
    // questions the view gets an empty array instead of a
    // paginator. Main admins only (auth.admin:man, perms).
    //
    // Used by:
    //   - admin-panel/lm_categories.blade.php — the Knowledge
    //     Test link of each category
    //   - admin-panel/knowledge.blade.php — its page links
    // -----------------------------------------------------------

    public function index(Request $request, $uid)
    {
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






    // -----------------------------------------------------------
    // import_question_dialog
    // -----------------------------------------------------------
    //
    // GET /admin-panel/dialog/lm/knowledge_test/import/{uid}
    //
    // The import dialog for the panel's modal,
    // admin-panel/dialog/knowledge.blade.php: a file chooser
    // and the category id — the route's uid, which Laravel
    // hands over by position as cat_id — posting to
    // import_questions. Main admins only (auth.admin:man,
    // perms).
    //
    // Used by:
    //   - import_knowledge in public/js/window.js — Import
    //     Questions on admin-panel/knowledge.blade.php
    // -----------------------------------------------------------

    public function import_question_dialog(Request $request, $cat_id)
    {
        return view('admin-panel.dialog.knowledge', ['cat_id'=>$cat_id]);
    }






    // -----------------------------------------------------------
    // import_questions
    // -----------------------------------------------------------
    //
    // POST /admin-panel/lm/knowledge_test/import
    //
    // Imports the questions of a plain-text file into the
    // category in cat_id, in the format of the
    // self-evaluation import: blocks cut at "::", a question
    // line whose text follows its first backslash-colon (up
    // to the next one), "=" before a correct option and "~"
    // before a wrong one, other lines skipped. Only a line
    // starting with Q opens a question here, not the K, J or
    // Greek epsilon the self-evaluation import also takes;
    // options carry no points. Back with "Questions
    // imported!". Main admins only (auth.admin:man, perms).
    //
    // Nothing runs in a transaction, and broken input ends
    // in a 500 with the blocks before it already stored: a
    // question line without a backslash-colon is an
    // undefined offset, and an option before any question
    // was stored meets an undefined $addQ. A block without a
    // Q line files its options under the question of an
    // earlier block.
    //
    // Known bug (low): an option's marker is taken out with
    // str_replace (KnowledgeController.php:284 and 298),
    // which drops every "=" or "~" of the line, not only the
    // first, so an address with a query string loses its
    // "=" signs. Fix: cut the first character only.
    // Pinned as import-markers.
    //
    // Used by:
    //   - admin-panel/dialog/knowledge.blade.php — the import
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

                // Counted in the first pass and reset after the
                // block, but nothing reads it — dead code
                $correct_answers = 0;


                // STEP 2: first pass over the block's lines — the
                // question is stored
                // ===============================================
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

                            // "=" opens a correct option
                            $correct_answers++;
                        }

                    }

                }


                // STEP 3: second pass — the options, correct or not
                // =================================================
                foreach($q as $kk1 => $vv1){

                    if(!empty($vv1)){

                        if($vv1[0] == '='){

                            // A correct option; str_replace takes out
                            // every "=" of the line, not only the marker
                            $answer = trim(str_replace("=","", $vv1));

                            $addOption = new KnowledgeOption;
                            $addOption->question_id = $addQ->id;
                            $addOption->option = $answer;
                            $addOption->correct = 1;
                            $addOption->save();

                        }

                        if($vv1[0] == '~'){

                            // A wrong option; every "~" of the line goes
                            // the same way
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






    // -----------------------------------------------------------
    // question_delete
    // -----------------------------------------------------------
    //
    // GET /admin-panel/lm/knowledge_test/delete/{uid}
    //
    // Deletes the question's options, then the question, and
    // goes back with "Question deleted!". The members'
    // recorded answers to it stay in
    // bei_users_knowledge_options, and their attempts keep
    // its id in their question sets. Main admins only
    // (auth.admin:man, perms).
    //
    // Known bug (medium): a plain GET reaches the delete at
    // KnowledgeController.php:352, so a link a signed-in
    // admin opens is enough. Pinned as admin-get-deletes.
    // Fix: a POST route behind the CSRF check.
    //
    // Used by:
    //   - admin-panel/knowledge.blade.php — the delete icon of
    //     each question, after Confirm_Dialog (admn.js)
    // -----------------------------------------------------------

    public function question_delete(Request $request, $uid)
    {
        KnowledgeOption::where(['question_id'=>$uid])->delete();
        KnowledgeTest::where(['id'=>$uid])->delete();

        return back()->withSuccess("Question deleted!");
    }






    // -----------------------------------------------------------
    // question_edit
    // -----------------------------------------------------------
    //
    // GET /admin-panel/lm/knowledge_test/edit/{uid}
    //
    // The edit page of one question,
    // admin-panel/knowledge_edit.blade.php: its text and its
    // options with their correctness. Main admins only
    // (auth.admin:man, perms).
    //
    // Used by:
    //   - admin-panel/knowledge.blade.php — the edit icon of
    //     each question
    // -----------------------------------------------------------

    public function question_edit(Request $request, $uid)
    {
        $question = KnowledgeTest::where(['id'=>$uid])->get()->pluck('question')->first();

        $answers = KnowledgeOption::where(['question_id'=>$uid])->get()->toArray();

        return view('admin-panel.knowledge_edit', ['uid'=>$uid, 'question'=>$question, 'answers'=>$answers]);
    }






    // -----------------------------------------------------------
    // question_update
    // -----------------------------------------------------------
    //
    // POST /admin-panel/lm/knowledge_test/update/{uid}
    //
    // Saves the edit page: the question's text, then every
    // posted option, keyed by its id, with its text and
    // correctness. Nothing checks that the options belong to
    // this question. Back with "Updated!". Main admins only
    // (auth.admin:man, perms).
    //
    // Known bug (low): with no options posted nothing is
    // saved (KnowledgeController.php:424), not even the text,
    // and the page still says "Updated!", so a question the
    // import left without options cannot be edited. Fix:
    // save the question before the options check.
    // Pinned as question-update-no-options.
    //
    // Used by:
    //   - admin-panel/knowledge_edit.blade.php — the question
    //     form
    // -----------------------------------------------------------

    public function question_update(Request $request, $uid)
    {
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
