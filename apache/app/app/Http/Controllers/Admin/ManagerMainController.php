<?php

// -----------------------------------------------------------
//  [*] ManagerMainController — the panel's home and reports
//
//  The admin side of the members' progress: the dashboard
//  every manager lands on after signing in, the manager's
//  own password change, and the per-member reports the
//  dashboard leads to — self-evaluation history, simulation
//  history and one simulation run — plus an unfinished
//  knowledge-test report nothing links to. The dashboard
//  and the reports are open to country managers
//  (auth.admin:man alone); the password change carries
//  perms too. The members' own dashboard is the other
//  ManagerMainController, in App\Http\Controllers.
//
//  Used by:
//    - routes/web_admin.php — admin-panel/dashboard,
//      change-pass and the user/… report routes
// -----------------------------------------------------------


namespace App\Http\Controllers\Admin;

use Session;
use App\Http\Controllers\Controller;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use App\Models\LMUsers;
use App\Models\Langs;
use App\Models\Members;
use App\Models\Managers;
use App\Models\Scenarios;
use App\Models\ScenariosUsers;
use App\Models\ScenariosUsersOptions;
use App\Models\ScenariosOptions;
use App\Models\SelfEvalOption;
use App\Models\SelfEvalUser;
use App\Models\SelfEvalUserTest;
use App\Models\KnowledgeTest;
use App\Models\KnowledgeOption;
use App\Models\KnowledgeUserTest;
use App\Models\KnowledgeUserOptions;








// -----------------------------------------------------------
// ManagerMainController
// -----------------------------------------------------------
//
// Reads the members' rows for the manager pages; only
// change_pass writes. The reports trust the member or the
// run the URL names — manager-country-scope, noted in
// their banners.
//
//   dashboard               — the home page, per language
//   change_pass             — the manager's own password
//   self_evaluation_history,
//   simulations_history     — a member's attempts, paged
//   simulation_report       — one simulation run
//   knowledge_report        — one knowledge test, a stub
//   question_correct,
//   knowledge_results       — helpers of the pages above
//
// Used by:
//   - routes/web_admin.php
// -----------------------------------------------------------

class ManagerMainController extends BaseController
{






    // -----------------------------------------------------------
    // __construct
    // -----------------------------------------------------------
    //
    // Only runs the admin BaseController's constructor: the
    // man-guard check and the session flags shared with the
    // views.
    //
    // Used by:
    //   - Laravel, when it dispatches a route of this
    //     controller
    // -----------------------------------------------------------

    public function __construct()
    {
        parent::__construct();
    }






    // -----------------------------------------------------------
    // dashboard
    // -----------------------------------------------------------
    //
    // GET /admin-panel/dashboard
    //
    // The panel's home page: a tab per language, each listing
    // the members of the signed-in manager's country, newest
    // first, with that language's course progress, the
    // simulations count (the language's simulations, then the
    // distinct ones the member started, finished or not) and
    // the knowledge results — a few queries per member and
    // language. The country is read from the manager's row
    // whatever main says, so a main admin, too, sees only the
    // members of its own country. Open to every signed-in
    // manager (auth.admin:man, no perms). Answers with
    // admin-panel/dashboard.blade.php.
    //
    // Used by:
    //   - AdminLoginController::login — the redirect after
    //     signing in
    //   - admin-panel/sidebar.blade.php — the Home entry of
    //     the menu
    // -----------------------------------------------------------

    public function dashboard()
    {
        $langlist = Langs::all()->pluck('name', 'id')->toArray();

        if(count(Members::where(['country'=>Managers::where(['id'=>Auth::guard('man')->id()])->get()->pluck('country')->first()])->get()->toArray()) > 0){

            // Every language gets the same members: only the
            // columns filled in below depend on the language
            $arr = array();
            foreach($langlist as $k => $v){
                $arr[$k] = Members::where(['country'=>Managers::where(['id'=>Auth::guard('man')->id()])->get()->pluck('country')->first()])
                ->orderByDesc('id')
                ->get()
                ->toArray();
            }

            foreach($arr as $kk => $vv){

                foreach($vv as $kkk => $vvv){

                    $arr[$kk][$kkk]['course_progress'] = LMUsers::course_progress($vvv['id'], $kk);
                    $arr[$kk][$kkk]['simulations'] = Scenarios::where(['lang_id'=>$kk])->count().' / '.ScenariosUsers::selectraw("*")->where(['user_id'=>$vvv['id'], 'lang_id'=>$kk])->distinct('scenario_id')->count();
                    $arr[$kk][$kkk]['knowledge_results'] = ManagerMainController::knowledge_results($vvv['id'], $kk);
                }

            }

        } else {

            $arr = array();

        }

        return view('admin-panel.dashboard', ['langlist'=>$langlist, 'recordlist'=>$arr, 'total_users'=>count(Members::where(['country'=>Managers::where(['id'=>Auth::guard('man')->id()])->get()->pluck('country')->first()])->get()->toArray())]);
    }






