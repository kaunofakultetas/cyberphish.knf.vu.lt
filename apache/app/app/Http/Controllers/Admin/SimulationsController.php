<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use App\Http\Controllers\SettingsController;
use App\Models\Langs;
use App\Models\Scenarios;
use App\Models\ScenariosAttributes;
use App\Models\ScenariosCategory;
use App\Models\ScenariosInAttributes;
use App\Models\ScenariosInCategory;
use App\Models\ScenariosOptions;
use App\Models\ScenariosUsers;
use App\Models\ScenariosUsersOptions;
use Illuminate\Support\Facades\Validator;

class SimulationsController extends BaseController {

    public function __construct()
    {
        parent::__construct();
    }

    public function index(Request $request)
    {

        $langlist = Langs::all()->pluck('name', 'id')->toArray();

        if(count(Scenarios::all()->toArray()) > 0){

            $arr1 = Scenarios::orderByDesc('id')->get()->toArray();

            $arr = array();
            foreach($arr1 as $k => $v){
                $arr[$v['lang_id']][] = $arr1[$k];
            }

        } else {

            $arr = array();

        }


        return view('admin-panel.simulations', ['recordlist' => $arr, 'langlist'=>$langlist]);

    }

    public function dialog_import_simulation(Request $request, $lang_id){


        return view('admin-panel.dialog.simulation_import', ['lang_id'=>$lang_id]);
    }

    public function simulations_import(Request $request){

        $lang_id = $request->get('lang_id');

        $extension = strtolower($request->file('doc')->getClientOriginalExtension());
        $allowed = ['xlsx'];
        if (!in_array($extension, $allowed)) {
            return back()->withErrors("File type '.$extension' is not allowed. Only .xlsx files are accepted.");
        }

        $random_name = Str::random(40).'.'.$extension;
        $mimetype = $request->file('doc')->getmimetype();
        $request->file('doc')->move(public_path('upload/simulations/'), $random_name);

        $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader('Xlsx');
        $reader->setReadDataOnly(TRUE);
        $spreadsheet = $reader->load(public_path('upload/simulations/').$random_name);

        $worksheet = $spreadsheet->getActiveSheet();

        $descr = $worksheet->getCellByColumnAndRow(3, 19)->getCalculatedValue();
        $goal = $worksheet->getCellByColumnAndRow(3, 20)->getCalculatedValue();
        $actors = $worksheet->getCellByColumnAndRow(3, 21)->getCalculatedValue();
        $source = $worksheet->getCellByColumnAndRow(3, 22)->getCalculatedValue();
        $choose_type = $worksheet->getCellByColumnAndRow(3, 26)->getCalculatedValue();
        $attack_type = $worksheet->getCellByColumnAndRow(3, 27)->getCalculatedValue();
        $main_image = $worksheet->getCellByColumnAndRow(3, 30)->getCalculatedValue();

        $categories = $spreadsheet->getActiveSheet()->rangeToArray('C34:C40', NULL, TRUE, TRUE, TRUE);
        $categories_selected = $spreadsheet->getActiveSheet()->rangeToArray('B34:B40', NULL, TRUE, TRUE, TRUE);

        $attributes = $spreadsheet->getActiveSheet()->rangeToArray('E34:E53', NULL, TRUE, TRUE, TRUE);
        $attributes_selected = $spreadsheet->getActiveSheet()->rangeToArray('D34:D53', NULL, TRUE, TRUE, TRUE);

        $cats = $this->get_xlsx_categories($categories_selected, $categories);
        $attrs = $this->get_xlsx_attrs($attributes_selected, $attributes);

        $questionsx = $spreadsheet->getActiveSheet()->rangeToArray('A58:H141', NULL, TRUE, TRUE, TRUE);

        $questions = $this->xlsx_process_questions($questionsx);

        $addScenario = new Scenarios;
        $addScenario->lang_id = $lang_id;
        $addScenario->descr = $descr;
        $addScenario->goal = $goal;
        $addScenario->actors = $actors;
        $addScenario->source = $source;
        $addScenario->choose_type = $choose_type;
        $addScenario->attack_type = $attack_type;
        $addScenario->image = $main_image;
        $addScenario->save();

        $scenario_id = $addScenario->id;

        foreach($cats as $k1 => $v1){
            $addCat = new ScenariosInCategory;
            $addCat->lang_id = $lang_id;
            $addCat->cat_id = $v1;
            $addCat->scenario_id = $scenario_id;
            $addCat->save();
        }

        foreach($attrs as $k2 => $v2){
            $addAttr = new ScenariosInAttributes;
            $addAttr->lang_id = $lang_id;
            $addAttr->attribute_id = $v2;
            $addAttr->scenario_id = $scenario_id;
            $addAttr->save();
        }

        $parent_option_id = 0;
        $level = 1;
        $prev_parent_options = [];
        foreach($questions as $kl => $val){

                if($val['level'] > $level){
                    $parent_option_id = $option_id;
                }

                if($val['level'] < $level){
                    if($val['level'] <> 1){
                        $prev_level = $val['level']-1;
                        $parent_option_id = $prev_parent_options[$prev_level][count($prev_parent_options[$prev_level])-1];
                    } else {
                        $parent_option_id = 0;
                    }

                }

            $addOption = new ScenariosOptions;
            $addOption->scenario_id = $scenario_id;
            $addOption->parent_option_id = $parent_option_id;
            $addOption->level = $val['level'];
            $addOption->situation = $val['situation'];
            $addOption->feedback = $val['feedback'];
            $addOption->image = $val['image'];
            $addOption->link = $val['link'];
            $addOption->option_type = $val['answer'];
            $addOption->points = $val['points'];
            $addOption->save();

            $option_id = $addOption->id;
            $level = $val['level'];

            $prev_parent_options[$val['level']][] = $option_id;


        }

        return Redirect::to(URL::previous() . "#a".$lang_id)->withSuccess("Imported!");


    }

