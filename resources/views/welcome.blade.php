@php
    $locale = app()->getLocale();
    $direction = $locale === 'ar' ? 'rtl' : 'ltr';
    $isAuthenticated = auth()->check();

    $features = [
        [
            'title' => __('Exam Builder'),
            'description' => __('Create structured exams, manage question types, preview templates, and publish when every detail is ready.'),
            'icon' => 'M6 7.5h12M6 12h8M6 16.5h10M5 3.5h14a2 2 0 0 1 2 2v13a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-13a2 2 0 0 1 2-2Z',
        ],
        [
            'title' => __('Academic Operations'),
            'description' => __('Connect majors, courses, students, assignments, sessions, and grading in one guided workspace.'),
            'icon' => 'M4 6.5 12 3l8 3.5-8 3.5-8-3.5Zm3 4v4.5c0 1.7 2.2 3 5 3s5-1.3 5-3v-4.5',
        ],
        [
            'title' => __('Insightful Control'),
            'description' => __('Track exams, results, activity, access, and readiness signals with a clean interface built for administrators.'),
            'icon' => 'M5 19V9m7 10V5m7 14v-7M3.5 19h17',
        ],
    ];

    $benefits = [
        __('Reduce training time with guided workflows and clear next steps.'),
        __('Keep exam delivery consistent with reusable templates and preview checks.'),
        __('Support English and Arabic users with direction-aware navigation.'),
    ];

    $stats = [
        ['value' => '3', 'label' => __('exam templates')],
        ['value' => '7+', 'label' => __('question modes')],
        ['value' => '2', 'label' => __('interface languages')],
        ['value' => '1', 'label' => __('academic workspace')],
    ];
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', $locale) }}" dir="{{ $direction }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="description" content="{{ __('Minhaj is a modern academic examination platform for building exams, managing students, and monitoring assessment workflows.') }}">

        <title>{{ __('Minhaj') }} | {{ __('Academic Examination Platform') }}</title>

        <link rel="preload" href="{{ asset('fonts/DINNextLTArabic-Regular-2.otf') }}" as="font" type="font/otf" crossorigin>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen overflow-x-hidden bg-[#f7f9fc] text-slate-900 antialiased">
        <div class="relative isolate min-h-screen">
            <div class="pointer-events-none absolute inset-x-0 top-0 -z-10 h-[38rem] bg-[linear-gradient(180deg,rgba(255,248,232,0.94),rgba(247,249,252,0.42)_62%,rgba(247,249,252,0))]"></div>
            <div class="pointer-events-none absolute inset-0 -z-10 opacity-[0.055] [background-image:linear-gradient(#003f73_1px,transparent_1px),linear-gradient(90deg,#003f73_1px,transparent_1px)] [background-size:44px_44px]"></div>

            <header class="sticky top-0 z-40 border-b border-white/60 bg-white/80 shadow-sm backdrop-blur-2xl">
                <nav class="mx-auto flex max-w-7xl items-center justify-between gap-4 px-4 py-3 sm:px-6 lg:px-8" aria-label="{{ __('Primary') }}">
                    <a href="{{ url('/') }}" class="flex min-h-11 items-center gap-3 rounded-xl px-1 focus:outline-none focus-visible:ring-4 focus-visible:ring-orange-100">
                        <img src="{{ asset('brand/logo.png') }}" alt="{{ __('Minhaj logo') }}" class="h-11 w-auto rounded-none">
                        <div class="hidden sm:block">
                            <p class="text-base font-extrabold text-brand-navy">{{ __('Minhaj') }}</p>
                            <p class="text-xs font-semibold text-slate-500">{{ __('Academic examination system') }}</p>
                        </div>
                    </a>

                    <div class="flex items-center gap-2 sm:gap-3">
                        <x-language-switcher compact />

                        @if (Route::has('login'))
                            @auth
                                <a href="{{ url('/dashboard') }}" class="inline-flex min-h-11 items-center justify-center rounded-xl bg-brand-navy px-4 py-2 text-sm font-bold text-white shadow-sm hover:bg-brand-ink">
                                    {{ __('Dashboard') }}
                                </a>
                            @else
                                <a href="{{ route('login') }}" class="inline-flex min-h-11 items-center justify-center rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-bold text-brand-navy shadow-sm hover:border-orange-200 hover:bg-orange-50">
                                    {{ __('Log in') }}
                                </a>
                            @endauth
                        @endif
                    </div>
                </nav>
            </header>

            <main>
                <section class="relative flex min-h-[76svh] items-center overflow-hidden px-4 py-14 sm:px-6 lg:px-8">
                    <img src="{{ asset('brand/logo.png') }}" alt="" aria-hidden="true" class="pointer-events-none absolute end-[-5rem] top-10 -z-10 w-[28rem] max-w-none rounded-none opacity-[0.09] sm:end-[-4rem] lg:end-[4rem] lg:top-8 lg:w-[34rem]">

                    <div class="mx-auto grid w-full max-w-7xl gap-10 lg:grid-cols-[minmax(0,1fr)_minmax(20rem,28rem)] lg:items-center">
                        <div class="max-w-4xl" data-reveal>
                            <div class="inline-flex items-center gap-2 rounded-full border border-orange-200 bg-white/90 px-3 py-2 text-sm font-bold text-orange-700 shadow-sm backdrop-blur">
                                <span class="h-2 w-2 rounded-full bg-brand-gold"></span>
                                {{ __('Premium academic assessment platform') }}
                            </div>

                            <h1 class="mt-7 max-w-4xl text-5xl font-extrabold leading-[1.05] text-brand-ink sm:text-6xl lg:text-7xl">
                                {{ __('Minhaj') }}
                            </h1>
                            <p class="mt-5 max-w-2xl text-xl font-semibold leading-8 text-slate-800 sm:text-2xl">
                                {{ __('Build, deliver, and manage academic examinations with clarity, control, and confidence.') }}
                            </p>
                            <p class="mt-5 max-w-2xl text-base leading-8 text-slate-600">
                                {{ __('A polished examination and academic operations system for administrators, instructors, and students who need reliable workflows without unnecessary complexity.') }}
                            </p>

                            <div class="mt-8 flex flex-col gap-3 sm:flex-row">
                                @auth
                                    <a href="{{ route('dashboard') }}" class="inline-flex min-h-12 items-center justify-center rounded-xl bg-brand-navy px-6 py-3 text-sm font-extrabold text-white shadow-lg shadow-blue-950/15 hover:bg-brand-ink">
                                        {{ __('Open dashboard') }}
                                    </a>
                                @else
                                    <a href="{{ route('login') }}" class="inline-flex min-h-12 items-center justify-center rounded-xl bg-brand-navy px-6 py-3 text-sm font-extrabold text-white shadow-lg shadow-blue-950/15 hover:bg-brand-ink">
                                        {{ __('Sign in to Minhaj') }}
                                    </a>
                                @endauth

                                <a href="#features" class="inline-flex min-h-12 items-center justify-center rounded-xl border border-slate-200 bg-white/90 px-6 py-3 text-sm font-extrabold text-brand-navy shadow-sm hover:border-orange-200 hover:bg-orange-50">
                                    {{ __('Explore features') }}
                                </a>
                            </div>
                        </div>

                        <div class="hidden lg:block" data-reveal>
                            <div class="rounded-lg border border-white/70 bg-white/90 p-5 shadow-2xl shadow-blue-950/10 backdrop-blur-xl">
                                <div class="flex items-center justify-between gap-4 border-b border-slate-100 pb-4">
                                    <div>
                                        <p class="text-sm font-bold text-orange-700">{{ __('Live workspace') }}</p>
                                        <p class="mt-1 text-lg font-extrabold text-brand-ink">{{ __('Exam readiness') }}</p>
                                    </div>
                                    <span class="rounded-full bg-emerald-50 px-3 py-1.5 text-xs font-bold text-emerald-700">{{ __('Stable') }}</span>
                                </div>
                                <div class="mt-5 space-y-4">
                                    @foreach ([92, 74, 86] as $score)
                                        <div>
                                            <div class="flex items-center justify-between text-sm">
                                                <span class="font-semibold text-slate-700">{{ [__('Questions'), __('Academic setup'), __('Publishing checks')][$loop->index] }}</span>
                                                <span class="font-bold text-brand-navy">{{ $score }}%</span>
                                            </div>
                                            <div class="mt-2 h-2 rounded-full bg-slate-100">
                                                <div class="h-full rounded-full bg-brand-gold" style="width: {{ $score }}%"></div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                                <div class="mt-5 grid grid-cols-3 gap-3">
                                    @foreach ($stats as $stat)
                                        <div class="rounded-lg bg-slate-50 p-3">
                                            <p class="text-xl font-extrabold text-brand-navy">{{ $stat['value'] }}</p>
                                            <p class="mt-1 text-xs font-semibold leading-5 text-slate-500">{{ $stat['label'] }}</p>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <div class="border-y border-white/70 bg-white/70 px-4 py-8 backdrop-blur sm:px-6 lg:px-8" aria-label="{{ __('Platform highlights') }}">
                    <div class="mx-auto grid max-w-7xl gap-4 sm:grid-cols-2 lg:grid-cols-4">
                        @foreach ($stats as $stat)
                            <div class="rounded-lg border border-slate-200 bg-white/90 p-5 shadow-sm" data-reveal>
                                <p class="text-3xl font-extrabold text-brand-navy">{{ $stat['value'] }}</p>
                                <p class="mt-1 text-sm font-semibold text-slate-600">{{ $stat['label'] }}</p>
                            </div>
                        @endforeach
                    </div>
                </div>

                <section id="features" class="px-4 py-20 sm:px-6 lg:px-8">
                    <div class="mx-auto max-w-7xl">
                        <div class="max-w-3xl" data-reveal>
                            <p class="text-sm font-extrabold uppercase text-orange-700">{{ __('Built for academic teams') }}</p>
                            <h2 class="mt-3 text-3xl font-extrabold leading-tight text-brand-ink sm:text-4xl">{{ __('Everything important, organized into clear workflows.') }}</h2>
                            <p class="mt-4 text-base leading-8 text-slate-600">{{ __('Minhaj brings exam creation, student delivery, academic structure, and administrative insight into one calm, production-ready experience.') }}</p>
                        </div>

                        <div class="mt-10 grid gap-5 md:grid-cols-3">
                            @foreach ($features as $feature)
                                <article class="rounded-lg border border-slate-200 bg-white/90 p-6 shadow-sm transition hover:border-orange-200 hover:shadow-lg" data-reveal>
                                    <div class="flex h-11 w-11 items-center justify-center rounded-lg bg-orange-50 text-orange-700">
                                        <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                            <path d="{{ $feature['icon'] }}" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
                                        </svg>
                                    </div>
                                    <h3 class="mt-5 text-xl font-extrabold text-brand-ink">{{ $feature['title'] }}</h3>
                                    <p class="mt-3 text-sm leading-7 text-slate-600">{{ $feature['description'] }}</p>
                                </article>
                            @endforeach
                        </div>
                    </div>
                </section>

                <section class="px-4 pb-20 sm:px-6 lg:px-8">
                    <div class="mx-auto grid max-w-7xl gap-8 rounded-lg border border-slate-200 bg-white/90 p-6 shadow-sm backdrop-blur md:grid-cols-[0.9fr_1.1fr] md:p-8 lg:p-10" data-reveal>
                        <div>
                            <p class="text-sm font-extrabold uppercase text-orange-700">{{ __('Why teams trust it') }}</p>
                            <h2 class="mt-3 text-3xl font-extrabold leading-tight text-brand-ink">{{ __('A system that feels simple even when the work is complex.') }}</h2>
                            <p class="mt-4 text-base leading-8 text-slate-600">{{ __('Every screen is designed to reduce uncertainty: users know where they are, what matters now, and what happens next.') }}</p>
                        </div>
                        <div class="grid gap-3">
                            @foreach ($benefits as $benefit)
                                <div class="flex gap-3 rounded-lg border border-slate-100 bg-slate-50/80 p-4">
                                    <span class="mt-1 flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-brand-navy text-white">
                                        <svg class="h-3.5 w-3.5" viewBox="0 0 16 16" fill="none" aria-hidden="true">
                                            <path d="M3.5 8.2 6.6 11 12.5 4.8" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                        </svg>
                                    </span>
                                    <p class="text-sm font-semibold leading-7 text-slate-700">{{ $benefit }}</p>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </section>
            </main>

            <footer class="border-t border-slate-200 bg-white/80 px-4 py-8 sm:px-6 lg:px-8">
                <div class="mx-auto flex max-w-7xl flex-col gap-6 md:flex-row md:items-center md:justify-between">
                    <div class="flex items-center gap-3">
                        <img src="{{ asset('brand/logo.png') }}" alt="{{ __('Minhaj logo') }}" class="h-10 w-auto rounded-none">
                        <div>
                            <p class="font-extrabold text-brand-navy">{{ __('Minhaj') }}</p>
                            <p class="text-sm text-slate-500">{{ __('Modern academic examination platform') }}</p>
                        </div>
                    </div>
                    <div class="flex flex-wrap items-center gap-3 text-sm font-bold text-slate-600">
                        @auth
                            <a href="{{ route('dashboard') }}" class="hover:text-brand-navy">{{ __('Dashboard') }}</a>
                        @else
                            <a href="{{ route('login') }}" class="hover:text-brand-navy">{{ __('Log in') }}</a>
                        @endauth
                        @if (Route::has('register'))
                            <a href="{{ route('register') }}" class="hover:text-brand-navy">{{ __('Create account') }}</a>
                        @endif
                        <a href="#features" class="hover:text-brand-navy">{{ __('Features') }}</a>
                    </div>
                </div>
            </footer>
        </div>
    </body>
</html>