    // -----------------------------------------------------------
    // change_pass
    // -----------------------------------------------------------
    //
    // POST /admin-panel/change-pass
    //
    // Changes the signed-in manager's password. It refuses,
    // each time with a main.msg_* message, a wrong
    // current password, a new one equal to it, a repeat that
    // differs and a new one under 6 characters — the minimum
    // the admin login asks for too; then it stores the new
    // hash and answers back with main.msg_pass_changed.
    // Auth::user() and Auth::id() are the manager because man
    // is the default guard. perms keeps the route to main
    // admins: for a country manager the dialog and this route
    // both answer 404, so a country manager cannot change
    // their password here.
    //
    // Known bug (low): no resources/lang file defines the five
    // main.msg_* keys (ManagerMainController.php:209, 214, 219,
    // 229, 235), so the dialog shows the raw key names instead
    // of a message. Fix: add the keys to the main.php files.
    // Pinned as admin-pass-messages.
    //
    // Used by:
    //   - admin-panel/dialog/changepass.blade.php — the
    //     password dialog, opened by the Change password item
    //     of admin-panel/layouts/main.blade.php
    // -----------------------------------------------------------

    public function change_pass(Request $request)
    {
        if (!(Hash::check($request->get('password'), Auth::user()->password))) {

            return redirect()->back()->withErrors(__('main.msg_current_pass_wrong'));
        }

        if(strcmp($request->get('password'), $request->get('newpass')) == 0){

            return redirect()->back()->withErrors(__('main.msg_current_new_same'));
        }

        if(strcmp($request->get('newpass'), $request->get('renewpass')) <> 0){

            return redirect()->back()->withErrors(__('main.msg_new_pass_dont_match'));
        }

        $validator = Validator::make($request->all(), [
            'newpass' => 'required|min:6',
            'renewpass' => 'required|min:6'
        ]);

        if($validator->fails()){

            return redirect()->back()->withErrors(__('main.msg_pass_too_short', ['length'=>6]));

        }

        Managers::where('id', Auth::id())->update(['password' => Hash::make($request->get('newpass'))]);

        return redirect()->back()->withSuccess(__('main.msg_pass_changed'));
    }






    // -----------------------------------------------------------
    // self_evaluation_history
    // -----------------------------------------------------------
    //
    // GET /admin-panel/user/self_evaluation_history/{lang_id}/{user_id}
    //
    // One member's self-evaluation attempts in one language,
    // best points first, each with its category's name; 25 a
    // page, cut out by hand for a LengthAwarePaginator (the
    // query runs twice, once only to count). For each attempt
    // on the page it counts the questions answered exactly
    // right (question_correct) into correct, the column the
    // query fills with a placeholder 0. Answers with
    // admin-panel/user_self_eval_history.blade.php, headed by
    // the member's address and the language's name. Open to
    // every signed-in manager (auth.admin:man, no perms).
    //
    // Known bug (medium): the member comes from the URL and
    // nothing checks its country (ManagerMainController.php:284),
    // so a country manager reads any member's history by
    // changing the id; pinned as manager-country-scope. Fix:
    // answer 404 unless a main admin asks or the member is of
    // the manager's country.
    //
    // Used by:
    //   - admin-panel/dashboard.blade.php — the Self
    //     Evaluation History link of each member row
    // -----------------------------------------------------------

