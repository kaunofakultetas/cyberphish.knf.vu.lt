<?php

// -----------------------------------------------------------
//  [*] SiteSettingsController — the panel's site settings
//
//  The Other Settings page and its three forms, which keep
//  their values in bei_settings rows with fixed ids: row 1
//  holds the home page's about text and link per language
//  (JSON in val1), row 2 the number of questions a
//  self-evaluation draws (val3), row 3 how many questions
//  the knowledge test draws per language and category
//  (JSON in val1). The saves UPDATE rows they never create;
//  mysql/init.sql seeds all three. The page also offers
//  every simulation on one printable page. Every route sits
//  behind auth.admin:man and perms: main admins only.
//
//  Used by:
//    - routes/web_admin.php — the admin-panel/settings/other
//      page, its three saves and the simulations printout
// -----------------------------------------------------------


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








// -----------------------------------------------------------
// SiteSettingsController
// -----------------------------------------------------------
//
// One page, three saves and a printout. Each save answers
// a redirect to the page, opened on the tab of its form.
//
//   other                        — the page
//   self_eval_save               — saves row 2
//   about_us_save                — saves row 1
//   knowledge_test_settings_save — saves row 3
//   SimulationsDownloadPdf       — the simulations printout
//
// Used by:
//   - routes/web_admin.php — the settings routes
// -----------------------------------------------------------

class SiteSettingsController extends BaseController
{






    // -----------------------------------------------------------
    // __construct
    // -----------------------------------------------------------
    //
    // Nothing of its own: BaseController's constructor adds
    // the auth:man middleware and shares the session's main
    // and country flags with every view of the panel.
    //
    // Used by:
    //   - Laravel's router, for every route of this controller
    // -----------------------------------------------------------

    public function __construct()
    {
        parent::__construct();
    }






    // -----------------------------------------------------------
    // other
    // -----------------------------------------------------------
    //
    // GET /admin-panel/settings/other
    //
    // The page of admin-panel/settings_other.blade.php, one
    // tab per form: the about text of row 1 and the knowledge
    // test limits of row 3, both decoded from JSON, the
    // self-evaluation number of row 2, and every language's
    // learning-material categories, each of which the limits
    // form asks a number for. A missing row reads as nothing,
    // and its form opens empty.
    //
    // Used by:
    //   - admin-panel/sidebar.blade.php — Other Settings,
    //     under Settings
    //   - self_eval_save, about_us_save,
    //     knowledge_test_settings_save (below) — their
    //     redirects, by the route name other_settings
    // -----------------------------------------------------------

    public function other()
    {
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






    // -----------------------------------------------------------
    // self_eval_save
    // -----------------------------------------------------------
    //
    // POST /admin-panel/settings/self_eval_save
    //
    // Stores how many questions a self-evaluation draws in
    // val3 of settings row 2, the row mysql/init.sql seeds.
    // Answers a redirect to the page's Self Evaluation tab.
    //
    // Used by:
    //   - admin-panel/settings_other.blade.php — the form of
    //     the Self Evaluation tab
    // -----------------------------------------------------------

    public function self_eval_save(Request $request)
    {
        Settings::where(['id'=>2])->update(['val3'=>$request->get('self_eval')]);

        return Redirect::to(route('other_settings') . "#a2")->withSuccess("Updated!");
    }






    // -----------------------------------------------------------
    // about_us_save
    // -----------------------------------------------------------
    //
    // POST /admin-panel/settings/about_us_save
    //
    // Stores the home page's about text and link of every
    // language, as JSON keyed by language id, in val1 of
    // settings row 1 — an UPDATE, so the row must exist, as
    // mysql/init.sql makes sure. Answers a redirect to the
    // page's Main page tab.
    //
    // Used by:
    //   - admin-panel/settings_other.blade.php — the form of
    //     the Main page tab
    // -----------------------------------------------------------

    public function about_us_save(Request $request)
    {
        // The 1 is json_encode's JSON_HEX_TAG flag, not the
        // assoc switch of json_decode: < and > are stored
        // escaped, and decode back unchanged
        Settings::where(['id'=>1])->update(['val1'=>json_encode($request->get('about'), 1)]);

        return Redirect::to(route('other_settings') . "#a1")->withSuccess("Updated!");
    }






    // -----------------------------------------------------------
    // knowledge_test_settings_save
    // -----------------------------------------------------------
    //
    // POST /admin-panel/settings/knowledge_test_settings_save
    //
    // Stores how many questions the knowledge test draws from
    // each category of each language, as JSON keyed by
    // language id and category id, in val1 of settings row 3
    // — an UPDATE, so the row must exist, as mysql/init.sql
    // makes sure. Answers a redirect to the page's Knowledge
    // Test tab.
    //
    // Used by:
    //   - admin-panel/settings_other.blade.php — the form of
    //     the Knowledge Test tab
    // -----------------------------------------------------------

    public function knowledge_test_settings_save(Request $request)
    {
        // The 1 is JSON_HEX_TAG, as in about_us_save (above)
        $knowledge = json_encode($request->get('knowledge'), 1);

        Settings::where(['id'=>3])->update(['val1'=>$knowledge]);

        return Redirect::to(route('other_settings') . "#a3")->withSuccess("Updated!");
    }






    // -----------------------------------------------------------
    // SimulationsDownloadPdf
    // -----------------------------------------------------------
    //
    // GET /admin-panel/settings/simulations_download_pdf
    //
    // Every simulation of every language on one page,
    // admin-panel/pdf/simulations.blade.php: per language its
    // scenarios, each with all of its options and its own
    // categories and attributes. Despite the
    // name the answer is HTML, not a PDF — the PDF facade
    // imported above is never used — and the button opens it
    // in a new tab.
    //
    // Used by:
    //   - admin-panel/settings_other.blade.php — the Download
    //     (PDF) button of the Download Simulations tab
    // -----------------------------------------------------------

    public function SimulationsDownloadPdf(Request $request)
    {
        $langlist = Langs::all()->pluck('name', 'id')->toArray();

        $simulations = [];

        foreach($langlist as $k => $v){

            $simulation_list = Scenarios::where(['lang_id'=>$k])->get()->toArray();

            foreach($simulation_list as $kk => $vv){

                $simulation_list[$kk]['option'] = ScenariosOptions::where(['scenario_id'=>$vv['id']])->get()->toArray();
                $simulation_list[$kk]['attributes'] = ScenariosInAttributes::select("bei_scenarios_attributes.name")->leftJoin('bei_scenarios_attributes', 'bei_scenarios_attributes.id', 'bei_scenarios_in_attributes.attribute_id')->where(['bei_scenarios_in_attributes.scenario_id'=>$vv['id']])->get()->toArray();
                $simulation_list[$kk]['categories'] = ScenariosInCategory::select("bei_scenarios_category.name")->leftJoin('bei_scenarios_category', 'bei_scenarios_category.id', 'bei_scenarios_in_category.cat_id')->where(['bei_scenarios_in_category.scenario_id'=>$vv['id']])->get()->toArray();

            }

            $simulations[$k]['lang'] = $v;
            $simulations[$k]['lang_id'] = $k;

            $simulations[$k]['simulations'] = $simulation_list;
        }

        return view('admin-panel.pdf.simulations', ['simulations'=>$simulations]);
    }
}
