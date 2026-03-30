<?php

namespace App\Http\Controllers\Admin;

use Carbon\Carbon;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use App\Http\Controllers\SettingsController;
use App\Models\Information as Information;
use App\Models\Langs as Langs;
use Illuminate\Support\Facades\Validator;

class InfoController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
    }

    public function index(Request $request)
    {

        $langlist = Langs::all()->pluck('name', 'id')->toArray();

        if(count(Information::all()->toArray()) > 0){

            $arr = Information::select('bei_information.*', DB::raw('bei_lang.`name` AS lang_name'))
            ->leftJoin('bei_lang', 'bei_information.lang_id', '=', 'bei_lang.id')->orderByDesc('id')->get()->toArray();


        } else {

            $arr = array();

        }

        if (empty($request->get('page')) || ! is_numeric($request->get('page'))) {

            $curr_page = 1;
        } else {

            $curr_page = $request->get('page');
        }

        $perPage = 25;

        $currentItems = array_slice($arr, $perPage * ($curr_page - 1), $perPage);

        $paginator = new LengthAwarePaginator($currentItems, count($arr), $perPage, $curr_page, [
            'path' => url('admin-panel/information')
        ]);

        $paginator = $paginator->appends('page', request('page'));

        if (count($arr) == 0) {

            $arr = array();
            $paginator = array();
        }



        return view('admin-panel.information', ['recordlist' => $paginator, 'langlist'=>$langlist]);

    }

    public function form(Request $request, $uid=null){

        $langlist = Langs::all()->pluck('name', 'id')->toArray();

        if($uid == null){

            return view('admin-panel.information_form', ['url'=>env('APP_URL').'/admin-panel/information/create', 'langlist'=>$langlist]);

        } else {

            return view('admin-panel.information_form',
                [
                    'url'=>env('APP_URL').'/admin-panel/information/update',
                    'uid'=>$uid,
                    'title'=>Information::where(['id'=>$uid])->pluck('title')->first(),
                    'content'=>Information::where(['id'=>$uid])->pluck('content')->first(),
                    'lang_id'=>Information::where(['id'=>$uid])->pluck('lang_id')->first(),
                    'alias'=>Information::where(['id'=>$uid])->pluck('alias')->first(),
                    'langlist'=>$langlist
                ]);

        }

    }

    public function create(Request $request){

        $validatedData = $request->validate([
            'title' => [ 'required', 'min:1', 'max:255' ],
        ]);

        $addRecord = new Information;
        $addRecord->title = $validatedData['title'];
        $addRecord->content = $request->get('content');
        $addRecord->lang_id = $request->get('lang_id');
        $addRecord->alias = Str::slug($validatedData['title']);
        $addRecord->save();

        return redirect()->intended(route('information'))->withSuccess("Created!");

    }

    public function update(Request $request){

        $validatedData = $request->validate([
            'uid' => [ 'required', 'integer' ],
            'title' => [ 'required', 'min:1', 'max:255' ],
        ]);

        $update = [
            'title'=>$validatedData['title'],
            'content'=>$request->get('content'),
            'alias'=>Str::slug($validatedData['title']),
            'lang_id'=>$request->get('lang_id')
        ];


        Information::where(['id' => $validatedData['uid']])->update($update);

        return back()->withSuccess("Updated!");

    }

    public function delete(Request $request, $uid){

        Information::destroy($uid);

        return back()->withSuccess("Removed!");
    }


}