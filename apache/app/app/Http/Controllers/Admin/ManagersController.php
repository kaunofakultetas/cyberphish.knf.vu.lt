<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use App\Http\Controllers\SettingsController;
use Illuminate\Support\Facades\Hash;
use App\Models\Managers as Managers;
use Illuminate\Support\Facades\Validator;

class ManagersController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
    }

    public function managers(Request $request)
    {
        $managerlist = Managers::where(['main'=>0])->get()->toArray();
        return view('admin-panel.manager_settings', ['managerlist'=>$managerlist]);

    }

    public function manager_form(Request $request, $uid=null){

        if($uid == null){

            return view('admin-panel.dialog.managers_form', ['url'=>env('APP_URL').'/admin-panel/manager/create']);

        } else {

            return view('admin-panel.dialog.managers_form',
                [
                    'url'=>env('APP_URL').'/admin-panel/manager/update',
                    'uid'=>$uid,
                    'email'=>Managers::where(['id'=>$uid])->where('main','!=', 1)->get()->pluck('email')->first()
                ]);

        }


    }

    public function manager_status($status_id, $user_id){

        Managers::where(['id' => $user_id])->where('main', '!=', '1')->update(['status'=>$status_id]);

        return back()->withSuccess("Status changed!");

    }

    public function manager_create(Request $request){

        $validatedData = $request->validate([
            'email' => [ 'required', 'email' ],
            'pass' => [ 'required', 'min:6', 'max:50' ],
            'repass' => [ 'required', 'min:6', 'max:50' ],
        ]);

        if($validatedData['pass'] == $validatedData['repass']){

            if(Managers::where(['email'=>$validatedData['email']])->count() == 0){

                $addAdmin = new Managers;
                $addAdmin->email = $validatedData['email'];
                $addAdmin->password = Hash::make($validatedData['pass']);
                $addAdmin->main = 0;
                $addAdmin->status = 0;
                $addAdmin->country = $request->get('country');
                $addAdmin->save();


                return back()->withSuccess("Manager created! Don't forget to add permissions.");

            } else {

                return back()->withErrors("Manager with this email already exist!");

            }

        } else {

            return back()->withErrors("Passwords don't match!");

        }

    }

    public function manager_update(Request $request){

        $validatedData = $request->validate([
            'uid' => [ 'required', 'integer' ],
            'email' => [ 'required', 'email' ],
            'pass' => [ 'required', 'min:6', 'max:50' ],
            'repass' => [ 'required', 'min:6', 'max:50' ],
        ]);


        if($validatedData['pass'] == $validatedData['repass']){

            if(Managers::where(['email'=>$validatedData['email']])->where('id','!=', $validatedData['uid'])->count() == 0){

                Managers::where(['id' => $validatedData['uid']])->update([
                    'email'=>$validatedData['email'],
                    'password'=>Hash::make($validatedData['pass'])
                ]);

                return back()->withSuccess("Manager updated!");

            } else {

                return back()->withErrors("Manager with this email already exist!");

            }

        } else {

            return back()->withErrors("Passwords don't match!");

        }


        return back()->withSuccess("Updated!");

    }

    public function manager_delete(Request $request, $uid){

        Managers::where(['id'=>$uid])->where('main', '!=', '1')->delete();

        return back()->withSuccess("Manager deleted!");
    }


}