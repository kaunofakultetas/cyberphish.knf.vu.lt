<?php // /app/Http/Middleware/Cors.php

namespace App\Http\Middleware;

use Closure;

class Cors {
    public function handle($request, Closure $next)
    {
        return $next($request)->header('Access-Control-Allow-Origin', '*')->header('Cache-Control', 'no-cache, must-revalidate')->header('Access-Control-Allow-Methods','*');
    }
}