    public function categories(Request $request)
    {

        $langlist = Langs::all()->pluck('name', 'id')->toArray();

        if(count(ScenariosCategory::all()->toArray()) > 0){

            $arr1 = ScenariosCategory::orderByDesc('id')->get()->toArray();

            $arr = array();
            foreach($arr1 as $k => $v){
                $arr[$v['lang_id']][] = $arr1[$k];
            }

        } else {

            $arr = array();

        }


        return view('admin-panel.simulations_categories', ['recordlist' => $arr, 'langlist'=>$langlist]);

    }

    public function categories_form(Request $request, $type, $uid){

        if($type =='new'){

            return view('admin-panel.dialog.simulations_category_form', ['url'=>env('APP_URL').'/admin-panel/dialog/simulations/categories/create', 'uid'=>$uid]);

        } elseif($type =='edit'){

            return view('admin-panel.dialog.simulations_category_form',
                [
                    'url'=>env('APP_URL').'/admin-panel/dialog/simulations/categories/update',
                    'uid'=>$uid,
                    'name'=>ScenariosCategory::where(['id'=>$uid])->pluck('name')->first()
                ]);

        }

    }

    public function categories_create(Request $request){

        $validatedData = $request->validate([
            'name' => [ 'required', 'min:1', 'max:255' ],
        ]);

        $addRecord = new ScenariosCategory;
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

        ScenariosCategory::where(['id' => $validatedData['uid']])->update(['name'=>$validatedData['name']]);

        return Redirect::to(URL::previous() . "#a".ScenariosCategory::where(['id'=>$validatedData['uid']])->pluck('lang_id')->first())->withSuccess("Updated!");

    }

    public function categories_delete(Request $request, $uid){

        $lang_id = ScenariosCategory::where(['id'=>$uid])->pluck('lang_id')->first();

        ScenariosInCategory::where(['cat_id'=>$uid])->delete();
        ScenariosCategory::destroy($uid);

        return Redirect::to(URL::previous() . "#a".$lang_id)->withSuccess("Removed!");

    }

    public function attributes(Request $request)
    {

        $langlist = Langs::all()->pluck('name', 'id')->toArray();

        if(count(ScenariosAttributes::all()->toArray()) > 0){

            $arr1 = ScenariosAttributes::orderByDesc('id')->get()->toArray();

            $arr = array();
            foreach($arr1 as $k => $v){
                $arr[$v['lang_id']][] = $arr1[$k];
            }

        } else {

            $arr = array();

        }


        return view('admin-panel.simulations_attributes', ['recordlist' => $arr, 'langlist'=>$langlist]);

    }

    public function attributes_form(Request $request, $type, $uid){

        if($type =='new'){

            return view('admin-panel.dialog.simulations_attributes_form', ['url'=>env('APP_URL').'/admin-panel/dialog/simulations/attributes/create', 'uid'=>$uid]);

        } elseif($type =='edit'){

            return view('admin-panel.dialog.simulations_attributes_form',
                [
                    'url'=>env('APP_URL').'/admin-panel/dialog/simulations/attributes/update',
                    'uid'=>$uid,
                    'name'=>ScenariosAttributes::where(['id'=>$uid])->pluck('name')->first()
                ]);

        }

    }

    public function attributes_create(Request $request){

        $validatedData = $request->validate([
            'name' => [ 'required', 'min:1', 'max:255' ],
        ]);

        $addRecord = new ScenariosAttributes;
        $addRecord->name = $validatedData['name'];
        $addRecord->lang_id = $request->get('uid');
        $addRecord->save();

        return Redirect::to(URL::previous() . "#a".$request->get('uid'))->withSuccess("Created!");


    }

    public function attributes_update(Request $request){

        $validatedData = $request->validate([
            'uid' => [ 'required', 'integer' ],
            'name' => [ 'required', 'min:1', 'max:255' ],
        ]);

        ScenariosAttributes::where(['id' => $validatedData['uid']])->update(['name'=>$validatedData['name']]);

        return Redirect::to(URL::previous() . "#a".ScenariosAttributes::where(['id'=>$validatedData['uid']])->pluck('lang_id')->first())->withSuccess("Updated!");

    }

