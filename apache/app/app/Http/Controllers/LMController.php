<?php

// -----------------------------------------------------------
//  [*] LMController — lessons and self-evaluations of members
//
//  The member side of the learning material: the overview
//  of the lessons, a lesson page with its slides and files,
//  marking a lesson completed, with the badges that follow,
//  and the self-evaluation of a category — its start, one
//  question at a time, the result and the member's history.
//  Every route sits behind auth:mem except the lesson page,
//  which anyone may read. Two static helpers serve every
//  page of the member side through BaseController: the
//  lesson menu and the post-course questionnaire link.
//
//  Used by:
//    - routes/web_user.php — the learning-material and
//      self-evaluation routes
//    - BaseController — category_content_list and
//      pc_questionare_link, on every member-side page
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
use App\Models\LMContent;
use App\Models\Langs;
use App\Models\LMCategory;
use App\Models\LMFiles;
use App\Models\LMUsers;








// -----------------------------------------------------------
// LMController
// -----------------------------------------------------------
//
// A BaseController, so every action runs behind the
// middleware that sets the locale — from the URL's {lang}
// when it names one of the five languages, else from the
// session — and shares the member's data with the views.
// Lessons are bei_lm_content rows in the categories of
// bei_lm_category; a member's completed lessons are
// bei_lm_users rows. A self-evaluation attempt is a
// bei_users_selfeval_test row the browser knows by its
// public id, and each option the member chose in it is a
// bei_users_selfeval row.
//
//   index, content          — the overview and a lesson
//   category_content_list   — the lesson menu of every page
//   mark_completed          — a lesson done, and its badge
//   check_if_all_presentations_finished, show_self_eval
//                           — what the member has finished
//   self_evaluation_test, self_eval_test,
//   self_eval_test_save     — an attempt, question by
//                             question
//   gen_test_id             — a fresh public id
//   self_eval_test_results  — the result of an attempt
//   self_evaluation_history — every attempt of the member
//   pc_questionare_link     — the questionnaire link
//
// Used by:
//   - routes/web_user.php — the routes of its actions
//   - BaseController — its static helpers
// -----------------------------------------------------------

class LMController extends BaseController
{






    // -----------------------------------------------------------
    // __construct
    // -----------------------------------------------------------
    //
    // BaseController's constructor and nothing more: it
    // registers the middleware that sets the locale and shares
    // the lesson menu, the progress, the badges and the
    // questionnaire link with every view.
    //
    // Used by:
    //   - Laravel — the router builds the controller for every
    //     request to one of its routes
    // -----------------------------------------------------------

    public function __construct()
    {
        parent::__construct();
    }






    // -----------------------------------------------------------
    // index
    // -----------------------------------------------------------
    //
    // GET /{lang}/cp/learning-material
    //
    // The learning-material overview, behind auth:mem: every
    // category of the language with its lessons. The view
    // lists category_content_list, which BaseController shares
    // with every view, so the action passes nothing of its
    // own. Answers learning-material.blade.php.
    //
    // Used by:
    //   - layouts/main.blade.php — the course-progress bar in
    //     the member's menu
    // -----------------------------------------------------------

    public function index()
    {
        return view('learning-material');
    }






    // -----------------------------------------------------------
    // content
    // -----------------------------------------------------------
    //
    // GET /{lang}/learn/{slug}/{uid}
    //
    // One lesson, open to anyone: the route has no auth, only
    // lm.exists, which answers anything but the numeric id of
    // an existing lesson with a 404. The lesson is found by
    // its id alone, whatever the language, and the slug is
    // never read. Answers lm-content.blade.php with the
    // lesson, its extra files (embed 0, listed for download),
    // its slides (embed 1, shown in the PDF viewer), its
    // category and id, and whether the self-evaluation button
    // shows — only for a signed-in member who has completed
    // every lesson of the category.
    //
    // Used by:
    //   - learning-material.blade.php — the overview's lesson
    //     links
    //   - parts/lm_left_sidebar.blade.php — the lessons of the
    //     learning sidebar
    //   - layouts/main.blade.php — the learning-material menu
    // -----------------------------------------------------------

