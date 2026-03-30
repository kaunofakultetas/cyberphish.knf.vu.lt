<?php

namespace App\Http\Middleware;

use Log;
use App\Providers\RouteServiceProvider;
use Closure;
use Illuminate\Support\Facades\Auth;

class RedirectIfAuthenticated
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string|null  $guard
     * @return mixed
     */
    public function handle($request, Closure $next, $guard = null)
    {
        if ($guard =='man' && Auth::guard($guard)->check()) {

            return redirect(route("admin_dashboard"));

        } elseif ($guard =='mem' && Auth::guard($guard)->check()) {

            return redirect(route("home"));

        }

     return $next($request);

    }
}
