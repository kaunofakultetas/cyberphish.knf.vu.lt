<?php

// -----------------------------------------------------------
//  [*] routes/channels.php — the broadcast channels
//
//  Laravel's stock channel file: the private channel of one
//  user, which only that user may listen to. Dead code —
//  App\Providers\BroadcastServiceProvider, whose boot
//  requires this file, is switched off in config/app.php,
//  the app broadcasts nothing, and the App.User name points
//  at a model the app does not have.
//
//  Used by:
//    - nothing loads it at the moment (the switched-off
//      BroadcastServiceProvider would)
// -----------------------------------------------------------


use Illuminate\Support\Facades\Broadcast;

// A user may listen to the channel that carries their own
// id
Broadcast::channel('App.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});
