<?php

// -----------------------------------------------------------
//  [*] Kernel — the middleware every request passes
//
//  The HTTP kernel of the app: the global stack every
//  request goes through, the web and api groups the route
//  files are loaded under, and the aliases routes and
//  controllers name middleware by. Two of Laravel's stock
//  middleware are switched off here, TrustHosts and
//  AuthenticateSession.
//
//  Used by:
//    - bootstrap/app.php — bound as the HTTP kernel
//    - public/index.php — runs every request through it
// -----------------------------------------------------------


namespace App\Http;

use Illuminate\Foundation\Http\Kernel as HttpKernel;








// -----------------------------------------------------------
// Kernel
// -----------------------------------------------------------
//
// Laravel's HTTP kernel with the app's three lists. Most
// entries are Laravel's middleware or the app's thin
// subclasses of them in app/Http/Middleware; the app's own
// are Cors and BlockIpMiddleware in the global stack and,
// among the aliases, validate.reset, news.exists,
// page.exists, lm.exists, finished.se, r_exists and perms.
//
// Used by:
//   - bootstrap/app.php — bound as the HTTP kernel
//   - public/index.php — runs every request through it
// -----------------------------------------------------------

class Kernel extends HttpKernel
{

    // The global stack, run in this order on every request.
    // HandleCors — Laravel's own since the fruitcake package
    // went into the framework — acts only on the api/* paths
    // of config/cors.php, which hold no routes; Cors stamps
    // its headers on every response.
    protected $middleware = [
        // TrustHosts is switched off: any Host header is answered
        \App\Http\Middleware\TrustProxies::class,
        \Illuminate\Http\Middleware\HandleCors::class,
        \App\Http\Middleware\CheckForMaintenanceMode::class,
        \Illuminate\Foundation\Http\Middleware\ValidatePostSize::class,
        \App\Http\Middleware\Cors::class,
        \App\Http\Middleware\TrimStrings::class,
        \App\Http\Middleware\BlockIpMiddleware::class,
        \Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull::class,
    ];

    // The groups RouteServiceProvider loads the route files
    // under: web for routes/web_user.php and
    // routes/web_admin.php, api for routes/api.php, which
    // declares no routes
    protected $middlewareGroups = [
        'web' => [
            \App\Http\Middleware\EncryptCookies::class,
            \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            \Illuminate\Session\Middleware\StartSession::class,
            // AuthenticateSession is switched off: a new password logs no one out
            \Illuminate\View\Middleware\ShareErrorsFromSession::class,
            \App\Http\Middleware\VerifyCsrfToken::class,
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
        ],

        'api' => [
            'throttle:60,1',
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
        ],
    ];

    // The names routes and controllers give middleware.
    // auth, auth.admin and guest are the app's versions of
    // Laravel's, and the last seven, from validate.reset on,
    // are the app's own checks; the rest are Laravel's.
    protected $routeMiddleware = [
        'auth' => \App\Http\Middleware\Authenticate::class,
        'auth.admin' => \App\Http\Middleware\AuthenticateAdmin::class,
        'auth.basic' => \Illuminate\Auth\Middleware\AuthenticateWithBasicAuth::class,
        'bindings' => \Illuminate\Routing\Middleware\SubstituteBindings::class,
        'cache.headers' => \Illuminate\Http\Middleware\SetCacheHeaders::class,
        'can' => \Illuminate\Auth\Middleware\Authorize::class,
        'guest' => \App\Http\Middleware\RedirectIfAuthenticated::class,
        'password.confirm' => \Illuminate\Auth\Middleware\RequirePassword::class,
        'signed' => \Illuminate\Routing\Middleware\ValidateSignature::class,
        'throttle' => \Illuminate\Routing\Middleware\ThrottleRequests::class,
        'verified' => \Illuminate\Auth\Middleware\EnsureEmailIsVerified::class,
        'validate.reset' => \App\Http\Middleware\ValidateReset::class,
        'news.exists' => \App\Http\Middleware\NewsExists::class,
        'page.exists' => \App\Http\Middleware\PageExists::class,
        'lm.exists' => \App\Http\Middleware\LMExists::class,
        'finished.se' => \App\Http\Middleware\FinishedSe::class,
        'r_exists' => \App\Http\Middleware\R_Exists::class,
        'perms' => \App\Http\Middleware\Perms::class
    ];
}
