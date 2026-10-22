<?php

// -----------------------------------------------------------
//  [*] config/cors.php — CORS headers for the api paths
//
//  The settings of HandleCors — Laravel's own since the
//  fruitcake/laravel-cors package went into the framework —
//  which sits in the global stack of app/Http/Kernel.php
//  but acts only on the paths listed here: api/*, where
//  routes/api.php registers no route. The CORS headers the
//  site's responses actually carry come from the app's own
//  Cors middleware (app/Http/Middleware/Cors.php), further
//  down the same stack: Access-Control-Allow-Origin and
//  Access-Control-Allow-Methods set to * on every response,
//  whatever this file says. In production the endpoint
//  answers a request with a foreign Origin with a 403
//  before Apache sees it.
//
//  Changed from the stock file: the dc-api.adobe.io entries
//  beside * in allowed_origins. They change nothing — *
//  already lets every origin through, and an origin never
//  carries a path — and the calls the Adobe PDF Embed
//  viewer makes to dc-api.adobe.io are answered with
//  Adobe's CORS headers, not this site's. No environment
//  variable feeds this file.
//
//  Used by:
//    - HandleCors (Illuminate\Http\Middleware) — every
//      request, but it handles only the api/* paths
// -----------------------------------------------------------


return [

    // The paths HandleCors answers for — api/* only
    'paths' => ['api/*'],

    // The methods a cross-origin request may use — any
    'allowed_methods' => ['*'],

    // The origins allowed: * lets every one through, which
    // makes the Adobe entries after it dead weight
    'allowed_origins' => ['*', 'dc-api.adobe.io/system/log', 'https://dc-api.adobe.io/system/log', 'dc-api.adobe.io', 'https://dc-api.adobe.io', 'https://dc-api.adobe.io/*'],

    // Origins allowed by regular expression — none
    'allowed_origins_patterns' => [],

    // The request headers a cross-origin request may send —
    // any
    'allowed_headers' => ['*'],

    // Response headers exposed to the calling page beyond
    // the safe ones — none
    'exposed_headers' => [],

    // Seconds a browser may keep a preflight answer — none
    'max_age' => 0,

    // Off: a page on another origin cannot read the answer
    // to a request sent with the visitor's cookies
    'supports_credentials' => false,

];
