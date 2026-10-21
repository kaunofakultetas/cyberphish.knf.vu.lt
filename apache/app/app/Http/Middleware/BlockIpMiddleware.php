<?php

// -----------------------------------------------------------
//  [*] BlockIpMiddleware — the address block list
//
//  Turns visitors away by address before any route runs.
//  The list is empty, so today it blocks nobody; it still
//  runs on every request, and asks SettingsController for
//  the visitor's address each time.
//
//  Used by:
//    - Kernel::$middleware (app/Http/Kernel.php) — every
//      request
// -----------------------------------------------------------


namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Http\Controllers\SettingsController;








// -----------------------------------------------------------
// BlockIpMiddleware
// -----------------------------------------------------------
//
// Guards the whole site against the addresses in blockIps:
// such a visitor gets a 403 with a one-line message,
// whatever the URL. Nobody is listed at the moment.
//
// Used by:
//   - Kernel::$middleware — the global stack, every request
// -----------------------------------------------------------

class BlockIpMiddleware
{

    // The addresses refused with a 403, as text — none at the
    // moment
    public $blockIps = [];






    // -----------------------------------------------------------
    // handle
    // -----------------------------------------------------------
    //
    // Checks the visitor's address against blockIps. The
    // address comes from SettingsController::getUserIP, which
    // reads the server variables itself — a request built
    // in-process, as in the Feature suite, has no REMOTE_ADDR
    // unless the test sets one (TestCase::setUp does).
    //
    // Known bug (low): getUserIP believes a CF-Connecting-IP
    // or Client-IP header before the address Apache saw
    // (BlockIpMiddleware.php:78), and the endpoint passes both
    // on, so a blocked visitor gets past the list by sending
    // one — pinned as client-ip-header. Harmless while the
    // list is empty. Fix: let getUserIP return the address
    // Apache resolved.
    //
    // Used by:
    //   - Laravel's HTTP kernel, for every request
    // -----------------------------------------------------------

    public function handle(Request $request, Closure $next)
    {
        if (in_array(SettingsController::getUserIP(), $this->blockIps)) {

            abort(403, "You are restricted to access the site.");

        }
        return $next($request);
    }
}
