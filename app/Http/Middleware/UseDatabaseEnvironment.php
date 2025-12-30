<?php

namespace App\Http\Middleware;

use App\Services\DatabaseEnvironmentService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class UseDatabaseEnvironment
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! app()->runningInConsole()) {
            app(DatabaseEnvironmentService::class)->apply();
        }

        return $next($request);
    }
}
