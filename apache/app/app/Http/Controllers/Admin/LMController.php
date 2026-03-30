<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use App\Http\Controllers\SettingsController;
use App\Models\Langs as Langs;
use App\Models\LMCategory as LMCategory;
use App\Models\LMContent as LMContent;
use App\Models\LMFiles as LMFiles;
use App\Models\LMUsers as LMUsers;
use Illuminate\Support\Facades\Validator;

class LMController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
    }

    public function index(){

        $langlist = Langs::all()->pluck('name', 'id')->toArray();

        if(count(LMContent::all()->toArray()) > 0){

            $arr1 = LMContent::selectraw("bei_lm_content.*, bei_lm_category.name AS category_name, COUNT(bei_lm_content_files.id) AS additional_files")
            ->leftJoin("bei_lm_category", 'bei_lm_category.id', '=', 'bei_lm_content.cat_id')
            ->leftJoin("bei_lm_content_files", 'bei_lm_content.id', '=', 'bei_lm_content_files.content_id')
            ->groupBy("bei_lm_content.id")
            ->orderByDesc('id')->get()->toArray();

            $arr = array();
            foreach($arr1 as $k => $v){
                $arr[$v['lang_id']][] = $arr1[$k];
            }

        } else {

            $arr = array();

        }

        return view('admin-panel.lm_content', ['recordlist' => $arr, 'langlist'=>$langlist]);


    }

    public function form(Request $request, $type, $uid){

        $langlist = Langs::all()->pluck('name', 'id')->toArray();

        if($type == 'new'){

            $catlist = LMCategory::where(['lang_id'=>$uid])->pluck('name', 'id')->toArray();

            return view('admin-panel.lm_content_form', ['url'=>env('APP_URL').'/admin-panel/lm/content/create', 'langlist'=>$langlist, 'uid'=>$uid, 'catlist'=>$catlist]);

        } elseif($type == 'edit'){

            $catlist = LMCategory::where(['lang_id'=>LMContent::where(['id'=>$uid])->pluck('lang_id')->first()])->pluck('name', 'id')->toArray();

            return view('admin-panel.lm_content_form',
                [
                    'url'=>env('APP_URL').'/admin-panel/lm/content/update',
                    'uid'=>$uid,
                    'title'=>LMContent::where(['id'=>$uid])->pluck('title')->first(),
                    'cat_id'=>LMContent::where(['id'=>$uid])->pluck('cat_id')->first(),
                    'content'=>LMContent::where(['id'=>$uid])->pluck('content')->first(),
                    'hours'=>LMContent::where(['id'=>$uid])->pluck('hours')->first(),
                    'catlist'=>$catlist
                ]);

        }

    }

    public function create(Request $request){

        $validatedData = $request->validate([
            'title' => [ 'required', 'min:1', 'max:255' ],
        ]);

        $addRecord = new LMContent;
        $addRecord->cat_id = $request->get('cat_id');
        $addRecord->title = $request->get('title');
        $addRecord->content = $request->get('content');
        $addRecord->hours = $request->get('hours');
        $addRecord->lang_id = $request->get('uid');
        $addRecord->save();


        return Redirect::to(route('lm') . "#a".$request->get('uid'))->withSuccess("Created!");

    }

    public function update(Request $request){

        $validatedData = $request->validate([
            'title' => [ 'required', 'min:1', 'max:255' ],
        ]);

        $update = array(
            'cat_id'=>$request->get('cat_id'),
            'title'=>$request->get('title'),
            'content'=>$request->get('content'),
            'hours'=>$request->get('hours')
        );


        LMContent::where(['id' => $request->get('uid')])->update($update);

        return back()->withSuccess("Updated!");

    }

    public function delete(Request $request, $uid){

        $lang_id = LMContent::where(['id'=>$uid])->pluck('lang_id')->first();

        $files = LMFiles::where(['content_id'=>$uid])->get()->toArray();
        if(count($files)>0){
            foreach($files as $k => $v){
                $file_path = public_path('upload/').$v['file_name'];
                if(file_exists($file_path)){
                    unlink($file_path);
                    LMFiles::where(['id'=>$v['id']])->delete();
                }
            }
        }

        LMUsers::where(['content_id'=>$uid])->delete();
        LMContent::destroy($uid);
        return Redirect::to(URL::previous() . "#a".$uid)->withSuccess("Removed!");
    }

    public function categories(Request $request)
    {

        $langlist = Langs::all()->pluck('name', 'id')->toArray();

        if(count(LMCategory::all()->toArray()) > 0){

            $arr1 = LMCategory::orderByDesc('id')->get()->toArray();

            $arr = array();
            foreach($arr1 as $k => $v){
                $arr[$v['lang_id']][] = $arr1[$k];
            }

        } else {

            $arr = array();

        }


        return view('admin-panel.lm_categories', ['recordlist' => $arr, 'langlist'=>$langlist]);

    }

    public function categories_form(Request $request, $type, $uid){

        if($type =='new'){

            return view('admin-panel.dialog.lm_category_form', ['url'=>env('APP_URL').'/admin-panel/dialog/lm/categories/create', 'uid'=>$uid]);

        } elseif($type =='edit'){

            return view('admin-panel.dialog.lm_category_form',
                [
                    'url'=>env('APP_URL').'/admin-panel/dialog/lm/categories/update',
                    'uid'=>$uid,
                    'name'=>LMCategory::where(['id'=>$uid])->pluck('name')->first()
                ]);

        }

    }

    public function categories_create(Request $request){

        $validatedData = $request->validate([
            'name' => [ 'required', 'min:1', 'max:255' ],
        ]);

        $addRecord = new LMCategory;
        $addRecord->name = $validatedData['name'];
        $addRecord->lang_id = $request->get('uid');
        $addRecord->save();

        return Redirect::to(URL::previous() . "#a".$request->get('uid'))->withSuccess("Created!");


    }

    public function categories_update(Request $request){

        $validatedData = $request->validate([
            'uid' => [ 'required', 'integer' ],
            'name' => [ 'required', 'min:1', 'max:255' ],
        ]);

        LMCategory::where(['id' => $validatedData['uid']])->update(['name'=>$validatedData['name']]);

        return Redirect::to(URL::previous() . "#a".LMCategory::where(['id'=>$validatedData['uid']])->pluck('lang_id')->first())->withSuccess("Updated!");

    }

    public function categories_delete(Request $request, $uid){

        $lang_id = LMCategory::where(['id'=>$uid])->pluck('lang_id')->first();

        LMCategory::destroy($uid);

        return Redirect::to(URL::previous() . "#a".$uid)->withSuccess("Removed!");

    }

    public function additional_files(Request $request, $uid){

        $arr = LMFiles::where(['content_id'=>$uid])->get()->toArray();

        $name = LMContent::where(['id'=>$uid])->pluck('title')->first();

        if (empty($request->get('page')) || ! is_numeric($request->get('page'))) {

            $curr_page = 1;
        } else {

            $curr_page = $request->get('page');
        }

        $perPage = 25;

        $currentItems = array_slice($arr, $perPage * ($curr_page - 1), $perPage);

        $paginator = new LengthAwarePaginator($currentItems, count($arr), $perPage, $curr_page, [
            'path' => url('admin-panel/lm/files/'.$uid)
        ]);

        $paginator = $paginator->appends('page', request('page'));

        if (count($arr) == 0) {

            $arr = array();
            $paginator = array();
        }



        return view('admin-panel.lm_additional_files', [
            'recordlist'=>$paginator,
            'name'=>$name,
            'uid'=>$uid
        ]);

    }

    public function files_form(Request $request, $type, $uid){

        if($type =='new'){

            return view('admin-panel.dialog.lm_files_form', ['url'=>env('APP_URL').'/admin-panel/lm/file/create', 'uid'=>$uid, 'type'=>$type]);

        } elseif($type =='edit'){

            return view('admin-panel.dialog.lm_files_form',
                [
                    'url'=>env('APP_URL').'/admin-panel/lm/file/update',
                    'uid'=>$uid,
                    'name'=>LMFiles::where(['id'=>$uid])->pluck('name')->first(),
                    'embed'=>LMFiles::where(['id'=>$uid])->pluck('embed')->first(),
                    'type'=>$type
                ]);

        }

    }

    private static $allowed_extensions = [
        'pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'odt', 'ods', 'odp',
        'jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp',
        'mp4', 'mp3', 'wav', 'avi', 'mov',
        'zip', 'rar', 'txt', 'csv', 'rtf',
    ];

    public function files_create(Request $request){
         
        if ($request->hasFile('doc')) {

            $extension = strtolower($request->file('doc')->getClientOriginalExtension());
            if (!in_array($extension, self::$allowed_extensions)) {
                return back()->withErrors("File type '.$extension' is not allowed.");
            }

            $random_name = Str::random(40).'.'.$extension;
            $mimetype = $request->file('doc')->getmimetype();
           
            $request->file('doc')->move(public_path('upload/lm/'), $random_name);


            $addRecord = new LMFiles;
            $addRecord->content_id = $request->get('uid');
            $addRecord->name = $request->get('name');
            $addRecord->file_name = 'lm/'.$random_name;
            $addRecord->mime_type = $mimetype;
            $addRecord->file_size = $request->file('doc')->getsize();
            $addRecord->embed = $request->get('embed');
            $addRecord->save();
             

        }

        return back()->withSuccess("Created!");

    }

    public function files_update(Request $request){

        LMFiles::where(['id'=>$request->get('uid')])->update([
            'name'=>$request->get('name'),
            'embed'=>$request->get('embed')
        ]);

        return back()->withSuccess("Updated!");

    }

    public function files_delete(Request $request, $uid){

        $file = LMFiles::where(['id'=>$uid])->pluck('file_name')->first();

        $file_path = public_path('upload/').$file;

        if(file_exists($file_path)){
            unlink($file_path);
        }

        LMFiles::where(['id'=>$uid])->delete();

        return back()->withSuccess("Updated!");

    }




}