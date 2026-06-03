@php
    $active = $active ?? 'settings';
    $questionCount = $questionCount ?? null;
    $totalQuestionMarks = $totalQuestionMarks ?? null;

    $tabs = [
        [
            'key' => 'settings',
            'label' => 'Settings',
            'href' => route('instructor.exams.edit', $exam),
            'meta' => $exam->duration_minutes . ' min',
        ],
        [
            'key' => 'questions',
            'label' => 'Questions',
            'href' => route('instructor.exams.question-types.index', $exam),
            'meta' => ($questionCount ?? $exam->questions()->count()) . ' selected',
        ],
        [
            'key' => 'order',
            'label' => 'Order',
            'href' => route('instructor.exams.questions.order.index', $exam),
            'meta' => $totalQuestionMarks === null ? 'Marks' : number_format($totalQuestionMarks, 2) . ' marks',
        ],
        [
            'key' => 'preview',
            'label' => 'Preview',
            'href' => route('instructor.exams.preview.show', $exam),
            'meta' => ucfirst($exam->status),
        ],
    ];
@endphp

<section class="mb-6 rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
    <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
        <div>
            <p class="text-sm font-semibold uppercase tracking-wide text-orange-600">Exam workspace</p>
            <h3 class="mt-1 text-lg font-semibold text-slate-950">{{ $exam->title }}</h3>
            <p class="mt-1 text-sm text-slate-600">{{ $exam->course->code }} - {{ $exam->course->name }}</p>
        </div>

        <a href="{{ route('instructor.exams.create') }}"
            class="inline-flex items-center justify-center rounded-md border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-800 shadow-sm hover:bg-slate-50">
            All exams
        </a>
    </div>

    <div class="mt-5 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
        @foreach ($tabs as $tab)
            <a href="{{ $tab['href'] }}"
                class="rounded-lg border px-4 py-3 transition hover:border-orange-300 hover:bg-orange-50 focus:outline-none focus:ring-2 focus:ring-orange-500 focus:ring-offset-2 {{ $active === $tab['key'] ? 'border-orange-300 bg-orange-50' : 'border-slate-200 bg-white' }}">
                <div class="flex items-center justify-between gap-3">
                    <span class="font-semibold {{ $active === $tab['key'] ? 'text-orange-700' : 'text-slate-900' }}">{{ $tab['label'] }}</span>
                    <span class="text-xs font-medium text-slate-500">{{ $tab['meta'] }}</span>
                </div>
            </a>
        @endforeach
    </div>
</section>
