<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ $currentDirection ?? (app()->getLocale() === 'ar' ? 'rtl' : 'ltr') }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Application font -->
        <link rel="preload" href="{{ asset('fonts/DINNextLTArabic-Regular-2.otf') }}" as="font" type="font/otf" crossorigin>

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-slate-900 antialiased">
        <div class="flex min-h-screen flex-col items-center justify-center px-4 py-8">
            <div class="mb-4 text-center">
                <a href="/" class="inline-flex items-center gap-3 rounded-2xl px-3 py-2 transition hover:bg-white/70">
                    <x-application-logo class="h-16 w-16" />
                    <span class="brand-wordmark text-xl">{{ __('Minhaj') }}</span>
                </a>
            </div>

            <div class="mb-6">
                <x-language-switcher />
            </div>

            <div class="auth-card w-full max-w-md px-6 py-6 sm:px-8">
                {{ $slot }}
            </div>
        </div>
    </body>
</html>
