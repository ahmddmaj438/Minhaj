@php
    $displayFormat = $displayFormat ?? \App\Support\Exams\ExamDisplayFormatCatalog::normalize($exam->display_format);
    $formatMeta = $formatMeta ?? \App\Support\Exams\ExamDisplayFormatCatalog::find($displayFormat);
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-sm font-medium text-orange-600">{{ __('Exam Builder') }}</p>
                <h2 class="text-2xl font-semibold leading-tight text-slate-950">{{ __('Preview as Student') }}</h2>
            </div>
            <div class="text-sm text-slate-500">{{ __('Step 5 of 5: Preview and Publish') }}</div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="exam-preview-chrome">
                @include('instructor.exams.partials.workspace-nav', [
                    'exam' => $exam,
                    'active' => 'preview',
                    'questionCount' => $questions->count(),
                    'totalQuestionMarks' => $totalQuestionMarks,
                ])
            </div>

            <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_330px]">
                <div class="space-y-6">
                    <section class="exam-print-surface rounded-2xl border border-slate-200 bg-white p-6 shadow-sm" data-preview-format="{{ $displayFormat }}">
                        <div class="flex flex-col gap-5 border-b border-slate-100 pb-5 md:flex-row md:items-start md:justify-between">
                            <div class="flex items-center gap-4">
                                <img src="{{ asset('brand/logo.png') }}" alt="{{ config('app.name', 'Minhaj') }}" class="h-14 w-auto">
                                <div>
                                    <p class="text-sm font-semibold uppercase tracking-wide text-orange-600">{{ __('Student exam preview') }}</p>
                                    <h3 class="mt-1 text-2xl font-semibold text-slate-950">{{ $exam->title }}</h3>
                                    <p class="mt-1 text-sm text-slate-600">
                                        {{ $exam->course?->code ?? __('No course') }}
                                        @if ($exam->course)
                                            <span class="text-slate-400">/</span>
                                            {{ $exam->course->name }}
                                        @endif
                                    </p>
                                </div>
                            </div>

                            <div class="rounded-xl bg-orange-50 px-4 py-3 text-sm">
                                <p class="font-semibold text-orange-900">{{ $formatMeta['title'] }}</p>
                                <p class="mt-1 text-orange-800">{{ $formatMeta['best_for'] }}</p>
                            </div>
                        </div>

                        <div class="mt-5 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                            <div class="rounded-xl bg-slate-50 p-4">
                                <p class="text-sm text-slate-500">{{ __('Duration') }}</p>
                                <p class="mt-1 font-semibold text-slate-950">{{ __(':count minutes', ['count' => $exam->duration_minutes]) }}</p>
                            </div>
                            <div class="rounded-xl bg-slate-50 p-4">
                                <p class="text-sm text-slate-500">{{ __('Total marks') }}</p>
                                <p class="mt-1 font-semibold text-slate-950">{{ number_format((float) $exam->total_marks, 2) }}</p>
                            </div>
                            <div class="rounded-xl bg-slate-50 p-4">
                                <p class="text-sm text-slate-500">{{ __('Questions') }}</p>
                                <p class="mt-1 font-semibold text-slate-950">{{ $questions->count() }}</p>
                            </div>
                            <div class="rounded-xl bg-slate-50 p-4">
                                <p class="text-sm text-slate-500">{{ __('Status') }}</p>
                                <p class="mt-1 font-semibold capitalize text-slate-950">{{ $exam->status }}</p>
                            </div>
                        </div>

                        <div class="mt-5 grid gap-4 md:grid-cols-3">
                            <div class="rounded-xl border border-slate-200 bg-white px-4 py-3">
                                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Student name') }}</p>
                                <p class="mt-3 border-b border-slate-300 pb-2 text-sm text-slate-500">{{ __('Preview placeholder') }}</p>
                            </div>
                            <div class="rounded-xl border border-slate-200 bg-white px-4 py-3">
                                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Student number') }}</p>
                                <p class="mt-3 border-b border-slate-300 pb-2 text-sm text-slate-500">{{ __('Preview placeholder') }}</p>
                            </div>
                            <div class="rounded-xl border border-slate-200 bg-white px-4 py-3">
                                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Instructor') }}</p>
                                <p class="mt-3 border-b border-slate-300 pb-2 text-sm text-slate-700">{{ $exam->instructor?->name ?? __('Instructor') }}</p>
                            </div>
                        </div>

                        <div class="mt-5 rounded-xl border border-slate-200 bg-slate-50 p-4">
                            <h4 class="text-base font-semibold text-slate-950">{{ __('Instructions') }}</h4>
                            <p class="mt-2 text-sm leading-6 text-slate-600">
                                {{ $exam->description ?: __('Read each question carefully, answer within the available time, and review your responses before submitting.') }}
                            </p>
                            <div class="mt-3 grid gap-2 text-sm text-slate-600 sm:grid-cols-2">
                                <p>{{ __('Starts') }}: {{ $exam->starts_at?->format('Y-m-d H:i') ?? __('Controlled by assignment') }}</p>
                                <p>{{ __('Ends') }}: {{ $exam->ends_at?->format('Y-m-d H:i') ?? __('Controlled by assignment') }}</p>
                            </div>
                        </div>
                    </section>

                    @include('instructor.exams.partials.preview-template', [
                        'exam' => $exam,
                        'questions' => $questions,
                        'displayFormat' => $displayFormat,
                        'formatMeta' => $formatMeta,
                    ])

                    <footer class="exam-print-footer rounded-xl border border-slate-200 bg-white px-5 py-4 text-sm text-slate-600 shadow-sm">
                        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                            <span>{{ config('app.name', 'Minhaj') }} / {{ $exam->title }}</span>
                            <span>{{ __('Template') }}: {{ $formatMeta['title'] }}</span>
                        </div>
                    </footer>
                </div>

                <aside class="exam-preview-chrome space-y-6">
                    <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                        <h3 class="text-base font-semibold text-slate-950">{{ __('Preview summary') }}</h3>
                        <dl class="mt-4 space-y-3 text-sm">
                            <div>
                                <dt class="text-slate-500">{{ __('Template') }}</dt>
                                <dd class="mt-1 font-semibold text-slate-900">{{ $formatMeta['title'] }}</dd>
                            </div>
                            <div>
                                <dt class="text-slate-500">{{ __('Questions') }}</dt>
                                <dd class="mt-1 font-semibold text-slate-900">{{ $questions->count() }}</dd>
                            </div>
                            <div>
                                <dt class="text-slate-500">{{ __('Question marks total') }}</dt>
                                <dd class="mt-1 font-semibold text-slate-900">{{ number_format((float) $totalQuestionMarks, 2) }}</dd>
                            </div>
                            <div>
                                <dt class="text-slate-500">{{ __('Configured exam marks') }}</dt>
                                <dd class="mt-1 font-semibold text-slate-900">{{ number_format((float) $exam->total_marks, 2) }}</dd>
                            </div>
                        </dl>
                    </section>

                    <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                        <h3 class="text-base font-semibold text-slate-950">{{ __('Template checks') }}</h3>
                        <div class="mt-4 space-y-3 text-sm">
                            @foreach ($displayFormats as $key => $format)
                                <div class="rounded-xl border px-3 py-2 {{ $displayFormat === $key ? 'border-orange-300 bg-orange-50 text-orange-900' : 'border-slate-200 bg-slate-50 text-slate-600' }}">
                                    <p class="font-semibold">{{ $format['title'] }}</p>
                                    <p class="mt-1 text-xs leading-5">{{ $format['summary'] }}</p>
                                </div>
                            @endforeach
                        </div>
                    </section>

                    <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                        <h3 class="text-base font-semibold text-slate-950">{{ __('Actions') }}</h3>
                        <div class="mt-4 grid gap-3">
                            <button type="button" onclick="window.print()"
                                class="inline-flex items-center justify-center rounded-xl bg-orange-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-orange-700">
                                {{ __('Print / save as PDF') }}
                            </button>
                            <a href="{{ route('instructor.exams.edit', $exam) }}#advanced-options"
                                class="inline-flex items-center justify-center rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-800 shadow-sm hover:bg-slate-50">
                                {{ __('Advanced Options') }}
                            </a>
                            <a href="{{ route('instructor.exams.questions.order.index', $exam) }}"
                                class="inline-flex items-center justify-center rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-800 shadow-sm hover:bg-slate-50">
                                {{ __('Manage order') }}
                            </a>
                            <a href="{{ route('instructor.exams.publish.show', $exam) }}"
                                class="inline-flex items-center justify-center rounded-xl bg-slate-950 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-slate-800">
                                {{ __('Continue to publish') }}
                            </a>
                        </div>
                    </section>
                </aside>
            </div>
        </div>
    </div>
</x-app-layout>
