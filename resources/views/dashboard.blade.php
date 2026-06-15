@php
    $tones = [
        'blue' => 'border-blue-100 bg-blue-50 text-blue-700 ring-blue-100',
        'orange' => 'border-orange-100 bg-orange-50 text-orange-700 ring-orange-100',
        'emerald' => 'border-emerald-100 bg-emerald-50 text-emerald-700 ring-emerald-100',
        'violet' => 'border-violet-100 bg-violet-50 text-violet-700 ring-violet-100',
    ];

    $statusTotal = max($examStatus->sum(), 1);
    $typeMax = max($questionTypes->max() ?? 0, 1);
    $statusColors = ['#003F73', '#FDB33A', '#10b981'];
    $statusCursor = 0;
    $statusStops = [];

    foreach ($examStatus->values() as $index => $value) {
        $start = $statusCursor;
        $statusCursor += ($value / $statusTotal) * 100;
        $statusStops[] = $statusColors[$index % count($statusColors)].' '.$start.'% '.$statusCursor.'%';
    }

    $statusDonut = 'conic-gradient('.implode(', ', $statusStops).')';
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-sm font-semibold uppercase tracking-wide text-orange-600">{{ __('Minhaj analytics') }}</p>
                <h2 class="mt-1 text-2xl font-semibold leading-tight text-slate-950">
                    {{ __('Dashboard') }}
                </h2>
            </div>

            <div class="flex flex-wrap gap-2">
                @can('screen.dashboard.view')
                    <a href="{{ route('dashboard') }}" class="inline-flex items-center rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 shadow-sm hover:bg-slate-50">
                        {{ __('Overview') }}
                    </a>
                @endcan

                @if (Route::has('instructor.exams.create'))
                    <a href="{{ route('instructor.exams.create') }}" class="inline-flex items-center rounded-xl bg-orange-500 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-orange-600">
                        {{ __('New Exam') }}
                    </a>
                @endif
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            <section data-reveal class="dashboard-reveal overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
                <div class="grid gap-0 lg:grid-cols-[1.4fr_0.8fr]">
                    <div class="p-6 sm:p-8">
                        <div class="flex flex-wrap items-center gap-3">
                            <span class="rounded-md bg-orange-100 px-3 py-1 text-sm font-semibold text-orange-700">{{ __('Exam platform') }}</span>
                            <span class="rounded-md bg-slate-100 px-3 py-1 text-sm font-semibold text-slate-700">{{ now()->translatedFormat('M j, Y') }}</span>
                        </div>
                        <h3 class="mt-5 max-w-3xl text-3xl font-semibold text-slate-950 sm:text-4xl">
                            {{ __('Monitor exams, users, questions, answers, and outcomes from one place.') }}
                        </h3>
                        <p class="mt-4 max-w-2xl text-sm leading-6 text-slate-600">
                            {{ __('Track the learning workflow across local instructor exams and TCExam data, with quick signals for content growth, completion activity, and result quality.') }}
                        </p>
                    </div>

                    <div class="border-t border-slate-200 bg-brand-ink p-6 text-white lg:border-l lg:border-t-0">
                        <p class="text-sm font-semibold text-orange-200">{{ __('Result quality') }}</p>
                        <div class="mt-6 flex items-center gap-5">
                            <div class="dashboard-donut relative grid h-28 w-28 place-items-center rounded-full" style="background: conic-gradient(#22c55e {{ $passRate }}%, #334155 0);">
                                <div class="grid h-20 w-20 place-items-center rounded-full bg-brand-ink">
                                    <span class="text-2xl font-bold">{{ $passRate }}%</span>
                                </div>
                            </div>
                            <div>
                                <p class="text-lg font-semibold">{{ __('Pass rate') }}</p>
                                <p class="mt-2 text-sm leading-6 text-slate-300">{{ __('Based on synced exam result snapshots with a pass or fail value.') }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section data-reveal class="dashboard-reveal grid gap-4 lg:grid-cols-[1.2fr_0.8fr]">
                @can('screen.instructor.exams.create.view')
                    <article class="dashboard-card-motion rounded-lg border border-orange-200 bg-white p-5 shadow-sm">
                        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                            <div>
                                <p class="text-sm font-semibold uppercase tracking-wide text-orange-600">{{ __('Start faster') }}</p>
                                @if ($nextDraftExam)
                                    <h3 class="mt-1 text-xl font-semibold text-slate-950">{{ __('Continue your draft exam') }}</h3>
                                    <p class="mt-2 text-sm leading-6 text-slate-600">
                                        {{ $nextDraftExam->title }} · {{ $nextDraftExam->questions_count }} {{ __('questions') }} · {{ $nextDraftExam->updated_at?->diffForHumans() }}
                                    </p>
                                @else
                                    <h3 class="mt-1 text-xl font-semibold text-slate-950">{{ __('Create your first exam workflow') }}</h3>
                                    <p class="mt-2 text-sm leading-6 text-slate-600">{{ __('Start with exam information, then add questions, preview, and publish from one workspace.') }}</p>
                                @endif
                            </div>

                            @if ($nextDraftExam)
                                <a href="{{ route('instructor.exams.question-types.index', $nextDraftExam) }}"
                                    class="inline-flex shrink-0 items-center justify-center rounded-xl bg-orange-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-orange-700">
                                    {{ __('Add questions') }}
                                </a>
                            @else
                                <a href="{{ route('instructor.exams.create') }}"
                                    class="inline-flex shrink-0 items-center justify-center rounded-xl bg-orange-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-orange-700">
                                    {{ __('Create exam') }}
                                </a>
                            @endif
                        </div>

                        @if ($nextDraftExam)
                            <div class="mt-5 grid gap-3 sm:grid-cols-3">
                                <a href="{{ route('instructor.exams.edit', $nextDraftExam) }}" class="rounded-md border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-semibold text-slate-800 hover:border-orange-300 hover:bg-orange-50">
                                    {{ __('Edit setup') }}
                                </a>
                                <a href="{{ route('instructor.exams.preview.show', $nextDraftExam) }}" class="rounded-md border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-semibold text-slate-800 hover:border-orange-300 hover:bg-orange-50">
                                    {{ __('Preview') }}
                                </a>
                                <a href="{{ route('instructor.exams.publish.show', $nextDraftExam) }}" class="rounded-md border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-semibold text-slate-800 hover:border-orange-300 hover:bg-orange-50">
                                    {{ __('Publish') }}
                                </a>
                            </div>
                        @endif
                    </article>
                @endcan

                <article class="dashboard-card-motion rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-sm font-semibold uppercase tracking-wide text-orange-600">{{ __('Quick actions') }}</p>
                    <div class="mt-4 grid gap-3 sm:grid-cols-2">
                        @can('screen.instructor.grading.index.view')
                            <a href="{{ route('instructor.grading.index') }}" class="rounded-md border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-semibold text-slate-800 hover:border-orange-300 hover:bg-orange-50">
                                {{ __('Grade submissions') }}
                            </a>
                        @endcan
                        @can('screen.data.tables.index.view')
                            <a href="{{ route('data.tables.index') }}" class="rounded-md border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-semibold text-slate-800 hover:border-orange-300 hover:bg-orange-50">
                                {{ __('Browse data tables') }}
                            </a>
                        @endcan
                        @can('screen.users.index.view')
                            <a href="{{ route('users.index') }}" class="rounded-md border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-semibold text-slate-800 hover:border-orange-300 hover:bg-orange-50">
                                {{ __('Manage users') }}
                            </a>
                        @endcan
                        @can('screen.admin.access.index.view')
                            <a href="{{ route('admin.access.index') }}" class="rounded-md border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-semibold text-slate-800 hover:border-orange-300 hover:bg-orange-50">
                                {{ __('Configure access') }}
                            </a>
                        @endcan
                    </div>
                </article>
            </section>

            <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                @foreach ($stats as $stat)
                    <article data-reveal class="dashboard-reveal dashboard-card-motion rounded-lg border border-slate-200 bg-white p-5 shadow-sm" style="--reveal-delay: {{ $loop->index * 45 }}ms;">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <p class="text-sm font-medium text-slate-500">{{ $stat['label'] }}</p>
                                <p class="mt-2 text-3xl font-semibold text-slate-950">{{ number_format($stat['value']) }}</p>
                            </div>
                            <div class="rounded-md border p-2 ring-4 {{ $tones[$stat['tone']] ?? $tones['blue'] }}">
                                <span class="block h-3 w-3 rounded-full bg-current"></span>
                            </div>
                        </div>
                        <p class="mt-4 text-sm text-slate-600">{{ $stat['detail'] }}</p>
                    </article>
                @endforeach
            </section>

            <section class="grid gap-6 lg:grid-cols-[1.35fr_0.9fr]">
                <article data-reveal class="dashboard-reveal dashboard-card-motion rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
                    <div class="flex items-center justify-between gap-4">
                        <div>
                            <h3 class="text-lg font-semibold text-slate-950">{{ __('Completions This Week') }}</h3>
                            <p class="mt-1 text-sm text-slate-500">{{ __('Daily completed TCExam snapshots') }}</p>
                        </div>
                        <span class="rounded-md bg-slate-100 px-3 py-1 text-sm font-semibold text-slate-700">{{ __('7 days') }}</span>
                    </div>

                    <div class="mt-8 flex h-64 items-end gap-3 border-b border-slate-200 pb-3">
                        @foreach ($completionTrend as $day)
                            <div class="flex min-w-0 flex-1 flex-col items-center gap-3">
                                <div class="flex h-44 w-full items-end rounded-md bg-slate-100 p-1">
                                    <div class="dashboard-bar-fill w-full rounded bg-brand-navy" style="height: {{ max(8, ($day['completed'] / $maxTrend) * 100) }}%; --reveal-delay: {{ 70 + ($loop->index * 35) }}ms;"></div>
                                </div>
                                <div class="text-center">
                                    <p class="text-sm font-semibold text-slate-900">{{ $day['completed'] }}</p>
                                    <p class="text-xs text-slate-500">{{ $day['label'] }}</p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </article>

                <article data-reveal class="dashboard-reveal dashboard-card-motion rounded-lg border border-slate-200 bg-white p-6 shadow-sm" style="--reveal-delay: 60ms;">
                    <h3 class="text-lg font-semibold text-slate-950">{{ __('Exam Mix') }}</h3>
                    <p class="mt-1 text-sm text-slate-500">{{ __('Draft, published, and TCExam inventory') }}</p>

                    <div class="mt-7 flex flex-col items-center gap-6 sm:flex-row lg:flex-col xl:flex-row">
                        <div class="dashboard-donut h-40 w-40 rounded-full p-5" style="background: {{ $statusDonut }};">
                            <div class="grid h-full w-full place-items-center rounded-full bg-white text-center">
                                <div>
                                    <p class="text-3xl font-semibold text-slate-950">{{ number_format($examStatus->sum()) }}</p>
                                    <p class="text-xs font-semibold uppercase text-slate-500">{{ __('Exams') }}</p>
                                </div>
                            </div>
                        </div>

                        <div class="w-full space-y-3">
                            @foreach ($examStatus as $label => $value)
                                <div class="flex items-center justify-between gap-3 rounded-md bg-slate-50 px-3 py-2">
                                    <div class="flex items-center gap-2">
                                        <span class="h-2.5 w-2.5 rounded-full" style="background-color: {{ $statusColors[$loop->index % count($statusColors)] }}"></span>
                                        <span class="text-sm font-medium text-slate-700">{{ __($label) }}</span>
                                    </div>
                                    <span class="text-sm font-semibold text-slate-950">{{ number_format($value) }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </article>
            </section>

            <section class="grid gap-6 lg:grid-cols-[0.95fr_1.25fr]">
                <article data-reveal class="dashboard-reveal dashboard-card-motion rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
                    <h3 class="text-lg font-semibold text-slate-950">{{ __('Question Types') }}</h3>
                    <p class="mt-1 text-sm text-slate-500">{{ __('Top categories in the question bank') }}</p>

                    <div class="mt-6 space-y-4">
                        @forelse ($questionTypes as $type => $total)
                            <div>
                                <div class="mb-2 flex items-center justify-between gap-3">
                                    <p class="truncate text-sm font-medium capitalize text-slate-700">{{ str_replace(['_', '-'], ' ', $type) }}</p>
                                    <p class="text-sm font-semibold text-slate-950">{{ number_format($total) }}</p>
                                </div>
                                <div class="h-2 rounded-full bg-slate-100">
                                    <div class="dashboard-progress-fill h-2 rounded-full bg-emerald-500" style="width: {{ max(8, ($total / $typeMax) * 100) }}%; --reveal-delay: {{ $loop->index * 45 }}ms;"></div>
                                </div>
                            </div>
                        @empty
                            <div class="rounded-md bg-slate-50 p-4 text-sm text-slate-600">
                                {{ __('Question data will appear here after exams have questions.') }}
                            </div>
                        @endforelse
                    </div>
                </article>

                <article data-reveal class="dashboard-reveal dashboard-card-motion rounded-lg border border-slate-200 bg-white shadow-sm" style="--reveal-delay: 60ms;">
                    <div class="border-b border-slate-200 p-6">
                        <h3 class="text-lg font-semibold text-slate-950">{{ __('Recent Instructor Exams') }}</h3>
                        <p class="mt-1 text-sm text-slate-500">{{ __('Latest exams created in the local builder') }}</p>
                    </div>

                    <div class="divide-y divide-slate-100">
                        @forelse ($recentExams as $exam)
                            <div class="grid gap-3 p-5 transition-colors hover:bg-orange-50/50 sm:grid-cols-[1fr_auto] sm:items-center">
                                <div>
                                    @can('screen.instructor.exams.create.view')
                                        <a href="{{ route('instructor.exams.edit', $exam) }}" class="font-semibold text-slate-950 hover:text-orange-700">
                                            {{ $exam->title }}
                                        </a>
                                    @else
                                        <p class="font-semibold text-slate-950">{{ $exam->title }}</p>
                                    @endcan
                                    <p class="mt-1 text-sm text-slate-500">
                                        {{ $exam->course?->code ?? __('No course') }} &middot; {{ $exam->questions_count }} {{ __('questions') }} &middot; {{ $exam->updated_at?->diffForHumans() }}
                                    </p>
                                </div>
                                <div class="flex flex-wrap items-center gap-2 sm:justify-end">
                                    <span class="rounded-md px-2.5 py-1 text-xs font-semibold {{ $exam->status === 'published' ? 'bg-emerald-100 text-emerald-700' : 'bg-orange-100 text-orange-700' }}">
                                        {{ __(ucfirst($exam->status)) }}
                                    </span>
                                    @can('screen.instructor.exams.create.view')
                                        <a href="{{ route('instructor.exams.question-types.index', $exam) }}" class="rounded-md border border-slate-200 bg-white px-2.5 py-1 text-xs font-semibold text-slate-700 hover:border-orange-300 hover:text-orange-700">
                                            {{ __('Questions') }}
                                        </a>
                                        <a href="{{ route('instructor.exams.preview.show', $exam) }}" class="rounded-md border border-slate-200 bg-white px-2.5 py-1 text-xs font-semibold text-slate-700 hover:border-orange-300 hover:text-orange-700">
                                            {{ __('Preview') }}
                                        </a>
                                        <a href="{{ route('instructor.exams.publish.show', $exam) }}" class="rounded-md border border-slate-200 bg-white px-2.5 py-1 text-xs font-semibold text-slate-700 hover:border-orange-300 hover:text-orange-700">
                                            {{ __('Publish') }}
                                        </a>
                                    @endcan
                                </div>
                            </div>
                        @empty
                            <div class="p-6">
                                <p class="text-sm text-slate-600">{{ __('Recent instructor exams will appear here after the first exam is created.') }}</p>
                                @can('screen.instructor.exams.create.view')
                                    <a href="{{ route('instructor.exams.create') }}" class="mt-4 inline-flex items-center justify-center rounded-xl bg-orange-600 px-4 py-2 text-sm font-semibold text-white hover:bg-orange-700">
                                        {{ __('Create exam') }}
                                    </a>
                                @endcan
                            </div>
                        @endforelse
                    </div>
                </article>
            </section>

            <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                @foreach ($operations as $label => $value)
                    <article data-reveal class="dashboard-reveal dashboard-card-motion rounded-lg border border-slate-200 bg-white p-5 shadow-sm" style="--reveal-delay: {{ $loop->index * 40 }}ms;">
                        <p class="text-sm font-medium capitalize text-slate-500">{{ __(str($label)->replace('_', ' ')->title()->toString()) }}</p>
                        <p class="mt-2 text-2xl font-semibold text-slate-950">{{ number_format($value) }}</p>
                    </article>
                @endforeach
            </section>

            @if (auth()->user()->can('screen.groups.index.view') && auth()->user()->can('button.dashboard.group_management'))
                <section data-reveal class="dashboard-reveal dashboard-card-motion rounded-lg border border-orange-200 bg-white p-6 shadow-sm">
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <h3 class="text-lg font-semibold text-slate-950">{{ __('User Access Management') }}</h3>
                            <p class="mt-1 text-sm text-slate-600">{{ __('Manage user groups and permission rules from one place.') }}</p>
                        </div>
                        <a href="{{ route('groups.index') }}" class="inline-flex items-center justify-center rounded-xl bg-orange-500 px-4 py-2 text-sm font-semibold text-white hover:bg-orange-600">
                            {{ __('Group Management') }}
                        </a>
                    </div>
                </section>
            @endif
        </div>
    </div>
</x-app-layout>
