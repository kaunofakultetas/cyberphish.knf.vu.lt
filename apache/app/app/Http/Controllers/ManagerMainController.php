<?php

namespace App\Http\Controllers;


use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use App\Models\LoginLog;
use App\Models\Members;

class ManagerMainController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
    }

    public function dashboard()
    {

        return view('dashboard', [
            'username'=>Members::select('username')->where(['id'=>Auth::guard('mem')->id()])->get()->pluck('username')->first()
        ]);
    }

    public function change_pass(Request $request){

        if (!(Hash::check($request->get('password'), Auth::guard('mem')->user()->password))) {

            return redirect()->back()->withErrors(__('main.curr_pass_wrong'));
        }

        if(strcmp($request->get('password'), $request->get('newpass')) == 0){

            return redirect()->back()->withErrors(__('main.curr_new_same'));
        }

        if(strcmp($request->get('newpass'), $request->get('renewpass')) <> 0){

            return redirect()->back()->withErrors(__('main.new_pass_not_match'));
        }

        $validator = Validator::make($request->all(), [
            'newpass' => 'required|min:6',
            'renewpass' => 'required|min:6'
        ]);

        if($validator->fails()){

            return redirect()->back()->withErrors(__('main.pass_too_short', ['length'=>6]));

        }


        Members::where('id', Auth::guard('mem')->id())->update(['password' => Hash::make($request->get('newpass'))]);

        return redirect()->back()->withSuccess(__('main.pass_changed'));


    }

    public function change_username(Request $request){

        $validator = Validator::make($request->all(), [
            'username' => 'required|min:6|max:100'
        ]);

        if($validator->fails()){

            return redirect()->back()->withErrors(__('Error!'));

        }


        $usr = Members::where('username', '=', $request->get('username'))->where('id', '<>', Auth::guard('mem')->id())->count();

        if($usr == 0){

            Members::where(['id'=>Auth::guard('mem')->id()])->update(['username'=>$request->get('username')]);

            return back()->withSuccess(__('main.pass_updated'));

        } else {
            return back()->withErrors(__('main.pass_unavailable'));
        }


    }


    public function change_fullname(Request $request){

        $validator = Validator::make($request->all(), [
            'fullname' => 'required|min:6|max:20'
        ]);

        if($validator->fails()){

            return redirect()->back()->withErrors(__('Error!'));

        }

        Members::where(['id'=>Auth::guard('mem')->id()])->update(['fullname'=>$request->get('fullname')]);

        return back()->withSuccess(__('main.updated'));



    }


}
