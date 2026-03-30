<?php

namespace App\Http\Middleware;

use Session;
use Closure;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use App\Models\Langs;
use App\Models\Badges;
use App\Models\Settings;
use App\Models\SelfEvalUserTest;
use App\Models\SelfEval;
use App\Models\SelfEvalOption;
use App\Models\SelfEvalUser;

class FinishedSe
{

    public function handle($request, Closure $next)
    {

       $public_id = $request->route('public_id');
       $cat_id = $request->route('cat_id');

       if(SelfEvalUserTest::where(['public_id'=>$public_id, 'user_id'=>Auth::guard('mem')->id(), 'cat_id'=>$cat_id])->count() == 1) {

              $test_id = SelfEvalUserTest::select('id')->where(['public_id'=>$public_id])->get()->pluck('id')->first();

              $total_questions = (int)Settings::where(['id'=>2])->pluck('val3')->first();

              $answered = SelfEvalUser::select("question_id")->where(['test_id'=>$test_id, 'user_id'=>Auth::guard('mem')->id(), 'cat_id'=>$cat_id])->groupby('question_id')->get()->toArray();

              if($total_questions == count($answered)){

                  SelfEvalUserTest::where(['public_id'=>$public_id, 'user_id'=>Auth::guard('mem')->id()])->update(['finished'=>1, 'ended'=>date("Y-m-d H:i:s"), 'points'=>$this->calucate_points($public_id)]);

                  $num = SelfEvalUserTest::where(['user_id'=>Auth::guard('mem')->id(), 'lang_id'=>Langs::getLangId(Session::get('lang'))])->count();

                  if($num == 1){

                      Badges::save_badge(Langs::getLangId(Session::get('lang')), 'badge_self_evaluation_test');

                      return redirect('/cp/se_r/'.$public_id)->withSuccess(__('main.new_badge'));


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

    public static function calucate_points($public_id){

        $answered = SelfEvalUser::select("question_id")->where(['public_id'=>$public_id, 'user_id'=>Auth::guard('mem')->id()])->groupby('question_id')->get()->pluck('question_id')->toArray();

        $points = 0;
        foreach($answered as $k => $v){

            $max_points = (int)SelfEvalOption::select('points')->where(['question_id'=>$v])->orderByDesc('points')->get()->pluck('points')->first();

            $answers = SelfEvalUser::where(['question_id'=>$v, 'public_id'=>$public_id])->get()->toArray();

            foreach($answers as $kk => $vv){

                if($vv['correct'] == 1){
                    $points = $points + $max_points;
                } else {
                    $points = $points - $max_points;
                }

            }
        }

        if($points < 0){
            return 0;
        } else {
            return $points;
        }

    }


}