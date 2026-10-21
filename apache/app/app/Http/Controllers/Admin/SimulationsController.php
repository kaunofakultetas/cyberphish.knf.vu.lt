<?php

// -----------------------------------------------------------
//  [*] SimulationsController — the panel's simulation pages
//
//  The admin side of the phishing simulations: the
//  simulations of each language, their categories and
//  attributes, the import that turns the project's
//  simulation spreadsheet into a simulation, the edit form
//  for its texts, and deleting. Every route carries
//  auth.admin:man and perms, so only main admins reach it;
//  a country manager gets a 404. The list pages show a tab
//  per language, and most forms answer back to the page
//  they came from, on the language's tab. The members'
//  side is the other SimulationsController, in
//  App\Http\Controllers.
//
//  Used by:
//    - routes/web_admin.php — the admin_simulations,
//      dialog/simulations and dialog/simulation routes
// -----------------------------------------------------------


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








// -----------------------------------------------------------
// SimulationsController
// -----------------------------------------------------------
//
// A simulation is a bei_scenarios row with its steps in
// bei_scenarios_options — a tree, each step hanging under
// the step that leads to it, parent 0 at level 1 — filed
// under categories and tagged with attributes through
// bei_scenarios_in_category and bei_scenarios_in_attributes.
// Categories and attributes belong to one language each.
//
//   index, categories, attributes
//                     — the three list pages
//   categories_form, categories_create, categories_update,
//   categories_delete
//                     — the simulation categories
//   attributes_form, attributes_create, attributes_update,
//   attributes_delete
//                     — the simulation attributes
//   dialog_import_simulation, simulations_import
//                     — the spreadsheet import, with its
//                       helpers get_xlsx_categories,
//                       get_xlsx_attrs,
//                       xlsx_process_questions and
//                       get_answer_type_id
//   simulation_edit, simulation_update,
//   simulation_categories, simulation_categories_update,
//   simulation_delete
//                     — one simulation
//
// Used by:
//   - routes/web_admin.php
// -----------------------------------------------------------

class SimulationsController extends BaseController
{






    // -----------------------------------------------------------
    // __construct
    // -----------------------------------------------------------
    //
    // Only runs the admin BaseController's constructor: the
    // man-guard check and the session flags shared with the
    // views.
    //
    // Used by:
    //   - Laravel, when it dispatches a route of this
    //     controller
    // -----------------------------------------------------------

    public function __construct()
    {
        parent::__construct();
    }






    // -----------------------------------------------------------
    // index
    // -----------------------------------------------------------
    //
    // GET /admin-panel/admin_simulations
    //
    // Every simulation, newest first, grouped by language for
    // the page's tabs, with the language list. Main admins
    // only (auth.admin:man, perms). Answers with
    // admin-panel/simulations.blade.php.
    //
    // Used by:
    //   - admin-panel/sidebar.blade.php — the Simulations entry
    //     under Simulations in the menu
    // -----------------------------------------------------------

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






    // -----------------------------------------------------------
    // dialog_import_simulation
    // -----------------------------------------------------------
    //
    // GET /admin-panel/dialog/simulation/import/{lang_id}
    //
    // The import dialog of one language: a file field and the
    // language id, posted to simulations_import. Answers with
    // admin-panel/dialog/simulation_import.blade.php, which
    // the list page loads into its modal. Main admins only
    // (auth.admin:man, perms).
    //
    // Used by:
    //   - admin-panel/simulations.blade.php — the Import
    //     Simulation button of each language tab, through
    //     simulation_import() in public/js/window.js
    // -----------------------------------------------------------

    public function dialog_import_simulation(Request $request, $lang_id)
    {
        return view('admin-panel.dialog.simulation_import', ['lang_id'=>$lang_id]);
    }






