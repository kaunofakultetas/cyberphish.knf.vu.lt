<?php

// -----------------------------------------------------------
//  [*] KnowledgeTestController — the final knowledge test
//
//  The members-only knowledge test of a language, the exam
//  that unlocks the certificate (CertController). A start
//  draws so many random questions per learning-material
//  category, as the limits in settings row 3 ask, into an
//  attempt of bei_users_knowledge_test; its question set —
//  a JSON map of question id to answered flag — is the
//  whole state of the test. The progress page asks the
//  unanswered questions one by one, each answer adds at
//  most one row to bei_users_knowledge_options, and once
//  nothing is left the attempt closes with the share of its
//  questions answered correctly as its result. Above 74
//  passes. Every route sits behind auth:mem.
//
//  Used by:
//    - routes/web_user.php — {lang}/knowledge_test,
//      {lang}/knowledge_start and the progress page
// -----------------------------------------------------------


namespace App\Http\Controllers;

use Session;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
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








// -----------------------------------------------------------
// KnowledgeTestController
// -----------------------------------------------------------
//
// An attempt is known to the pages by its random public id.
// Only the member's own attempt opens, an answer is taken
// only for a drawn question still unanswered in an open
// attempt, and it scores only with one of that question's
// own options.
//
//   index             — the test page: tries, results, start
//   start             — the lessons check and the lock
//   draw_or_resume    — draws a new attempt or resumes one
//   gen_test_id       — a public id no attempt has yet
//   progress          — the next question, or the result
//   progress_save     — the lock around save_answer
//   save_answer       — stores one answer
//   calculate_results — the result of an attempt
//   mark_answered     — passes over an unanswerable question
//   get_lang_cats     — the categories of a language
//   knowledge_results — the results line of the test page
//
// Used by:
//   - routes/web_user.php — its four routes
// -----------------------------------------------------------

class KnowledgeTestController extends BaseController
{






    // -----------------------------------------------------------
    // __construct
    // -----------------------------------------------------------
    //
    // BaseController's constructor and nothing else: it
    // registers the middleware that sets the language and
    // shares the member's data with every view.
    //
    // Used by:
    //   - Laravel, when it builds the controller to dispatch
    //     one of its routes
    // -----------------------------------------------------------

    public function __construct()
    {
        parent::__construct();
    }






    // -----------------------------------------------------------
    // index
    // -----------------------------------------------------------
    //
    // GET /{lang}/knowledge_test
    //
    // The knowledge-test page of the language, behind
    // auth:mem, once the member has done every lesson of the
    // language — badge_all_presentations, which the sidebar's
    // button waits for too; before that the page sends the
    // member to the lessons. An attempt still open sends the
    // member straight back into it, to its progress page.
    // Otherwise knowledge_test.blade.php shows the tries used,
    // whether the language has questions at all, the results
    // of the finished attempts and — once one passed — the
    // full-name form, with the certificate link once a name is
    // stored. The page hides the start form after three tries,
    // and start refuses a fourth.
    //
    // Used by:
    //   - parts/lm_left_sidebar.blade.php — the knowledge-test
    //     button, shown once the member holds
    //     badge_all_presentations
    //   - draw_or_resume (below) — its redirects back after
    //     three tries or when there is nothing to draw
    // -----------------------------------------------------------

