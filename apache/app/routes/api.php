<?php

// -----------------------------------------------------------
//  [*] routes/api.php — the API routes, none at the moment
//
//  RouteServiceProvider loads this file under the api
//  middleware group — a throttle of 60 requests a minute —
//  with the /api prefix. It registers no route: Laravel's
//  stock /api/user route is gone, and the two imports are
//  what is left of it. The CORS settings of config/cors.php
//  are written for these paths.
//
//  Used by:
//    - RouteServiceProvider::mapApiRoutes — on every request
// -----------------------------------------------------------


use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