    public function content(Request $request, $lang, $slug, $uid)
    {
        return view('lm-content',[
            'content'=>LMContent::where(['id'=>$uid])->get()->first()->toArray(),
            'additional_files'=>LMFiles::where(['content_id'=>$uid, 'embed'=>0])->get()->toArray(),
            'embeded_files'=>LMFiles::where(['content_id'=>$uid, 'embed'=>1])->get()->toArray(),
            'show_self_eval'=>$this->show_self_eval($uid),
            'cat_id'=>LMContent::where(['id'=>$uid])->pluck('cat_id')->first(),
            'uid'=>$uid
        ]);
    }






    // -----------------------------------------------------------
    // category_content_list
    // -----------------------------------------------------------
    //
    // The lesson menu of the session's language: per category
    // id, the category's name and its lessons' titles by
    // lesson id. BaseController builds it for every page of
    // the member side, visitors included — one query per
    // category, every time.
    //
    // Used by:
    //   - BaseController — shared with every view as
    //     category_content_list, which layouts/main.blade.php,
    //     learning-material.blade.php and
    //     parts/lm_left_sidebar.blade.php list
    // -----------------------------------------------------------

    public static function category_content_list()
    {
        $get_categories = LMCategory::where(['lang_id'=>Langs::getLangId(Session::get('lang'))])->get()->pluck('name', 'id')->toArray();

        $arr = array();
        foreach($get_categories as $k => $v){

            $arr[$k]['category_name'] = $v;

            $arr[$k]['items'] = LMContent::select("id", "title")->where(['cat_id'=>$k])->get()->pluck("title", "id")->toArray();

        }

        return $arr;
    }






    // -----------------------------------------------------------
    // mark_completed
    // -----------------------------------------------------------
    //
    // POST /cp/mark_completed
    //
    // Records a lesson as completed by the member (auth:mem)
    // and sends the browser back, with main.new_badge flashed
    // when the completion earned a badge. The form posts the
    // lesson as ued, the MD5 of the MD5 of APP_SALT and the
    // lesson id, so only the lesson's own page can complete
    // it; a hash that matches no lesson changes nothing.
    // Badges belong to the lesson's language, and every one
    // that falls due comes with the same completion:
    // badge_topic with the member's first lesson there,
    // badge_category the first time a category is finished,
    // badge_all_presentations once every lesson of the
    // language is done. The message is flashed only when one
    // of them is new.
    //
    // Used by:
    //   - lm-content.blade.php — the mark-as-completed form
    // -----------------------------------------------------------

    public function mark_completed(Request $request)
    {
        // STEP 1: the lesson behind the posted hash, and its
        // language — get_id answers false when no lesson
        // matches
        // ==================================================
        $content_id = LMContent::get_id($request->get('ued'));
        $lang_id = LMContent::where(['id'=>$content_id])->get()->pluck('lang_id')->first();
        $user_id = Auth::guard('mem')->id();

        if($content_id !== false){
            // STEP 2: record the completion — firstOrCreate
            // keeps it to one row per member and lesson
            // =============================================
            LMUsers::firstOrCreate([
                'user_id'=>$user_id,
                'content_id'=>$content_id
            ]);


            // STEP 3: every badge the completion earns — a
            // lesson of the language earns badge_topic, a
            // finished category badge_category, a finished
            // language badge_all_presentations; save_badge
            // stores each once and answers true when it is new
            // ================================================
            $new = Badges::save_badge($lang_id, 'badge_topic');

            if($this->show_self_eval($content_id) == true){
                $new = Badges::save_badge($lang_id, 'badge_category') || $new;
            }

            if(!in_array(0, LMController::check_if_all_presentations_finished($lang_id))){
                $new = Badges::save_badge($lang_id, 'badge_all_presentations') || $new;
            }

            if($new){
                return back()->withSuccess(__('main.new_badge'));
            }

        }

        return back();
    }






    // -----------------------------------------------------------
    // check_if_all_presentations_finished
    // -----------------------------------------------------------
    //
    // One entry per lesson of the language: 1 when the member
    // has completed every lesson of that lesson's category,
    // else 0 — so the language is done when no 0 is left. A
    // language without lessons gives an empty list, which
    // reads as done. A few queries per lesson.
    //
    // Used by:
    //   - mark_completed (above)
    // -----------------------------------------------------------

