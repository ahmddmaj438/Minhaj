@php
    $exam = $exam ?? null;
    $active = $active ?? 'information';
    $questionCount = $questionCount ?? null;
    $totalQuestionMarks = $totalQuestionMarks ?? null;

    $tabs = [
        [
            'key' => 'information',
            'label' => 'Exam Information',
            'href' => $exam ? route('instructor.exams.edit', $exam).'#exam-information' : null,
            'meta' => $exam ? $exam->duration_minutes.' min' : 'Start here',
        ],
        [
            'key' => 'format',
            'label' => 'Exam Format',
            'href' => $exam ? route('instructor.exams.edit', $exam).'#exam-format' : null,
            'meta' => $exam ? str($exam->display_format)->replace('_', ' ')->title()->toString() : 'Choose layout',
        ],
        [
            'key' => 'questions',
            'label' => 'Question Management',
            'href' => $exam ? route('instructor.exams.question-types.index', $exam) : null,
            'meta' => $exam ? (($questionCount ?? $exam->questions()->count()).' questions') : 'Build exam',
        ],
        [
            'key' => 'preview',
            'label' => 'Preview',
            'href' => $exam ? route('instructor.exams.preview.show', $exam) : null,
            'meta' => 'Student view',
        ],
        [
            'key' => 'publish',
            'label' => 'Publish',
            'href' => $exam ? route('instructor.exams.publish.show', $exam) : null,
            'meta' => $exam ? ucfirst($exam->status) : 'Final check',
        ],
    ];
@endphp

<section class="mb-6 rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
    <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
        <div>
            <p class="text-sm font-semibold uppercase tracking-wide text-orange-600">Exam workspace</p>
            <h3 class="mt-1 text-lg font-semibold text-slate-950">{{ $exam?->title ?? 'Create a new exam' }}</h3>
            <p class="mt-1 text-sm text-slate-600">
                {{ $exam ? $exam->course->code.' - '.$exam->course->name : 'Complete each step in order, then publish when ready.' }}
            </p>
        </div>

        @if ($exam)
            <a href="{{ route('instructor.exams.create') }}"
                class="inline-flex items-center justify-center rounded-md border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-800 shadow-sm hover:bg-slate-50">
                All exams
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
</section>
