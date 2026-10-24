<?php

// -----------------------------------------------------------
//  [*] SimulationsController — members' phishing simulations
//
//  The phishing simulations as members play them, every
//  route behind auth:mem: the categories of the language,
//  the simulations of a category with the member's last
//  score, a simulation's start page, and a run — a walk down
//  the simulation's tree of choices, one level per step,
//  until a choice leads nowhere. The end of a run scores
//  it, mails the result and may earn the all-simulations
//  badge; the member's history lists the runs.
//
//  Used by:
//    - routes/web_user.php — the member simulation routes
// -----------------------------------------------------------


namespace App\Http\Controllers;

use Mail;
use Session;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Mail\SimulationsResults;
use App\Models\Badges;
use App\Models\SelfEvalUserTest;
use App\Models\SelfEval;
use App\Models\SelfEvalOption;
use App\Models\SelfEvalUser;
use App\Models\Settings;
use App\Models\Langs;
use App\Models\Members;
use App\Models\Scenarios;
use App\Models\ScenariosAttributes;
use App\Models\ScenariosCategory;
use App\Models\ScenariosInAttributes;
use App\Models\ScenariosInCategory;
use App\Models\ScenariosOptions;
use App\Models\ScenariosUsers;
use App\Models\ScenariosUsersOptions;








// -----------------------------------------------------------
// SimulationsController
// -----------------------------------------------------------
//
// A BaseController, so every action runs behind the
// middleware that sets the locale — from the URL's {lang}
// when it names one of the five languages, else from the
// session — and shares the member's data with the views. A
// simulation is a bei_scenarios row whose choices form a
// tree in bei_scenarios_options: level 1 choices hang under
// parent 0, every deeper one under the choice that leads to
// it, and each carries its option_type and points. A run is
// a bei_users_scenarios row the browser knows by its public
// id; every choice made in it is a
// bei_users_scenarios_options row.
//
//   index                          — unused
//   categories, simulations_list,
//   simulation_start               — choosing a simulation
//   simulation_progress            — a new run
//   gen_test_id                    — a fresh public id
//   simulation_do_stuff,
//   simulation_do_stuff_save,
//   save_choice                    — one step of a run
//   step_options                   — the choices of a step
//   simulation_total_points        — the score of a run
//   history                        — every run of the member
//   check_if_all_simulations_finished
//                                  — the badge condition
//
// Used by:
//   - routes/web_user.php — the routes of its actions
// -----------------------------------------------------------

