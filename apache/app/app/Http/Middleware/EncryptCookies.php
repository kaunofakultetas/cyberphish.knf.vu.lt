<?php

// -----------------------------------------------------------
//  [*] EncryptCookies — cookies sealed with the app key
//
//  Laravel's cookie encryption for the web routes: every
//  cookie the app sets is encrypted with APP_KEY on the way
//  out and decrypted on the way in, so a browser can
//  neither read nor forge one — the session cookie
//  included.
//
//  Used by:
//    - Kernel::$middlewareGroups (app/Http/Kernel.php) — the
//      web group
// -----------------------------------------------------------


namespace App\Http\Middleware;

use Illuminate\Cookie\Middleware\EncryptCookies as Middleware;








// -----------------------------------------------------------
// EncryptCookies
// -----------------------------------------------------------
//
// Encrypts every cookie, with no exceptions. A cookie that
// fails to decrypt — tampered with, or sealed under another
// key — reaches the app as if it had not been sent.
//
// Used by:
//   - Kernel::$middlewareGroups — the web group, on every
//     route of routes/web_user.php and routes/web_admin.php
// -----------------------------------------------------------

class EncryptCookies extends Middleware
{

    // The cookies left unencrypted — none
    protected $except = [
    ];
}
