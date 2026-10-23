<?php

// -----------------------------------------------------------
//  [*] Cors — the cache header on every response
//
//  Stamps a no-cache, must-revalidate Cache-Control on
//  whatever the app answers, so browsers revalidate a page
//  before they reuse it. The name is left from the CORS
//  headers it also set — any origin and any method — until
//  they were dropped: no response of the site invites
//  another site's scripts. Laravel's HandleCors, in the
//  same stack, acts only on the api/* paths of
//  config/cors.php, which hold no routes.
//
//  Used by:
//    - Kernel::$middleware (app/Http/Kernel.php) — every
//      request
// -----------------------------------------------------------


namespace App\Http\Middleware;

use Closure;








// -----------------------------------------------------------
// Cors
// -----------------------------------------------------------
//
// Adds the no-cache, must-revalidate Cache-Control and
// refuses nothing. Cross-origin reads get no CORS header
// from the app, and the endpoint (endpoint/Caddyfile)
// answers any request whose Origin names another site
// with 403 before Apache sees it.
//
// Used by:
//   - Kernel::$middleware — the global stack, every request
// -----------------------------------------------------------

class Cors
{






    // -----------------------------------------------------------
    // handle
    // -----------------------------------------------------------
    //
    // Lets the request through first, then sets the header
    // on the response on its way out. It calls the
    // response's header method, which every response the app
    // builds has — the certificate PDF included, which DomPDF
    // wraps in a Laravel response; a bare Symfony file or
    // stream response has no such method and would fail here.
    //
    // Used by:
    //   - Laravel's HTTP kernel, for every request
    // -----------------------------------------------------------

    public function handle($request, Closure $next)
    {
        return $next($request)->header('Cache-Control', 'no-cache, must-revalidate');
    }
}
