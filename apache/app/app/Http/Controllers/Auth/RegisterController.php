<?php

namespace App\Http\Controllers\Auth;

use Mail;
use App\Http\Controllers\BaseController;
use App\Http\Controllers\Controller;
use App\Providers\RouteServiceProvider;
use App\Http\Controllers\SettingsController;
use App\Mail\SendVerification;
use Illuminate\Http\Request;
use App\Models\Members;
use Illuminate\Foundation\Auth\RegistersUsers;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class RegisterController extends BaseController
{
    use RegistersUsers;

    protected $redirectTo = '/register';

    public function __construct()
    {
        parent::__construct();
        $this->middleware('guest');
    }

    public function register_form(){

        return view('auth.register');

    }

    public function register(Request $request)
    {

        $this->validate($request, [
            'email'   => 'required|email|min:4|max:254',
            'password'=> 'required|min:12|max:50',
            'password2'=>'required|min:12|max:50',
            'country'=>'required|integer',
            'g-recaptcha-response' => 'required|captcha',
        ]);

        if (Members::where(array('email' => $request->get('email')))->count() == 0) {

            if($request->get('password') == $request->get('password2')){

                $uid = SettingsController::random_string(50);

                Mail::to($request->get('email'))->send(new SendVerification(['uid'=>$uid]));

                Members::create([
                    'username'=> RegisterController::getUsername(),
                    'email' => $request->get('email'),
                    'password' => Hash::make($request->get('password')),
                    'verify_token'=>$uid,
                    'country'=>$request->get('country')
                ]);

                return back()->withSuccess(__('main.user_registered'));

            } else {

                return back()->withErrors(__('main.pass_dont_match'));
            }

        } else {

            return back()->withErrors(__('main.email_already_exists'));
        }
    }

    public function verify(Request $request, $verify_token){

        if($verify_token <> ''){

            if(Members::where(array('verify_token' => $verify_token))->count() == 0) {

            } else {

                Members::where(['verify_token' => $verify_token])->update(['status'=>1,'verify_token'=>'']);

                return redirect()->intended(route('login'))->withSuccess(__('main.user_verified'));
            }

        } else {

            return redirect(route('login'));

        }

        return redirect(route('login'));


    }

    public static function getUsername(){

        $username = "User".RegisterController::randomGen(99, 999999, 1)[0];

        if(Members::where(['username'=>$username])->count() == 0){
            return $username;
        } else {
            return RegisterController::getUsername();
        }


    }

    public static function randomGen($min, $max, $quantity) {
        $numbers = range($min, $max);
        shuffle($numbers);
        return array_slice($numbers, 0, $quantity);
    }

}
