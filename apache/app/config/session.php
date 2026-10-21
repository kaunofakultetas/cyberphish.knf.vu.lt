<?php

// -----------------------------------------------------------
//  [*] config/session.php — the session and its cookie
//
//  Laravel 7's stock session file, unchanged. None of its
//  variables is set in docker-compose.yml, so production
//  runs on the defaults: file sessions under
//  storage/framework/sessions — a tmpfs in the
//  cyberphish-apache container, so a restart signs every
//  member and manager out — that expire after 120 idle
//  minutes, behind a cookie named cyberphish_session
//  (APP_NAME as a slug) which scripts cannot read and other
//  sites' forms do not carry (HttpOnly, SameSite lax). The
//  in-process tests switch the driver to array
//  (phpunit.xml).
//
//  Known bug (low): the cookie's Secure flag comes from
//  SESSION_SECURE_COOKIE (session.php:97), which the
//  cyberphish-apache service does not set, so the cookie
//  goes out without it although the site is served over
//  HTTPS only, and a browser would send it along with any
//  plain-HTTP request to the host; the endpoint's
//  Strict-Transport-Security header prevents that only in
//  browsers that have already seen it. Pinned as
//  session-cookie-secure. Fix: set SESSION_SECURE_COOKIE to
//  true in docker-compose.yml.
//
//  Used by:
//    - Laravel's SessionManager — the StartSession
//      middleware of the web group, on every page
//    - the Session facade and session() helper of the
//      controllers and views — the site language, flash
//      messages, the guards' signed-in user
//    - ConfigTest (tests/Platform) — HttpOnly and SameSite
// -----------------------------------------------------------


use Illuminate\Support\Str;

return [

    // Where sessions are kept, from SESSION_DRIVER — not set,
    // so file. Laravel supports file, cookie, database, apc,
    // memcached, redis, dynamodb and array
    'driver' => env('SESSION_DRIVER', 'file'),

    // Minutes a session may sit idle before it expires, from
    // SESSION_LIFETIME — not set, so 120
    'lifetime' => env('SESSION_LIFETIME', 120),

    // Off: the cookie outlives a browser restart, for the
    // lifetime above
    'expire_on_close' => false,

    // Off: the session files hold their data unencrypted
    'encrypt' => false,

    // The folder of the file driver — a tmpfs in production,
    // emptied by every restart
    'files' => storage_path('framework/sessions'),

    // The connection of the database and redis drivers, from
    // SESSION_CONNECTION; unused with file sessions
    'connection' => env('SESSION_CONNECTION', null),

    // The table of the database driver; unused, and
    // mysql/init.sql creates no sessions table
    'table' => 'sessions',

    // The cache store of the apc, dynamodb, memcached and
    // redis drivers, from SESSION_STORE; unused
    'store' => env('SESSION_STORE', null),

    // The odds that a request sweeps out the expired session
    // files: 2 in 100
    'lottery' => [2, 100],

    // The cookie's name: SESSION_COOKIE, or APP_NAME as a slug
    // plus _session — cyberphish_session here. A new name
    // signs everyone out
    'cookie' => env(
        'SESSION_COOKIE',
        Str::slug(env('APP_NAME', 'laravel'), '_').'_session'
    ),

    // The path the cookie is sent for — the whole site
    'path' => '/',

    // The cookie's domain, from SESSION_DOMAIN — not set, so
    // the cookie belongs to the host that set it alone
    'domain' => env('SESSION_DOMAIN', null),

    // The cookie's Secure flag, from SESSION_SECURE_COOKIE —
    // not set in production, so the cookie goes out without
    // it (the known bug in the header)
    'secure' => env('SESSION_SECURE_COOKIE'),

    // HttpOnly: scripts in the page cannot read the cookie
    'http_only' => true,

    // SameSite lax: other sites' forms and embedded requests
    // do not carry the cookie, links followed from them do.
    // Laravel supports lax, strict, none and null
    'same_site' => 'lax',

];