class SimulationsController extends BaseController
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
    // An empty action. No route names
    // SimulationsController@index — the admin panel's
    // simulation list belongs to Admin\SimulationsController.
    //
    // Used by:
    //   - no route reaches it and nothing calls it at the
    //     moment
    // -----------------------------------------------------------

    public function index(Request $request, $lang)
    {
    }






    // -----------------------------------------------------------
    // categories
    // -----------------------------------------------------------
    //
    // GET /{lang}/simulations
    //
    // The simulation categories of the session's language,
    // newest first, behind auth:mem. Answers
    // simulations_categories.blade.php.
    //
    // Used by:
    //   - layouts/main.blade.php — the learning-material menu
    //   - parts/lm_left_sidebar.blade.php — the simulations
    //     button of the learning sidebar
    //   - simulation_do_stuff.blade.php — the other-simulations
    //     button of a run's end page
    // -----------------------------------------------------------

    public function categories()
    {
        if(count(ScenariosCategory::where(['lang_id'=>Langs::getLangId(Session::get('lang'))])->get()->toArray()) > 0){

            $arr = ScenariosCategory::where(['lang_id'=>Langs::getLangId(Session::get('lang'))])->orderByDesc('id')->get()->toArray();

        } else {

            $arr = array();

        }

        return view('simulations_categories', ['recordlist'=>$arr]);
    }






    // -----------------------------------------------------------
    // simulations_list
    // -----------------------------------------------------------
    //
    // GET /{lang}/simulations/{name}/{uid}
    //
    // The simulations filed under category uid in the
    // session's language, behind auth:mem and r_exists:sim_cat
    // (a 404 for an unknown category). Each comes with the
    // member's latest finished run of it in that language
    // under history, empty when there is none. The name in
    // the URL is never read. Answers
    // simulations_list.blade.php with the category's name.
    //
    // Used by:
    //   - simulations_categories.blade.php — the category
    //     buttons
    // -----------------------------------------------------------

    public function simulations_list(Request $request, $lang, $name, $uid)
    {
        $cat_simulations = ScenariosInCategory::selectraw('bei_scenarios.*')
        ->where(['bei_scenarios_in_category.cat_id'=>$uid, 'bei_scenarios_in_category.lang_id'=>Langs::getLangId(Session::get('lang'))])
        ->leftJoin('bei_scenarios', 'bei_scenarios.id', 'bei_scenarios_in_category.scenario_id')
        ->get()->toArray();

        foreach($cat_simulations as $k => $v){

            $cc = ScenariosUsers::where(['scenario_id'=>$v['id'], 'lang_id'=>Langs::getLangId(Session::get('lang')), 'finished'=>1, 'user_id'=>Auth::guard('mem')->id()])->orderByDesc('id');

            if($cc->count()>0){

                $cat_simulations[$k]['history'] = $cc->get()->first()->toArray();

            } else {

                $cat_simulations[$k]['history'] = [];

            }
        }

        return view('simulations_list', [
            'name'=>ScenariosCategory::where(['id'=>$uid])->pluck('name')->first(),
            'recordlist'=>$cat_simulations
        ]);
    }






    // -----------------------------------------------------------
    // simulation_start
    // -----------------------------------------------------------
    //
    // GET /{lang}/simulation/{uid}
    //
    // The start page of a simulation, behind auth:mem and
    // r_exists:sim_id (a 404 for an unknown simulation); a
    // simulation of another language than the session's is a
    // 404 too. Its columns become the view's variables, next
    // to the names of its categories and attributes by id,
    // and the page's form starts a run in one of two modes —
    // a simulation filed under no category hands the view an
    // empty list of them. Answers simulation_start.blade.php.
    //
    // Used by:
    //   - simulations_list.blade.php — each simulation's link
    //   - simulation_do_stuff.blade.php — the do-it-again
    //     button of a run's end page
    // -----------------------------------------------------------

    public function simulation_start(Request $request, $lang, $uid)
    {
        $scenario = Scenarios::where(['id'=>$uid, 'lang_id'=>Langs::getLangId(Session::get('lang'))]) ->get()->first();

        if($scenario == null){
            abort(404);
        }

        $scenario = $scenario->toArray();

        $categories = ScenariosInCategory::where(['scenario_id'=>$scenario['id'], 'lang_id'=>Langs::getLangId(Session::get('lang'))])->get()->toArray();
        $attributes = ScenariosInAttributes::where(['scenario_id'=>$scenario['id'], 'lang_id'=>Langs::getLangId(Session::get('lang'))])->get()->toArray();

        $scenario['categories'] = [];
        foreach($categories as $k => $v){
            $scenario['categories'][$v['cat_id']] = ScenariosCategory::where(['id'=>$v['cat_id']])->get()->pluck('name')->first();
        }

        foreach($attributes as $k1 => $v1){
            $scenario['attributes'][$v1['attribute_id']] = ScenariosAttributes::where(['id'=>$v1['attribute_id']])->get()->pluck('name')->first();
        }

        return view('simulation_start', $scenario);
    }






    // -----------------------------------------------------------
    // simulation_progress
    // -----------------------------------------------------------
    //
    // POST /{lang}/simulation_progress/{uid}
    //
    // Starts a run of the simulation, behind auth:mem and
    // r_exists:sim_id: a bei_users_scenarios row for the
    // member in the URL's language, open, started now, with
    // no points, under a fresh public id; then a redirect to
    // its first step. A simulation of another language is a
    // 404, as on the start page, so a forged post files no
    // run under a language it does not belong to. The mode chosen on the start page — 1
    // "for learning purposes", the feedback after every
    // choice, or 2 "for knowledge testing purposes", the
    // feedback listed at the end — is kept only in the
    // session, under simulation_type_ and the public id, so a
    // run resumed in another session shows no feedback at
    // all.
    //
    // Used by:
    //   - simulation_start.blade.php — the start form
    // -----------------------------------------------------------

    public function simulation_progress(Request $request, $lang, $uid)
    {
        if(Scenarios::where(['id'=>$uid, 'lang_id'=>Langs::getLangId($lang)])->count() == 0){
            abort(404);
        }

        $public_id = SimulationsController::gen_test_id();

        $add = new ScenariosUsers;
        $add->public_id = $public_id;
        $add->user_id = Auth::guard('mem')->id();
        $add->scenario_id = $uid;
        $add->lang_id = Langs::getLangId($lang);
        $add->finished = 0;
        $add->started = date("Y-m-d H:i:s");
        $add->points = 0;
        $add->save();

        Session::put('simulation_type_'.$public_id, $request->get('simulation_type'));

        return redirect('/'.$lang.'/simulation/progress/'.$public_id);
    }






    // -----------------------------------------------------------
    // gen_test_id
    // -----------------------------------------------------------
    //
    // A fresh public id for a run: 16 random letters and
    // digits from SettingsController::random_string, checked
    // against the ids bei_users_scenarios already holds; an
    // id taken already is drawn again. With 62 to the 16th
    // possible ids no test can make two collide.
    //
    // Used by:
    //   - simulation_progress (above)
    // -----------------------------------------------------------

    private static function gen_test_id()
    {
        $p_id = SettingsController::random_string();

        $num = ScenariosUsers::where(['public_id'=>$p_id])->count();

        if($num == 0){
            return $p_id;
        } elseif($num > 0){
            return SimulationsController::gen_test_id();
        }
    }






    // -----------------------------------------------------------
    // simulation_do_stuff
    // -----------------------------------------------------------
    //
    // GET /{lang}/simulation/progress/{public_id}
    //
    // One step of a run, behind auth:mem and
    // r_exists:sim_public_id (a 404 unless the run is the
    // member's). The member's latest choice decides the step:
    // before the first one the level 1 choices, afterwards the
    // choices under the latest one, a level deeper
    // (step_options). When there are none the run is over — at
    // once for a simulation without steps: a run still open is
    // closed with its points and the SimulationsResults mail
    // takes them to the member — once per run, however often
    // the end page is shown; a mail relay that cannot be
    // reached is logged, and the page still shows the result —
    // badge_all_simulations follows once every simulation the
    // run's language offers is finished
    // (check_if_all_simulations_finished), and in mode 2 the
    // page lists the feedback of every choice. The run's
    // language is the one it was started in, whatever code the
    // URL carries now: another language's, or one no language
    // has, which once stored the badge with no language, a
    // 500. Answers simulation_do_stuff.blade.php with the
    // choices, the run's ids, whether it is finished and the
    // points so far; the mode 1 feedback of the last choice
    // reaches the page through the session. The new-badge
    // message comes only with the visit that saved the badge.
    //
    // Used by:
    //   - simulation_progress (above), simulation_do_stuff_save
    //     (below) — their redirects
    //   - simulations_history.blade.php — the link of each run
    // -----------------------------------------------------------

    public function simulation_do_stuff(Request $request, $lang, $public_id)
    {
        // STEP 1: the run so far — its simulation and the
        // language it was started in
        // ===============================================
        $scenario_id = ScenariosUsers::where(['public_id'=>$public_id])->get()->pluck('scenario_id')->first();

        // The badge goes to the run's own language: the URL's code
        // may name another language, or none at all
        $lang_id = ScenariosUsers::where(['public_id'=>$public_id])->get()->pluck('lang_id')->first();

        $finished=0;

        $feedback_list = [];


        // STEP 2: the choices of this step — the level 1
        // choices before the first choice, afterwards the ones
        // under the latest choice, one level deeper
        // ====================================================
        $options = SimulationsController::step_options($public_id, $scenario_id);

        if(count($options)==0){
            // STEP 3: no choice left, the run is over — at once
            // for a simulation without steps: close it with its
            // points while it is still open, mail the result
            // when this visit closed it, and award
            // badge_all_simulations once every simulation the
            // run's language offers is finished
            // =================================================
            $finished=1;

            $closed = ScenariosUsers::where([
                    'public_id'=>$public_id,
                    'user_id'=>Auth::guard('mem')->id(),
                    'finished'=>0])->update(['ended'=>date("Y-m-d H:i:s"),
                        'finished'=>1,
                        'points'=>SimulationsController::simulation_total_points($public_id)]);

            // The mail is a courtesy: when the relay cannot be reached
            // the failure is logged and the end page still comes, the
            // run being closed already
            if($closed > 0){
                try {
                    Mail::to(Members::where(['id'=>Auth::guard('mem')->id()])->pluck('email')->first())
                    ->send(new SimulationsResults(['points'=>SimulationsController::simulation_total_points($public_id)]));
                } catch (\Throwable $e) {
                    report($e);
                }
            }

            if(!in_array(0, SimulationsController::check_if_all_simulations_finished($lang_id))){

                if(Badges::save_badge($lang_id, 'badge_all_simulations') == true){
                    $request->session()->now('success', __('main.new_badge'));
                }

            }


            // STEP 4: mode 2 lists every choice that has
            // feedback on the end page
            // ==========================================
            if(Session::get('simulation_type_'.$public_id) == 2){

                $feedback_list = ScenariosUsersOptions::selectraw('bei_users_scenarios_options.*, bei_scenarios_options.feedback, bei_scenarios_options.situation')
                ->leftJoin('bei_scenarios_options', 'bei_users_scenarios_options.option_id', 'bei_scenarios_options.id')
                ->where(['user_scenario_public_id'=>$public_id, 'user_id'=>Auth::guard('mem')->id()])
                ->whereraw("bei_scenarios_options.feedback != ''")
                ->get()
                ->toArray();

            } else {
                // mode 1 showed each choice's feedback on the way
            }

        }

        return view('simulation_do_stuff', [
            'options'=>$options,
            'public_id'=>$public_id,
            'scenario_id'=>$scenario_id,
            'finished'=>$finished,
            'points'=>SimulationsController::simulation_total_points($public_id),
            'feedback_list'=>$feedback_list
        ]);
    }






    // -----------------------------------------------------------
    // simulation_do_stuff_save
    // -----------------------------------------------------------
    //
    // POST /{lang}/simulation/progress/{public_id}
    //
    // Records the choice posted as a for the current step of
    // the run, behind auth:mem and r_exists:sim_public_id: a
    // bei_users_scenarios_options row with the option's type
    // and points. In mode 1 the option's feedback goes into
    // the session for the next step to show; in any other
    // mode the session's feedback is emptied. Only a choice
    // the current step offers (step_options) is taken: an
    // option of another step or simulation, or a choice sent
    // twice, records nothing, so a run never scores past its
    // best path — choices posted at the same moment included:
    // the run's row is locked while one of them is checked
    // and recorded (save_choice), so the next finds the step
    // moved on. Redirects to the run's next step.
    //
    // Used by:
    //   - simulation_do_stuff.blade.php — the choice form,
    //     posted back to its own URL
    // -----------------------------------------------------------

    public function simulation_do_stuff_save(Request $request, $lang, $public_id)
    {
        return DB::transaction(function () use ($request, $lang, $public_id) {
            ScenariosUsers::where(['public_id'=>$public_id])->lockForUpdate()->first();

            return SimulationsController::save_choice($request, $lang, $public_id);
        });
    }






    // -----------------------------------------------------------
    // save_choice
    // -----------------------------------------------------------
    //
    // The work of simulation_do_stuff_save, inside its
    // transaction and with the run's row locked: the step
    // check reads the run's latest choice only once a choice
    // posted at the same moment has been recorded, so the
    // second of them no longer belongs to the current step
    // and records nothing — where each once passed the check
    // and scored.
    //
    // Used by:
    //   - simulation_do_stuff_save (above)
    // -----------------------------------------------------------

    private static function save_choice(Request $request, $lang, $public_id)
    {
        // STEP 1: the posted choice, the run it goes to, and
        // the option's type, points and feedback
        // ==================================================
        $option_id = $request->get('a');

        $user_scenario_id = ScenariosUsers::where(['public_id'=>$public_id])->get()->pluck('id')->first();
        $scenario_id = ScenariosUsers::where(['public_id'=>$public_id])->get()->pluck('scenario_id')->first();

        $option_type = ScenariosOptions::where(['id'=>$option_id])->get()->pluck('option_type')->first();
        $points = ScenariosOptions::where(['id'=>$option_id])->get()->pluck('points')->first();
        $feedback = ScenariosOptions::where(['id'=>$option_id])->get()->pluck('feedback')->first();

        // A choice the current step does not offer records
        // nothing
        if(!in_array($option_id, array_column(SimulationsController::step_options($public_id, $scenario_id), 'id'))){
            return redirect('/'.$lang.'/simulation/progress/'.$public_id);
        }


        // STEP 2: record the choice
        // =========================
        $add = new ScenariosUsersOptions;
        $add->user_id = Auth::guard('mem')->id();
        $add->user_scenario_id = $user_scenario_id;
        $add->user_scenario_public_id = $public_id;
        $add->scenario_id = $scenario_id;
        $add->option_id = $option_id;
        $add->option_type = $option_type;
        $add->points = $points;
        $add->save();


        // STEP 3: mode 1 shows this choice's feedback at the
        // next step; any other mode empties it
        // ==================================================
        if(Session::get('simulation_type_'.$public_id) == 1){

            Session::put('feedback_'.$public_id, $feedback);

        } else {

            Session::put('feedback_'.$public_id, '');
        }

        return redirect('/'.$lang.'/simulation/progress/'.$public_id);
    }






    // -----------------------------------------------------------
    // step_options
    // -----------------------------------------------------------
    //
    // The choices the current step of a run offers, as rows of
    // bei_scenarios_options: the simulation's level 1 choices
    // before the first choice, afterwards the choices under
    // the member's latest choice, one level deeper — none
    // once the run has reached its end.
    //
    // Used by:
    //   - simulation_do_stuff (above) — the step to show
    //   - save_choice (above) — the choices it
    //     accepts
    // -----------------------------------------------------------

    private static function step_options($public_id, $scenario_id)
    {
        $if_any = ScenariosUsersOptions::where(['user_scenario_public_id'=>$public_id])->count();

        if($if_any == 0){
            return ScenariosOptions::where(['scenario_id'=>$scenario_id, 'parent_option_id'=>0, 'level'=>1])->get()->toArray();
        }

        $level = ScenariosUsersOptions::selectraw("bei_scenarios_options.level as level")
        ->where(['bei_users_scenarios_options.user_scenario_public_id'=>$public_id, 'bei_users_scenarios_options.user_id'=>Auth::guard('mem')->id()])
        ->leftJoin('bei_scenarios_options', 'bei_scenarios_options.id', 'bei_users_scenarios_options.option_id')
        ->orderByDesc('bei_users_scenarios_options.id')->get()->pluck('level')->first();

        $parent_option_id = ScenariosUsersOptions::where(['user_scenario_public_id'=>$public_id, 'user_id'=>Auth::guard('mem')->id()])
        ->orderByDesc('id')->get()->pluck('option_id')->first();

        $new_level = $level+1;

        return ScenariosOptions::where(['scenario_id'=>$scenario_id, 'parent_option_id'=>$parent_option_id, 'level'=>$new_level])->get()->toArray();
    }






    // -----------------------------------------------------------
    // simulation_total_points
    // -----------------------------------------------------------
    //
    // The points of a run so far: the sum over the member's
    // choices in it, one choice a level — the latest at each,
    // the one the run went on from — so a choice a race once
    // stored twice counts once; 0 before the first choice.
    //
    // Used by:
    //   - simulation_do_stuff (above)
    // -----------------------------------------------------------

    public static function simulation_total_points($public_id)
    {
        $latest_of_each_level = ScenariosUsersOptions::selectraw('MAX(bei_users_scenarios_options.id)')
            ->leftJoin('bei_scenarios_options', 'bei_scenarios_options.id', 'bei_users_scenarios_options.option_id')
            ->where(['bei_users_scenarios_options.user_scenario_public_id'=>$public_id])
            ->groupBy('bei_scenarios_options.level');

        return ScenariosUsersOptions::where(['user_scenario_public_id'=>$public_id, 'user_id'=>Auth::guard('mem')->id()])
            ->whereIn('id', $latest_of_each_level)
            ->sum('points');
    }






    // -----------------------------------------------------------
    // history
    // -----------------------------------------------------------
    //
    // GET /cp/simulations-history
    //
    // Every run of the member in the session's language,
    // finished or not, behind auth:mem: its simulation's
    // columns with the run's public id, points, start, end
    // and state. Answers simulations_history.blade.php, which
    // links each run to its step page.
    //
    // Used by:
    //   - parts/left-user-links.blade.php — the member's side
    //     links
    // -----------------------------------------------------------

    public function history()
    {
        $my_simulations = ScenariosUsers::selectraw("bei_scenarios.*, bei_users_scenarios.public_id, bei_users_scenarios.points, bei_users_scenarios.started, bei_users_scenarios.ended,  bei_users_scenarios.finished")->
        leftJoin('bei_scenarios', 'bei_scenarios.id', 'bei_users_scenarios.scenario_id')->
        where(['bei_users_scenarios.user_id'=>Auth::guard('mem')->id(), 'bei_users_scenarios.lang_id'=>Langs::getLangId(Session::get('lang'))])->get()->toArray();

        return view('simulations_history', ['my_simulations'=>$my_simulations]);
    }






    // -----------------------------------------------------------
    // check_if_all_simulations_finished
    // -----------------------------------------------------------
    //
    // One entry per simulation the language offers: 1 when
    // the member has finished a run of it, else 0 — so every
    // simulation is done when no 0 is left. A simulation is
    // offered when a category of its language lists it, as
    // the category pages do (simulations_list); one filed
    // under no category of its language is out of every
    // member's reach, and once kept the badge, and with it
    // badge_finished_course, out of reach too — six of
    // Latvian's 55 did. A language without simulations gives
    // an empty list, which reads as done.
    //
    // Used by:
    //   - simulation_do_stuff (above)
    // -----------------------------------------------------------

    public static function check_if_all_simulations_finished($lang_id)
    {
        $offered = ScenariosInCategory::select('scenario_id')->where(['lang_id'=>$lang_id])
            ->whereIn('cat_id', ScenariosCategory::select('id')->where(['lang_id'=>$lang_id]));

        $get_scenarios = Scenarios::where(['lang_id'=>$lang_id])->whereIn('id', $offered)->get()->toArray();

        $return = [];

        foreach($get_scenarios as $k => $v){

            $solved = ScenariosUsers::where(['user_id'=>Auth::guard('mem')->id(), 'scenario_id'=>$v['id'], 'finished'=>1])->count();

            if($solved > 0){
                $return[] = 1;
            } else {
                $return[] = 0;
            }

        }

        return $return;
    }
}
