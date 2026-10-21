<?php

// -----------------------------------------------------------
//  [*] AuthServiceProvider — policies and gates, none
//
//  Laravel's place for authorization policies and gates.
//  The app defines neither: who may open what is decided
//  by the guards and by the perms middleware.
//
//  Used by:
//    - config/app.php — the providers list
// -----------------------------------------------------------


namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;








// -----------------------------------------------------------
// AuthServiceProvider
// -----------------------------------------------------------
//
// An empty policy map and no gates; boot runs Laravel's
// policy registration over the empty map.
//
// Used by:
//   - config/app.php — the providers list
// -----------------------------------------------------------

class AuthServiceProvider extends ServiceProvider
{

    // The model classes mapped to their policies — none
    protected $policies = [
    ];






    // -----------------------------------------------------------
    // boot
    // -----------------------------------------------------------
    //
    // Hands the policy map, empty, to Laravel's Gate, and
    // defines no gates of its own.
    //
    // Used by:
    //   - Laravel, once every provider is registered
    // -----------------------------------------------------------

    public function boot()
    {
        $this->registerPolicies();
    }
}
