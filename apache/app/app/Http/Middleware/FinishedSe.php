<?php

// -----------------------------------------------------------
//  [*] FinishedSe — closes a self-evaluation once answered
//
//  Sits in front of the self-evaluation test page and its
//  answer form. It turns away an attempt that is not the
//  member's, and once the member has answered as many
//  questions as a self-evaluation asks — or every question
//  of a category that has fewer — it closes the attempt —
//  finished, end time, points — and sends the member to the
//  results instead of another question.
//
//  Used by:
//    - Kernel::$routeMiddleware (app/Http/Kernel.php) — the
//      alias finished.se
// -----------------------------------------------------------


namespace App\Http\Middleware;

use Session;
use Closure;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use App\Models\Badges;
use App\Models\SelfEvalUserTest;
use App\Models\SelfEval;
use App\Models\SelfEvalOption;
use App\Models\SelfEvalUser;








// -----------------------------------------------------------
// FinishedSe
// -----------------------------------------------------------
//
// Guards a self-evaluation attempt by its public id: it
// must be the signed-in member's, in the category of the
// URL, or the answer is a 404. A fully answered attempt is
// closed and redirected to its results page.
//
//   handle          — the ownership check and the close
//   calucate_points — the attempt's score
//
// Used by:
//   - Kernel::$routeMiddleware — the alias finished.se
//   - routes/web_user.php — GET and POST
//     /cp/se/{public_id}/{cat_id}
// -----------------------------------------------------------

class FinishedSe
{






    // -----------------------------------------------------------
    // handle
    // -----------------------------------------------------------
    //
    // Checks that exactly one attempt of the signed-in member
    // has the URL's public id and category; anything else is a
    // 404. Then counts the distinct questions answered in the
    // attempt against the self-evaluation length (val3 of
    // bei_settings row 2), or against the category's
    // answerable questions (SelfEval::scopeUsable) when it has
    // fewer. Fewer answered, and the request goes on to the
    // page or the save. As many or more, and an open attempt
    // is closed — finished set, ended stamped, the points of
    // calucate_points stored — and the member is redirected to
    // the results page; more can be answered when the length
    // was lowered, or questions deleted, after the attempt
    // began. Every close saves badge_self_evaluation_test in
    // the attempt's language — its category's, stored when it
    // began — whichever language the session shows by then,
    // in another tab or after signing in again; the close
    // that earns it carries the new-badge message, translated
    // into the session's language, the one the member reads —
    // this runs before BaseController sets the request's
    // locale. An attempt closed before keeps its end date and
    // points and only leads to its results. The answer to the
    // last question passes here before the controller saves
    // it: the controller's redirect back to the test page is
    // what closes the attempt.
    //
    // Used by:
    //   - Laravel's router, on the routes in the class banner
    // -----------------------------------------------------------

    public function handle($request, Closure $next)
    {
        // STEP 1: the attempt of the URL — exactly one of the
        // signed-in member's has its public id and category, or
        // the answer is a 404
        // =====================================================
        $public_id = $request->route('public_id');
        $cat_id = $request->route('cat_id');

        if(SelfEvalUserTest::where(['public_id'=>$public_id, 'user_id'=>Auth::guard('mem')->id(), 'cat_id'=>$cat_id])->count() == 1) {

            $test_id = SelfEvalUserTest::select('id')->where(['public_id'=>$public_id])->get()->pluck('id')->first();


            // STEP 2: how many answered questions close the
            // attempt — val3 of settings row 2, or the category's
            // number of questions when it has fewer
            // ===================================================
            $total_questions = SelfEval::attempt_length($cat_id);


            // STEP 3: the distinct questions answered so far — that
            // many or more close the attempt and lead to the results,
            // fewer let the request through to the page or the save
            // =======================================================
            $answered = SelfEvalUser::select("question_id")->where(['test_id'=>$test_id, 'user_id'=>Auth::guard('mem')->id(), 'cat_id'=>$cat_id])->groupby('question_id')->get()->toArray();

            if(count($answered) >= $total_questions){

                // Closed before: its end date and points stay
                if(SelfEvalUserTest::select('finished')->where(['id'=>$test_id])->get()->pluck('finished')->first() == 1){
                    return redirect('/cp/se_r/'.$public_id);
                }

                SelfEvalUserTest::where(['public_id'=>$public_id, 'user_id'=>Auth::guard('mem')->id()])->update(['finished'=>1, 'ended'=>date("Y-m-d H:i:s"), 'points'=>$this->calucate_points($public_id)]);

                $new = Badges::save_badge(SelfEvalUserTest::where(['id'=>$test_id])->value('lang_id'), 'badge_self_evaluation_test');

                if($new == true){
                    return redirect('/cp/se_r/'.$public_id)->withSuccess(__('main.new_badge', [], Session::get('lang')));
                } else {
                    return redirect('/cp/se_r/'.$public_id);
                }

            } else {

                return $next($request);
            }

        } else {

            abort(404);

        }
    }






    // -----------------------------------------------------------
    // calucate_points
    // -----------------------------------------------------------
    //
    // The score of an attempt; the misspelled name is the one
    // handle calls. Each question the signed-in member
    // answered in the attempt is worth its best option's
    // points: every option chosen for the question adds that
    // much when it is correct and takes it away when it is
    // not, each option once even should its row have been
    // stored twice. The points count as stored, to the
    // hundredth — three right options share 100 at 33.33
    // each — and the total is rounded to a whole number once,
    // at the end, so a fully right answer scores 100. They
    // were once cut to whole numbers first: 33 three times,
    // 99. A negative total counts as 0.
    //
    // Used by:
    //   - handle (above) — when it closes an attempt
    // -----------------------------------------------------------

    public static function calucate_points($public_id)
    {
        $answered = SelfEvalUser::select("question_id")->where(['public_id'=>$public_id, 'user_id'=>Auth::guard('mem')->id()])->groupby('question_id')->get()->pluck('question_id')->toArray();

        $points = 0;
        foreach($answered as $k => $v){

            $max_points = (float)SelfEvalOption::select('points')->where(['question_id'=>$v])->orderByDesc('points')->get()->pluck('points')->first();

            $answers = SelfEvalUser::select('option_id', 'correct')->where(['question_id'=>$v, 'public_id'=>$public_id])->distinct()->get()->toArray();

            foreach($answers as $kk => $vv){

                if($vv['correct'] == 1){
                    $points = $points + $max_points;
                } else {
                    $points = $points - $max_points;
                }

            }
        }

        $points = (int) round($points);

        if($points < 0){
            return 0;
        } else {
            return $points;
        }
    }
}