    // -----------------------------------------------------------
    // simulations_import
    // -----------------------------------------------------------
    //
    // POST /admin-panel/admin_simulations/import
    //
    // Turns one filled-in copy of the project's simulation
    // spreadsheet into a simulation of the posted language.
    // Only a file whose name ends in .xlsx passes; it is
    // kept in public/upload/simulations under a random name.
    // From the active sheet it reads the texts in column C —
    // description C19, goal C20, actors C21, source C22,
    // choose type C26, attack type C27, main image C30 — the
    // categories marked Yes in B34:B40 (named in C), the
    // attributes marked Yes in D34:D53 (named in E) and the
    // steps in rows 58 to 141, saves it all and redirects
    // back to the page it came from, on the language's tab,
    // with "Imported!". Main admins only (auth.admin:man,
    // perms).
    //
    // Known bug (medium): nothing runs in a transaction, and
    // two mistakes of the sheet surface only after the
    // simulation row is saved (SimulationsController.php:290):
    // a category or attribute marked Yes whose name no row
    // has saves a null id into a NOT NULL column, and a first
    // step deeper than level 1 reads an undefined $option_id.
    // Either stops the import with a 500 and leaves a
    // half-imported simulation behind. Fix: check the names
    // and the levels before saving, and save inside
    // DB::transaction.
    // Pinned as simulation-import-partial.
    //
    // Used by:
    //   - admin-panel/dialog/simulation_import.blade.php — the
    //     import dialog's form
    // -----------------------------------------------------------

