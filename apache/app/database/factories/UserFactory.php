<?php

// -----------------------------------------------------------
//  [*] UserFactory — Laravel's stock factory for App\User
//
//  How a factory call would build a fake App\User: a name,
//  a unique email, a verified address, the hash of
//  Laravel's stock password and a remember token. Laravel's
//  factory loader requires the file with the factory
//  registry in $factory. Dead code: App\User does not exist
//  (members are bei_users rows, managers bei_managers
//  rows), Faker is a development package the image does
//  not install, and nothing asks for a factory — the tests
//  build their rows with Tests\Support\World.
//
//  Used by:
//    - nothing calls it at the moment (Laravel's factory
//      loader would read it on the first factory call)
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