    public function self_evaluation_history(Request $request, $lang_id, $user_id)
    {
        if(count(SelfEvalUserTest::selectraw("bei_users_selfeval_test.*, bei_lm_category.name as category_name")
            ->leftJoin('bei_lm_category', 'bei_lm_category.id', 'bei_users_selfeval_test.cat_id')
            ->where([
                'bei_users_selfeval_test.user_id'=>$user_id,
                'bei_lm_category.lang_id'=>$lang_id
            ])->orderByDesc('points')->get()->toArray()) > 0){

            $arr = SelfEvalUserTest::selectraw("bei_users_selfeval_test.*, bei_lm_category.name as category_name, @correct:=0 AS correct")
            ->leftJoin('bei_lm_category', 'bei_lm_category.id', 'bei_users_selfeval_test.cat_id')
            ->where([
                'bei_users_selfeval_test.user_id'=>$user_id,
                'bei_lm_category.lang_id'=>$lang_id
            ])->orderByDesc('points')->get()->toArray();

        } else {

            $arr = array();

        }

        if (empty($request->get('page')) || ! is_numeric($request->get('page'))) {

            $curr_page = 1;
        } else {

            $curr_page = $request->get('page');
        }

        $perPage = 25;

        $currentItems = array_slice($arr, $perPage * ($curr_page - 1), $perPage);

        foreach($currentItems as $k => $v){

            $a = SelfEvalUser::where(['test_id'=>$v['id']])->groupBy('question_id')->get()->toArray();

            $c=0;
            foreach($a as $kk => $vv){

                if(ManagerMainController::question_correct($vv['question_id'], $vv['test_id'], $vv['user_id']) == true){
                    $c++;
                }

            }

            $currentItems[$k]['correct'] = $c;
            $c=0;

        }

        $paginator = new LengthAwarePaginator($currentItems, count($arr), $perPage, $curr_page, [
            'path' => url('admin-panel/user/self_evaluation_history/'.$lang_id.'/'.$user_id)
        ]);

        $paginator = $paginator->appends('page', request('page'));

        if (count($arr) == 0) {

            $arr = array();
            $paginator = array();
        }
        return view('admin-panel.user_self_eval_history', ['recordlist' => $paginator, 'email'=>Members::where(['id'=>$user_id])->get()->pluck('email')->first(), 'language'=>Langs::where(['id'=>$lang_id])->get()->pluck('name')->first()]);
    }






    // -----------------------------------------------------------
    // simulations_history
    // -----------------------------------------------------------
    //
    // GET /admin-panel/user/simulations_history/{lang_id}/{user_id}
    //
    // One member's simulation runs in one language, each with
    // its simulation's columns, its points, its start and end
    // and whether it finished; 25 a page, cut out by hand for
    // a LengthAwarePaginator (the query runs twice, once only
    // to count). Answers with
    // admin-panel/user_simulations_history.blade.php, headed
    // by the member's address and the language's name. Open
    // to every signed-in manager (auth.admin:man, no perms).
    //
    // Known bug (medium): the member comes from the URL and
    // nothing checks its country (ManagerMainController.php:379),
    // so a country manager reads any member's runs — and the
    // public ids that open their reports — by changing the
    // id; pinned as manager-country-scope. Fix: answer 404
    // unless a main admin asks or the member is of the
    // manager's country.
    //
    // Used by:
    //   - admin-panel/dashboard.blade.php — the Simulations
    //     History link of each member row
    // -----------------------------------------------------------

