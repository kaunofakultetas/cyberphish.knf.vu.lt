<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use App\Http\Controllers\SettingsController;
use App\Models\Langs as Langs;
use Illuminate\Support\Facades\Validator;

class LanguagesController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
    }

    public function index(Request $request)
    {
        if(count(Langs::all()->toArray()) > 0){

            $arr = Langs::all()->toArray();

        } else {

            $arr = array();

        }


        return view('admin-panel.langs', ['recordlist' => $arr]);

    }

    public function form(Request $request, $uid=null){

        if($uid == null){

            return view('admin-panel.dialog.lang_form', ['url'=>env('APP_URL').'/admin-panel/dialog/lang/create']);

        } else {

            return view('admin-panel.dialog.lang_form',
                [
                    'url'=>env('APP_URL').'/admin-panel/dialog/lang/update',
                    'uid'=>$uid,
                    'name'=>Langs::where(['id'=>$uid])->pluck('name')->first(),
                    'locale'=>Langs::where(['id'=>$uid])->pluck('locale')->first()
                ]);

        }

    }

    public function create(Request $request){

        $validatedData = $request->validate([
            'name' => [ 'required', 'min:1', 'max:255' ],
        ]);

        $addLangs = new Langs;
        $addLangs->name = $validatedData['name'];
        $addLangs->locale = $request->get('locale');
        $addLangs->save();

        return back()->withSuccess("Created!");

    }

    public function update(Request $request){

        $validatedData = $request->validate([
            'uid' => [ 'required', 'integer' ],
            'name' => [ 'required', 'min:1', 'max:255' ],
        ]);

        Langs::where(['id' => $validatedData['uid']])->update(['name'=>$validatedData['name'], 'locale'=>$request->get('locale')]);

        return back()->withSuccess("Updated!");

    }

    public function delete(Request $request, $uid){

        Langs::destroy($uid);

        return back()->withSuccess("Removed!");
    }


}
