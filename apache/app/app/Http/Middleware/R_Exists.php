<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use App\Models\Members;
use App\Models\SelfEvalUserTest;
use App\Models\SelfEval;
use App\Models\SelfEvalOption;
use App\Models\SelfEvalUser;
use App\Models\LMContent;
use App\Models\Langs;
use App\Models\LMCategory;
use App\Models\LMFiles;
use App\Models\LMUsers;
use App\Models\Scenarios;
use App\Models\ScenariosAttributes;
use App\Models\ScenariosCategory;
use App\Models\ScenariosInAttributes;
use App\Models\ScenariosInCategory;
use App\Models\ScenariosOptions;
use App\Models\ScenariosUsers;
use App\Models\ScenariosUsersOptions;

class R_Exists
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next, $type)
    {


        if($type =='lm_cat'){

            $uid = $request->route('cat_id');

            if(LMCategory::where(['id'=>$uid])->count() == 1) {

                return $next($request);

            } else {
                abort(404);
            }

        }


        if($type =='se_public_unfinished'){

            $uid = $request->route('public_id');

            if(SelfEvalUserTest::where(['public_id'=>$uid, 'user_id'=>Auth::guard('mem')->id()])->count() > 0) {

                return $next($request);

            } else {
                abort(404);
            }

        }


        if($type =='sim_cat'){

            $uid = $request->route('uid');

            if(ScenariosCategory::where(['id'=>$uid])->count() == 1) {

                return $next($request);

            } else {
                abort(404);
            }

        }

        if($type =='sim_id'){

            $uid = $request->route('uid');

            if(Scenarios::where(['id'=>$uid])->count() == 1) {

                return $next($request);

            } else {
                abort(404);
            }

        }

        if($type =='sim_public_id'){

            $uid = $request->route('public_id');

            if(ScenariosUsers::where(['public_id'=>$uid, 'user_id'=>Auth::guard('mem')->id()])->count() == 1) {

                return $next($request);

            } else {
                abort(404);
            }

        }




























    }
}