    public function simulations_history(Request $request, $lang_id, $user_id)
    {
        if(count(ScenariosUsers::selectraw("bei_scenarios.*, bei_users_scenarios.public_id, bei_users_scenarios.points, bei_users_scenarios.started, bei_users_scenarios.ended,  bei_users_scenarios.finished")->
            leftJoin('bei_scenarios', 'bei_scenarios.id', 'bei_users_scenarios.scenario_id')->
            where(['bei_users_scenarios.user_id'=>$user_id, 'bei_users_scenarios.lang_id'=>$lang_id])->get()->toArray()) > 0){

            $arr = ScenariosUsers::selectraw("bei_scenarios.*, bei_users_scenarios.public_id, bei_users_scenarios.points, bei_users_scenarios.started, bei_users_scenarios.ended,  bei_users_scenarios.finished")->
            leftJoin('bei_scenarios', 'bei_scenarios.id', 'bei_users_scenarios.scenario_id')->
            where(['bei_users_scenarios.user_id'=>$user_id, 'bei_users_scenarios.lang_id'=>$lang_id])->get()->toArray();

        } else {

            $arr = array();

        }

        if (empty($request->get('page')) || ! is_numeric($request->get('page'))) {

            $curr_page = 1;
        } else {

            $curr_page = $request->get('page');
        }

        $perPage = 25;

        $currentItems = array_slice($arr, $perPage * ($curr_page - 1), $perPage);

        $paginator = new LengthAwarePaginator($currentItems, count($arr), $perPage, $curr_page, [
            'path' => url('admin-panel/user/simulations_history/'.$lang_id.'/'.$user_id)
        ]);

        $paginator = $paginator->appends('page', request('page'));

        if (count($arr) == 0) {

            $arr = array();
            $paginator = array();
        }

        return view('admin-panel.user_simulations_history', ['recordlist' => $paginator, 'email'=>Members::where(['id'=>$user_id])->get()->pluck('email')->first(), 'language'=>Langs::where(['id'=>$lang_id])->get()->pluck('name')->first()]);
    }






    // -----------------------------------------------------------
    // question_correct
    // -----------------------------------------------------------
    //
    // True when the options the member chose for one question
    // of one self-evaluation attempt are exactly the
    // question's correct options — as many, every chosen one
    // correct and every correct one chosen; false otherwise.
    //
    // Used by:
    //   - self_evaluation_history (above)
    // -----------------------------------------------------------

    public static function question_correct($question_id, $test_id, $user_id)
    {
        // $pasirinkimai is Lithuanian for the choices: the
        // options the member picked for the question
        $pasirinkimai = SelfEvalUser::select('option_id')->where(['question_id'=>$question_id, 'test_id'=>$test_id, 'user_id'=>$user_id])->get()->pluck('option_id')->toArray();
        $question_options = SelfEvalOption::select('id')->where(['question_id'=>$question_id, 'correct'=>1])->get()->pluck('id')->toArray();

        if(count($pasirinkimai) == count($question_options)){

            // Left over: nothing reads $i
            $i=0;
            foreach($pasirinkimai as $k => $v){

                if(!in_array($v, $question_options)){
                    return false;
                }

            }

            foreach($question_options as $k => $v){

                if(!in_array($v, $pasirinkimai)){
                    return false;
                }

            }

            return true;

        } else {
            return false;
        }
    }






    // -----------------------------------------------------------
    // knowledge_results
    // -----------------------------------------------------------
    //
    // A member's finished knowledge tests in one language as
    // one line of percentages, comma-separated, or an empty
    // string when there are none. Each percentage still
    // carries the link to its report (user/knowledge_results)
    // inside HTML comments — the link was switched off that
    // way — so the dashboard, which prints the line raw,
    // shows bare percentages and nothing links to
    // knowledge_report.
    //
    // Used by:
    //   - dashboard (above) — the Knowledge results column
    // -----------------------------------------------------------

    public static function knowledge_results($user_id, $lang_id)
    {
        $results = KnowledgeUserTest::select(['public_id', 'results'])->where(['user_id'=>$user_id, 'lang_id'=>$lang_id, 'finished'=>1])->get()->toArray();

        if(count($results)>0){
            foreach($results as $k => $v){
                $link[] = "<!--<a href=\"".env('APP_URL_ADMIN')."/user/knowledge_results/{$v['public_id']}\">-->{$v['results']}%<!--</a>-->";
            }
        } else {
            $link = [];
        }

        if(count($results)>0){
            return implode(", ", $link);
        } else {
            return '';
        }
    }






    // -----------------------------------------------------------
    // simulation_report
    // -----------------------------------------------------------
    //
    // GET /admin-panel/user/simulation/report/{public_id}
    //
    // One simulation run, found by its public id: the
    // simulation's description and all its steps, the points
    // the run scored, the steps the member picked, and the
    // member's address and the language's name — four
    // separate lookups of the same run row. Answers with
    // admin-panel/simulation_report.blade.php. Open to every
    // signed-in manager (auth.admin:man, no perms).
    //
    // Known bug (medium): the run comes from the URL and
    // nothing checks its member's country
    // (ManagerMainController.php:541), so a country manager
    // opens any member's report from its public id, which
    // simulations_history hands out; pinned as
    // manager-country-scope. Fix: answer 404 unless a main
    // admin asks or the member is of the manager's country.
    //
    // Used by:
    //   - admin-panel/user_simulations_history.blade.php — the
    //     description of each run links here
    // -----------------------------------------------------------

