<?php

// -----------------------------------------------------------
//  [*] CreateFailedJobsTable — Laravel's stock migration
//
//  The failed_jobs table, where a queue worker records the
//  jobs that failed — the one the failed section of
//  config/queue.php names. Nothing runs this at the
//  moment: the schema comes from mysql/init.sql, which the
//  MySQL container loads on its first start, and neither
//  apache/Dockerfile nor the test runner calls artisan
//  migrate. The app queues nothing and runs no worker; no
//  failed_jobs table exists.
//
//  Used by:
//    - artisan migrate, which nothing runs at the moment
// -----------------------------------------------------------


use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;








// -----------------------------------------------------------
// CreateFailedJobsTable
// -----------------------------------------------------------
//
// One migration. artisan migrate finds it by the file
// name: the date orders it among the others, the rest
// names this class.
//
//   up   — creates the table
//   down — drops it
//
// Used by:
//   - artisan migrate, which nothing runs at the moment
// -----------------------------------------------------------

class CreateFailedJobsTable extends Migration
{






    // -----------------------------------------------------------
    // up
    // -----------------------------------------------------------
    //
    // Creates failed_jobs: an auto-increment id, the
    // connection and queue the job ran on, its payload, the
    // exception it died of, and failed_at, which defaults to
    // the moment of the insert.
    //
    // Used by:
    //   - artisan migrate, which nothing runs at the moment
    // -----------------------------------------------------------

    public function up()
    {
        Schema::create('failed_jobs', function (Blueprint $table) {
            $table->id();
            $table->text('connection');
            $table->text('queue');
            $table->longText('payload');
            $table->longText('exception');
            $table->timestamp('failed_at')->useCurrent();
        });
    }






    // -----------------------------------------------------------
    // down
    // -----------------------------------------------------------
    //
    // Drops failed_jobs, when it exists.
    //
    // Used by:
    //   - artisan migrate:rollback, migrate:reset and
    //     migrate:refresh, which nothing runs at the moment
    // -----------------------------------------------------------

    public function down()
    {
        Schema::dropIfExists('failed_jobs');
    }
}
