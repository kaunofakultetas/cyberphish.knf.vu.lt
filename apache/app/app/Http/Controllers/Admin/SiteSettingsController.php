<?php

namespace App\Http\Controllers\Admin;

use PDF;
use Carbon\Carbon;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\URL;
use Illuminate\Http\Request;
use App\Models\Settings;
use App\Models\LMCategory;
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

class SiteSettingsController extends BaseController
{

    public function __construct()
    {
        parent::__construct();
    }

    public function other(){

        $langlist = Langs::all()->pluck('name', 'id')->toArray();

        $categories_values = json_decode(Settings::where(['id'=>3])->pluck('val1')->first(),1);

        $categories = [];
        foreach($langlist as $k => $v){
            $categories[$k] = LMCategory::where(['lang_id'=>$k])->get()->toArray();
        }

        $data = [
            'self_eval'=>Settings::where(['id'=>2])->pluck('val3')->first(),
            'about'=>json_decode(Settings::where(['id'=>1])->pluck('val1')->first(), 1),
            'langlist'=>$langlist,
            'categories'=>$categories,
            'cat_values'=>$categories_values
        ];

        return view("admin-panel.settings_other", $data);
    }

    public function self_eval_save(Request $request){

        Settings::where(['id'=>2])->update(['val3'=>$request->get('self_eval')]);

        return Redirect::to(route('other_settings') . "#a2")->withSuccess("Updated!");

    }

    public function about_us_save(Request $request){

        Settings::where(['id'=>1])->update(['val1'=>json_encode($request->get('about'), 1)]);

        return Redirect::to(route('other_settings') . "#a1")->withSuccess("Updated!");

    }

    public function knowledge_test_settings_save(Request $request){

        $knowledge = json_encode($request->get('knowledge'), 1);

        Settings::where(['id'=>3])->update(['val1'=>$knowledge]);

        return Redirect::to(route('other_settings') . "#a3")->withSuccess("Updated!");

    }

    public function SimulationsDownloadPdf(Request $request){
        
        $langlist = Langs::all()->pluck('name', 'id')->toArray();
        
        $simulations = [];
         
        foreach($langlist as $k => $v){
            
            $simulation_list = Scenarios::where(['lang_id'=>$k])->get()->toArray();
            
            foreach($simulation_list as $kk => $vv){
                 
                $simulation_list[$kk]['option'] = ScenariosOptions::where(['scenario_id'=>$vv['id']])->get()->toArray();
                $simulations[$k]['attributes'] = ScenariosInAttributes::select("bei_scenarios_attributes.name")->leftJoin('bei_scenarios_attributes', 'bei_scenarios_attributes.id', 'bei_scenarios_in_attributes.attribute_id')->where(['bei_scenarios_in_attributes.scenario_id'=>$vv['id']])->get()->toArray();
                $simulations[$k]['categories'] = ScenariosInCategory::select("bei_scenarios_category.name")->leftJoin('bei_scenarios_category', 'bei_scenarios_category.id', 'bei_scenarios_in_category.cat_id')->where(['bei_scenarios_in_category.scenario_id'=>$vv['id']])->get()->toArray(); 
                
            }
            
            $simulations[$k]['lang'] = $v;
            $simulations[$k]['lang_id'] = $k; 
                        
            $simulations[$k]['simulations'] = $simulation_list;
        }
         
       return view('admin-panel.pdf.simulations', ['simulations'=>$simulations]);
    }

}