    public function simulation_report(Request $request, $public_id)
    {
        $user_id = ScenariosUsers::where(['public_id'=>$public_id])->get()->pluck('user_id')->first();
        $lang_id = ScenariosUsers::where(['public_id'=>$public_id])->get()->pluck('lang_id')->first();
        $points = ScenariosUsers::where(['public_id'=>$public_id])->get()->pluck('points')->first();
        $scenario_id = ScenariosUsers::where(['public_id'=>$public_id])->get()->pluck('scenario_id')->first();

        $options = ScenariosOptions::where(['scenario_id'=>$scenario_id])->get()->toArray();

        $descr = Scenarios::where(['id'=>$scenario_id])->get()->pluck('descr')->first();

        $selected_options = ScenariosUsersOptions::where(['user_scenario_public_id'=>$public_id])->get()->pluck('option_id')->toArray();

        return view('admin-panel.simulation_report', [
            'email'=>Members::where(['id'=>$user_id])->get()->pluck('email')->first(),
            'language'=>Langs::where(['id'=>$lang_id])->get()->pluck('name')->first(),
            'descr'=>$descr,
            'options'=>$options,
            'points'=>$points,
            'selected_options'=>$selected_options
        ]);
    }






    // -----------------------------------------------------------
    // knowledge_report
    // -----------------------------------------------------------
    //
    // GET /admin-panel/user/knowledge_results/{public_id}
    //
    // Meant as the report of one knowledge test, found by its
    // public id, and left unfinished: it builds a breakdown
    // per question in $rez — the question, its options,
    // whether the member was right, the correct option — that
    // never reaches the view, and the view shows only the
    // member's address over an empty table; results is passed
    // and not shown either. Open to every signed-in manager
    // (auth.admin:man, no perms).
    //
    // Known bug (medium): nothing checks the member's country
    // (ManagerMainController.php:603), so a country manager
    // opens any member's attempt from its public id; pinned
    // as manager-country-scope. Fix: answer 404 unless a main
    // admin asks or the member is of the manager's country.
    //
    // Known bug (low): an unknown public id has no question
    // set, so json_decode gives null and the foreach over it
    // (ManagerMainController.php:612) stops with a 500, like
    // the member side's knowledge-unknown-attempt. Pinned as
    // knowledge-report-unknown. Fix: answer 404 when no
    // attempt has that public id.
    //
    // Used by:
    //   - nothing links here at the moment: the dashboard's
    //     link, built by knowledge_results (above), sits inside
    //     HTML comments
    // -----------------------------------------------------------

    public function knowledge_report(Request $request, $public_id)
    {
        $user_id = KnowledgeUserTest::where(['public_id'=>$public_id])->get()->pluck('user_id')->first();
        $results = KnowledgeUserTest::where(['public_id'=>$public_id])->get()->pluck('results')->first();

        $email = Members::where(['id'=>$user_id])->get()->pluck('email')->first();

        $question_set = json_decode(KnowledgeUserTest::where(['public_id'=>$public_id])->get()->pluck('question_set')->first(), 1);

        // Built and never handed to the view
        $rez = [];
        foreach($question_set as $k => $v){

            $rez[$k]['question'] = KnowledgeTest::where(['id'=>$k])->get()->pluck('question')->first();
            $rez[$k]['answers'] = KnowledgeOption::select(['id', 'option'])->where(['question_id'=>$k])->get()->pluck('option', 'id')->toArray();
            $rez[$k]['is_correct'] = KnowledgeUserOptions::where(['public_id'=>$public_id, 'question_id'=>$k])->get()->pluck('correct')->first();
            $rez[$k]['correct_option_id'] = KnowledgeOption::select('id')->where(['question_id'=>$k, 'correct'=>1])->get()->pluck('id')->first();

        }

        return view('admin-panel.knowledge_results', [
            'email'=>$email,
            'results'=>$results
        ]);
    }
}
