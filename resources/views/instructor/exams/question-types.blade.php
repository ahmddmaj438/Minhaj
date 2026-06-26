<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-sm font-medium text-orange-600">Exam Builder</p>
                <h2 class="text-2xl font-semibold leading-tight text-slate-950">Choose Question Type</h2>
            </div>
            <div class="text-sm text-slate-500">Step 3 of 5: Questions</div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="mb-6 rounded-md border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800">
                    {{ session('status') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="mb-6 rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                    <p class="font-semibold">Please select a valid question type.</p>
                    <ul class="mt-2 list-disc space-y-1 ps-5">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @include('instructor.exams.partials.workspace-nav', [
                'exam' => $exam,
                'active' => 'questions',
                'questionCount' => $questionCount,
                'totalQuestionMarks' => $totalQuestionMarks,
            ])

            <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_360px]">
                <div class="space-y-6">
                    <section class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
                        <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
                            <div>
                                <p class="text-sm font-semibold uppercase tracking-wide text-orange-600">{{ __('Step 3: Questions') }}</p>
                                <h3 class="mt-1 text-xl font-semibold text-slate-950">{{ $exam->title }}</h3>
                                <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-600">
                                    {{ __('Choose the kind of question you want to place in the exam. After choosing, you will edit the question card details.') }}
                                </p>
                            </div>
                            <dl class="grid min-w-56 grid-cols-2 gap-3 text-sm">
                                <div class="rounded-md bg-slate-50 p-3">
                                    <dt class="text-slate-500">Course</dt>
                                    <dd class="mt-1 font-semibold text-slate-900">{{ $exam->course->code }}</dd>
                                </div>
                                <div class="rounded-md bg-slate-50 p-3">
                                    <dt class="text-slate-500">Marks</dt>
                                    <dd class="mt-1 font-semibold text-slate-900">{{ $exam->total_marks }}</dd>
                                </div>
                            </dl>
                        </div>
                    </section>

                    <section class="rounded-lg border border-orange-200 bg-white p-6 shadow-sm">
                        <div class="flex flex-col gap-3 border-b border-orange-100 pb-5 sm:flex-row sm:items-start sm:justify-between">
                            <div>
                                <p class="text-sm font-semibold uppercase tracking-wide text-orange-600">{{ __('Question Bank') }}</p>
                                <h3 class="mt-1 text-xl font-semibold text-slate-950">{{ __('Add an existing question') }}</h3>
                                <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-600">
                                    {{ __('Reuse a saved question by adding a copy to this exam. The original question stays unchanged in the question bank.') }}
                                </p>
                            </div>
                            <span class="rounded-full bg-orange-100 px-3 py-1 text-xs font-semibold text-orange-700">
                                {{ __(':count saved', ['count' => $bankQuestions->count()]) }}
                            </span>
                        </div>

                        @if ($bankQuestions->isEmpty())
                            <div class="mt-5 rounded-lg border border-dashed border-slate-300 bg-slate-50 px-4 py-5 text-sm text-slate-600">
                                <p class="font-semibold text-slate-900">{{ __('No saved questions yet') }}</p>
                                <p class="mt-1">{{ __('Edit any question and choose Save this question to the question bank. Saved questions will appear here for reuse.') }}</p>
                            </div>
                        @else
                            <div class="mt-5 grid gap-4 md:grid-cols-2">
                                @foreach ($bankQuestions as $bankQuestion)
                                    @php
                                        $bankPrompt = $bankQuestion->prompt ?? [];
                                        $bankQuestionText = $bankPrompt['question_text']
                                            ?? $bankPrompt['statement']
                                            ?? $bankPrompt['problem_statement']
                                            ?? $bankPrompt['scenario']
                                            ?? $bankQuestion->title;
                                    @endphp
                                    <article class="flex min-h-44 flex-col justify-between rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
                                        <div>
                                            <div class="flex flex-wrap items-center gap-2">
                                                <span class="rounded-full bg-slate-950 px-2.5 py-1 text-xs font-semibold text-white">
                                                    {{ __(str($bankQuestion->type)->replace('_', ' ')->title()->toString()) }}
                                                </span>
                                                <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-700">
                                                    {{ number_format((float) $bankQuestion->marks, 2) }} {{ __('marks') }}
                                                </span>
                                                @if ($bankQuestion->exam?->course)
                                                    <span class="rounded-full bg-orange-100 px-2.5 py-1 text-xs font-semibold text-orange-700">
                                                        {{ $bankQuestion->exam->course->code }}
                                                    </span>
                                                @endif
                                            </div>
                                            <h4 class="mt-3 text-base font-semibold text-slate-950">{{ $bankQuestion->title }}</h4>
                                            <p class="mt-2 text-sm leading-6 text-slate-600">{{ str($bankQuestionText)->limit(180) }}</p>
                                        </div>

                                        <form method="POST" action="{{ route('instructor.exams.questions.bank.store', $exam) }}" class="mt-4">
                                            @csrf
                                            <input type="hidden" name="bank_question_id" value="{{ $bankQuestion->id }}">
                                            <button type="submit"
                                                class="inline-flex w-full items-center justify-center rounded-md bg-orange-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-orange-700 focus:outline-none focus:ring-2 focus:ring-orange-500 focus:ring-offset-2">
                                                {{ __('Add from question bank') }}
                                            </button>
                                        </form>
                                    </article>
                                @endforeach
                            </div>
                        @endif
                    </section>

                    @foreach ($categories as $category)
                        @php
                            $accentClasses = match ($category['accent']) {
                                'orange' => 'border-orange-200 bg-orange-50 text-orange-700',
                                'navy' => 'border-slate-300 bg-slate-950 text-white',
                                'emerald' => 'border-emerald-200 bg-emerald-50 text-emerald-700',
                                default => 'border-slate-200 bg-slate-50 text-slate-700',
                            };
                        @endphp

                        <section class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
                            <div class="flex flex-col gap-3 border-b border-slate-100 pb-5 sm:flex-row sm:items-start sm:justify-between">
                                <div>
                                    <span class="inline-flex rounded-full border px-3 py-1 text-xs font-semibold {{ $accentClasses }}">
                                        {{ $category['label'] }}
                                    </span>
                                    <p class="mt-3 text-sm leading-6 text-slate-600">{{ $category['description'] }}</p>
                                </div>
                                <p class="text-sm text-slate-500">{{ count($category['types']) }} types</p>
                            </div>

                            <div class="mt-5 grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                                @foreach ($category['types'] as $type)
                                    <form method="POST" action="{{ route('instructor.exams.question-types.store', $exam) }}"
                                        class="flex min-h-48 flex-col justify-between rounded-lg border border-slate-200 bg-white p-4 shadow-sm transition hover:border-orange-300 hover:shadow-md">
                                        @csrf
                                        <input type="hidden" name="question_type" value="{{ $type['key'] }}">

                                        <div>
                                            <div class="flex items-start justify-between gap-3">
                                                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-md bg-slate-100 text-xs font-bold text-slate-800">
                                                    {{ $type['short_label'] }}
                                                </div>
                                                <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-600">
                                                    {{ $type['builder_phase'] }}
                                                </span>
                                            </div>
                                            <h4 class="mt-4 text-base font-semibold text-slate-950">{{ $type['label'] }}</h4>
                                            <p class="mt-2 text-sm leading-6 text-slate-600">{{ $type['description'] }}</p>

                                            @if (isset($type['language']))
                                                <div class="mt-4 rounded-md border border-slate-800 bg-slate-950 p-3 font-mono text-xs text-slate-100">
                                                    <div class="flex items-center justify-between text-slate-400">
                                                        <span>language</span>
                                                        <span>{{ $type['language'] }}</span>
                                                    </div>
                                                    <div class="mt-3 text-orange-300">starter_code: ready for builder</div>
                                                    <div class="text-emerald-300">expected_output: defined later</div>
                                                </div>
                                            @endif

                                            @if ($type['key'] === 'packet_tracer')
                                                <div class="mt-4 grid gap-2 text-xs text-slate-600">
                                                    <div class="rounded-md border border-dashed border-slate-300 bg-slate-50 px-3 py-2">.pkt file upload</div>
                                                    <div class="rounded-md border border-dashed border-slate-300 bg-slate-50 px-3 py-2">topology screenshot</div>
                                                    <div class="rounded-md border border-dashed border-slate-300 bg-slate-50 px-3 py-2">scenario tasks</div>
                                                </div>
                                            @endif
                                        </div>

                                        <button type="submit"
                                            class="mt-5 inline-flex w-full items-center justify-center rounded-md bg-slate-950 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-orange-600 focus:outline-none focus:ring-2 focus:ring-orange-500 focus:ring-offset-2">
                                            {{ __('Add Question') }}
                                        </button>
                                    </form>
                                @endforeach
                            </div>
                        </section>
                    @endforeach
                </div>

                @include('instructor.exams.partials.exam-map', [
                    'exam' => $exam,
                    'questions' => $questions,
                    'totalQuestionMarks' => $totalQuestionMarks,
                ])
            </div>
        </div>
    </div>
</x-app-layout>
