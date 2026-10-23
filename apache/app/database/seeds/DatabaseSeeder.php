<?php

// -----------------------------------------------------------
//  [*] DatabaseSeeder — the root seeder, seeding nothing
//
//  The seeder artisan db:seed starts from, as Laravel's
//  stub left it: an empty run. Nothing runs this at the
//  moment — the seed rows (English, the main admin,
//  settings rows 1 to 3) come from mysql/init.sql, and
//  neither apache/Dockerfile nor the test runner calls
//  db:seed. Composer's classmap of database/seeds
//  autoloads the class.
//
//  Used by:
//    - artisan db:seed, which nothing runs at the moment
// -----------------------------------------------------------


use Illuminate\Database\Seeder;








// -----------------------------------------------------------
// DatabaseSeeder
// -----------------------------------------------------------
//
// The seeder db:seed runs when it is given no class of its
// own.
//
//   run — seeds nothing
//
// Used by:
//   - artisan db:seed, which nothing runs at the moment
// -----------------------------------------------------------

class DatabaseSeeder extends Seeder
{






    // -----------------------------------------------------------
    // run
    // -----------------------------------------------------------
    //
    // Seeds nothing; the body is Laravel's empty stub.
    //
    // Used by:
    //   - artisan db:seed, which nothing runs at the moment
    // -----------------------------------------------------------

    public function run()
    {
    }
}
