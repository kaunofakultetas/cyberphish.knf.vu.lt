<?php

// -----------------------------------------------------------
//  [*] Badges — the badges a member earns, per language
//
//  One bei_users_badges row per badge a member holds in one
//  language. A badge is a key the pages translate and draw:
//  the first lesson, a category's lessons all done, every
//  lesson, every simulation, the whole course, a knowledge
//  test passed at whichever try, the first self-evaluation,
//  ten days of logins in a row. Both helpers work for the
//  member signed in on the mem guard.
//
//  Used by:
//    - BaseController, BadgesController, LMController,
//      KnowledgeTestController, SimulationsController,
//      RanksController (Http/Controllers/)
//    - FinishedSe (Http/Middleware/)
//    - LoginLog (this folder)
// -----------------------------------------------------------


namespace App\Models;

use Session;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use App\Models\Langs;








// -----------------------------------------------------------
// Badges
// -----------------------------------------------------------
//
// Columns: lang_id, user_id and name, the badge's key. The
// table has no unique key, so only save_badge's check keeps
// a badge from being stored twice.
//
//   save_badge — awards a badge, once per language
//   get_badge  — whether the member holds a badge
//
// Used by:
//   - BaseController::__construct — the course badges every
//     member page checks and awards
//   - BadgesController::index — the badges page
//   - LMController::mark_completed,
//     KnowledgeTestController::progress,
//     SimulationsController::simulation_do_stuff,
//     FinishedSe::handle, LoginLog::if_login_10 — the badges
//     they award
//   - RanksController::with_badges — the badges beside
//     each ranked member of both rank tables
// -----------------------------------------------------------

class Badges extends Model
{

    // The table, which has no created_at / updated_at
    // columns; rememberTokenName belongs to the account
    // models, a plain model never reads it
    protected $table = "bei_users_badges";
    public $timestamps = false;
    protected $rememberTokenName = false;

    // Empty: nothing mass-assigns this model
    protected $fillable = [
    ];

    // Empty: toArray hands out every column
    protected $hidden = [
    ];

    // Empty: every column comes back uncast
    protected $casts = [
    ];






    // -----------------------------------------------------------
    // save_badge
    // -----------------------------------------------------------
    //
    // Awards the badge to the signed-in member in the given
    // language, unless the member holds it there already.
    // Answers true for a new badge — the callers then show
    // the new-badge message — and false for one held before.
    // The count and the insert are separate queries with no
    // lock between them.
    //
    // Used by:
    //   - BaseController::__construct — badge_finished_course
    //   - LMController::mark_completed — badge_topic,
    //     badge_category, badge_all_presentations
    //   - KnowledgeTestController::progress — badge_final_test
    //   - SimulationsController::simulation_do_stuff —
    //     badge_all_simulations
    //   - FinishedSe::handle — badge_self_evaluation_test
    //   - LoginLog::if_login_10 (this folder) — badge_login_10
    // -----------------------------------------------------------

    public static function save_badge($lang_id, $badge_name)
    {
        $num = Badges::where(['lang_id'=>$lang_id, 'user_id'=>Auth::guard('mem')->id(), 'name'=>$badge_name])->count();

        if($num == 0){

            $add = new Badges;
            $add->lang_id = $lang_id;
            $add->user_id = Auth::guard('mem')->id();
            $add->name = $badge_name;
            $add->save();

            return true;

        } else {

            return false;

        }
    }






    // -----------------------------------------------------------
    // get_badge
    // -----------------------------------------------------------
    //
    // Whether the signed-in member holds the badge in the
    // language the session shows: true for a row of it, even
    // for two — save_badge's unlocked check lets two requests
    // at the same moment store it twice — false for none.
    //
    // Used by:
    //   - BaseController::__construct — badge_all_presentations,
    //     badge_all_simulations and badge_finished_course
    //   - KnowledgeTestController::index and start —
    //     badge_all_presentations, which opens the test
    // -----------------------------------------------------------

    public static function get_badge($name)
    {
        $num = Badges::where(['name'=>$name, 'user_id'=>Auth::guard('mem')->id(), 'lang_id'=>Langs::getLangId(Session::get('lang'))])->count();

        return $num > 0;
    }
}
