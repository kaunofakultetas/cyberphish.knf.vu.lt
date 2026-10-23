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
    // The visitor's address as the app records it:
    // REMOTE_ADDR, the address Apache resolved. mod_remoteip
    // takes it from X-Forwarded-For only when the request
    // comes from a trusted proxy, the Caddy endpoint
    // (000-default.conf). Headers a client sends itself —
    // Client-IP, CF-Connecting-IP (no Cloudflare stands in
    // front of the site), an X-Forwarded-For of its own — are
    // never read, so no client picks the address stored as
    // its member's last_ip or the one BlockIpMiddleware
    // checks. It reads $_SERVER, not Laravel's request, which
    // is why the tests set REMOTE_ADDR by hand.
    //
    // Used by:
    //   - BlockIpMiddleware::handle — every request
    //   - LoginController::login — the member's last_ip
    //   - AdminLoginController::login — the manager's last_ip
    // -----------------------------------------------------------

    public static function getUserIP()
    {
        return $_SERVER['REMOTE_ADDR'];
    }






    // -----------------------------------------------------------
    // random_string
    // -----------------------------------------------------------
    //
    // A random string of digits and ASCII letters, 16
    // characters unless told otherwise, each drawn from the
    // pool on its own, so a character may repeat. The draws
    // come from random_int, PHP's cryptographic generator:
    // no seed steers it, so tokens one has seen tell nothing
    // of the next. It makes the verification and
    // password-reset tokens, the new password the reset link
    // mails, and the public ids of self-evaluation and
    // knowledge-test attempts and of simulation runs.
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

        $string = '';
        for ($i = 0; $i < $length; $i++) {
            $string .= $pool[random_int(0, strlen($pool) - 1)];
        }

        return $string;
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
