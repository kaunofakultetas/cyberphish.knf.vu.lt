<?php

namespace App\Http\Controllers;

use Session;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Models\News;
use App\Models\Settings;
use App\Models\Langs;

class HomeController extends BaseController
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();

    }

    public function index()
    {

        return view('welcome', [
            'latest_news'=>News::latest_news(3, Langs::getLangId(Session::get('lang'))),
            'about'=>json_decode(Settings::select("val1")->where(['id'=>1])->get()->pluck("val1")->first(),1),
            'lang_id'=>Langs::getLangId(Session::get('lang'))
        ]);
    }

}
