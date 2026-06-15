<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class LanguageController extends Controller
{
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'locale' => ['required', 'string', Rule::in(['en', 'ar'])],
            'redirect_to' => ['nullable', 'string', 'max:2048'],
        ]);

        $request->session()->put('locale', $validated['locale']);

        $redirectTo = $validated['redirect_to'] ?? null;

        if ($this->isLocalRedirect($redirectTo)) {
            return redirect()->to($redirectTo);
        }

        return redirect()->back();
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