    public function simulations_import(Request $request)
    {
        // STEP 1: take the upload — only a name ending in
        // .xlsx passes — and keep the file in
        // public/upload/simulations under a random name
        // ===============================================
        $lang_id = $request->get('lang_id');

        $extension = strtolower($request->file('doc')->getClientOriginalExtension());
        $allowed = ['xlsx'];
        if (!in_array($extension, $allowed)) {
            return back()->withErrors("File type '.$extension' is not allowed. Only .xlsx files are accepted.");
        }

        $random_name = Str::random(40).'.'.$extension;
        // Read and never used
        $mimetype = $request->file('doc')->getmimetype();
        $request->file('doc')->move(public_path('upload/simulations/'), $random_name);


        // STEP 2: read the sheet — the texts, the categories
        // and attributes marked Yes, and the step rows
        // ==================================================
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

        // The last TRUE keys every row by its sheet row and
        // every cell by its column letter: the helpers pair a
        // mark with its name through the shared row key
        $categories = $spreadsheet->getActiveSheet()->rangeToArray('C34:C40', NULL, TRUE, TRUE, TRUE);
        $categories_selected = $spreadsheet->getActiveSheet()->rangeToArray('B34:B40', NULL, TRUE, TRUE, TRUE);

        $attributes = $spreadsheet->getActiveSheet()->rangeToArray('E34:E53', NULL, TRUE, TRUE, TRUE);
        $attributes_selected = $spreadsheet->getActiveSheet()->rangeToArray('D34:D53', NULL, TRUE, TRUE, TRUE);

        $cats = $this->get_xlsx_categories($categories_selected, $categories);
        $attrs = $this->get_xlsx_attrs($attributes_selected, $attributes);

        $questionsx = $spreadsheet->getActiveSheet()->rangeToArray('A58:H141', NULL, TRUE, TRUE, TRUE);

        $questions = $this->xlsx_process_questions($questionsx);


        // STEP 3: save the simulation, then its category and
        // attribute links
        // ==================================================
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


        // STEP 4: save the steps in sheet order, each under
        // the last step saved one level up
        // =================================================
        $parent_option_id = 0;
        $level = 1;
        $prev_parent_options = [];
        foreach($questions as $kl => $val){

            // A deeper step hangs under the step saved just
            // before it, a shallower one under the last step
            // saved one level above its own — under nothing
            // (0) at level 1; a step on the same level keeps
            // the parent of the one before
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






    // -----------------------------------------------------------
    // categories
    // -----------------------------------------------------------
    //
    // GET /admin-panel/admin_simulations/categories
    //
    // Every simulation category, newest first, grouped by
    // language for the page's tabs, with the language list.
    // Main admins only (auth.admin:man, perms). Answers with
    // admin-panel/simulations_categories.blade.php.
    //
    // Used by:
    //   - admin-panel/sidebar.blade.php — the Categories entry
    //     under Simulations in the menu
    // -----------------------------------------------------------

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






    // -----------------------------------------------------------
    // categories_form
    // -----------------------------------------------------------
    //
    // GET /admin-panel/dialog/simulations/categories/{type}/{uid}
    //
    // The category dialog. With type new, uid is the language
    // the category will belong to and the form posts to
    // categories_create; with type edit, uid is the category,
    // whose name fills the field, and the form posts to
    // categories_update. Any other type answers an empty
    // page. Main admins only (auth.admin:man, perms).
    // Answers with
    // admin-panel/dialog/simulations_category_form.blade.php.
    //
    // Used by:
    //   - admin-panel/simulations_categories.blade.php — the
    //     Create Category button of each language tab and the
    //     settings icon of each row, through
    //     simulations_categories_form() in public/js/window.js
    // -----------------------------------------------------------

    public function categories_form(Request $request, $type, $uid)
    {
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






    // -----------------------------------------------------------
    // categories_create
    // -----------------------------------------------------------
    //
    // POST /admin-panel/dialog/simulations/categories/create
    //
    // Adds a category with the posted name (1 to 255
    // characters) to the language the form's uid carries, and
    // redirects back to the page the form came from, on that
    // language's tab, with "Created!". Main admins only
    // (auth.admin:man, perms).
    //
    // Used by:
    //   - admin-panel/dialog/simulations_category_form.blade.php
    //     — the category dialog of type new
    // -----------------------------------------------------------

    public function categories_create(Request $request)
    {
        $validatedData = $request->validate([
            'name' => [ 'required', 'min:1', 'max:255' ],
        ]);

        $addRecord = new ScenariosCategory;
        $addRecord->name = $validatedData['name'];
        $addRecord->lang_id = $request->get('uid');
        $addRecord->save();

        return Redirect::to(URL::previous() . "#a".$request->get('uid'))->withSuccess("Created!");
    }






    // -----------------------------------------------------------
    // categories_update
    // -----------------------------------------------------------
    //
    // POST /admin-panel/dialog/simulations/categories/update
    //
    // Renames the category the form's uid names (the new name
    // 1 to 255 characters) and redirects back to the page the
    // form came from, on the category's language tab, with
    // "Updated!". Main admins only (auth.admin:man, perms).
    //
    // Used by:
    //   - admin-panel/dialog/simulations_category_form.blade.php
    //     — the category dialog of type edit
    // -----------------------------------------------------------

    public function categories_update(Request $request)
    {
        $validatedData = $request->validate([
            'uid' => [ 'required', 'integer' ],
            'name' => [ 'required', 'min:1', 'max:255' ],
        ]);

        ScenariosCategory::where(['id' => $validatedData['uid']])->update(['name'=>$validatedData['name']]);

        return Redirect::to(URL::previous() . "#a".ScenariosCategory::where(['id'=>$validatedData['uid']])->pluck('lang_id')->first())->withSuccess("Updated!");
    }






    // -----------------------------------------------------------
    // categories_delete
    // -----------------------------------------------------------
    //
    // GET /admin-panel/admin_simulations/categores/delete/{uid}
    //
    // Removes the category's simulation links
    // (bei_scenarios_in_category) and the category, then
    // redirects back to the page the request came from, on
    // the category's language tab, with "Removed!" — the
    // route spells "categores" that way. A simulation whose
    // only category this was is left in none, and its start
    // page on the member side is then a 500
    // (simulation-without-category). Main admins only
    // (auth.admin:man, perms).
    //
    // Known bug (medium): the category goes on a plain GET
    // (SimulationsController.php:562), so a link a signed-in
    // admin opens deletes it; pinned as admin-get-deletes.
    // Fix: a POST route, posted from a form that carries the
    // CSRF token.
    //
    // Known bug (low): the delete icon meant to call this
    // route (admin-panel/simulations_categories.blade.php:51)
    // links to /admin-panel/simulations/categores/delete/ —
    // the admin_ of the path is missing — so it lands on a
    // 404 and the panel cannot delete a category. Fix: link
    // to admin_simulations/categores/delete in the view.
    // Pinned as simulation-delete-links.
    //
    // Used by:
    //   - nothing reaches it from the panel at the moment, see
    //     the second note
    // -----------------------------------------------------------

    public function categories_delete(Request $request, $uid)
    {
        $lang_id = ScenariosCategory::where(['id'=>$uid])->pluck('lang_id')->first();

        ScenariosInCategory::where(['cat_id'=>$uid])->delete();
        ScenariosCategory::destroy($uid);

        return Redirect::to(URL::previous() . "#a".$lang_id)->withSuccess("Removed!");
    }






    // -----------------------------------------------------------
    // attributes
    // -----------------------------------------------------------
    //
    // GET /admin-panel/admin_simulations/attributes
    //
    // Every simulation attribute, newest first, grouped by
    // language for the page's tabs, with the language list.
    // Main admins only (auth.admin:man, perms). Answers with
    // admin-panel/simulations_attributes.blade.php.
    //
    // Used by:
    //   - admin-panel/sidebar.blade.php — the Attributes entry
    //     under Simulations in the menu
    // -----------------------------------------------------------

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






    // -----------------------------------------------------------
    // attributes_form
    // -----------------------------------------------------------
    //
    // GET /admin-panel/dialog/simulations/attributes/{type}/{uid}
    //
    // The attribute dialog. With type new, uid is the language
    // the attribute will belong to and the form posts to
    // attributes_create; with type edit, uid is the attribute,
    // whose name fills the field, and the form posts to
    // attributes_update. Any other type answers an empty
    // page. Main admins only (auth.admin:man, perms).
    // Answers with
    // admin-panel/dialog/simulations_attributes_form.blade.php.
    //
    // Used by:
    //   - admin-panel/simulations_attributes.blade.php — the
    //     Create Attribute button of each language tab and the
    //     settings icon of each row, through
    //     simulations_attributes_form() in public/js/window.js
    // -----------------------------------------------------------

    public function attributes_form(Request $request, $type, $uid)
    {
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






    // -----------------------------------------------------------
    // attributes_create
    // -----------------------------------------------------------
    //
    // POST /admin-panel/dialog/simulations/attributes/create
    //
    // Adds an attribute with the posted name (1 to 255
    // characters) to the language the form's uid carries, and
    // redirects back to the page the form came from, on that
    // language's tab, with "Created!". Main admins only
    // (auth.admin:man, perms).
    //
    // Used by:
    //   - admin-panel/dialog/simulations_attributes_form.blade.php
    //     — the attribute dialog of type new
    // -----------------------------------------------------------

    public function attributes_create(Request $request)
    {
        $validatedData = $request->validate([
            'name' => [ 'required', 'min:1', 'max:255' ],
        ]);

        $addRecord = new ScenariosAttributes;
        $addRecord->name = $validatedData['name'];
        $addRecord->lang_id = $request->get('uid');
        $addRecord->save();

        return Redirect::to(URL::previous() . "#a".$request->get('uid'))->withSuccess("Created!");
    }






    // -----------------------------------------------------------
    // attributes_update
    // -----------------------------------------------------------
    //
    // POST /admin-panel/dialog/simulations/attributes/update
    //
    // Renames the attribute the form's uid names (the new
    // name 1 to 255 characters) and redirects back to the
    // page the form came from, on the attribute's language
    // tab, with "Updated!". Main admins only (auth.admin:man,
    // perms).
    //
    // Used by:
    //   - admin-panel/dialog/simulations_attributes_form.blade.php
    //     — the attribute dialog of type edit
    // -----------------------------------------------------------

    public function attributes_update(Request $request)
    {
        $validatedData = $request->validate([
            'uid' => [ 'required', 'integer' ],
            'name' => [ 'required', 'min:1', 'max:255' ],
        ]);

        ScenariosAttributes::where(['id' => $validatedData['uid']])->update(['name'=>$validatedData['name']]);

        return Redirect::to(URL::previous() . "#a".ScenariosAttributes::where(['id'=>$validatedData['uid']])->pluck('lang_id')->first())->withSuccess("Updated!");
    }






    // -----------------------------------------------------------
    // attributes_delete
    // -----------------------------------------------------------
    //
    // GET /admin-panel/admin_simulations/attributes/delete/{uid}
    //
    // Removes the attribute's simulation links
    // (bei_scenarios_in_attributes) and the attribute, then
    // redirects back to the page the request came from, on
    // the attribute's language tab, with "Removed!". Main
    // admins only (auth.admin:man, perms).
    //
    // Known bug (medium): the attribute goes on a plain GET
    // (SimulationsController.php:766), so a link a signed-in
    // admin opens deletes it; pinned as admin-get-deletes.
    // Fix: a POST route, posted from a form that carries the
    // CSRF token.
    //
    // Known bug (low): the delete icon meant to call this
    // route (admin-panel/simulations_attributes.blade.php:51)
    // links to /admin-panel/simulations/attributes/delete/ —
    // the admin_ of the path is missing — so it lands on a
    // 404 and the panel cannot delete an attribute. Fix: link
    // to admin_simulations/attributes/delete in the view.
    // Pinned as simulation-delete-links.
    //
    // Used by:
    //   - nothing reaches it from the panel at the moment, see
    //     the second note
    // -----------------------------------------------------------

    public function attributes_delete(Request $request, $uid)
    {
        $lang_id = ScenariosAttributes::where(['id'=>$uid])->pluck('lang_id')->first();

        ScenariosInAttributes::where(['attribute_id'=>$uid])->delete();
        ScenariosAttributes::destroy($uid);

        return Redirect::to(URL::previous() . "#a".$lang_id)->withSuccess("Removed!");
    }






    // -----------------------------------------------------------
    // get_xlsx_categories
    // -----------------------------------------------------------
    //
    // The ids of the categories a sheet marks: every cell of
    // column B that says exactly Yes adds the id of the
    // category named in column C of its row, found by
    // ScenariosCategory::IdByCategory. That lookup trims the
    // name and matches it in any language — a name two
    // languages share gives whichever row the database
    // returns first — and gives nothing for a name no
    // category has.
    //
    // Used by:
    //   - simulations_import (above)
    // -----------------------------------------------------------

    public static function get_xlsx_categories($selected, $categories)
    {
        $items = [];
        foreach($selected as $k => $v){

            if($v['B'] =="Yes"){
                $items[] = ScenariosCategory::IdByCategory($categories[$k]['C']);
            }
        }

        return $items;
    }






    // -----------------------------------------------------------
    // get_xlsx_attrs
    // -----------------------------------------------------------
    //
    // The ids of the attributes a sheet marks: every cell of
    // column D that says exactly Yes adds the id of the
    // attribute named in column E of its row, found by
    // ScenariosAttributes::IdByAttr — which, like the
    // category lookup, trims the name, matches it in any
    // language and gives nothing for a name no attribute has.
    //
    // Used by:
    //   - simulations_import (above)
    // -----------------------------------------------------------

    public static function get_xlsx_attrs($selected, $attrs)
    {
        $items = [];
        foreach($selected as $k => $v){

            if($v['D'] =="Yes"){
                $items[] = ScenariosAttributes::IdByAttr($attrs[$k]['E']);
            }
        }

        return $items;
    }






    // -----------------------------------------------------------
    // xlsx_process_questions
    // -----------------------------------------------------------
    //
    // The step rows of a sheet as step records, in sheet
    // order. A row with an empty situation (column C) is
    // skipped; the others give the level (A), the situation,
    // the answer type (D, as get_answer_type_id's code), the
    // points that type is worth (0, 50, 100 or 200), the
    // feedback (E), the link (F) and the image (H). Columns B
    // and G go unused.
    //
    // Used by:
    //   - simulations_import (above)
    // -----------------------------------------------------------

    public static function xlsx_process_questions($questions)
    {
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






    // -----------------------------------------------------------
    // get_answer_type_id
    // -----------------------------------------------------------
    //
    // The option type of an answer-type name, as
    // bei_scenarios_options.option_type keeps it: Incorrect 0,
    // Semi-incorrect 1, Semi-correct 2, Correct 3. Any other
    // name — a typo, an empty cell beside a situation — is an
    // undefined index, and the import stops with a 500 before
    // any row is written.
    //
    // Used by:
    //   - xlsx_process_questions (above)
    // -----------------------------------------------------------

    public static function get_answer_type_id($name)
    {
        $arr = ['Incorrect'=>0,'Semi-correct'=>2,'Correct'=>3,'Semi-incorrect'=>1];

        return $arr[$name];
    }






    // -----------------------------------------------------------
    // simulation_delete
    // -----------------------------------------------------------
    //
    // GET /admin-panel/admin_simulations/delete/{uid}
    //
    // Removes the simulation with everything that hangs off
    // it — its category and attribute links, its steps, the
    // members' runs and the options picked in them — then
    // redirects back to the page the request came from, on
    // the simulation's language tab, with "Removed!". Main
    // admins only (auth.admin:man, perms).
    //
    // Known bug (medium): all of it goes on a plain GET
    // (SimulationsController.php:959), so a link a signed-in
    // admin opens deletes the simulation and the members'
    // runs; pinned as admin-get-deletes. Fix: a POST route,
    // posted from a form that carries the CSRF token.
    //
    // Used by:
    //   - admin-panel/simulations.blade.php — the delete icon
    //     of each row, through Confirm_Dialog
    //     (public/js/admn.js)
    // -----------------------------------------------------------

    public function simulation_delete(Request $request, $uid)
    {
        $lang_id = Scenarios::where(['id'=>$uid])->pluck('lang_id')->first();

        ScenariosInCategory::where(['scenario_id'=>$uid])->delete();
        ScenariosInAttributes::where(['scenario_id'=>$uid])->delete();
        ScenariosOptions::where(['scenario_id'=>$uid])->delete();
        ScenariosUsers::where(['scenario_id'=>$uid])->delete();
        ScenariosUsersOptions::where(['scenario_id'=>$uid])->delete();
        Scenarios::destroy($uid);

        return Redirect::to(URL::previous() . "#a".$lang_id)->withSuccess("Removed!");
    }






    // -----------------------------------------------------------
    // simulation_edit
    // -----------------------------------------------------------
    //
    // GET /admin-panel/admin_simulations/edit/{uid}
    //
    // The edit page of one simulation: its description and
    // every step's situation and feedback, in fields keyed by
    // the step ids. Main admins only (auth.admin:man, perms).
    // Answers with admin-panel/simulations_edit.blade.php.
    //
    // Used by:
    //   - admin-panel/simulations.blade.php — the cogs icon of
    //     each row
    // -----------------------------------------------------------

    public function simulation_edit(Request $request, $uid)
    {
        $situation = Scenarios::where(['id'=>$uid])->get()->pluck('descr')->first();

        $options = ScenariosOptions::where(['scenario_id'=>$uid])->get()->toArray();

        return view('admin-panel.simulations_edit', [
            'uid'=>$uid,
            'situation'=>$situation,
            'options'=>$options
        ]);
    }






    // -----------------------------------------------------------
    // simulation_update
    // -----------------------------------------------------------
    //
    // POST /admin-panel/admin_simulations/update/{uid}
    //
    // Saves the edit page: the description, and the situation
    // and feedback of every step posted, found by the step id
    // its field names carry — whichever simulation the step
    // belongs to. Main admins only (auth.admin:man, perms).
    // Answers back with "Updated!".
    //
    // Known bug (low): the description is saved only when the
    // form posted steps (SimulationsController.php:1037), so
    // for a simulation without steps the edit is dropped
    // while the page still says "Updated!". Fix: save the
    // description outside that check.
    // Pinned as simulation-update-descr.
    //
    // Used by:
    //   - admin-panel/simulations_edit.blade.php — the edit
    //     form
    // -----------------------------------------------------------

    public function simulation_update(Request $request, $uid)
    {
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






    // -----------------------------------------------------------
    // simulation_categories
    // -----------------------------------------------------------
    //
    // GET /admin-panel/dialog/simulation/cat/{lang_id}/{simulation_id}
    //
    // The categories dialog of one simulation: every category
    // of the language, the simulation's own ones ticked. Main
    // admins only (auth.admin:man, perms). Answers with
    // admin-panel/dialog/simulation_categories.blade.php.
    //
    // Used by:
    //   - admin-panel/simulations.blade.php — the Categories
    //     link of each row, through simulation_categories() in
    //     public/js/window.js
    // -----------------------------------------------------------

    public function simulation_categories(Request $request, $lang_id, $simulation_id)
    {
        $categories = ScenariosCategory::where(['lang_id'=>$lang_id])->get()->toArray();

        $in_cat = ScenariosInCategory::where(['lang_id'=>$lang_id, 'scenario_id'=>$simulation_id])->get()->pluck('cat_id')->toArray();

        return view('admin-panel.dialog.simulation_categories', ['categories'=>$categories, 'scenario_id'=>$simulation_id, 'lang_id'=>$lang_id, 'in_cat'=>$in_cat]);
    }






    // -----------------------------------------------------------
    // simulation_categories_update
    // -----------------------------------------------------------
    //
    // POST /admin-panel/dialog/simulation/cat/{lang_id}/{simulation_id}
    //
    // Replaces the simulation's categories in that language
    // with the ticked ones and redirects back to the page the
    // form came from, on the language's tab, with "Updated!".
    // With nothing ticked nothing changes: the simulation
    // keeps the categories it had. Main admins only
    // (auth.admin:man, perms).
    //
    // Used by:
    //   - admin-panel/dialog/simulation_categories.blade.php —
    //     the categories dialog's form
    // -----------------------------------------------------------

    public function simulation_categories_update(Request $request, $lang_id, $simulation_id)
    {
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
