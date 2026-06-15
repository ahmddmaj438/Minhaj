<?php

namespace App\Http\Middleware;

use Closure;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    /**
     * @var array<int, string>
     */
    private array $supportedLocales = ['en', 'ar'];

    public function handle(Request $request, Closure $next): Response
    {
        $locale = $request->session()->get('locale', config('app.locale', 'en'));

        if (! in_array($locale, $this->supportedLocales, true)) {
            $locale = 'en';
        }

        App::setLocale($locale);
        Carbon::setLocale($locale);

        View::share('currentLocale', $locale);
        View::share('currentDirection', $locale === 'ar' ? 'rtl' : 'ltr');

        return $next($request);
    }
}
