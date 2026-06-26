@php
    $questions = $questions ?? collect();
    $totalQuestionMarks = (float) ($totalQuestionMarks ?? $questions->sum(fn ($question) => (float) $question->marks));
    $examMarks = (float) ($exam->total_marks ?? 0);
    $markDifference = round($totalQuestionMarks - $examMarks, 2);
    $questionEditRoutes = $questionEditRoutes ?? $editRoutes ?? [];
    $missingQuestions = $questions->filter(fn ($question) => ($question->prompt['status'] ?? null) !== 'configured' || (float) $question->marks <= 0);
    $hasHeader = filled($exam->title) && $exam->course && (float) $exam->total_marks > 0 && (int) $exam->duration_minutes >= 5;
    $hasInstructions = filled($exam->description);
    $hasQuestions = $questions->isNotEmpty();
    $marksMatch = abs($markDifference) < 0.01;
    $ready = $hasHeader && $hasInstructions && $hasQuestions && $marksMatch && $missingQuestions->isEmpty();

    $checks = [
        ['label' => __('Exam information'), 'passed' => $hasHeader, 'href' => route('instructor.exams.edit', $exam).'#exam-header'],
        ['label' => __('Exam instructions'), 'passed' => $hasInstructions, 'href' => route('instructor.exams.edit', $exam).'#exam-instructions'],
        ['label' => __('Questions'), 'passed' => $hasQuestions && $missingQuestions->isEmpty(), 'href' => route('instructor.exams.question-types.index', $exam)],
        ['label' => __('Marks balance'), 'passed' => $marksMatch, 'href' => route('instructor.exams.questions.order.index', $exam)],
        ['label' => __('Preview'), 'passed' => $hasQuestions, 'href' => route('instructor.exams.preview.show', $exam)],
    ];
@endphp

<aside class="space-y-5 lg:sticky lg:top-24">
    <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
        <div class="flex items-center justify-between gap-3">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-orange-600">{{ __('Exam Map') }}</p>
                <h3 class="mt-1 text-lg font-semibold text-slate-950">{{ __('Build status') }}</h3>
            </div>
            <span class="rounded-full px-3 py-1 text-xs font-semibold {{ $ready ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-800' }}">
                {{ $ready ? __('Ready') : __('Needs review') }}
            </span>
        </div>

        <div class="mt-5 grid gap-3">
            @foreach ($checks as $check)
                <a href="{{ $check['href'] }}"
                    class="flex items-center justify-between gap-3 rounded-md border px-3 py-2 text-sm transition {{ $check['passed'] ? 'border-emerald-200 bg-emerald-50 text-emerald-900' : 'border-amber-200 bg-amber-50 text-amber-900 hover:border-amber-300' }}">
                    <span class="font-semibold">{{ $check['label'] }}</span>
                    <span class="text-xs font-bold">{{ $check['passed'] ? __('Done') : __('Missing') }}</span>
                </a>
            @endforeach
        </div>
    </section>

    <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
        <h3 class="text-base font-semibold text-slate-950">{{ __('Exam structure') }}</h3>
        <dl class="mt-4 space-y-3 text-sm">
            <div class="flex justify-between gap-4">
                <dt class="text-slate-500">{{ __('Questions') }}</dt>
                <dd class="font-semibold text-slate-900">{{ $questions->count() }}</dd>
            </div>
            <div class="flex justify-between gap-4">
                <dt class="text-slate-500">{{ __('Question marks') }}</dt>
                <dd class="font-semibold {{ $marksMatch ? 'text-emerald-700' : 'text-amber-800' }}">
                    {{ number_format($totalQuestionMarks, 2) }} / {{ number_format($examMarks, 2) }}
                </dd>
            </div>
            <div class="flex justify-between gap-4">
                <dt class="text-slate-500">{{ __('Duration') }}</dt>
                <dd class="font-semibold text-slate-900">{{ __(':count min', ['count' => $exam->duration_minutes]) }}</dd>
            </div>
            <div class="flex justify-between gap-4">
                <dt class="text-slate-500">{{ __('Status') }}</dt>
                <dd class="font-semibold capitalize text-slate-900">{{ $exam->status }}</dd>
            </div>
        </dl>

        @unless ($marksMatch)
            <div class="mt-4 rounded-md border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-900">
                @if ($markDifference > 0)
                    {{ __('Question marks are :count above the exam total.', ['count' => number_format(abs($markDifference), 2)]) }}
                @else
                    {{ __('Question marks are :count below the exam total.', ['count' => number_format(abs($markDifference), 2)]) }}
                @endif
            </div>
        @endunless
    </section>

    <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
        <div class="flex items-center justify-between gap-3">
            <h3 class="text-base font-semibold text-slate-950">{{ __('Questions list') }}</h3>
            <a href="{{ route('instructor.exams.question-types.index', $exam) }}" class="text-sm font-semibold text-orange-700 hover:text-orange-800">
                {{ __('Add Question') }}
            </a>
        </div>

        @if ($questions->isEmpty())
            <div class="mt-4 rounded-md border border-dashed border-slate-300 bg-slate-50 px-3 py-4 text-center text-sm text-slate-600">
                {{ __('No questions yet.') }}
            </div>
        @else
            <div class="mt-4 max-h-96 space-y-2 overflow-y-auto pr-1">
                @foreach ($questions as $question)
                    @php
                        $isMissing = ($question->prompt['status'] ?? null) !== 'configured' || (float) $question->marks <= 0;
                        $questionText = $question->prompt['question_text']
                            ?? $question->prompt['statement']
                            ?? $question->prompt['problem_statement']
                            ?? $question->prompt['scenario']
                            ?? $question->title;
                    @endphp
                    <a href="{{ $questionEditRoutes[$question->id] ?? route('instructor.exams.question-types.index', $exam) }}"
                        class="block rounded-md border px-3 py-2 text-sm transition {{ $isMissing ? 'border-amber-200 bg-amber-50 text-amber-900' : 'border-slate-200 bg-slate-50 text-slate-700 hover:border-orange-300' }}">
                        <div class="flex items-center justify-between gap-3">
                            <span class="font-semibold">{{ __('Question :number', ['number' => $question->position]) }}</span>
                            <span class="text-xs font-semibold">{{ number_format((float) $question->marks, 2) }}</span>
                        </div>
                        <p class="mt-1 line-clamp-2 text-xs leading-5">{{ str($questionText)->limit(90) }}</p>
                    </a>
                @endforeach
            </div>
        @endif
    </section>

    <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
        <h3 class="text-base font-semibold text-slate-950">{{ __('Preview and publish') }}</h3>
        <div class="mt-4 grid gap-3">
            <a href="{{ route('instructor.exams.preview.show', $exam) }}"
                class="inline-flex items-center justify-center rounded-md border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-800 shadow-sm hover:bg-slate-50">
                {{ __('Preview as Student') }}
            </a>
            <a href="{{ route('instructor.exams.publish.show', $exam) }}"
                class="inline-flex items-center justify-center rounded-md {{ $ready ? 'bg-emerald-600 hover:bg-emerald-700' : 'bg-slate-950 hover:bg-slate-800' }} px-4 py-2 text-sm font-semibold text-white shadow-sm">
                {{ $ready ? __('Publish Exam') : __('Review Readiness') }}
            </a>
        </div>
    </section>
</aside>
