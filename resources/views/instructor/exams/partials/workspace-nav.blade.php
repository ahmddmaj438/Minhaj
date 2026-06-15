@php
    $exam = $exam ?? null;
    $active = $active ?? 'information';
    $questionCount = $questionCount ?? null;
    $totalQuestionMarks = $totalQuestionMarks ?? null;
    $formatTitle = $exam
        ? \App\Support\Exams\ExamDisplayFormatCatalog::title($exam->display_format)
        : __('Choose layout');

    $tabs = [
        [
            'key' => 'information',
            'label' => __('Exam Information'),
            'href' => $exam ? route('instructor.exams.edit', $exam).'#exam-information' : null,
            'meta' => $exam ? __(':count min', ['count' => $exam->duration_minutes]) : __('Start here'),
        ],
        [
            'key' => 'format',
            'label' => __('Exam Format'),
            'href' => $exam ? route('instructor.exams.edit', $exam).'#exam-format' : null,
            'meta' => $formatTitle,
        ],
        [
            'key' => 'questions',
            'label' => __('Question Management'),
            'href' => $exam ? route('instructor.exams.question-types.index', $exam) : null,
            'meta' => $exam ? __(':count questions', ['count' => $questionCount ?? $exam->questions()->count()]) : __('Build exam'),
        ],
        [
            'key' => 'preview',
            'label' => __('Preview'),
            'href' => $exam ? route('instructor.exams.preview.show', $exam) : null,
            'meta' => __('Student view'),
        ],
        [
            'key' => 'publish',
            'label' => __('Publish'),
            'href' => $exam ? route('instructor.exams.publish.show', $exam) : null,
            'meta' => $exam ? __(ucfirst($exam->status)) : __('Final check'),
        ],
    ];

    $nextAction = null;

    if ($exam) {
        $nextAction = match ($active) {
            'information' => [
                'label' => __('Add questions'),
                'description' => __('The exam shell is ready. Add the content students will answer.'),
                'href' => route('instructor.exams.question-types.index', $exam),
            ],
            'questions' => [
                'label' => $questionCount > 0 ? __('Review order and marks') : __('Choose a question type'),
                'description' => $questionCount > 0
                    ? __('Check sequence and point totals before previewing.')
                    : __('Select the first question type to start building.'),
                'href' => $questionCount > 0
                    ? route('instructor.exams.questions.order.index', $exam)
                    : route('instructor.exams.question-types.index', $exam),
            ],
            'preview' => [
                'label' => __('Continue to publish'),
                'description' => __('Run the final readiness check when the student view looks correct.'),
                'href' => route('instructor.exams.publish.show', $exam),
            ],
            'publish' => [
                'label' => __('Preview exam'),
                'description' => __('Return to the student-facing preview before publishing changes.'),
                'href' => route('instructor.exams.preview.show', $exam),
            ],
            default => [
                'label' => __('Open workspace'),
                'description' => __('Continue editing this exam.'),
                'href' => route('instructor.exams.edit', $exam),
            ],
        };
    }
@endphp

<section class="mb-6 rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
    <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
        <div>
            <p class="text-sm font-semibold uppercase tracking-wide text-orange-600">{{ __('Exam workspace') }}</p>
            <h3 class="mt-1 text-lg font-semibold text-slate-950">{{ $exam?->title ?? __('Create a new exam') }}</h3>
            <p class="mt-1 text-sm text-slate-600">
                {{ $exam ? $exam->course->code.' - '.$exam->course->name : __('Complete each step in order, then publish when ready.') }}
            </p>
        </div>

        @if ($exam)
            <a href="{{ route('instructor.exams.create') }}"
                class="inline-flex items-center justify-center rounded-md border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-800 shadow-sm hover:bg-slate-50">
                {{ __('All exams') }}
            </a>
        @endif
    </div>

    <div class="mt-5 grid gap-3 sm:grid-cols-2 xl:grid-cols-5">
        @foreach ($tabs as $index => $tab)
            @if ($tab['href'])
                <a href="{{ $tab['href'] }}"
                    class="rounded-lg border px-4 py-3 transition hover:border-orange-300 hover:bg-orange-50 focus:outline-none focus:ring-2 focus:ring-orange-500 focus:ring-offset-2 {{ $active === $tab['key'] ? 'border-orange-300 bg-orange-50' : 'border-slate-200 bg-white' }}">
            @else
                <div class="rounded-lg border px-4 py-3 {{ $active === $tab['key'] ? 'border-orange-300 bg-orange-50' : 'border-slate-200 bg-slate-50 opacity-60' }}">
            @endif
                <div class="flex items-center justify-between gap-3">
                    <span class="font-semibold {{ $active === $tab['key'] ? 'text-orange-700' : 'text-slate-900' }}">
                        {{ $index + 1 }}. {{ $tab['label'] }}
                    </span>
                    <span class="text-xs font-medium text-slate-500">{{ $tab['meta'] }}</span>
                </div>
            @if ($tab['href'])
                </a>
            @else
                </div>
            @endif
        @endforeach
    </div>

    @if ($nextAction)
        <div class="mt-5 flex flex-col gap-3 rounded-xl border border-orange-200 bg-orange-50 px-4 py-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-sm font-semibold text-orange-900">{{ __('Recommended next step') }}</p>
                <p class="mt-1 text-sm leading-6 text-orange-800">{{ $nextAction['description'] }}</p>
            </div>
            <a href="{{ $nextAction['href'] }}" class="inline-flex shrink-0 items-center justify-center rounded-xl bg-orange-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-orange-700">
                {{ $nextAction['label'] }}
            </a>
        </div>
    @else
        <div class="mt-5 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm leading-6 text-slate-600">
            {{ __('Create the exam shell first. The next steps unlock automatically after saving.') }}
        </div>
    @endif
</section>