    public function index(Request $request, $lang)
    {
        if(!Badges::get_badge('badge_all_presentations')){
            return redirect('/'.$lang.'/cp/learning-material');
        }

        $count = KnowledgeUserTest::where(['user_id'=>Auth::guard('mem')->id(), 'lang_id'=>Langs::getLangId($lang)])->count();

        $unfinished = KnowledgeUserTest::where(['finished'=>0, 'user_id'=>Auth::guard('mem')->id(), 'lang_id'=>Langs::getLangId($lang)])->count();

        $count_questions = KnowledgeTest::whereIn('cat_id', KnowledgeTestController::get_lang_cats($lang))->count();

        // A pass is a result above 74, the mark CertController
        // checks too
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






    // -----------------------------------------------------------
    // start
    // -----------------------------------------------------------
    //
    // POST /{lang}/knowledge_start
    //
    // Starts the member's knowledge test in the language,
    // behind auth:mem, and redirects into its progress page;
    // an attempt still open there is resumed instead
    // (draw_or_resume). A member who has not done every
    // lesson of the language — badge_all_presentations, the
    // badge index waits for — is sent to the lessons, and no
    // attempt is drawn. The member's row stays locked until
    // the attempt is stored, so starts posted at the same
    // moment take their turns: each later one finds the
    // attempt the first stored, and none passes the three-try
    // check alongside it.
    //
    // Used by:
    //   - knowledge_test.blade.php — the start form
    // -----------------------------------------------------------

    public function start(Request $request, $lang)
    {
        if(!Badges::get_badge('badge_all_presentations')){
            return redirect('/'.$lang.'/cp/learning-material');
        }

        return DB::transaction(function () use ($lang) {
            Members::where(['id'=>Auth::guard('mem')->id()])->lockForUpdate()->first();

            return KnowledgeTestController::draw_or_resume($lang);
        });
    }






    // -----------------------------------------------------------
    // draw_or_resume
    // -----------------------------------------------------------
    //
    // The heart of start, run with the member's row locked:
    // the open attempt of the language, or a new one. A new
    // attempt draws, for each category the language's limits
    // in settings row 3 name, that many of its answerable
    // questions (KnowledgeTest::scopeAnswerable) at random — a
    // limit left blank draws none, as the settings page shows
    // it — and is stored open with every question
    // unanswered and a result of 0. A member with three
    // attempts in the language — the tries the page offers —
    // is sent back to the test page, so a start posted by hand
    // draws no fourth. So is a start that finds nothing to
    // draw: a language missing from settings row 3 — its first
    // category added after the limits were last saved — or
    // limits whose categories hold no question; no attempt is
    // stored then.
    //
    // Used by:
    //   - start (above) — inside its transaction
    // -----------------------------------------------------------

    private static function draw_or_resume($lang)
    {
        // STEP 1: an attempt still open in the language is
        // resumed — no second one is drawn while it lasts — and
        // after three tries none is drawn at all
        // =====================================================
        $unfinished = KnowledgeUserTest::where(['finished'=>0, 'user_id'=>Auth::guard('mem')->id(), 'lang_id'=>Langs::getLangId($lang)])->count();

        $tries = KnowledgeUserTest::where(['user_id'=>Auth::guard('mem')->id(), 'lang_id'=>Langs::getLangId($lang)])->count();

        if($unfinished == 0 && $tries >= 3){
            return redirect('/'.$lang.'/knowledge_test');
        }

        if($unfinished == 0){
            // STEP 2: none open — a new public id, and for each
            // category the language's limits name, that many
            // questions at random
            // =================================================
            $public_id = KnowledgeTestController::gen_test_id();

            $knowledge_limits = json_decode(Settings::where(['id'=>3])->get()->pluck('val1')->first(),1);

            $lang_limits = $knowledge_limits[Langs::getLangId($lang)] ?? [];

            $q = []; $qq = [];

            foreach($lang_limits as $k => $v){

                $questions = KnowledgeTest::where(['cat_id'=>$k])->answerable()->inRandomOrder()->limit((int) $v)->get()->pluck('id')->toArray();

                $q = array_merge($q, $questions);

            }

            if(count($q) == 0){
                return redirect('/'.$lang.'/knowledge_test');
            }


            // STEP 3: store the attempt — open, no result yet,
            // each drawn question mapped to 0, unanswered
            // ================================================
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


        // STEP 4: into the progress page of the new attempt
        // or of the open one
        // =================================================
        return redirect('/'.$lang.'/knowledge_test/progress/'.$public_id);
    }






    // -----------------------------------------------------------
    // gen_test_id
    // -----------------------------------------------------------
    //
    // A public id for a new attempt: a random string of 16
    // characters from SettingsController::random_string,
    // drawn again — through this method itself — until no
    // knowledge attempt has it.
    //
    // Used by:
    //   - draw_or_resume (above)
    // -----------------------------------------------------------

    private static function gen_test_id()
    {
        $p_id = SettingsController::random_string();

        $num = KnowledgeUserTest::where(['public_id'=>$p_id])->count();

        if($num == 0){
            return $p_id;
        } elseif($num > 0){
            return KnowledgeTestController::gen_test_id();
        }
    }






    // -----------------------------------------------------------
    // progress
    // -----------------------------------------------------------
    //
    // GET /{lang}/knowledge_test/progress/{public_id}
    //
    // One step of an attempt, behind auth:mem; only the
    // member's own attempt opens, any other public id is a
    // 404. While a drawn question is unanswered the page shows
    // the first one with its options in random order and the
    // share answered so far; the question's id goes into the
    // form as a double MD5 hash. A drawn question deleted
    // since the draw, or left without options, can never be
    // answered: the page marks it answered with no answer row
    // (mark_answered), so it counts as wrong, as a skipped
    // question does, and the attempt can still close. Once none is left an open
    // attempt closes — finished, ended now, the result from
    // calculate_results — and a pass (above 74) earns
    // badge_final_test of the language, whichever try it is,
    // with a message on this page when the badge is new
    // (save_badge refuses a second copy). A finished attempt
    // keeps its end date, the date the certificate prints,
    // and its result. The page is knowledge_progress.blade.php
    // either way.
    //
    // Used by:
    //   - index, start, progress_save (this class) — their
    //     redirects; no page links here
    // -----------------------------------------------------------

    public function progress(Request $request, $lang, $public_id)
    {
        // STEP 1: the member's own attempt — any other public
        // id is a 404
        // ===================================================
        $lang_id = Langs::getLangId($lang);
        $attempt = KnowledgeUserTest::where(['public_id'=>$public_id, 'user_id'=>Auth::guard('mem')->id()])->first();

        if($attempt == null){
            abort(404);
        }

        $id = $attempt->id;
        $qids = KnowledgeUserTest::getQIds($id);


        // STEP 2: the question to ask — the first one still at
        // 0 that can be answered, category by category, in the
        // order start drew them; those before it count as
        // answered, and one that can no longer be answered is
        // marked answered on the way
        // ====================================================
        $finished = 1;
        $answered = 0;
        $passed_over = [];
        foreach($qids as $k => $v){

            if($v == 0 && $attempt->finished == 0 && !KnowledgeTest::where(['id'=>$k])->answerable()->exists()){
                $passed_over[] = $k;
                $v = 1;
            }

            if($v == 0){
                $q_id = $k;
                $finished = 0;
                break;
            } else {
                $answered++;
            }
        }

        if(count($passed_over) > 0){
            KnowledgeTestController::mark_answered($id, $passed_over);
        }


        // STEP 3: while a question is left, it and its options
        // in random order; else the result — the stored one of
        // an attempt closed before, or a new one that closes
        // the attempt and earns badge_final_test on a pass
        // ====================================================
        if($finished == 0){

            $options = KnowledgeOption::where(['question_id'=>$q_id])->inRandomOrder()->get()->toArray();

            $question = KnowledgeTest::getQuestion($q_id);

            $progress = round($answered * 100 / count($qids));

            $results = 0;

        } elseif($finished == 1 && $attempt->finished == 1){

            // Closed before: its end date and result stay
            $progress = 100;
            $results = $attempt->results;

            $q_id = 0;
            $question = '';
            $options = [];

        } elseif($finished == 1){

            $progress = 100;

            $results = KnowledgeTestController::calculate_results($id);

            $q_id = 0;
            $question = '';
            $options = [];

            KnowledgeUserTest::where(['id'=>$id, 'user_id'=>Auth::guard('mem')->id()])->update(['finished'=>1, 'ended'=>date('Y-m-d H:i:s'), 'results'=>$results]);

            if($results > 74){

                $a = Badges::save_badge($lang_id, 'badge_final_test');

                if($a == true){
                    $request->session()->now('success', __('main.new_badge'));
                }

            }

        }


        // STEP 4: the page, knowledge_progress.blade.php, for
        // a question and for the result alike
        // ===================================================
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






    // -----------------------------------------------------------
    // progress_save
    // -----------------------------------------------------------
    //
    // POST /{lang}/knowledge_test/progress/{public_id}
    //
    // Stores the answer to one question, behind auth:mem, and
    // redirects back to the progress page for the next; only
    // the member's own attempt takes answers, any other public
    // id is a 404. The question arrives as the page's double
    // MD5 hash (pbid), matched against every question. While
    // the attempt is open and the question one of its drawn,
    // unanswered ones, the first option posted in a[] — the
    // page's radio buttons send one — becomes an answer row
    // with the option's correct flag and the category of the
    // question, provided it is an option of that question; and
    // the question is marked answered in the question set,
    // also when no option came, which leaves no row. Anything
    // else — a question already answered or never drawn, a
    // closed attempt — stores nothing, also when the same
    // answer is posted twice at once (save_answer).
    //
    // Used by:
    //   - knowledge_progress.blade.php — the answer form, which
    //     posts back to the page's own URL
    // -----------------------------------------------------------

    public function progress_save(Request $request, $lang, $public_id)
    {
        return DB::transaction(function () use ($request, $lang, $public_id) {
            return KnowledgeTestController::save_answer($request, $lang, $public_id);
        });
    }






    // -----------------------------------------------------------
    // save_answer
    // -----------------------------------------------------------
    //
    // The work of progress_save, inside its transaction. The
    // attempt's row is read with a lock that holds until the
    // answer is stored and the question marked, so posts that
    // arrive at the same moment take their turns: the second
    // finds the question answered and stores nothing — where
    // each once passed the check and stored a correct answer
    // of its own.
    //
    // Used by:
    //   - progress_save (above)
    // -----------------------------------------------------------

    private static function save_answer(Request $request, $lang, $public_id)
    {
        // STEP 1: the member's own attempt, its row locked, and
        // the question answered, whose id the page sends as a
        // double MD5 hash; $lang_id is left over and never read
        // =====================================================
        $lang_id = Langs::getLangId($lang);
        $attempt = KnowledgeUserTest::where(['public_id'=>$public_id, 'user_id'=>Auth::guard('mem')->id()])->lockForUpdate()->first();

        if($attempt == null){
            abort(404);
        }

        $id = $attempt->id;
        $qids = json_decode($attempt->question_set, 1);

        $question_id = KnowledgeTest::getDecryptedId($request->get('pbid'));

        $a = $request->get('a');

        // Only a drawn question still unanswered, in an attempt
        // still open, takes an answer
        $open = $attempt->finished == 0 && isset($qids[$question_id]) && $qids[$question_id] == 0;


        // STEP 2: one answer row, from the first option posted
        // and only if it is an option of that question; none
        // when no option came
        // ====================================================
        if($open && is_array($a) && count($a)>0){

            $option = KnowledgeOption::where(['id'=>reset($a), 'question_id'=>$question_id])->first();

            if($option != null){

                $addRecord = new KnowledgeUserOptions;
                $addRecord->cat_id = KnowledgeTest::getCatId($question_id);
                $addRecord->test_id = $id;
                $addRecord->public_id = $public_id;
                $addRecord->user_id = Auth::guard('mem')->id();
                $addRecord->question_id = $question_id;
                $addRecord->correct = $option->correct;
                $addRecord->save();

            }
        }


        // STEP 3: mark the question answered in the question
        // set
        // ==================================================
        if($open){
            foreach($qids as $k => $v){
                if($k == $question_id){
                    $newqids[$k] = 1;
                } else {
                    $newqids[$k] = $v;
                }
            }

            // The 1 is JSON_HEX_TAG, harmless for a map of numbers
            KnowledgeUserTest::where(['id'=>$id, 'user_id'=>Auth::guard('mem')->id()])->update(['question_set'=>json_encode($newqids,1)]);
        }


        // STEP 4: back to the progress page for the next
        // question
        // ==============================================
        return redirect('/'.$lang.'/knowledge_test/progress/'.$public_id);
    }






    // -----------------------------------------------------------
    // calculate_results
    // -----------------------------------------------------------
    //
    // The result of an attempt as a whole percentage: the
    // questions the signed-in member answered correctly in
    // it — each once, should its answer have been stored
    // twice — over the questions the attempt drew, rounded. A question answered with no option left
    // no row and counts as wrong, so skipping every question
    // scores 0; an attempt that drew nothing scores 0 too.
    //
    // Used by:
    //   - progress (above) — when it closes an attempt whose
    //     every question is answered
    // -----------------------------------------------------------

    public static function calculate_results($test_id)
    {
        $total_questions = count(KnowledgeUserTest::getQIds($test_id) ?? []);

        $correct_questions = KnowledgeUserOptions::where(['test_id'=>$test_id, 'user_id'=>Auth::guard('mem')->id(), 'correct'=>1])->distinct()->count('question_id');

        if($total_questions == 0){
            return 0;
        }

        return round($correct_questions * 100 / $total_questions);
    }






    // -----------------------------------------------------------
    // mark_answered
    // -----------------------------------------------------------
    //
    // Marks the given questions of an attempt answered in its
    // question set, with no answer row, so they count as
    // wrong. The attempt's row is locked while the set is
    // read and written again, so an answer saved at the same
    // moment is not lost.
    //
    // Used by:
    //   - progress (above) — for drawn questions that can no
    //     longer be answered
    // -----------------------------------------------------------

    private static function mark_answered($test_id, $question_ids)
    {
        DB::transaction(function () use ($test_id, $question_ids) {
            $attempt = KnowledgeUserTest::where(['id'=>$test_id])->lockForUpdate()->first();
            $qids = json_decode($attempt->question_set, 1);

            foreach($question_ids as $question_id){
                if(isset($qids[$question_id]) && $qids[$question_id] == 0){
                    $qids[$question_id] = 1;
                }
            }

            // The 1 is JSON_HEX_TAG, as in save_answer (above)
            KnowledgeUserTest::where(['id'=>$test_id])->update(['question_set'=>json_encode($qids, 1)]);
        });
    }






    // -----------------------------------------------------------
    // get_lang_cats
    // -----------------------------------------------------------
    //
    // The ids of the language's learning-material categories,
    // which the knowledge questions are filed under.
    //
    // Used by:
    //   - index (above) — to count the language's questions
    // -----------------------------------------------------------

    public static function get_lang_cats($lang)
    {
        $lang_id = Langs::getLangId($lang);

        $cats = LMCategory::select('id')->where(['lang_id'=>$lang_id])->get()->pluck('id')->toArray();

        return $cats;
    }






    // -----------------------------------------------------------
    // knowledge_results
    // -----------------------------------------------------------
    //
    // The results line of the test page: the translated
    // results label, then each finished result of the member
    // in the language as a percentage, comma-separated — an
    // empty string before the first finished attempt.
    // Admin\ManagerMainController has a method of this name
    // of its own.
    //
    // Used by:
    //   - index (above)
    // -----------------------------------------------------------

    public static function knowledge_results($user_id, $lang_id)
    {
        $results = KnowledgeUserTest::select('results')->where(['user_id'=>$user_id, 'lang_id'=>$lang_id, 'finished'=>1])->get()->pluck('results')->toArray();

        if(count($results)>0){
            return __('main.results').' '. implode("%, ", $results).'%';
        } else {
            return '';
        }
    }
}
