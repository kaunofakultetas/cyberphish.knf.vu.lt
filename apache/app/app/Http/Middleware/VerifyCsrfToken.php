<?php

// -----------------------------------------------------------
//  [*] VerifyCsrfToken — the CSRF check of the web routes
//
//  Laravel's cross-site request forgery check: a request
//  that changes something must come from a page of the
//  site, as the browser reports it, or carry the token of
//  its own session, so another site cannot post a form in
//  the name of a signed-in visitor.
//
//  Used by:
//    - Kernel::$middlewareGroups (app/Http/Kernel.php) — the
//      web group
// -----------------------------------------------------------


namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;








// -----------------------------------------------------------
// VerifyCsrfToken
// -----------------------------------------------------------
//
// Checks every POST, PUT, PATCH and DELETE. Laravel 13's
// PreventRequestForgery, which this class extends through
// Laravel's VerifyCsrfToken, lets one through when the
// browser's Sec-Fetch-Site header says same-origin, and
// otherwise wants the session's token — the form's _token
// field, or an X-CSRF-TOKEN or X-XSRF-TOKEN header; a
// request with neither is answered with 419. GET, HEAD and
// OPTIONS pass unchecked, which is why the panel's deletes
// and status changes are all POST routes: no link can set
// one off. Laravel skips the check under PHPUnit, so only
// the Apache suite, which goes through the real server,
// meets it.
//
// Used by:
//   - Kernel::$middlewareGroups — the web group, on every
//     route of routes/web_user.php and routes/web_admin.php
// -----------------------------------------------------------

class VerifyCsrfToken extends Middleware
{

    // The URIs left unchecked — none
    protected $except = [
    ];
}
