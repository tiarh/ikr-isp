<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class NoCacheHtml
{
    /**
     * Set Cache-Control: no-store on HTML responses so the browser always
     * fetches fresh HTML on Inertia page visits. This prevents stale HTML
     * that references old asset hashes.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        $contentType = $response->headers->get('Content-Type', '');

        if (str_contains($contentType, 'text/html')) {
            $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
            $response->headers->set('Pragma', 'no-cache');
        }

        return $response;
    }
}
