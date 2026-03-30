<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use App\Models\Members as Members;

class ValidateVerify
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

        $verify_token = $request->route('verify_token');

        if(Members::where(['verify_token'=>$verify_token])->where('verify_token', '<>', '')->whereNotNull('verify_token')->count() == 1) {

                return $next($request);

            } else {

                return redirect(route('login'));

            }


    }
}