<?php

// -----------------------------------------------------------
//  [*] routes/console.php — closure-based artisan commands
//
//  Laravel's stock console routes: the one command defined
//  here, inspire, prints a random quote. The console kernel
//  requires this file whenever it boots, so the command is
//  always registered — and nothing runs it. The app has no
//  console commands of its own.
//
//  Used by:
//    - App\Console\Kernel::commands — on every artisan run,
//      the image build's package:discover among them, and in
//      every test of the suite (CreatesApplication boots the
//      app through the console kernel)
// -----------------------------------------------------------


use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

// inspire prints one of Laravel's quotes
Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->describe('Display an inspiring quote');
