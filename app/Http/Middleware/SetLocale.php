<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    /** @var list<string> */
    private const SUPPORTED_LOCALES = ['vi', 'en'];

    public function handle(Request $request, Closure $next): Response
    {
        $locale = $request->session()->get('locale', 'vi');

        if (! in_array($locale, self::SUPPORTED_LOCALES, true)) {
            $locale = 'vi';
            $request->session()->forget('locale');
        }

        App::setLocale($locale);

        return $next($request);
    }
}
