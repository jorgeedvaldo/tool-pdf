<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Session;

class SetLocale
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
        $locale = $request->segment(1);

        if (in_array($locale, ['en', 'pt', 'es', 'fr', 'zh', 'hi', 'ru'])) {
            App::setLocale($locale);
            // Ensure all future route() calls include the current locale automatically
            \Illuminate\Support\Facades\URL::defaults(['locale' => $locale]);
            Session::put('locale', $locale);
        } else {
            // Fallback
            $fallback = Session::get('locale', config('app.fallback_locale'));
            App::setLocale($fallback);
            \Illuminate\Support\Facades\URL::defaults(['locale' => $fallback]);
        }

        return $next($request);
    }
}
