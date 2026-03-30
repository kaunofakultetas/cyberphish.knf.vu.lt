<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use App\Models\LMContent as LMContent;

class LMExists
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {

        $uid = $request->route('uid');

        if(is_numeric($uid) && LMContent::where(['id'=>$uid])->count() == 1) {

                return $next($request);

            } else {

                abort(404);

            }


    }
}