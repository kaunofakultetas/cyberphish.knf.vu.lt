<?php

namespace App\Http\Controllers\Auth;

use Session;
use Mail;
use App\Http\Controllers\BaseController;
use App\Mail\SendRemindLink;
use App\Mail\SendNewPass;
use App\Http\Controllers\Controller;
use App\Http\Controllers\SettingsController;
use App\Providers\RouteServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\LoginLog;
use App\Models\Members;
use Illuminate\Support\Facades\Validator;
use Route;
use Carbon\Carbon;

class LoginController extends BaseController
{

    use AuthenticatesUsers;

    /**
     * Where to redirect users after login.
     *
     * @var string
     */
    protected $redirectTo = "/cp/dashboard";

    public function __construct()
    {
        parent::__construct();
        $this->middleware('guest')->except('logout');
        $this->middleware('guest:mem')->except('logout');
    }

    public function showLoginForm()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {

        $this->validate($request, [
            'email'   => 'required|email',
            'password' => 'required|min:12|max:50'
        ]);

        if (Auth::guard('mem')->attempt(['email' => $request->email, 'password' => $request->password, 'status'=>1])) {

            Members::where(['id' => Auth::guard('mem')->id()])->update(['last_login' => Carbon::now(), 'last_ip' => SettingsController::getUserIP(), 'remember_token'=>'']);

            LoginLog::save_login(Auth::guard('mem')->id());
            LoginLog::if_login_10(Auth::guard('mem')->id());

         //   MemberLogs::add_login_log(Auth::guard('mem')->id());

            return redirect()->intended(route('dashboard'));

        }

        return redirect()->intended(route('login'))->withErrors([__('main.auth_failed')]);

    }

    public function logout()
    {
        Auth::guard('mem')->logout();
        return redirect('/');
    }

    public function ForgotPasswordForm(){

        return view('auth.forgot-password');

    }

    public function forgotpassword(Request $request){

        $this->validate($request, [
            'email'   => 'required|email',
            'g-recaptcha-response' => 'required|captcha',
        ]);

        if (Members::where(array('email' => $request->get('email')))->count() == 1) {

            $uid = SettingsController::random_string(50);

            Mail::to($request->get('email'))->send(new SendRemindLink(['uid'=>$uid]));

            Members::where(['email'=>$request->get('email')])->update(['remember_token'=>$uid]);

        }

        return back()->withSuccess(__('main.forgot_pass_msg'));

    }

    public function reset_pass($reset_token){

        $new_pass = SettingsController::random_string(12);

        $user_email = Members::select('email')->where('remember_token', '=', $reset_token)->get()->pluck('email')->first();

        Mail::to($user_email)->send(new SendNewPass(['new_pass'=>$new_pass]));

        Members::where('remember_token', '=', $reset_token)->update(['password'=>Hash::make($new_pass), 'remember_token'=>'']);

        return redirect()->intended(route('login'))->withSuccess(__('main.new_pass_emailed'));

    }

}
