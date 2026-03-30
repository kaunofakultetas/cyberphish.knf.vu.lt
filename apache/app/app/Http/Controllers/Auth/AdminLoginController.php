<?php

namespace App\Http\Controllers\Auth;

use Session;
use App\Http\Controllers\Controller;
use App\Http\Controllers\SettingsController;
use App\Providers\RouteServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Models\Managers as Managers;
use Route;
use Carbon\Carbon;

class AdminLoginController extends Controller
{

    use AuthenticatesUsers;

    /**
     * Where to redirect users after login.
     *
     * @var string
     */
    protected $redirectTo = '/admin-panel/dashboard';

    public function __construct()
    {
        $this->middleware('guest')->except('logout');
        $this->middleware('guest:mem')->except('logout');
        $this->middleware('guest:man')->except('logout');
    }

    public function showLoginForm()
    {
        return view('admin-panel.auth.login');
    }

    public function login(Request $request)
    {

        $this->validate($request, [
            'email'   => 'required|email',
            'password' => 'required|min:6'
        ]);

        if (Auth::guard('man')->attempt(['email' => $request->email, 'password' => $request->password, 'status'=>1])) {

            Managers::where(['id' => Auth::guard('man')->id()])->update(['last_login' => Carbon::now(), 'last_ip' => SettingsController::getUserIP()]);
            Session::put('main', Managers::select('main')->where(['id' => Auth::guard('man')->id(), 'status'=>1])->get()->pluck('main')->first());
            Session::put('country', Managers::select('country')->where(['id' => Auth::guard('man')->id(), 'status'=>1])->get()->pluck('country')->first());

            return redirect()->intended(route('admin_dashboard'));

        }

        return redirect()->intended(route('admin_login'))->withErrors([__('auth.failed')]);

    }

    public function logout()
    {
        Auth::guard('man')->logout();
        return redirect('/admin-panel');
    }


}
