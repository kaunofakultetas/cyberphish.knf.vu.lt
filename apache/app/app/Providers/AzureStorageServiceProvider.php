<?php

// -----------------------------------------------------------
//  [*] AzureStorageServiceProvider — an azure disk, unused
//
//  Would teach Laravel's Storage an azure driver: a
//  Flysystem filesystem on a blob container of an Azure
//  storage account, from the name, key and container the
//  disk's settings hold. It is dead code: config/app.php
//  does not list it. The azure disk of
//  config/filesystems.php took its driver from
//  matthewbdaly/laravel-azure-storage until the move to
//  PHP 8.4 dropped that package, so no driver serves the
//  disk now — and no code uses it; the default disk is
//  local.
//
//  Used by:
//    - nothing at the moment — not in the providers list of
//      config/app.php
// -----------------------------------------------------------


namespace App\Providers;

use Storage;

use League\Flysystem\Filesystem;

use Illuminate\Support\ServiceProvider;

use League\Flysystem\Azure\AzureAdapter;

use WindowsAzure\Common\ServicesBuilder;








// -----------------------------------------------------------
// AzureStorageServiceProvider
// -----------------------------------------------------------
//
// Registers the azure driver at boot and binds nothing.
// Even registered, it could not run as written:
// composer.lock holds neither league/flysystem-azure, whose
// AzureAdapter it builds on, nor microsoft/windowsazure,
// whose ServicesBuilder it calls.
//
// Used by:
//   - nothing at the moment — not in config/app.php
// -----------------------------------------------------------

class AzureStorageServiceProvider extends ServiceProvider
{






    // -----------------------------------------------------------
    // boot
    // -----------------------------------------------------------
    //
    // Registers the azure driver with Storage: the account's
    // connection string from the disk's name and key, a blob
    // service from the ServicesBuilder of
    // microsoft/windowsazure, and a filesystem on the disk's
    // container.
    //
    // Used by:
    //   - nothing at the moment — the provider is never
    //     registered
    // -----------------------------------------------------------

    public function boot()
    {
        Storage::extend('azure', function($app, $config) {
            $endpoint = sprintf(

                'DefaultEndpointsProtocol=https;AccountName=%s;AccountKey=%s',

                $config['name'],

                $config['key']

            );

            $blobRestProxy = ServicesBuilder::getInstance()->createBlobService($endpoint);

            return new Filesystem(new AzureAdapter($blobRestProxy, $config['container']));
        });
    }






    // -----------------------------------------------------------
    // register
    // -----------------------------------------------------------
    //
    // Binds nothing into the container; the driver is
    // registered at boot.
    //
    // Used by:
    //   - nothing at the moment — the provider is never
    //     registered
    // -----------------------------------------------------------

    public function register()
    {
    }
}
