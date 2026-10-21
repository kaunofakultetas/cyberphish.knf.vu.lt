<?php

// -----------------------------------------------------------
//  [*] Kernel — the Artisan side of the app
//
//  The console kernel: which commands Artisan knows and
//  what the scheduler would run. The app adds nothing to
//  Laravel's own commands but the scaffold's inspire, and
//  schedules nothing — nothing in the image runs the
//  scheduler anyway.
//
//  Used by:
//    - bootstrap/app.php — bound as the console kernel
//    - artisan — every command; composer install runs its
//      package:discover while the image is built
// -----------------------------------------------------------


namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;








// -----------------------------------------------------------
// Kernel
// -----------------------------------------------------------
//
// Laravel's console kernel with an empty command list and
// an empty schedule.
//
//   schedule — schedules nothing
//   commands — the closure commands of routes/console.php
//
// Used by:
//   - bootstrap/app.php — bound as the console kernel
//   - artisan — every command
// -----------------------------------------------------------

class Kernel extends ConsoleKernel
{

    // The command classes registered by name — none; the
    // commands method loads the rest
    protected $commands = [
    ];






    // -----------------------------------------------------------
    // schedule
    // -----------------------------------------------------------
    //
    // The app's schedule, which is empty: the scaffold's
    // example is gone, and no crontab entry runs the
    // scheduler.
    //
    // Used by:
    //   - Laravel's console kernel, when Artisan's schedule
    //     commands run — never, at the moment
    // -----------------------------------------------------------

    protected function schedule(Schedule $schedule)
    {
    }






    // -----------------------------------------------------------
    // commands
    // -----------------------------------------------------------
    //
    // Loads every command class of app/Console/Commands — a
    // folder the app does not have, so none — and the
    // closure commands of routes/console.php, which hold
    // only the scaffold's inspire.
    //
    // Used by:
    //   - Laravel's console kernel, before Artisan runs a
    //     command
    // -----------------------------------------------------------

    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
