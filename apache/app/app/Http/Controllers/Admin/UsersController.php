<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use App\Http\Controllers\SettingsController;
use App\Models\Members as Members;
use App\Models\LMUsers as LMUsers;
use Illuminate\Support\Facades\Validator;

class UsersController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
    }

    public function index(Request $request)
    {
        if(count(Members::all()->toArray()) > 0){

            $arr = Members::all()->toArray();

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
            'path' => url('admin-panel/users')
        ]);

        $paginator = $paginator->appends('page', request('page'));

        if (count($arr) == 0) {

            $arr = array();
            $paginator = array();
        }


        return view('admin-panel.users', ['userlist' => $paginator]);

    }

    public function delete($user_id)
    {

        $validator = Validator::make(['user_id'=>$user_id], [
            'user_id' => 'integer|required'
        ]);

        if(Members::where(array('id' => $user_id))->count() > 0) {


            LMUsers::where(['user_id'=>$user_id])->delete();
            Members::destroy($user_id);

        }

        return redirect()->intended(route('users'))->withSuccess("User deleted!");

    }

    public function change_status($status_id, $user_id){

        if (Members::where(array('id' => $user_id))->count() > 0) {

            $update = array('status'=> $status_id);

            Members::where(['id' => $user_id])->update($update);

            return redirect()->intended(route('users'));

        }

        return redirect()->intended(route('users'))->withErrors("User do not exist!");


    }


}
