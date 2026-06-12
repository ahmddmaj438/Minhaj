<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-sm font-medium text-orange-600">Exam Builder</p>
                <h2 class="text-2xl font-semibold leading-tight text-slate-950">Publish Exam</h2>
            </div>
            <span class="w-fit rounded-full px-3 py-1 text-xs font-semibold capitalize {{ $exam->status === \App\Models\Exam\InstructorExam::STATUS_PUBLISHED ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-200 text-slate-700' }}">
                {{ $exam->status }}
            </span>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="mb-6 rounded-md border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800">
                    {{ session('status') }}
                </div>
            @endif

            @if ($errors->has('publish'))
                <div class="mb-6 rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-800">
                    {{ $errors->first('publish') }}
                </div>
            @endif

            @include('instructor.exams.partials.workspace-nav', [
                'exam' => $exam,
                'active' => 'publish',
                'questionCount' => $readiness['question_count'],
                'totalQuestionMarks' => $readiness['question_marks'],
            ])

            <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_340px]">
                <section class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                    <div class="border-b border-slate-100 pb-5">
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-orange-700">Final check</p>
                        <h3 class="mt-1 text-xl font-semibold text-slate-950">Is this exam ready for students?</h3>
                        <p class="mt-2 text-sm leading-6 text-slate-600">
                            Review each item below. Publishing changes the exam status but does not create student assignments automatically.
                        </p>
                    </div>

                    <div class="mt-6 space-y-3">
                        @foreach ($readiness['checks'] as $check)
                            <a href="{{ $check['action'] }}"
                                class="flex flex-col gap-3 rounded-lg border p-4 transition sm:flex-row sm:items-center sm:justify-between {{ $check['passed'] ? 'border-emerald-200 bg-emerald-50/70 hover:border-emerald-300' : 'border-amber-200 bg-amber-50/70 hover:border-amber-300' }}">
                                <div class="flex gap-3">
                                    <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-sm font-bold {{ $check['passed'] ? 'bg-emerald-600 text-white' : 'bg-amber-500 text-white' }}">
                                        {{ $check['passed'] ? 'OK' : '!' }}
                                    </span>
                                    <div>
                                        <h4 class="font-semibold text-slate-950">{{ $check['label'] }}</h4>
                                        <p class="mt-1 text-sm leading-6 text-slate-600">{{ $check['description'] }}</p>
                                    </div>
                                </div>
                                <span class="shrink-0 text-sm font-semibold {{ $check['passed'] ? 'text-emerald-700' : 'text-amber-800' }}">
                                    {{ $check['passed'] ? 'Ready' : 'Review' }}
                                </span>
                            </a>
                        @endforeach
                    </div>
                </section>

                <aside class="space-y-6">
                    <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-orange-700">Exam summary</p>
                        <h3 class="mt-1 text-lg font-semibold text-slate-950">{{ $exam->title }}</h3>
                        <dl class="mt-5 space-y-3 text-sm">
                            <div class="flex justify-between gap-4">
                                <dt class="text-slate-500">Course</dt>
                                <dd class="font-semibold text-slate-900">{{ $exam->course->code }}</dd>
                            </div>
                            <div class="flex justify-between gap-4">
                                <dt class="text-slate-500">Questions</dt>
                                <dd class="font-semibold text-slate-900">{{ $readiness['question_count'] }}</dd>
                            </div>
                            <div class="flex justify-between gap-4">
                                <dt class="text-slate-500">Marks</dt>
                                <dd class="font-semibold text-slate-900">{{ number_format($readiness['question_marks'], 2) }} / {{ number_format($readiness['exam_marks'], 2) }}</dd>
                            </div>
                            <div class="flex justify-between gap-4">
                                <dt class="text-slate-500">Published</dt>
                                <dd class="font-semibold text-slate-900">{{ $exam->published_at?->format('Y-m-d H:i') ?? 'Not yet' }}</dd>
                            </div>
                        </dl>
                    </section>

                    <section class="rounded-xl border p-5 shadow-sm {{ $readiness['ready'] ? 'border-emerald-200 bg-emerald-50' : 'border-amber-200 bg-amber-50' }}">
                        @if ($exam->status === \App\Models\Exam\InstructorExam::STATUS_PUBLISHED)
                            <h3 class="font-semibold text-emerald-900">This exam is published</h3>
                            <p class="mt-2 text-sm leading-6 text-emerald-800">Return it to draft when changes need another review cycle.</p>
                            @can('button.instructor.exams.unpublish')
                                <form method="POST" action="{{ route('instructor.exams.publish.draft', $exam) }}" class="mt-4">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit"
                                        class="inline-flex w-full items-center justify-center rounded-md border border-emerald-300 bg-white px-4 py-2 text-sm font-semibold text-emerald-800 shadow-sm hover:bg-emerald-100">
                                        Return to draft
                                    </button>
                                </form>
                            @endcan
                        @elseif ($readiness['ready'])
                            <h3 class="font-semibold text-emerald-900">Ready to publish</h3>
                            <p class="mt-2 text-sm leading-6 text-emerald-800">All required checks passed.</p>
                            @can('button.instructor.exams.publish')
                                <form method="POST" action="{{ route('instructor.exams.publish.store', $exam) }}" class="mt-4"
                                    onsubmit="return confirm('Publish this exam?')">
                                    @csrf
                                    <button type="submit"
                                        class="inline-flex w-full items-center justify-center rounded-md bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-emerald-700">
                                        Publish exam
                                    </button>
                                </form>
                            @endcan
                        @else
                            <h3 class="font-semibold text-amber-900">Not ready yet</h3>
                            <p class="mt-2 text-sm leading-6 text-amber-800">Open each item marked Review, complete it, then return here.</p>
                            <button type="button" disabled
                                class="mt-4 inline-flex w-full cursor-not-allowed items-center justify-center rounded-md bg-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-600">
                                Complete readiness checks
                            </button>
                        @endif
                    </section>
                </aside>
            </div>
        </div>
    </div>
</x-app-layout>
