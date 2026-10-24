<?php

// -----------------------------------------------------------
//  [*] SettingsController — shared helpers, language switch
//
//  Despite the name it holds no settings: five static
//  helpers the rest of the app calls by class name — the
//  visitor's address, random strings for tokens and ids, a
//  stored date in local time, the page a list's ?page asks
//  for and whether a URL is the panel's — and the action
//  behind the language menu. It extends Controller, not
//  BaseController, so lang_switch runs without
//  BaseController's language middleware.
//
//  Used by:
//    - BlockIpMiddleware, LoginController and
//      Auth\AdminLoginController — getUserIP
//    - RegisterController, LoginController and the member
//      side's LMController, KnowledgeTestController and
//      SimulationsController (not their Admin\ namesakes) —
//      random_string
//    - CertController and the views that show a date —
//      local_time
//    - NewsController and the panel's paginated lists —
//      page_number
//    - LoginController and Auth\AdminLoginController —
//      is_panel_url
//    - routes/web_user.php — the language switch route
// -----------------------------------------------------------


namespace App\Http\Controllers;

use Session;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Carbon\Carbon;








// -----------------------------------------------------------
// SettingsController
// -----------------------------------------------------------
//
// The five helpers are static, so any class or view
// calls them by name, with no controller instance;
// getUserIP reads the $_SERVER superglobal, not
// Laravel's request.
//
//   getUserIP     — the address recorded for a visitor
//   random_string — letters and digits for tokens and ids
//   local_time    — a stored date as the pages show it
//   page_number   — the page a list's ?page asks for
//   is_panel_url  — whether a URL is a page of the panel
//   lang_switch   — the language menu's action
//
// Used by:
//   - BlockIpMiddleware and the two login controllers —
//     getUserIP
//   - the sign-up, password-reset and attempt-id code —
//     random_string
//   - CertController and the views that show a date —
//     local_time
//   - NewsController and the panel's paginated lists —
//     page_number
//   - the two login controllers — is_panel_url
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
    //   - PasswordResetTest (tests/Feature/Auth/) — seeds the
    //     Mersenne Twister twice to show the tokens ignore it
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
    // local_time
    // -----------------------------------------------------------
    //
    // A stored date as the pages show it. The app keeps every
    // date in UTC (config/app.php, timezone); this turns one
    // into the time of config('app.display_timezone'), the
    // Eastern European Time all five site countries keep, in
    // the given format — the full date and time unless told
    // otherwise. A date that is empty, NULL or zeroed comes
    // back as an empty string.
    //
    // Used by:
    //   - CertController::download — the day of the pass
    //   - news, news_content, welcome, self_eval_history,
    //     self_eval_results, simulations_history and
    //     simulations_list (resources/views/)
    //   - dashboard, users, manager_settings, news,
    //     user_self_eval_history and user_simulations_history
    //     (resources/views/admin-panel/)
    // -----------------------------------------------------------

    public static function local_time($datetime, $format = 'Y-m-d H:i:s')
    {
        if(empty($datetime) || str_starts_with((string) $datetime, '0000-00-00')){
            return '';
        }

        return Carbon::parse($datetime, 'UTC')->setTimezone(config('app.display_timezone'))->format($format);
    }






    // -----------------------------------------------------------
    // page_number
    // -----------------------------------------------------------
    //
    // The page of a list cut into pages by hand that its
    // ?page value asks for, given the list's length and the
    // rows per page. A whole number from 1 is taken, up to
    // one past the last page, which shows an empty list as it
    // always did; a larger number stops there, so the slice
    // the caller cuts stays within PHP's integers. Anything
    // else — no value, text, a fraction, 0 or less, a number
    // past PHP's integers — is page 1. A page number like
    // that once reached array_slice as a float, a TypeError
    // and a 500, or as a negative offset that counted from
    // the end of the list.
    //
    // Used by:
    //   - NewsController::index — the public news list
    //   - Admin\KnowledgeController::index,
    //     Admin\InfoController::index,
    //     Admin\LMController::additional_files,
    //     Admin\SelfevalController::index and the two
    //     histories of Admin\ManagerMainController — the
    //     panel's lists
    // -----------------------------------------------------------

    public static function page_number($page, $total, $per_page)
    {
        $last = max(1, (int) ceil($total / $per_page));

        $page = filter_var($page, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

        return $page === false ? 1 : min($page, $last + 1);
    }






    // -----------------------------------------------------------
    // is_panel_url
    // -----------------------------------------------------------
    //
    // Whether a URL is a page of the admin panel: its path is
    // /admin-panel or lies under it. Both sign-in redirects
    // park the page that asked for a sign-in under Laravel's
    // one url.intended, so each login follows a parked page
    // only into its own side — a member page parked before an
    // admin login once landed the manager on the member login,
    // which auth:mem sends a manager to.
    //
    // Used by:
    //   - LoginController::login, Auth\AdminLoginController::
    //     login
    // -----------------------------------------------------------

    public static function is_panel_url($url)
    {
        $path = (string) parse_url($url, PHP_URL_PATH);

        return $path == '/admin-panel' || strpos($path, '/admin-panel/') === 0;
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
    // home page; another pair of letters is a 400 that leaves
    // the session alone, and any other segment matches no
    // route, a 404 (RouteServiceProvider::boot). Pages under a
    // {lang} URL set the language themselves, through
    // BaseController.
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
