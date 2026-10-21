<?php

// -----------------------------------------------------------
//  [*] CreateUsersTable — Laravel's stock migration
//
//  The users table of a fresh Laravel install, for the
//  App\User model this app does not have. Nothing runs this
//  at the moment: the schema comes from mysql/init.sql,
//  which the MySQL container loads on its first start, and
//  neither apache/Dockerfile nor the test runner calls
//  artisan migrate. The app keeps its members in bei_users
//  and its managers in bei_managers; no users table exists.
//
//  Used by:
//    - artisan migrate, which nothing runs at the moment
// -----------------------------------------------------------


use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;








// -----------------------------------------------------------
// CreateUsersTable
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

class CreateUsersTable extends Migration
{






    // -----------------------------------------------------------
    // up
    // -----------------------------------------------------------
    //
    // Creates users: an auto-increment id, a name, a unique
    // email, a nullable email_verified_at, the password, a
    // remember_token, and created_at and updated_at.
    //
    // Used by:
    //   - artisan migrate, which nothing runs at the moment
    // -----------------------------------------------------------

    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->rememberToken();
            $table->timestamps();
        });
    }






    // -----------------------------------------------------------
    // down
    // -----------------------------------------------------------
    //
    // Drops users, when it exists.
    //
    // Used by:
    //   - artisan migrate:rollback, migrate:reset and
    //     migrate:refresh, which nothing runs at the moment
    // -----------------------------------------------------------

    public function down()
    {
        Schema::dropIfExists('users');
    }
}
