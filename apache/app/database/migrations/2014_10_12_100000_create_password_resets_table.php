<?php

// -----------------------------------------------------------
//  [*] CreatePasswordResetsTable — Laravel's stock migration
//
//  The password_resets table, where Laravel's own password
//  reset keeps its tokens. Nothing runs this at the moment:
//  the schema comes from mysql/init.sql, which the MySQL
//  container loads on its first start, and neither
//  apache/Dockerfile nor the test runner calls artisan
//  migrate. The app resets passwords its own way, with the
//  token in the reset_token column of bei_users
//  (LoginController); no password_resets table exists.
//
//  Used by:
//    - artisan migrate, which nothing runs at the moment
// -----------------------------------------------------------


use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;








// -----------------------------------------------------------
// CreatePasswordResetsTable
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

class CreatePasswordResetsTable extends Migration
{






    // -----------------------------------------------------------
    // up
    // -----------------------------------------------------------
    //
    // Creates password_resets: an indexed email, the token,
    // and a nullable created_at.
    //
    // Used by:
    //   - artisan migrate, which nothing runs at the moment
    // -----------------------------------------------------------

    public function up()
    {
        Schema::create('password_resets', function (Blueprint $table) {
            $table->string('email')->index();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });
    }






    // -----------------------------------------------------------
    // down
    // -----------------------------------------------------------
    //
    // Drops password_resets, when it exists.
    //
    // Used by:
    //   - artisan migrate:rollback, migrate:reset and
    //     migrate:refresh, which nothing runs at the moment
    // -----------------------------------------------------------

    public function down()
    {
        Schema::dropIfExists('password_resets');
    }
}
