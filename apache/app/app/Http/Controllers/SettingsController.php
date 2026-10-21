<?php

// -----------------------------------------------------------
//  [*] SettingsController — shared helpers, language switch
//
//  Despite the name it holds no settings: two static
//  helpers the rest of the app calls by class name — the
//  visitor's address and random strings for tokens and ids
//  — and the action behind the language menu. It extends
//  Controller, not BaseController, so lang_switch runs
//  without BaseController's language middleware.
//
//  Used by:
//    - BlockIpMiddleware, LoginController and
//      Auth\AdminLoginController — getUserIP
//    - RegisterController, LoginController and the member
//      side's LMController, KnowledgeTestController and
//      SimulationsController (not their Admin\ namesakes) —
//      random_string
//    - routes/web_user.php — the language switch route
// -----------------------------------------------------------


namespace App\Http\Controllers;

use Session;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;








// -----------------------------------------------------------
// SettingsController
// -----------------------------------------------------------
//
// The two helpers are static, so any class calls them by
// name, with no controller instance; getUserIP reads the
// $_SERVER superglobal, not Laravel's request.
//
//   getUserIP     — the address recorded for a visitor
//   random_string — letters and digits for tokens and ids
//   lang_switch   — the language menu's action
//
// Used by:
//   - BlockIpMiddleware and the two login controllers —
//     getUserIP
//   - the sign-up, password-reset and attempt-id code —
//     random_string
//   - routes/web_user.php — lang_switch
// -----------------------------------------------------------

class SettingsController extends Controller
{






    // -----------------------------------------------------------
    // getUserIP
    // -----------------------------------------------------------
    //
    // The visitor's address as the app records it: the
    // Client-IP header when it holds a valid address, else
    // X-Forwarded-For when it holds exactly one, else
    // REMOTE_ADDR. A CF-Connecting-IP header beats them all —
    // it is first copied over HTTP_CLIENT_IP and over
    // REMOTE_ADDR itself, so it also changes what REMOTE_ADDR
    // says for the rest of the request. It reads $_SERVER,
    // not Laravel's request, which is why the tests set
    // REMOTE_ADDR by hand.
    //
    // Known bug (low): CF-Connecting-IP and Client-IP are
    // headers any client can send, and no Cloudflare stands in
    // front of the site to overwrite them
    // (SettingsController.php:99 and :109), so a client picks
    // the address stored as its member's last_ip and the one
    // BlockIpMiddleware checks — its block list is empty
    // today; pinned as client-ip-header. Fix: record the
    // address Apache resolved, REMOTE_ADDR, and ignore these
    // headers.
    //
    // Used by:
    //   - BlockIpMiddleware::handle — every request
    //   - LoginController::login — the member's last_ip
    //   - AdminLoginController::login — the manager's last_ip
    // -----------------------------------------------------------

    public static function getUserIP()
    {
        // Cloudflare's header, when present, overwrites the
        // others — and REMOTE_ADDR for the whole request
        if (isset($_SERVER["HTTP_CF_CONNECTING_IP"])) {
            $_SERVER['REMOTE_ADDR'] = $_SERVER["HTTP_CF_CONNECTING_IP"];
            $_SERVER['HTTP_CLIENT_IP'] = $_SERVER["HTTP_CF_CONNECTING_IP"];
        }
        $client  = @$_SERVER['HTTP_CLIENT_IP'];
        $forward = @$_SERVER['HTTP_X_FORWARDED_FOR'];
        $remote  = $_SERVER['REMOTE_ADDR'];

        // A list of forwarded addresses is not a valid address,
        // so X-Forwarded-For counts only when it holds one
        if(filter_var($client, FILTER_VALIDATE_IP)) { $ip = $client; }
        elseif(filter_var($forward, FILTER_VALIDATE_IP)) { $ip = $forward; }
        else { $ip = $remote; }

        return $ip;
    }






    // -----------------------------------------------------------
    // random_string
    // -----------------------------------------------------------
    //
    // A random string of digits and ASCII letters, 16
    // characters unless told otherwise: the pool repeated as
    // many times as characters are wanted, shuffled, and cut
    // to length, so a character may repeat. It makes the
    // verification and password-reset tokens, the new
    // password the reset link mails, and the public ids of
    // self-evaluation and knowledge-test attempts and of
    // simulation runs.
    //
    // Known bug (medium): str_shuffle draws from PHP's
    // Mersenne Twister (SettingsController.php:157), which
    // is not a cryptographic generator: its output can be
    // predicted once its 32-bit seed is worked out, and tokens
    // of one's own can be enough for that — then the reset
    // tokens and new passwords the same Apache process hands
    // out next are known too. Not pinned. Fix: draw the
    // characters with random_int, or use Str::random, which
    // reads random_bytes.
    //
    // Used by:
    //   - RegisterController::register — the verify token
    //   - LoginController::forgotpassword — the reset token
    //   - LoginController::reset_pass — the new password
    //   - gen_test_id of the member side's LMController,
    //     KnowledgeTestController and SimulationsController —
    //     attempt and run ids
    // -----------------------------------------------------------

    public static function random_string($length = 16)
    {
        $pool = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';

        return substr(str_shuffle(str_repeat($pool, $length)), 0, $length);
    }






    // -----------------------------------------------------------
    // lang_switch
    // -----------------------------------------------------------
    //
    // GET /lang/{lang}
    //
    // Switches the site's language, for everyone: one of en,
    // lt, ee, lv and gr goes into the session — and into the
    // locale of this request — and the visitor lands on the
    // home page; anything else is a 400 that leaves the
    // session alone. Pages under a {lang} URL set the language
    // themselves, through BaseController.
    //
    // Used by:
    //   - layouts/main.blade.php — the five entries of the
    //     language menu
    // -----------------------------------------------------------

    public function lang_switch($lang)
    {
        if (! in_array($lang, ['en', 'lt', 'ee', 'lv', 'gr'])) {
            abort(400);
        }

        Session::put('lang', $lang);
        App::setLocale($lang);

        return redirect(route('home'));
    }
}
