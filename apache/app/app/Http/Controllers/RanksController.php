<?php

// -----------------------------------------------------------
//  [*] RanksController — the public rankings of a language
//
//  Two public tables, no sign-in needed: the members of one
//  language ranked by their self-evaluation points and by
//  their simulation points, where a member's points are the
//  best attempt per self-evaluation category, or per
//  simulation, summed up. The best attempts come from two
//  MySQL views of mysql/init.sql,
//  view_max_points_per_selfeval_cat and
//  view_max_points_per_scenario; each row shows the user
//  name, the points and the member's badges.
//
//  Used by:
//    - routes/web_user.php — {lang}/ranks/self-evaluation,
//      {lang}/ranks/simulations
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
use App\Models\Scenarios;
use App\Models\ScenariosAttributes;
use App\Models\ScenariosCategory;
use App\Models\ScenariosInAttributes;
use App\Models\ScenariosInCategory;
use App\Models\ScenariosOptions;
use App\Models\ScenariosUsers;
use App\Models\ScenariosUsersOptions;
use App\Models\ViewMaxPointsScenario;
use App\Models\ViewMaxPointsSelfEvalCat;








// -----------------------------------------------------------
// RanksController
// -----------------------------------------------------------
//
// The two actions are the same code over a different MySQL
// view and template. The badges beside a name are the
// member's distinct badge names in every language, not only
// the ranked one.
//
//   self_evaluation — the self-evaluation ranking
//   simulations     — the simulation ranking
//   with_badges     — the badges of every ranked member
//
// Used by:
//   - routes/web_user.php — the two {lang}/ranks/ routes
// -----------------------------------------------------------

class RanksController extends BaseController
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
    // self_evaluation
    // -----------------------------------------------------------
    //
    // GET /{lang}/ranks/self-evaluation
    //
    // The self-evaluation ranking of the language, open to
    // everyone: per member the sum of their best points in
    // each self-evaluation category, highest first, shown by
    // rank_self_eval.blade.php with user names and badges
    // (with_badges).
    //
    // Used by:
    //   - layouts/main.blade.php — the ranks menu of the top
    //     bar
    // -----------------------------------------------------------

    public function self_evaluation(Request $request, $lang)
    {
        $ranks = ViewMaxPointsSelfEvalCat::selectraw("id, username, SUM(max_points) as points")
        ->where(['lang_id'=>Langs::getLangId($lang)])
        ->groupby('id')
        ->orderbydesc('points')
        ->get()
        ->toArray();

        return view('rank_self_eval', ['ranking_table'=>RanksController::with_badges($ranks)]);
    }






    // -----------------------------------------------------------
    // simulations
    // -----------------------------------------------------------
    //
    // GET /{lang}/ranks/simulations
    //
    // The simulation ranking of the language, open to
    // everyone: per member the sum of their best points in
    // each simulation, highest first, shown by
    // rank_simulations.blade.php with user names and badges
    // (with_badges).
    //
    // Used by:
    //   - layouts/main.blade.php — the ranks menu of the top
    //     bar
    // -----------------------------------------------------------

    public function simulations(Request $request, $lang)
    {
        $ranks = ViewMaxPointsScenario::selectraw("id, username, SUM(max_points) as points")
        ->where(['lang_id'=>Langs::getLangId($lang)])
        ->groupby('id')
        ->orderbydesc('points')
        ->get()
        ->toArray();

        return view('rank_simulations', ['ranking_table'=>RanksController::with_badges($ranks)]);
    }






    // -----------------------------------------------------------
    // with_badges
    // -----------------------------------------------------------
    //
    // Gives every ranked member its badges: the distinct badge
    // names it holds, in the order it first earned them, each
    // as a row with its name, as the templates read them. One
    // query reads the badges of all the ranked members, and
    // they are dealt out by user id, so a ranking costs the
    // same queries however many members it lists.
    //
    // Used by:
    //   - self_evaluation, simulations (above)
    // -----------------------------------------------------------

    private static function with_badges(array $ranks)
    {
        $badges = [];
        foreach(Badges::select(['user_id', 'name'])->whereIn('user_id', array_column($ranks, 'id'))->orderBy('id')->get()->toArray() as $badge){

            // A name seen before keeps its first place
            $badges[$badge['user_id']][$badge['name']] = ['name'=>$badge['name']];

        }

        foreach($ranks as $k => $v){
            $ranks[$k]['badges'] = array_values($badges[$v['id']] ?? []);
        }

        return $ranks;
    }
}
