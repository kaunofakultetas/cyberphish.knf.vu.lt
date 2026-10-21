<?php

// -----------------------------------------------------------
//  [*] config/app.php — the application's core settings
//
//  The app's name, environment and debug switch, the URL
//  artisan builds links from, the time zone and languages,
//  the encryption key, and the service providers and facade
//  aliases every request and artisan run boots with.
//
//  Changed from Laravel 7's stock file: the name defaults
//  to CYBERPHISH and the URL has no default; a translation
//  missing from the visitor's language falls back to
//  Lithuanian instead of English; and three packages add
//  their providers and aliases — anhskohbo/no-captcha
//  (NoCaptcha), laravelcollective/html (Form, Html) and
//  barryvdh/laravel-dompdf (PDF).
//
//  The values come from the environment the
//  cyberphish-apache service gets in docker-compose.yml —
//  the app ships no .env file: APP_NAME (CYBERPHISH),
//  APP_DEBUG ("false"), APP_URL and APP_KEY. APP_ENV is not
//  set, so the app runs as production. The app also reads
//  APP_URL itself with env(), and with it APP_URL_ADMIN,
//  APP_SALT and ADOBE_EMBED_API, which no config file
//  names: seven admin controllers, two mails, the LMContent
//  model and 57 Blade views.
//
//  Used by:
//    - Laravel at boot, on every request and artisan run —
//      the providers, aliases, time zone, languages, key
//      and debug switch
//    - the page title of the layouts — layouts/main, login
//      and register, admin-panel/layouts/main and login
//      (resources/views)
//    - ConfigTest (tests/Platform) — the debug switch and
//      the key
// -----------------------------------------------------------


