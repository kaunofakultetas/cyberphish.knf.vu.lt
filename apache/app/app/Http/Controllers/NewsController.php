<?php

namespace App\Http\Controllers;

use Session;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use App\Models\Langs;
use App\Models\News;

class NewsController extends BaseController
{
    public function __construct()
    {
        parent::__construct();

    }

    public function index(Request $request){

        if(count(News::where(['lang_id'=>Langs::getLangId(Session::get('lang'))])->get()->toArray()) > 0){

            $arr = News::where(['lang_id'=>Langs::getLangId(Session::get('lang'))])->get()->toArray();


        } else {

            $arr = array();

        }

        if (empty($request->get('page')) || ! is_numeric($request->get('page'))) {

            $curr_page = 1;
        } else {

            $curr_page = $request->get('page');
        }

        $perPage = 12;

        $currentItems = array_slice($arr, $perPage * ($curr_page - 1), $perPage);

        $paginator = new LengthAwarePaginator($currentItems, count($arr), $perPage, $curr_page, [
            'path' => url('news')
        ]);

        $paginator = $paginator->appends('page', request('page'));

        if (count($arr) == 0) {

            $arr = array();
            $paginator = array();
        }

        return view('news', [
            'recordlist' => $paginator
        ]);

    }

    public function content($lang, $slug, $uid){

        return view('news_content', [
            'content'=>News::where(['id'=>$uid])->get()->first()->toArray()
        ]);
    }


}