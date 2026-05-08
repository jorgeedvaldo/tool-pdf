<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class SetSecurityHeaders
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        // Prevent clickjacking attacks
        $response->header('X-Frame-Options', 'SAMEORIGIN');

        // Prevent MIME type sniffing
        $response->header('X-Content-Type-Options', 'nosniff');

        // Enable XSS protection in older browsers
        $response->header('X-XSS-Protection', '1; mode=block');

        // Content Security Policy - restrict resource loading
        $response->header('Content-Security-Policy', "
            default-src 'self';
            script-src 'self' 'unsafe-inline' cdn.jsdelivr.net;
            style-src 'self' 'unsafe-inline' cdn.jsdelivr.net fonts.googleapis.com;
            img-src 'self' data: https:;
            font-src 'self' data: fonts.gstatic.com;
            connect-src 'self' https:;
            frame-ancestors 'self';
            base-uri 'self';
            form-action 'self';
        ");

        // Enforce HTTPS in all communications
        if (config('app.env') === 'production') {
            $response->header('Strict-Transport-Security', 'max-age=31536000; includeSubDomains; preload');
        }

        // Control referrer information
        $response->header('Referrer-Policy', 'strict-origin-when-cross-origin');

        // Restrict browser features and APIs
        $response->header('Permissions-Policy', 'geolocation=(), microphone=(), camera=(), payment=(), usb=(), magnetometer=(), gyroscope=(), accelerometer=()');

        return $response;
    }
}
