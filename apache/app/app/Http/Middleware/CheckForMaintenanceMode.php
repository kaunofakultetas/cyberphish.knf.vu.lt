<?php

// -----------------------------------------------------------
//  [*] CheckForMaintenanceMode — the site closed for works
//
//  Laravel's maintenance-mode check: while Artisan's down
//  command is in effect, every request is answered with 503
//  before any route runs, save those of a browser that has
//  opened the secret path the command may set, which hands
//  it a cookie to pass. Laravel 13's down command has no
//  list of addresses to let through.
//
//  Used by:
//    - Kernel::$middleware (app/Http/Kernel.php) — every
//      request
// -----------------------------------------------------------


namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\CheckForMaintenanceMode as Middleware;








// -----------------------------------------------------------
// CheckForMaintenanceMode
// -----------------------------------------------------------
//
// Guards the whole site while it is down for maintenance,
// answering 503; no URI is kept reachable meanwhile.
//
// Used by:
//   - Kernel::$middleware — the global stack, every request
// -----------------------------------------------------------

class CheckForMaintenanceMode extends Middleware
{

    // The URIs that stay reachable while the site is down —
    // none
    protected $except = [
    ];
}
