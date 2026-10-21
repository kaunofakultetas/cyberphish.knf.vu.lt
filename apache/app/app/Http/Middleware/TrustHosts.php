<?php

// -----------------------------------------------------------
//  [*] TrustHosts — the host names the app would answer to
//
//  Laravel's trusted-host check, set up to accept the host
//  of APP_URL and its subdomains and to refuse a request
//  for any other Host. It is switched off: its entry in the
//  global stack of app/Http/Kernel.php is left out, so
//  nothing runs it at the moment.
//
//  Used by:
//    - nothing at the moment — switched off in
//      Kernel::$middleware (app/Http/Kernel.php)
// -----------------------------------------------------------


namespace App\Http\Middleware;

use Illuminate\Http\Middleware\TrustHosts as Middleware;








// -----------------------------------------------------------
// TrustHosts
// -----------------------------------------------------------
//
// Names the hosts Laravel would trust when the check runs:
// APP_URL's host and every subdomain of it.
//
// Used by:
//   - nothing at the moment — switched off in
//     Kernel::$middleware
// -----------------------------------------------------------

class TrustHosts extends Middleware
{






    // -----------------------------------------------------------
    // hosts
    // -----------------------------------------------------------
    //
    // One pattern, which Laravel builds from the app.url
    // setting: the host itself or any subdomain of it.
    //
    // Used by:
    //   - Laravel's TrustHosts middleware, which nothing runs
    //     at the moment
    // -----------------------------------------------------------

    public function hosts()
    {
        return [
            $this->allSubdomainsOfApplicationUrl(),
        ];
    }
}
