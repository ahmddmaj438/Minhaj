<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __($title) }} - {{ config('app.name', 'Minhaj') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="min-h-screen bg-slate-50 text-slate-900 antialiased">
    <main class="flex min-h-screen items-center justify-center px-4 py-10">
        <section class="w-full max-w-2xl rounded-xl border border-slate-200 bg-white p-6 text-center shadow-sm">
            <p class="text-sm font-semibold uppercase tracking-wide text-orange-600">{{ __('System message') }}</p>
            <p class="mt-3 text-sm font-semibold text-slate-500">{{ __('Status') }} {{ $status }}</p>
            <h1 class="mt-2 text-2xl font-semibold leading-tight text-slate-950">{{ __($title) }}</h1>
            <p class="mt-3 text-base text-slate-700">{{ __($message) }}</p>

            <div class="mt-6 flex flex-col justify-center gap-3 sm:flex-row">
                <a href="{{ url()->previous() !== url()->current() ? url()->previous() : url('/') }}"
                    class="inline-flex min-h-11 items-center justify-center rounded-xl border border-orange-200 px-5 py-2 text-sm font-semibold text-slate-700 hover:bg-orange-50">
                    {{ __('Go back') }}
                </a>
                @auth
                    <a href="{{ route('dashboard') }}"
                        class="inline-flex min-h-11 items-center justify-center rounded-xl bg-orange-600 px-5 py-2 text-sm font-semibold text-white hover:bg-orange-700">
                        {{ __('Open dashboard') }}
                    </a>
                @endauth
            </div>
        </section>
    </main>
</body>

</html>
