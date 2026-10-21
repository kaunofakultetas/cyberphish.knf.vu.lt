<?php

// -----------------------------------------------------------
//  [*] BroadcastServiceProvider — broadcasting, unused
//
//  Would register Laravel's broadcast authorization route
//  and the channels of routes/channels.php. It is switched
//  off: its line in the providers list of config/app.php
//  is commented out, so neither loads — the app broadcasts
//  nothing.
//
//  Used by:
//    - nothing at the moment — switched off in
//      config/app.php
// -----------------------------------------------------------


namespace App\Providers;

use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\ServiceProvider;








// -----------------------------------------------------------
// BroadcastServiceProvider
// -----------------------------------------------------------
//
// Boots the broadcasting route and channels, and registers
// nothing.
//
// Used by:
//   - nothing at the moment — switched off in
//     config/app.php
// -----------------------------------------------------------

class BroadcastServiceProvider extends ServiceProvider
{






    // -----------------------------------------------------------
    // boot
    // -----------------------------------------------------------
    //
    // Registers the route on which Laravel's broadcasting
    // authorizes a browser for a channel, then loads
    // routes/channels.php, whose one channel is the
    // scaffold's App.User.{id}.
    //
    // Used by:
    //   - nothing at the moment — the provider is switched off
    // -----------------------------------------------------------

    public function boot()
    {
        Broadcast::routes();

        require base_path('routes/channels.php');
    }
}