    public function attributes_delete(Request $request, $uid){

        $lang_id = ScenariosAttributes::where(['id'=>$uid])->pluck('lang_id')->first();

        ScenariosInAttributes::where(['attribute_id'=>$uid])->delete();
        ScenariosAttributes::destroy($uid);

        return Redirect::to(URL::previous() . "#a".$lang_id)->withSuccess("Removed!");

    }

    public static function get_xlsx_categories($selected, $categories){

        $items = [];
        foreach($selected as $k => $v){

            if($v['B'] =="Yes"){
                $items[] = ScenariosCategory::IdByCategory($categories[$k]['C']);
            }
        }


        return $items;


    }

    public static function get_xlsx_attrs($selected, $attrs){

        $items = [];
        foreach($selected as $k => $v){

            if($v['D'] =="Yes"){
                $items[] = ScenariosAttributes::IdByAttr($attrs[$k]['E']);
            }
        }


        return $items;


    }

    public static function xlsx_process_questions($questions){

        $q = [];
        $i=0;
        foreach($questions as $k => $v){

            if(!empty($v['C'])){
                $q[$i]['level'] = $v['A'];
                $q[$i]['situation'] = $v['C'];
                $q[$i]['answer'] = SimulationsController::get_answer_type_id($v['D']);

                if(SimulationsController::get_answer_type_id($v['D']) == 0){
                    $q[$i]['points'] = 0;
                } elseif(SimulationsController::get_answer_type_id($v['D']) == 1){
                    $q[$i]['points'] = 50;
                } elseif(SimulationsController::get_answer_type_id($v['D']) == 2){
                    $q[$i]['points'] = 100;
                } elseif(SimulationsController::get_answer_type_id($v['D']) == 3){
                    $q[$i]['points'] = 200;
                }

                $q[$i]['feedback'] = $v['E'];
                $q[$i]['image'] = $v['H'];
                $q[$i]['link'] = $v['F'];

                $i++;
            }


        }

        return $q;

    }

    public static function get_answer_type_id($name){

        $arr = ['Incorrect'=>0,'Semi-correct'=>2,'Correct'=>3,'Semi-incorrect'=>1];

        return $arr[$name];

    }

    public function simulation_delete(Request $request, $uid){

        $lang_id = Scenarios::where(['id'=>$uid])->pluck('lang_id')->first();

        ScenariosInCategory::where(['scenario_id'=>$uid])->delete();
        ScenariosInAttributes::where(['scenario_id'=>$uid])->delete();
        ScenariosOptions::where(['scenario_id'=>$uid])->delete();
        ScenariosUsers::where(['scenario_id'=>$uid])->delete();
        ScenariosUsersOptions::where(['scenario_id'=>$uid])->delete();
        Scenarios::destroy($uid);

        return Redirect::to(URL::previous() . "#a".$lang_id)->withSuccess("Removed!");

    }

    public function simulation_edit(Request $request, $uid){

        $situation = Scenarios::where(['id'=>$uid])->get()->pluck('descr')->first();

        $options = ScenariosOptions::where(['scenario_id'=>$uid])->get()->toArray();


        return view('admin-panel.simulations_edit', [
            'uid'=>$uid,
            'situation'=>$situation,
            'options'=>$options
        ]);

    }

    public function simulation_update(Request $request, $uid){

        $descr = $request->get('situation');

        $options = $request->get('option');

        $feedback = $request->get('feedback');


        if(is_array($options) && count($options)>0){

            Scenarios::where(['id'=>$uid])->update(['descr'=>$descr]);

            foreach($options as $k => $v){

                ScenariosOptions::where(['id'=>$k])->update(['situation'=>$v, 'feedback'=>$feedback[$k]]);

            }

        }


        return back()->withSuccess("Updated!");
    }

    public function simulation_categories(Request $request, $lang_id, $simulation_id){


        $categories = ScenariosCategory::where(['lang_id'=>$lang_id])->get()->toArray();

        $in_cat = ScenariosInCategory::where(['lang_id'=>$lang_id, 'scenario_id'=>$simulation_id])->get()->pluck('cat_id')->toArray();

        return view('admin-panel.dialog.simulation_categories', ['categories'=>$categories, 'scenario_id'=>$simulation_id, 'lang_id'=>$lang_id, 'in_cat'=>$in_cat]);

    }


    public function simulation_categories_update(Request $request, $lang_id, $simulation_id){

        $cats = $request->get('cat');

        if(is_array($cats) && count($cats)>0){

            ScenariosInCategory::where(['lang_id'=>$lang_id, 'scenario_id'=>$simulation_id])->delete();

            foreach($cats as $k => $v){

                $a = new ScenariosInCategory;
                $a->lang_id = $lang_id;
                $a->scenario_id = $simulation_id;
                $a->cat_id = $v;
                $a->save();

            }

        }

        return Redirect::to(URL::previous() . "#a".$lang_id)->withSuccess("Updated!");

    }

}



?>