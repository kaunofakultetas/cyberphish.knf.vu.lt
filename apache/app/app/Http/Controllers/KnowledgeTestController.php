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
//  unanswered questions one by one, each answer adds rows
//  to bei_users_knowledge_options, and once nothing is left
//  the attempt closes with the share of correct answer rows
//  as its result. Above 74 passes. Every route sits behind
//  auth:mem.
//
//  Used by:
//    - routes/web_user.php — {lang}/knowledge_test,
//      {lang}/knowledge_start and the progress page
// -----------------------------------------------------------


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








// -----------------------------------------------------------
// KnowledgeTestController
// -----------------------------------------------------------
//
// An attempt is known to the pages by its random public id.
// The scoring trusts whatever is posted; the method banners
// name the pinned defects that follow from it.
//
//   index             — the test page: tries, results, start
//   start             — draws a new attempt or resumes one
//   gen_test_id       — a public id no attempt has yet
//   progress          — the next question, or the result
//   progress_save     — stores one answer
//   calculate_results — the result of an attempt
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
    // auth:mem. An attempt still open sends the member
    // straight back into it, to its progress page. Otherwise
    // knowledge_test.blade.php shows the tries used, whether
    // the language has questions at all, the results of the
    // finished attempts and — once one passed — the full-name
    // form, with the certificate link once a name is stored.
    // The page hides the start form after three tries; the
    // server enforces nothing of the kind (see start).
    //
    // Used by:
    //   - parts/lm_left_sidebar.blade.php — the knowledge-test
    //     button, shown once the member holds
    //     badge_all_presentations
    // -----------------------------------------------------------

    public function index(Request $request, $lang)
    {
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
    // an attempt still open there is resumed instead. A new
    // attempt draws, for each category the language's limits
    // in settings row 3 name, that many questions at random,
    // and is stored open with every question unanswered and a
    // result of 0.
    //
    // Known bug (medium): nothing here counts the tries — the
    // three-try limit lives only in knowledge_test.blade.php,
    // so a start posted by hand draws a fourth attempt and
    // more (KnowledgeTestController.php:204). Pinned as
    // knowledge-attempt-limit. Fix: refuse a start once the
    // member has three attempts in the language.
    //
    // Known bug (low): the language's limits are used
    // unchecked (KnowledgeTestController.php:213), so a
    // language missing from settings row 3 — its first
    // category added after the limits were last saved — or a
    // site without the row makes every start a 500. Fix:
    // treat a missing entry as nothing to draw.
    // Pinned as knowledge-limits-missing.
    //
    // Used by:
    //   - knowledge_test.blade.php — the start form
    // -----------------------------------------------------------

    public function start(Request $request, $lang)
    {
        // STEP 1: an attempt still open in the language is
        // resumed — no second one is drawn while it lasts
        // ================================================
        $unfinished = KnowledgeUserTest::where(['finished'=>0, 'user_id'=>Auth::guard('mem')->id(), 'lang_id'=>Langs::getLangId($lang)])->count();

        if($unfinished == 0){
            // STEP 2: none open — a new public id, and for each
            // category the language's limits name, that many
            // questions at random
            // =================================================
            $public_id = KnowledgeTestController::gen_test_id();

            $knowledge_limits = json_decode(Settings::where(['id'=>3])->get()->pluck('val1')->first(),1);

            $lang_limits = $knowledge_limits[Langs::getLangId($lang)];

            $q = []; $qq = [];

            foreach($lang_limits as $k => $v){

                $questions = KnowledgeTest::where(['cat_id'=>$k])->inRandomOrder()->limit($v)->get()->pluck('id')->toArray();

                $q = array_merge($q, $questions);

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
    //   - start (above)
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
    // One step of an attempt, behind auth:mem. While a drawn
    // question is unanswered the page shows the first one
    // with its options in random order and the share answered
    // so far; the question's id goes into the form as a
    // double MD5 hash. Once none is left the attempt closes —
    // finished, ended now, the result from calculate_results
    // — and when it is the member's first finished attempt
    // and passed (above 74), badge_final_test is awarded with
    // a flash message. The page is knowledge_progress.blade.php
    // either way.
    //
    // Known bug (low): the attempt is found by its public id
    // alone (KnowledgeTestController.php:344). An unknown id
    // runs a foreach over nothing, a 500 (pinned as
    // knowledge-unknown-attempt); another member's id opens
    // that member's questions (pinned as
    // knowledge-attempt-owner). Fix: a 404 unless the attempt
    // exists and is the member's.
    //
    // Known bug (medium): every visit to a finished attempt
    // closes it again (KnowledgeTestController.php:384): ended
    // moves to the latest visit — the date the certificate
    // prints — and the result is recomputed from whatever
    // answer rows the attempt has by then, rows progress_save
    // still accepts after the close. Fix: close only an
    // attempt that is still open.
    // Pinned as knowledge-reclose.
    //
    // Known bug (low): the badge check counts the member's
    // finished attempts in every language
    // (KnowledgeTestController.php:388), so a pass on a later
    // try, or a first pass in a second language, never earns
    // badge_final_test. Fix: award it for any pass in the
    // language — save_badge already refuses a second copy.
    // Pinned as knowledge-badge-later-pass.
    //
    // Used by:
    //   - index, start, progress_save (this class) — their
    //     redirects; no page links here
    // -----------------------------------------------------------

    public function progress(Request $request, $lang, $public_id)
    {
        $lang_id = Langs::getLangId($lang);
        $id = KnowledgeUserTest::getPrivateId($public_id);

        $qids = KnowledgeUserTest::getQIds($id);

        // The first question still at 0 is the one to ask —
        // category by category, in the order start drew them
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

            // Runs again on every later visit (see the banner)
            KnowledgeUserTest::where(['id'=>$id, 'user_id'=>Auth::guard('mem')->id()])->update(['finished'=>1, 'ended'=>date('Y-m-d H:i:s'), 'results'=>$results]);

            // badge_final_test needs the first finished attempt,
            // counted over every language (see the banner)
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






    // -----------------------------------------------------------
    // progress_save
    // -----------------------------------------------------------
    //
    // POST /{lang}/knowledge_test/progress/{public_id}
    //
    // Stores the answer to one question, behind auth:mem, and
    // redirects back to the progress page for the next. The
    // question arrives as the page's double MD5 hash (pbid),
    // matched against every question; each option id posted
    // in a[] becomes one answer row carrying the option's
    // correct flag, and the question is marked answered in
    // the question set — also when no option came, which
    // leaves no row. Only the marking checks that the
    // attempt is the member's.
    //
    // Known bug (high): every answer row's cat_id is looked up
    // by the ATTEMPT's id (KnowledgeTestController.php:488),
    // giving the category of whichever question shares the
    // attempt's number. Production's attempt ids reach 664
    // and its question ids run to 760, so answers save today
    // under a wrong category that nothing reads; from attempt
    // 761 on the insert fails on the NOT NULL column — a 500
    // on every answer, the member stuck on the first
    // question. Pinned as knowledge-category-lookup. Fix:
    // look the category up by the question's id.
    //
    // Known bug (high): nothing ties the answer rows to the
    // question (KnowledgeTestController.php:494): an option of
    // any question counts with its own flag, an id posted many
    // times counts each time, and a question already
    // answered, one never drawn, or an attempt already closed
    // takes more rows — counted on the next visit to
    // progress. Pinned as knowledge-repeated-option. Fix: one
    // option per open question of the attempt, and only an
    // option of that question.
    //
    // Used by:
    //   - knowledge_progress.blade.php — the answer form, which
    //     posts back to the page's own URL
    // -----------------------------------------------------------

    public function progress_save(Request $request, $lang, $public_id)
    {
        // STEP 1: the attempt and the question answered, whose
        // id the page sends as a double MD5 hash; $lang_id is
        // left over and never read
        // ====================================================
        $lang_id = Langs::getLangId($lang);
        $id = KnowledgeUserTest::getPrivateId($public_id);

        $question_id = KnowledgeTest::getDecryptedId($request->get('pbid'));

        $a = $request->get('a');


        // STEP 2: one answer row per option id posted, none
        // when no option came
        // =================================================
        if(is_array($a) && count($a)>0){
            foreach($a as $k => $v){

                $addRecord = new KnowledgeUserOptions;
                // The ATTEMPT's id, not the question's — pinned as
                // knowledge-category-lookup
                $addRecord->cat_id = KnowledgeTest::getCatId($id);
                $addRecord->test_id = $id;
                $addRecord->public_id = $public_id;
                $addRecord->user_id = Auth::guard('mem')->id();
                $addRecord->question_id = $question_id;
                // Any option id is taken, of any question
                $addRecord->correct = KnowledgeOption::select('correct')->where(['id'=>$v])->get()->pluck('correct')->first();
                $addRecord->save();

            }
        }


        // STEP 3: mark the question answered in the question
        // set; the update matches the member's own attempt only
        // =====================================================
        $qids = KnowledgeUserTest::getQIds($id);
        foreach($qids as $k => $v){
            if($k == $question_id){
                $newqids[$k] = 1;
            } else {
                $newqids[$k] = $v;
            }
        }

        // The 1 is JSON_HEX_TAG, harmless for a map of numbers
        KnowledgeUserTest::where(['id'=>$id, 'user_id'=>Auth::guard('mem')->id()])->update(['question_set'=>json_encode($newqids,1)]);


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
    // signed-in member's correct answer rows for it over all
    // of their answer rows for it, rounded.
    //
    // Known bug (high): the result counts answer rows, not
    // questions (KnowledgeTestController.php:562). A question
    // answered with no option leaves no row and drops out, so
    // one right answer and two skipped questions score 100.
    // Pinned as knowledge-skipped-answers. Fix: divide the
    // questions answered right by the questions drawn.
    //
    // Known bug (medium): with no answer rows at all the
    // division is by zero (KnowledgeTestController.php:562) —
    // a 500 on the progress page; the attempt never closes,
    // and the test page keeps sending the member back into
    // it, so the test is closed to them for good. Pinned as
    // knowledge-all-skipped. Fix: a result of 0 when there is
    // nothing to count.
    //
    // Used by:
    //   - progress (above) — whenever it finds every question
    //     answered
    // -----------------------------------------------------------

    public static function calculate_results($test_id)
    {
        $total_questions = KnowledgeUserOptions::where(['test_id'=>$test_id, 'user_id'=>Auth::guard('mem')->id()])->count();

        $correct_questions = KnowledgeUserOptions::where(['test_id'=>$test_id, 'user_id'=>Auth::guard('mem')->id(), 'correct'=>1])->count();

        $results = round($correct_questions * 100 / $total_questions);

        return $results;
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
