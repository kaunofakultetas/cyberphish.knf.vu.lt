<?php

// -----------------------------------------------------------
//  [*] UserFactory — Laravel's stock factory for App\User
//
//  How a Laravel 7 factory call would build a fake
//  App\User: a name, a unique email, a verified address,
//  the hash of Laravel's stock password and a remember
//  token. Laravel 7's factory loader required the file with
//  the factory registry in $factory; Laravel 8 replaced
//  such factories with factory classes, and
//  laravel/legacy-factories, which keeps the old loader, is
//  not installed. Dead code: nothing loads the file —
//  Composer's classmap of database/factories finds no class
//  in it — App\User does not exist (members are bei_users
//  rows, managers bei_managers rows), composer.json
//  requires no Faker, and the tests build their rows with
//  Tests\Support\World.
//
//  Used by:
//    - nothing loads it at the moment
// -----------------------------------------------------------


use App\User;
use Faker\Generator as Faker;
use Illuminate\Support\Str;

// The fake user's columns, for a users table the app does
// not have
$factory->define(User::class, function (Faker $faker) {
    return [
        'name' => $faker->name,
        'email' => $faker->unique()->safeEmail,
        'email_verified_at' => now(),
        'password' => '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', // bcrypt of the word "password"
        'remember_token' => Str::random(10),
    ];
});