    public static function check_if_all_presentations_finished($lang_id)
    {
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






    // -----------------------------------------------------------
    // show_self_eval
    // -----------------------------------------------------------
    //
    // True when the signed-in member has completed every
    // lesson of the given lesson's category — the moment the
    // lesson page offers the category's self-evaluation and
    // the category badge falls due. Always false for a
    // visitor.
    //
    // Used by:
    //   - content, mark_completed,
    //     check_if_all_presentations_finished (above)
    // -----------------------------------------------------------

    public static function show_self_eval($content_id)
    {
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






    // -----------------------------------------------------------
    // self_evaluation_test
    // -----------------------------------------------------------
    //
    // GET /{lang}/self-evaluation-test/{cat_id}
    //
    // Starts a self-evaluation of the category, behind
    // auth:mem and r_exists:lm_cat (a 404 for an unknown
    // category): a new bei_users_selfeval_test row for the
    // member in the category's own language — a lesson opens
    // under any language's URL, so the URL's need not be it —
    // started now, under a fresh public id, then a redirect
    // to its first question.
    // An attempt of the category the member still has open is
    // resumed instead, so another visit of the URL — a second
    // click on the button, a browser's prefetch — opens no new
    // one. Nothing here checks that the member has completed
    // the category's lessons — only the button waits for that.
    //
    // Used by:
    //   - lm-content.blade.php — the self-evaluation button
    //     of a finished category
    //   - self_eval_results.blade.php — the do-it-again button
    // -----------------------------------------------------------

    public function self_evaluation_test(Request $request, $lang, $cat_id)
    {
        $open = SelfEvalUserTest::where(['user_id'=>Auth::guard('mem')->id(), 'cat_id'=>$cat_id, 'finished'=>0])->get()->pluck('public_id')->first();

        if($open != null){
            return redirect('/cp/se/'.$open.'/'.$cat_id);
        }

        $p_id = LMController::gen_test_id();

        $addRecord = new SelfEvalUserTest;
        $addRecord->user_id = Auth::guard('mem')->id();
        $addRecord->cat_id = $cat_id;
        // the session's language, which BaseController's
        // middleware has just set from the URL
        $addRecord->lang_id = LMCategory::where(['id'=>$cat_id])->value('lang_id');
        $addRecord->public_id = $p_id;
        $addRecord->started = date("Y-m-d H:i:s");
        $addRecord->save();

        return redirect('/cp/se/'.$p_id.'/'.$cat_id);
    }






    // -----------------------------------------------------------
    // self_eval_test
    // -----------------------------------------------------------
    //
    // GET /cp/se/{public_id}/{cat_id}
    //
    // The next question of an attempt, behind auth:mem,
    // finished.se and r_exists:lm_cat. FinishedSe runs first:
    // a 404 unless the attempt is the member's own and of this
    // category, and once the member has answered as many
    // questions as an attempt asks — or every question of a
    // category that has fewer — it closes the attempt with
    // its points and redirects to the result instead. Here a
    // question the attempt has not answered yet is picked at
    // random among those a member can answer
    // (SelfEval::scopeUsable), its options shuffled, with the
    // progress so far.
    // Answers self-eval.blade.php, which shows
    // main.no_selfeval_questions for a category without any.
    //
    // Used by:
    //   - self_evaluation_test (above), self_eval_test_save
    //     (below) — their redirects
    //   - self_eval_history.blade.php — the link of an open
    //     attempt
    // -----------------------------------------------------------

    public function self_eval_test(Request $request, $public_id, $cat_id)
    {
        // STEP 1: the attempt so far — the category's name, the
        // number of questions an attempt asks (val3 of settings
        // row 2), the attempt's id and the questions it has
        // answered
        // =====================================================
        $cat_name = LMCategory::where(['id'=>$cat_id])->pluck('name')->first();

        $self_eval_limit = SelfEval::attempt_length($cat_id);

        $test_id = SelfEvalUserTest::select('id')->where(['public_id'=>$public_id])->get()->pluck('id')->first();

        $answered = SelfEvalUser::select("question_id")->where(['test_id'=>$test_id, 'user_id'=>Auth::guard('mem')->id()])->groupby('question_id')->get()->toArray();


        // STEP 2: a question of the category the attempt has
        // not answered, at random, with its options shuffled;
        // none left leaves both empty
        // ===================================================
        $next_count = SelfEval::selectraw("bei_selfeval_question.*")->where(['cat_id'=>$cat_id])->whereNotIn('bei_selfeval_question.id', $answered)->usable()->count();

        if($next_count > 0){
            $next_q = SelfEval::selectraw("bei_selfeval_question.*")->where(['cat_id'=>$cat_id])->whereNotIn('bei_selfeval_question.id', $answered)->usable()->inRandomOrder()->get()->first()->toArray();
            $answers = SelfEvalOption::where(['question_id'=>$next_q['id']])->inRandomOrder()->get()->toArray();
        } else {
            $next_q = [];
            $answers = [];
        }


        // STEP 3: the progress bar — the questions answered as
        // a share of the attempt's length, in percent, the number
        // FinishedSe closes it at, so the bar is full at the end
        // =======================================================
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






    // -----------------------------------------------------------
    // self_eval_test_save
    // -----------------------------------------------------------
    //
    // POST /cp/se/{public_id}/{cat_id}
    //
    // Stores the member's answer to one question of an
    // attempt, behind auth:mem and finished.se — the same
    // ownership check and closing redirect as the GET — then
    // redirects back to the question page. The question comes
    // as pbid, the MD5 of the MD5 of its id (unsalted, unlike
    // the lesson hash, so it hides nothing), and the chosen
    // options as the list a — one from the radio buttons of a
    // single-answer question, any number from the checkboxes
    // of a multi-answer one. Each chosen option of that
    // question becomes one bei_users_selfeval row carrying
    // the option's own correct flag and points; FinishedSe
    // scores the attempt from those rows when it closes it.
    // Nothing is stored without options, for a question of
    // another category, or for a question the attempt has
    // answered, so a forged or resubmitted form scores no
    // more than an honest one — posts that arrive together
    // included: the attempt's row is locked while one of
    // them checks for an answer and stores its own.
    //
    // Used by:
    //   - self-eval.blade.php — the question form, posted back
    //     to its own URL
    // -----------------------------------------------------------

    public function self_eval_test_save(Request $request, $public_id, $cat_id)
    {
        // STEP 1: the chosen options, the question behind pbid —
        // one of the attempt's category — and the attempt
        // behind the public id
        // ======================================================
        $a = $request->get('a');

        $question_id = SelfEval::select('id')->whereRaw("MD5(MD5(id))=?", [$request->get('pbid')])->where(['cat_id'=>$cat_id])->get()->pluck('id')->first();

        $test_id = SelfEvalUserTest::select('id')->where(['public_id'=>$public_id])->get()->pluck('id')->first();


        // STEP 2: one answer row per chosen option of the
        // question, each once, scored by that option's own
        // correct flag and points — with the attempt's row locked
        // first, so a post arriving at the same moment waits, then
        // finds this answer and stores none of its own
        // ========================================================
        DB::transaction(function () use ($a, $question_id, $test_id, $public_id, $cat_id) {
            SelfEvalUserTest::where(['id'=>$test_id])->lockForUpdate()->first();

            // A question the attempt has answered takes no second
            // answer
            $answered = SelfEvalUser::where(['test_id'=>$test_id, 'question_id'=>$question_id])->count() > 0;

            if($question_id != null && !$answered && is_array($a) && count($a)>0){

                $options = SelfEvalOption::where(['question_id'=>$question_id])->whereIn('id', array_unique($a))->get();

                foreach($options as $option){

                    $addRecord = new SelfEvalUser;
                    $addRecord->user_id = Auth::guard('mem')->id();
                    $addRecord->question_id = $question_id;
                    $addRecord->cat_id = $cat_id;
                    $addRecord->test_id = $test_id;
                    $addRecord->public_id = $public_id;
                    $addRecord->option_id = $option->id;
                    $addRecord->correct = $option->correct;
                    $addRecord->points = $option->points;
                    $addRecord->save();

                }
            }
        });

        return redirect('/cp/se/'.$public_id.'/'.$cat_id);
    }






    // -----------------------------------------------------------
    // gen_test_id
    // -----------------------------------------------------------
    //
    // A fresh public id for an attempt: 16 random letters and
    // digits from SettingsController::random_string, checked
    // against the ids bei_users_selfeval_test already holds;
    // an id taken already is drawn again. With 62 to the 16th
    // possible ids no test can make two collide.
    //
    // Used by:
    //   - self_evaluation_test (above)
    // -----------------------------------------------------------

    private static function gen_test_id()
    {
        $p_id = SettingsController::random_string();

        $num = SelfEvalUserTest::where(['public_id'=>$p_id])->count();

        if($num == 0){
            return $p_id;
        } elseif($num > 0){
            return LMController::gen_test_id();
        }
    }






    // -----------------------------------------------------------
    // self_eval_test_results
    // -----------------------------------------------------------
    //
    // GET /cp/se_r/{public_id}
    //
    // The result of one of the member's attempts, behind
    // auth:mem and r_exists:se_public_unfinished — which,
    // despite its name, only checks that the attempt is the
    // member's. Every question answered comes with all its
    // options and, per option, how often the member chose it,
    // next to the category, the start and end and the points.
    // The points, the answers and the category id behind the
    // do-it-again button come only from a finished attempt; an
    // open one shows its category's name and its dates, and
    // nothing else. Answers self_eval_results.blade.php.
    //
    // Used by:
    //   - FinishedSe — its redirect once the last answer is in
    //   - self_eval_history.blade.php — the link of a finished
    //     attempt
    // -----------------------------------------------------------

    public function self_eval_test_results(Request $request, $public_id)
    {
        // an open attempt leaves these three null, and so
        // finds no answers below
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

                // 1 or 0, the flag the view checks, even should an
                // option's row have been stored twice
                $results[$i]['answers'][$kk]['selected'] = SelfEvalUser::where(['test_id'=>$test_id, 'user_id'=>Auth::guard('mem')->id(), 'option_id'=>$vv['id']])->exists() ? 1 : 0;

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






    // -----------------------------------------------------------
    // self_evaluation_history
    // -----------------------------------------------------------
    //
    // GET /cp/self-evaluation-history
    //
    // Every self-evaluation attempt of the member (auth:mem)
    // in a category of the session's language, finished or
    // not, with the category's name, the highest points
    // first. Answers self_eval_history.blade.php, which links
    // a finished attempt to its result and an open one back
    // to its questions.
    //
    // Used by:
    //   - parts/left-user-links.blade.php — the member's side
    //     links
    // -----------------------------------------------------------

    public function self_evaluation_history()
    {
        $my_tests = SelfEvalUserTest::selectraw("bei_users_selfeval_test.*, bei_lm_category.name as category_name")
        ->leftJoin('bei_lm_category', 'bei_lm_category.id', 'bei_users_selfeval_test.cat_id')
        ->where([
            'bei_users_selfeval_test.user_id'=>Auth::guard('mem')->id(),
            'bei_lm_category.lang_id'=>Langs::getLangId(Session::get('lang'))
        ])->orderByDesc('points')->get()->toArray();

        return view('self_eval_history', ['my_tests'=>$my_tests]);
    }






    // -----------------------------------------------------------
    // pc_questionare_link
    // -----------------------------------------------------------
    //
    // The link to the post-course questionnaire, a Google
    // form, for the member's country — by the ids of the
    // registration form: 1 Lithuania, 2 Estonia, 3 Greece /
    // Cyprus, 4 Latvia, 5 Malta. Country 0 (Other, and every
    // visitor) has none, and neither has any other number: a
    // member row from before registration checked the range
    // would otherwise break every page of its member, since
    // BaseController asks on every page. The page shows the
    // link once the member holds badge_finished_course.
    //
    // Used by:
    //   - BaseController — shared with every view as pq_link,
    //     which parts/notif.blade.php shows
    // -----------------------------------------------------------

    public static function pc_questionare_link($country)
    {
        $links = [
            0=>'',
            1=>'https://forms.gle/obu5qrFHEMu8FTkx6',
            2=>'https://forms.gle/JaeYxCyQVBy7GWax5',
            3=>'https://forms.gle/pxofi1kKf7432xez5',
            4=>'https://forms.gle/4ZEAtra2UHzLdqYm9',
            5=>'https://forms.gle/wz2YtVcwCnz7kjXq6'
        ];

        return $links[$country] ?? '';
    }
}
