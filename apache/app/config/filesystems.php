<?php

// -----------------------------------------------------------
//  [*] config/filesystems.php — the storage disks
//
//  The disks the Storage facade writes to. The app does not
//  use them: the admin controllers move news pictures,
//  lesson files and simulation imports straight into
//  storage/app/public and public/upload, and the pages link
//  the files under /upload. Laravel 7's stock file, which
//  Laravel 13 reads unchanged, plus an azure disk in the
//  shape matthewbdaly/laravel-azure-storage read. The
//  upgrade dropped that package, and the app's own
//  AzureStorageServiceProvider is never loaded, so no
//  driver serves the disk — and nothing stores on it. Of
//  the variables here only APP_URL is set in
//  docker-compose.yml; none of the FILESYSTEM_, AWS_ or
//  AZURE_STORAGE_ ones is.
//
//  Used by:
//    - Laravel's FilesystemManager, which nothing in the app
//      calls at the moment
// -----------------------------------------------------------


return [

    // The disk the Storage facade uses when none is named,
    // from FILESYSTEM_DRIVER — not set, so local
    'default' => env('FILESYSTEM_DRIVER', 'local'),

    // The disk bound as the cloud disk, from FILESYSTEM_CLOUD
    // — not set, so s3, which has no credentials here
    'cloud' => env('FILESYSTEM_CLOUD', 's3'),

    // One entry per disk; Laravel supports the local, ftp,
    // sftp and s3 drivers. local is storage/app; public is
    // storage/app/public, linked under APP_URL/storage — a
    // URL no page uses
    'disks' => [

        'local' => [
            'driver' => 'local',
            'root' => storage_path('app'),
        ],

        'public' => [
            'driver' => 'local',
            'root' => storage_path('app/public'),
            'url' => env('APP_URL').'/storage',
            'visibility' => 'public',
        ],

        's3' => [
            'driver' => 's3',
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
            'region' => env('AWS_DEFAULT_REGION'),
            'bucket' => env('AWS_BUCKET'),
            'url' => env('AWS_URL'),
            'endpoint' => env('AWS_ENDPOINT'),
        ],
        // An Azure blob container for the driver of
        // matthewbdaly/laravel-azure-storage, which the upgrade
        // dropped: no driver serves it, and none of the
        // AZURE_STORAGE_ variables is set
        'azure' => [
            'driver'    => 'azure',
            'name'      => env('AZURE_STORAGE_NAME'),
            'key'       => env('AZURE_STORAGE_KEY'),
            'container' => env('AZURE_STORAGE_CONTAINER'),
            'url'       => env('AZURE_STORAGE_URL'),
            'prefix'    => null,
        ],

    ],

    // The links artisan storage:link would make; nothing
    // runs it — the Dockerfile links public/upload to
    // storage/app/public itself
    'links' => [
        public_path('storage') => storage_path('app/public'),
    ],

];
