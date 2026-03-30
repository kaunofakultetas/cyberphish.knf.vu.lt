<?php

namespace App\Http\Controllers\Admin;

use Session;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use View;

class BaseController extends Controller
{
    public function __construct() {

        $this->middleware('auth:man');

        $this->middleware(function ($request, $next) {

           View::share('main', Session::get('main'));
           View::share('country', Session::get('country'));

            return $next($request);
        });



    }
}