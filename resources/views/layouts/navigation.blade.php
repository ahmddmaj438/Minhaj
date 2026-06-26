<nav x-data="{ open: false }" class="app-topbar sticky top-0 z-40">
    @php
        $homeUrl = Auth::user()->isStudent()
            ? route('student.exams.index')
            : (auth()->user()->can('screen.dashboard.view') ? route('dashboard') : url('/'));

        $quickActions = collect([
            [
                'label' => __('New Exam'),
                'description' => __('Create an exam shell and add questions.'),
                'href' => Route::has('instructor.exams.create') ? route('instructor.exams.create') : null,
                'can' => Auth::user()->can('screen.instructor.exams.create.view'),
            ],
            [
                'label' => __('Grade submissions'),
                'description' => __('Review submitted answers.'),
                'href' => Route::has('instructor.grading.index') ? route('instructor.grading.index') : null,
                'can' => Auth::user()->can('screen.instructor.grading.index.view'),
            ],
            [
                'label' => __('Browse system data'),
                'description' => __('Open friendly data sections.'),
                'href' => Route::has('data.tables.index') ? route('data.tables.index') : null,
                'can' => Auth::user()->can('screen.data.tables.index.view'),
            ],
            [
                'label' => __('Manage users'),
                'description' => __('Create and review user accounts.'),
                'href' => Route::has('users.index') ? route('users.index') : null,
                'can' => Auth::user()->can('screen.users.index.view'),
            ],
            [
                'label' => __('My Exams'),
                'description' => __('Open assigned student exams.'),
                'href' => Route::has('student.exams.index') ? route('student.exams.index') : null,
                'can' => Auth::user()->isStudent(),
            ],
        ])->filter(fn ($action) => $action['can'] && $action['href'])->values();
    @endphp
    <!-- Primary Navigation Menu -->
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="flex min-h-16 items-center justify-between gap-3 py-2.5">
            <div class="flex min-w-0 flex-1 items-center">
                <!-- Logo -->
                <div class="flex shrink-0 items-center">
                    <a href="{{ $homeUrl }}" aria-label="{{ __('Go to home') }}" class="group inline-flex items-center gap-3 rounded-2xl px-2 py-1.5 transition hover:bg-white/80">
                        <x-application-logo class="h-11 w-11" />
                        <span class="hidden leading-tight sm:block">
                            <span class="brand-wordmark block text-base">LIU Yemen</span>
                            <span class="block text-xs font-semibold text-slate-500">{{ __('Minhaj') }}</span>
                        </span>
                    </a>
                </div>

                <!-- Navigation Links -->
                <div class="hidden min-w-0 flex-1 items-center gap-1 overflow-x-auto whitespace-nowrap px-1 sm:ms-6 lg:flex">
                    @if (Auth::user()->isStudent())
                        <x-nav-link :href="route('student.exams.index')" :active="request()->routeIs('student.exams.*')">
                            {{ __('My Exams') }}
                        </x-nav-link>
                    @endif
                    @can('screen.dashboard.view')
                        <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                            {{ __('Dashboard') }}
                        </x-nav-link>
                    @endcan
                    @can('screen.admin.access.index.view')
                        <x-nav-link :href="route('admin.access.index')" :active="request()->routeIs('admin.*')">
                            {{ __('Access') }}
                        </x-nav-link>
                    @endcan
                    @can('screen.users.index.view')
                        <x-nav-link :href="route('users.index')" :active="request()->routeIs('users.*')">
                            {{ __('Users') }}
                        </x-nav-link>
                    @endcan
                    @can('screen.academics.index.view')
                        <x-nav-link :href="route('academics.index')" :active="request()->routeIs('academics.*')">
                            {{ __('Academics') }}
                        </x-nav-link>
                    @endcan
                    @can('screen.reports.index.view')
                        <x-nav-link :href="route('reports.index')" :active="request()->routeIs('reports.*')">
                            {{ __('Reports') }}
                        </x-nav-link>
                    @endcan
                    @can('screen.data.tables.index.view')
                        <x-nav-link :href="route('data.tables.index')" :active="request()->routeIs('data.*')">
                            {{ __('System Data') }}
                        </x-nav-link>
                    @endcan
                    @can('screen.instructor.exams.create.view')
                        <x-nav-link :href="route('instructor.exams.create')" :active="request()->routeIs('instructor.exams.*')">
                            {{ __('Exam Builder') }}
                        </x-nav-link>
                    @elsecan('screen.exam.wizard.step1.view')
                        <x-nav-link :href="route('exam.wizard.step1')" :active="request()->routeIs('exam.wizard.*')">
                            {{ __('Exam Builder') }}
                        </x-nav-link>
                    @endcan
                    @can('screen.instructor.grading.index.view')
                        <x-nav-link :href="route('instructor.grading.index')" :active="request()->routeIs('instructor.grading.*')">
                            {{ __('Grading') }}
                        </x-nav-link>
                    @endcan
                    @can('grant_super_admin')
                        <x-nav-link :href="route('admin.super-users.index')" :active="request()->routeIs('admin.super-users.*')">
                            {{ __('Super Users') }}
                        </x-nav-link>
                    @endcan
                    @can('screen.admin.settings.ai-configuration.edit.view')
                        <x-nav-link :href="route('admin.settings.ai-configuration.edit')" :active="request()->routeIs('admin.settings.ai-configuration.*')">
                            {{ __('AI Configuration') }}
                        </x-nav-link>
                    @endcan
                </div>
            </div>

            <!-- Settings Dropdown -->
            <div class="hidden shrink-0 items-center gap-3 lg:flex lg:ms-4">
                @if ($quickActions->isNotEmpty())
                    <x-dropdown align="right" width="64">
                        <x-slot name="trigger">
                            <button type="button" aria-haspopup="menu" :aria-expanded="open.toString()" class="inline-flex items-center gap-2 rounded-2xl bg-brand-navy px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-brand-ink focus:outline-none focus-visible:ring-4 focus-visible:ring-orange-100">
                                <span>{{ __('Quick actions') }}</span>
                                <svg class="h-4 w-4 text-white/80" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </button>
                        </x-slot>

                        <x-slot name="content">
                            <div class="grid gap-1">
                                @foreach ($quickActions as $action)
                                    <a href="{{ $action['href'] }}" class="rounded-xl px-3 py-2.5 text-sm transition hover:bg-orange-50 focus:bg-orange-50 focus:outline-none">
                                        <span class="block font-semibold text-slate-900">{{ $action['label'] }}</span>
                                        <span class="mt-0.5 block text-xs leading-5 text-slate-500">{{ $action['description'] }}</span>
                                    </a>
                                @endforeach
                            </div>
                        </x-slot>
                    </x-dropdown>
                @endif

                <x-language-switcher compact />

                <x-dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <button type="button" aria-haspopup="menu" :aria-expanded="open.toString()" class="inline-flex items-center gap-3 rounded-2xl border border-slate-200/80 bg-white/80 px-3 py-2 text-sm font-semibold leading-4 text-slate-700 shadow-sm hover:border-orange-200 hover:text-brand-ink focus:outline-none focus-visible:ring-4 focus-visible:ring-orange-100">
                            <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-brand-navy text-sm font-bold text-white shadow-sm">
                                {{ str(Auth::user()->name)->substr(0, 1)->upper() }}
                            </span>
                            <span class="hidden max-w-36 truncate lg:block">{{ Auth::user()->name }}</span>

                            <div class="text-slate-400">
                                <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </div>
                        </button>
                    </x-slot>

                    <x-slot name="content">
                        @can('screen.profile.edit.view')
                            <x-dropdown-link :href="route('profile.edit')">
                                {{ __('Profile') }}
                            </x-dropdown-link>
                        @endcan

                        <!-- Authentication -->
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf

                            <x-dropdown-link :href="route('logout')"
                                    onclick="event.preventDefault();
                                                this.closest('form').submit();">
                                {{ __('Log Out') }}
                            </x-dropdown-link>
                        </form>
                    </x-slot>
                </x-dropdown>
            </div>

            <!-- Hamburger -->
            <div class="flex shrink-0 items-center gap-2 lg:hidden">
                <x-language-switcher compact />

                <button type="button" @click="open = ! open" :aria-expanded="open.toString()" aria-controls="mobile-navigation" aria-label="{{ __('Toggle navigation menu') }}" class="inline-flex items-center justify-center rounded-2xl border border-slate-200 bg-white/80 p-2.5 text-slate-600 shadow-sm hover:border-orange-200 hover:text-brand-ink focus:outline-none focus-visible:ring-4 focus-visible:ring-orange-100">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Responsive Navigation Menu -->
    <div id="mobile-navigation" :class="{'block': open, 'hidden': ! open}" class="hidden border-t border-slate-200/70 bg-white/88 px-4 pb-4 pt-3 backdrop-blur-xl lg:hidden">
        @if ($quickActions->isNotEmpty())
            <div class="mb-4 rounded-2xl border border-slate-200 bg-slate-50 p-3">
                <p class="px-1 text-xs font-semibold uppercase tracking-wide text-orange-600">{{ __('Quick actions') }}</p>
                <div class="mt-2 grid gap-2">
                    @foreach ($quickActions as $action)
                        <a href="{{ $action['href'] }}" class="rounded-xl bg-white px-3 py-2 text-sm font-semibold text-slate-800 shadow-sm hover:text-orange-700">
                            {{ $action['label'] }}
                        </a>
                    @endforeach
                </div>
            </div>
        @endif

        <div class="space-y-2">
            @if (Auth::user()->isStudent())
                <x-responsive-nav-link :href="route('student.exams.index')" :active="request()->routeIs('student.exams.*')">
                    {{ __('My Exams') }}
                </x-responsive-nav-link>
            @endif
            @can('screen.dashboard.view')
                <x-responsive-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                    {{ __('Dashboard') }}
                </x-responsive-nav-link>
            @endcan
            @can('screen.admin.access.index.view')
                <x-responsive-nav-link :href="route('admin.access.index')" :active="request()->routeIs('admin.*')">
                    {{ __('Access') }}
                </x-responsive-nav-link>
            @endcan
            @can('screen.users.index.view')
                <x-responsive-nav-link :href="route('users.index')" :active="request()->routeIs('users.*')">
                    {{ __('Users') }}
                </x-responsive-nav-link>
            @endcan
            @can('screen.academics.index.view')
                <x-responsive-nav-link :href="route('academics.index')" :active="request()->routeIs('academics.*')">
                    {{ __('Academics') }}
                </x-responsive-nav-link>
            @endcan
            @can('screen.reports.index.view')
                <x-responsive-nav-link :href="route('reports.index')" :active="request()->routeIs('reports.*')">
                    {{ __('Reports') }}
                </x-responsive-nav-link>
            @endcan
                    @can('screen.data.tables.index.view')
                        <x-responsive-nav-link :href="route('data.tables.index')" :active="request()->routeIs('data.*')">
                    {{ __('System Data') }}
                </x-responsive-nav-link>
            @endcan
            @can('screen.instructor.exams.create.view')
                <x-responsive-nav-link :href="route('instructor.exams.create')" :active="request()->routeIs('instructor.exams.*')">
                    {{ __('Exam Builder') }}
                </x-responsive-nav-link>
            @elsecan('screen.exam.wizard.step1.view')
                <x-responsive-nav-link :href="route('exam.wizard.step1')" :active="request()->routeIs('exam.wizard.*')">
                    {{ __('Exam Builder') }}
                </x-responsive-nav-link>
            @endcan
            @can('screen.instructor.grading.index.view')
                <x-responsive-nav-link :href="route('instructor.grading.index')" :active="request()->routeIs('instructor.grading.*')">
                    {{ __('Grading') }}
                </x-responsive-nav-link>
            @endcan
            @can('grant_super_admin')
                <x-responsive-nav-link :href="route('admin.super-users.index')" :active="request()->routeIs('admin.super-users.*')">
                    {{ __('Super Users') }}
                </x-responsive-nav-link>
            @endcan
            @can('screen.admin.settings.ai-configuration.edit.view')
                <x-responsive-nav-link :href="route('admin.settings.ai-configuration.edit')" :active="request()->routeIs('admin.settings.ai-configuration.*')">
                    {{ __('AI Configuration') }}
                </x-responsive-nav-link>
            @endcan
        </div>

        <!-- Responsive Settings Options -->
        <div class="mt-4 border-t border-slate-200 pt-4">
            <div class="rounded-2xl bg-slate-50 px-4 py-3">
                <div class="font-semibold text-base text-slate-900">{{ Auth::user()->name }}</div>
                <div class="font-medium text-sm text-slate-500">{{ Auth::user()->email }}</div>
            </div>

            <div class="mt-3 space-y-2">
                @can('screen.profile.edit.view')
                    <x-responsive-nav-link :href="route('profile.edit')">
                        {{ __('Profile') }}
                    </x-responsive-nav-link>
                @endcan

                <!-- Authentication -->
                <form method="POST" action="{{ route('logout') }}">
                    @csrf

                    <x-responsive-nav-link :href="route('logout')"
                            onclick="event.preventDefault();
                                        this.closest('form').submit();">
                        {{ __('Log Out') }}
                    </x-responsive-nav-link>
                </form>
            </div>
        </div>
    </div>
</nav>
