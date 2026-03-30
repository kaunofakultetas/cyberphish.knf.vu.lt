<?php

namespace App\Http\Controllers\Admin;

use Carbon\Carbon;
use App\Http\Controllers\Controller;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\URL;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use App\Http\Controllers\SettingsController;
use App\Models\Langs as Langs;
use App\Models\News as News;
use Illuminate\Support\Facades\Validator;

class NewsController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
    }

    public function index(Request $request)
    {

        $langlist = Langs::all()->pluck('name', 'id')->toArray();

        if(count(News::all()->toArray()) > 0){

            $arr1 = News::orderByDesc('id')->get()->toArray();

            $arr = array();
            foreach($arr1 as $k => $v){
                $arr[$v['lang_id']][] = $arr1[$k];
            }

        } else {

            $arr = array();

        }


        return view('admin-panel.news', ['recordlist' => $arr, 'langlist'=>$langlist]);

    }

    public function form(Request $request, $uid=null){

        $langlist = Langs::all()->pluck('name', 'id')->toArray();

        if($uid == null){

            return view('admin-panel.news_form', ['url'=>env('APP_URL').'/admin-panel/news/create', 'langlist'=>$langlist, 'feat_img'=>'']);

        } else {

            return view('admin-panel.news_form',
                [
                    'url'=>env('APP_URL').'/admin-panel/news/update',
                    'uid'=>$uid,
                    'title'=>News::where(['id'=>$uid])->pluck('title')->first(),
                    'feat_img'=>News::where(['id'=>$uid])->pluck('feat_img')->first(),
                    'content'=>News::where(['id'=>$uid])->pluck('content')->first(),
                    'lang_id'=>News::where(['id'=>$uid])->pluck('lang_id')->first(),
                    'langlist'=>$langlist
                ]);

        }

    }

    public function create(Request $request){

        $validatedData = $request->validate([
            'title' => [ 'required', 'min:1', 'max:255' ],
            'feat' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif,svg', 'max:2048'],
        ]);

        $addRecord = new News;
        $addRecord->title = $validatedData['title'];
        $addRecord->content = $request->get('content');
        $addRecord->lang_id = $request->get('lang_id');
        $addRecord->alias = Str::slug($validatedData['title']);

        if ($request->hasFile('feat')) {

            $imageName = Str::random(40).'.'.$request->feat->extension();
            $request->feat->move(storage_path('app/public/feat'), $imageName);
            $addRecord->feat_img = 'feat/'.$imageName;

        }

        $addRecord->created = Carbon::now();
        $addRecord->created_by = Auth::guard('man')->id();
        $addRecord->save();

        return Redirect::to(route('news') . "#a".$request->get('lang_id'))->withSuccess("Created!");

    }

    public function update(Request $request){

        $validatedData = $request->validate([
            'uid' => [ 'required', 'integer' ],
            'title' => [ 'required', 'min:1', 'max:255' ],
            'feat' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif,svg', 'max:2048'],
        ]);

        $update = [
            'title'=>$validatedData['title'],
            'content'=>$request->get('content'),
            'lang_id'=>$request->get('lang_id'),
            'alias'=>Str::slug($validatedData['title']),
        ];


        if ($request->hasFile('feat')) {

            $imageName = Str::random(40).'.'.$request->feat->extension();
            $request->feat->move(storage_path('app/public/feat'), $imageName);
            $update['feat_img'] = 'feat/'.$imageName;

        }



        News::where(['id' => $validatedData['uid']])->update($update);

        return back()->withSuccess("Updated!");

    }

    public function delete(Request $request, $uid){

        $lang_id = News::where(['id'=>$uid])->pluck('lang_id')->first();

        News::destroy($uid);

        return Redirect::to(URL::previous() . "#a".$lang_id)->withSuccess("Removed!");
    }

    public function delete_picture($uid){

        $picture = storage_path('app/public').'/'.News::where(['id'=>$uid])->pluck('feat_img')->first();

        if(file_exists($picture)){
            unlink($picture);
        }

        News::where(['id' => $uid])->update(['feat_img'=>'']);

        return back()->withSuccess("Removed!");
    }


}