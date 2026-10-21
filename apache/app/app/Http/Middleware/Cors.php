<?php

// -----------------------------------------------------------
//  [*] Cors — the cross-origin headers on every response
//
//  Stamps three headers on whatever the app answers: any
//  origin and any method may read it, and browsers must
//  revalidate it before reuse. Fruitcake's HandleCors, in
//  the same stack, acts only on the api/* paths of
//  config/cors.php, which hold no routes — so this is the
//  class that puts the CORS headers on the site.
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
// Lets every origin read every response — a wildcard
// Access-Control-Allow-Origin and the same for the allowed
// methods — and adds a no-cache, must-revalidate
// Cache-Control. It refuses nothing. Harmless in
// production: the endpoint (endpoint/Caddyfile) answers
// any request whose Origin names another site with 403
// before Apache sees it, and a wildcard origin never
// admits credentials.
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
    // Lets the request through first, then sets the three
    // headers on the response on its way out. It calls the
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
        return $next($request)->header('Access-Control-Allow-Origin', '*')->header('Cache-Control', 'no-cache, must-revalidate')->header('Access-Control-Allow-Methods','*');
    }
}
