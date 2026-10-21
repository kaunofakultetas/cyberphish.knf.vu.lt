<?php

// -----------------------------------------------------------
//  [*] TrustProxies — believes the forwarded headers
//
//  Laravel's proxy trust, opened wide: every proxy is
//  trusted and all four X-Forwarded headers are believed.
//  Apache is reached only through the endpoint, on the
//  stack's internal network (docker-compose.yml), and the
//  endpoint sets X-Forwarded-Proto to https — which is what
//  makes the URLs and redirects Laravel builds come out as
//  https.
//
//  Used by:
//    - Kernel::$middleware (app/Http/Kernel.php) — every
//      request
// -----------------------------------------------------------


namespace App\Http\Middleware;

use Fideloper\Proxy\TrustProxies as Middleware;
use Illuminate\Http\Request;








// -----------------------------------------------------------
// TrustProxies
// -----------------------------------------------------------
//
// Takes the scheme, host, port and client address the
// forwarded headers name from any peer, and refuses
// nothing. No code of the app reads Laravel's client
// address: SettingsController::getUserIP reads the server
// variables itself.
//
// Used by:
//   - Kernel::$middleware — the global stack, every request
// -----------------------------------------------------------

class TrustProxies extends Middleware
{

    // The peers trusted as proxies: all of them
    protected $proxies = '*';

    // The forwarded headers believed: For, Host, Port and
    // Proto
    protected $headers = Request::HEADER_X_FORWARDED_ALL;
}