return [

    // The name the layouts put in the page title, from
    // APP_NAME; the same variable names the session cookie
    // (config/session.php)
    'name' => env('APP_NAME', 'CYBERPHISH'),

    // The environment's name, from APP_ENV — not set in
    // production, so production; the in-process tests switch
    // it to testing (phpunit.xml)
    'env' => env('APP_ENV', 'production'),

    // Debug mode shows the code and stack trace of every
    // error; off unless APP_DEBUG turns it on, and
    // docker-compose.yml sets "false". ConfigTest
    // (tests/Platform) checks this line's exact text
    'debug' => (bool) env('APP_DEBUG', false),

    // The site's root URL, from APP_URL, which has no default;
    // artisan builds its links from it, a web request from
    // the request itself
    'url' => env('APP_URL'),

    // The host asset() links point at, from ASSET_URL — not
    // set, so the request's own
    'asset_url' => env('ASSET_URL', null),

    // PHP's time zone, set by Laravel at boot: every date PHP
    // makes is UTC, whatever /etc/localtime the container
    // mounts
    'timezone' => 'UTC',

    // The language a request starts in; the controllers then
    // switch to the {lang} of the URL or the language kept in
    // the session — en, lt, ee, lv or gr
    'locale' => 'en',

    // Where a translation missing from the current language
    // is taken from — Lithuanian here, where Laravel ships
    // English
    'fallback_locale' => 'lt',

    // The locale of Faker, for factories and seeders; the
    // image does not install Faker, and nothing seeds
    'faker_locale' => 'en_US',

    // The key the encrypter works with — every cookie, the
    // session's among them, is encrypted with it, so a new
    // key signs everyone out. APP_KEY, filled from
    // LARAVEL_APP_KEY by docker-compose.yml
    'key' => env('APP_KEY'),

    // The encrypter's cipher; AES-256-CBC needs a 32-byte key
    'cipher' => 'AES-256-CBC',

    // The service providers every request and artisan run
    // registers. Package discovery registers the providers
    // the installed packages declare as well, so the three
    // package providers here come twice — harmless, Laravel
    // registers a provider once
    'providers' => [

        // Laravel's own providers, and at the end the one of
        // anhskohbo/no-captcha: the captcha validation rule
        // and the reCAPTCHA widget
        Illuminate\Auth\AuthServiceProvider::class,
        Illuminate\Broadcasting\BroadcastServiceProvider::class,
        Illuminate\Bus\BusServiceProvider::class,
        Illuminate\Cache\CacheServiceProvider::class,
        Illuminate\Foundation\Providers\ConsoleSupportServiceProvider::class,
        Illuminate\Cookie\CookieServiceProvider::class,
        Illuminate\Database\DatabaseServiceProvider::class,
        Illuminate\Encryption\EncryptionServiceProvider::class,
        Illuminate\Filesystem\FilesystemServiceProvider::class,
        Illuminate\Foundation\Providers\FoundationServiceProvider::class,
        Illuminate\Hashing\HashServiceProvider::class,
        Illuminate\Mail\MailServiceProvider::class,
        Illuminate\Notifications\NotificationServiceProvider::class,
        Illuminate\Pagination\PaginationServiceProvider::class,
        Illuminate\Pipeline\PipelineServiceProvider::class,
        Illuminate\Queue\QueueServiceProvider::class,
        Illuminate\Redis\RedisServiceProvider::class,
        Illuminate\Auth\Passwords\PasswordResetServiceProvider::class,
        Illuminate\Session\SessionServiceProvider::class,
        Illuminate\Translation\TranslationServiceProvider::class,
        Illuminate\Validation\ValidationServiceProvider::class,
        Illuminate\View\ViewServiceProvider::class,
        Anhskohbo\NoCaptcha\NoCaptchaServiceProvider::class,

        // The app's own providers. With the broadcast one
        // switched off, nothing loads routes/channels.php;
        // AzureStorageServiceProvider is not listed, so it
        // never runs — matthewbdaly/laravel-azure-storage
        // registers the azure disk driver instead
        App\Providers\AppServiceProvider::class,
        App\Providers\AuthServiceProvider::class,
        // App\Providers\BroadcastServiceProvider is switched off
        App\Providers\EventServiceProvider::class,
        App\Providers\RouteServiceProvider::class,

        // laravelcollective/html (Form, Html) and
        // barryvdh/laravel-dompdf (PDF, the certificate)
        Collective\Html\HtmlServiceProvider::class,
        Barryvdh\DomPDF\ServiceProvider::class,

    ],

    // The facade aliases — short class names the controllers
    // and Blade views use without an import
    'aliases' => [

        'App' => Illuminate\Support\Facades\App::class,
        'Arr' => Illuminate\Support\Arr::class,
        'Artisan' => Illuminate\Support\Facades\Artisan::class,
        'Auth' => Illuminate\Support\Facades\Auth::class,
        'Blade' => Illuminate\Support\Facades\Blade::class,
        'Broadcast' => Illuminate\Support\Facades\Broadcast::class,
        'Bus' => Illuminate\Support\Facades\Bus::class,
        'Cache' => Illuminate\Support\Facades\Cache::class,
        'Config' => Illuminate\Support\Facades\Config::class,
        'Cookie' => Illuminate\Support\Facades\Cookie::class,
        'Crypt' => Illuminate\Support\Facades\Crypt::class,
        'DB' => Illuminate\Support\Facades\DB::class,
        'Eloquent' => Illuminate\Database\Eloquent\Model::class,
        'Event' => Illuminate\Support\Facades\Event::class,
        'File' => Illuminate\Support\Facades\File::class,
        'Gate' => Illuminate\Support\Facades\Gate::class,
        'Hash' => Illuminate\Support\Facades\Hash::class,
        'Http' => Illuminate\Support\Facades\Http::class,
        'Lang' => Illuminate\Support\Facades\Lang::class,
        'Log' => Illuminate\Support\Facades\Log::class,
        'Mail' => Illuminate\Support\Facades\Mail::class,
        'Notification' => Illuminate\Support\Facades\Notification::class,
        'Password' => Illuminate\Support\Facades\Password::class,
        'Queue' => Illuminate\Support\Facades\Queue::class,
        'Redirect' => Illuminate\Support\Facades\Redirect::class,
        'Redis' => Illuminate\Support\Facades\Redis::class,
        'Request' => Illuminate\Support\Facades\Request::class,
        'Response' => Illuminate\Support\Facades\Response::class,
        'Route' => Illuminate\Support\Facades\Route::class,
        'Schema' => Illuminate\Support\Facades\Schema::class,
        'Session' => Illuminate\Support\Facades\Session::class,
        'Storage' => Illuminate\Support\Facades\Storage::class,
        'Str' => Illuminate\Support\Str::class,
        'URL' => Illuminate\Support\Facades\URL::class,
        'Validator' => Illuminate\Support\Facades\Validator::class,
        'View' => Illuminate\Support\Facades\View::class,

        // The facades of the three packages
        'Form' => Collective\Html\FormFacade::class,
        'Html' => Collective\Html\HtmlFacade::class,
        'NoCaptcha' => Anhskohbo\NoCaptcha\Facades\NoCaptcha::class,
        'PDF' => Barryvdh\DomPDF\Facade::class,

    ],

];
