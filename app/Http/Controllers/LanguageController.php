<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class LanguageController extends Controller
{
    private const SUPPORTED_LOCALES = ['en', 'ar'];
    private const LOCALE_COOKIE_MINUTES = 60 * 24 * 365;

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'locale' => ['required', 'string', Rule::in(self::SUPPORTED_LOCALES)],
            'redirect_to' => ['nullable', 'string', 'max:2048'],
        ]);

        $this->storeLocale($request, $validated['locale']);

        $redirectTo = $validated['redirect_to'] ?? null;

        if ($this->isLocalRedirect($redirectTo)) {
            return $this->withLocaleCookie(redirect()->to($redirectTo), $validated['locale']);
        }

        return $this->withLocaleCookie(redirect()->back(), $validated['locale']);
    }

    public function switch(Request $request, string $locale): RedirectResponse
    {
        abort_unless(in_array($locale, self::SUPPORTED_LOCALES, true), 404);

        $this->storeLocale($request, $locale);

        $redirectTo = $request->query('redirect_to');
        $response = $this->isLocalRedirect($redirectTo)
            ? redirect()->to($redirectTo)
            : redirect()->back();

        return $this->withLocaleCookie($response, $locale);
    }

    private function storeLocale(Request $request, string $locale): void
    {
        $request->session()->put('locale', $locale);
    }

    private function withLocaleCookie(RedirectResponse $response, string $locale): RedirectResponse
    {
        return $response->withCookie(cookie(
            'locale',
            $locale,
            self::LOCALE_COOKIE_MINUTES,
            null,
            null,
            false,
            false,
            false,
            'lax'
        ));
    }

    private function isLocalRedirect(?string $url): bool
    {
        if (! is_string($url) || $url === '') {
            return false;
        }

        if (str_starts_with($url, '/')) {
            return ! str_starts_with($url, '//') && ! str_starts_with($url, '/\\');
        }

        $appHost = parse_url(url('/'), PHP_URL_HOST);
        $targetHost = parse_url($url, PHP_URL_HOST);

        return $appHost !== null && $targetHost === $appHost;
    }
}
