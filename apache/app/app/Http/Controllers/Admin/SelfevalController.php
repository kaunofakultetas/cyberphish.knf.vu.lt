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
use Illuminate\Support\Facades\DB;
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
//   file_text,
//   is_question_line,
//   line_without_marker    — the file's text and question
//                            lines, for both imports
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
    // paginator. The page's Import Questions link hands uid
    // to its script, so uid is taken as a number first.
    // Main admins only (auth.admin:man, perms).
    //
    // Used by:
    //   - admin-panel/lm_categories.blade.php — the Self
    //     Evaluation link of each category
    //   - admin-panel/self_eval.blade.php — its page links
    // -----------------------------------------------------------

    public function index(Request $request, $uid)
    {
        // The id as the number MySQL reads from it: the page
        // prints it into its script, where the segment as written
        // would run on the admin's next click
        $uid = (int) $uid;

        $arr = SelfEval::where(['cat_id'=>$uid])->get()->toArray();

        $name = LMCategory::where(['id'=>$uid])->pluck('name')->first();

        $perPage = 25;

        $curr_page = SettingsController::page_number($request->get('page'), count($arr), $perPage);

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
    // category in cat_id. The file is cut into blocks at "::".
    // In a block, a question line (is_question_line) opens a
    // question whose text follows its first backslash-colon,
    // up to the next one; a line starting with "=" is a
    // correct option, "~" a wrong one, its text everything
    // after that marker, and other lines are skipped. One
    // correct option makes a single-choice question (q_type
    // 1), more make a multiple-choice one (2); the correct
    // options share 100 points and the wrong ones get 0. Back
    // with "Questions imported!". Main admins only
    // (auth.admin:man, perms).
    //
    // A post without a file goes back with "No file was
    // uploaded!", as the simulation import does, where reading
    // the missing file was a 500. The file is read as UTF-8
    // (file_text): a UTF-16 one is converted and a byte order
    // mark dropped, and one in any other encoding goes back
    // with a message — its texts were once stored cut short,
    // or not at all, under "Questions imported!". A category
    // that is gone, or
    // a question line without a backslash-colon
    // (line_without_marker), refuses the whole file with a
    // message before anything is stored, and the blocks are
    // stored in one transaction: such a line once broke the
    // import halfway — a 500, the blocks before it kept, and
    // stored twice once the corrected file came. Each block
    // starts with no question, so a block whose question line
    // is not recognised stores none of its options, as in the
    // knowledge import, where they once went under an earlier
    // block's question, turning its type, and in the first
    // block were a 500.
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
        if (!$request->hasFile('doc')) {
            return back()->withErrors("No file was uploaded!");
        }

        $content = SelfevalController::file_text($request->file('doc'));
        $cat_id = $request->get('cat_id');

        if ($content === null) {
            return back()->withErrors("The file is not saved as UTF-8! Save it as UTF-8 and import it again.");
        }

        $content = explode("::", $content);


        // STEP 2: refuse the file, before anything is stored, for
        // a category that is gone or a question line without the
        // backslash-colon its text follows
        // =======================================================
        if (LMCategory::where(['id'=>$cat_id])->count() == 0) {
            return back()->withErrors("The category does not exist!");
        }

        $broken = SelfevalController::line_without_marker($content);
        if ($broken !== null) {
            return back()->withErrors("A question line has no \\: before its text: ".$broken);
        }

        // Stored in one transaction: a failure halfway leaves
        // none of the file behind
        DB::transaction(function () use ($content, $cat_id) {
            foreach($content as $k => $v){

                if(!empty(trim($v))){

                    $q = explode("\n", $v);

                    // No question yet: the block's own question line,
                    // if it has one, sets it
                    $addQ = null;

                    $correct_answers = 0;


                    // STEP 3: first pass over the block's lines — the
                    // question is stored, its correct options counted
                    // and its type set
                    // ===============================================
                    foreach($q as $kk => $vv){

                        if(!empty($vv)){

                            if(SelfevalController::is_question_line($vv)){

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
                            if($addQ == null){
                                // no question to set it on
                            } elseif($correct_answers == 1){

                                SelfEval::where(['id'=>$addQ->id])->update(['q_type'=>1]);

                            } elseif($correct_answers > 1){

                                SelfEval::where(['id'=>$addQ->id])->update(['q_type'=>2]);

                            }

                        }

                    }


                    // STEP 4: second pass — the options, the correct
                    // ones sharing 100 points
                    // ==============================================
                    foreach($q as $kk1 => $vv1){

                        if(!empty($vv1) && $addQ != null){

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
        });

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
    // Saves the edit page: the question's text and type, then
    // every posted option, keyed by its id, with its text,
    // correctness and points — points typed with a decimal
    // comma read as with a point: MySQL, its strict mode off,
    // took "33,33" for 33 with only a warning, and three such
    // right options scored 99. A field of the wrong shape —
    // the options, correctness or points as one value, or any
    // one of them as a list — goes back with an error, where
    // it once was a 500, and an option posted without its
    // correctness or points is stored with 0. Nothing checks
    // that the options belong to this question. A question
    // without options — an import block can leave one — saves
    // its text and type all the same. Back with "Updated!".
    // Main admins only (auth.admin:man, perms).
    //
    // Used by:
    //   - admin-panel/self_eval_edit.blade.php — the question
    //     form
    // -----------------------------------------------------------

    public function question_update(Request $request, $uid)
    {
        $single = function ($attribute, $value, $fail) {
            if (is_array($value)) {
                $fail('The '.$attribute.' must be a single value.');
            }
        };

        $request->validate([
            'q' => [ 'nullable', 'string' ],
            'q_type' => [ 'nullable', $single ],
            'option' => [ 'nullable', 'array' ],
            'option.*' => [ 'nullable', $single ],
            'correct' => [ 'nullable', 'array' ],
            'correct.*' => [ 'nullable', $single ],
            'points' => [ 'nullable', 'array' ],
            'points.*' => [ 'nullable', $single ],
        ]);

        $question = $request->get('q');

        $answers = $request->get('option');

        $correct = $request->get('correct');

        $points = $request->get('points');

        SelfEval::where(['id'=>$uid])->update(['question'=>$question, 'q_type'=>$request->get('q_type')]);

        if(is_array($answers) && count($answers)>0){

            foreach($answers as $k => $v){

                $option_points = is_string($points[$k] ?? null) ? str_replace(',', '.', $points[$k]) : ($points[$k] ?? 0);

                SelfEvalOption::where(['id'=>$k])->update(['option'=>$v, 'correct'=>$correct[$k] ?? 0, 'points'=>$option_points]);

            }

        }

        return back()->withSuccess("Updated!");
    }






    // -----------------------------------------------------------
    // file_text
    // -----------------------------------------------------------
    //
    // The text of an uploaded question file as UTF-8, or null
    // when it is in an encoding the imports cannot read. A
    // UTF-16 file, as Notepad saves "Unicode", is converted,
    // and a byte order mark at the start is dropped: it once
    // hid the first line's question marker, and that question
    // was lost. Any other text that is not UTF-8 — a legacy
    // code page such as Windows-1257 — cannot be told apart
    // from the others and is refused: MySQL, its strict mode
    // off, once cut each of its texts at the first byte that
    // was not UTF-8.
    //
    // Used by:
    //   - import_questions (this class)
    //   - KnowledgeController::import_questions
    // -----------------------------------------------------------

    public static function file_text($path)
    {
        $content = file_get_contents($path);

        if (strncmp($content, "\xFF\xFE", 2) === 0 || strncmp($content, "\xFE\xFF", 2) === 0) {
            $content = mb_convert_encoding($content, 'UTF-8', 'UTF-16');
        }

        if (strncmp($content, "\xEF\xBB\xBF", 3) === 0) {
            $content = substr($content, 3);
        }

        return mb_check_encoding($content, 'UTF-8') ? $content : null;
    }






    // -----------------------------------------------------------
    // is_question_line
    // -----------------------------------------------------------
    //
    // Whether a line of an import file opens a question: it
    // starts with Q, K or J — the initial of the word for
    // question in the site's languages — or the Greek capital
    // epsilon, the first character compared whole so the
    // two-byte letter matches too.
    //
    // Used by:
    //   - import_questions, line_without_marker (this class)
    //   - KnowledgeController::import_questions
    // -----------------------------------------------------------

    public static function is_question_line($line)
    {
        return $line[0] == 'Q' || $line[0] == 'K' || mb_substr($line, 0, 1) == 'Ε' || trim($line[0]) == 'J';
    }






    // -----------------------------------------------------------
    // line_without_marker
    // -----------------------------------------------------------
    //
    // The first question line of the file's blocks without a
    // backslash-colon before its text, trimmed, or null when
    // every question line has one. The imports read a
    // question's text after the first backslash-colon, and
    // such a line was an undefined array key.
    //
    // Used by:
    //   - import_questions (this class)
    //   - KnowledgeController::import_questions
    // -----------------------------------------------------------

    public static function line_without_marker(array $blocks)
    {
        foreach($blocks as $block){
            foreach(explode("\n", $block) as $line){
                if(!empty($line) && SelfevalController::is_question_line($line) && strpos($line, '\:') === false){
                    return trim($line);
                }
            }
        }

        return null;
    }
}
