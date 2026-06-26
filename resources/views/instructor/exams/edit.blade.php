@php
    $programNames = $exam->course?->majors?->pluck('name')->filter()->join(', ');
    $programLabel = $programNames ?: __('Program not linked yet');
    $totalQuestionMarks = (float) $totalQuestionMarks;
    $marksMatch = abs($totalQuestionMarks - (float) $exam->total_marks) < 0.01;
    $headerHasErrors = $errors->has('title')
        || $errors->has('course_id')
        || $errors->has('duration_minutes')
        || $errors->has('total_marks')
        || $errors->has('starts_at')
        || $errors->has('ends_at');
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-sm font-medium text-orange-600">{{ __('Exam Builder') }}</p>
                <h2 class="text-2xl font-semibold leading-tight text-slate-950">{{ __('Build the student exam page') }}</h2>
                <p class="mt-1 text-sm text-slate-500">{{ __('Edit the exam in the same order students will experience it.') }}</p>
            </div>
            <a href="{{ route('instructor.exams.preview.show', $exam) }}"
                class="inline-flex items-center justify-center rounded-md bg-slate-950 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-orange-600">
                {{ __('Preview as Student') }}
            </a>
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
                    <p class="font-semibold">{{ __('Please review the section with the warning.') }}</p>
                    <ul class="mt-2 list-disc space-y-1 ps-5">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @include('instructor.exams.partials.workspace-nav', [
                'exam' => $exam,
                'active' => 'information',
                'questionCount' => $questions->count(),
                'totalQuestionMarks' => $totalQuestionMarks,
            ])

            <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_360px]">
                <div class="space-y-6">
                    <form method="POST" action="{{ route('instructor.exams.update', $exam) }}" class="space-y-6">
                        @csrf
                        @method('PUT')

                        <section id="exam-header" class="scroll-mt-24 overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
                            <div class="border-b border-slate-100 bg-slate-50 px-6 py-4">
                                <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                                    <div>
                                        <p class="text-sm font-semibold uppercase tracking-wide text-orange-600">{{ __('Step 1: Exam Header') }}</p>
                                        <h3 class="mt-1 text-lg font-semibold text-slate-950">{{ __('Exam information shown at the top') }}</h3>
                                    </div>
                                    <span class="w-fit rounded-full px-3 py-1 text-xs font-semibold {{ $headerHasErrors ? 'bg-amber-100 text-amber-800' : 'bg-emerald-100 text-emerald-700' }}">
                                        {{ $headerHasErrors ? __('Needs review') : __('Header ready') }}
                                    </span>
                                </div>
                            </div>

                            <div class="bg-white p-6">
                                <div class="rounded-xl border border-slate-200 bg-slate-50 p-5">
                                    <div class="flex flex-col gap-5 md:flex-row md:items-start md:justify-between">
                                        <div class="min-w-0">
                                            <label for="title" class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Exam title') }}</label>
                                            <input id="title" name="title" value="{{ old('title', $exam->title) }}" required
                                                class="mt-2 block w-full border-0 bg-transparent p-0 text-2xl font-semibold text-slate-950 placeholder:text-slate-400 focus:border-transparent focus:ring-0"
                                                placeholder="{{ __('Example: Midterm Exam') }}">
                                            <x-input-error :messages="$errors->get('title')" class="mt-2" />
                                            <p class="mt-2 text-sm text-slate-600">{{ $exam->course?->code }} - {{ $exam->course?->name }}</p>
                                        </div>

                                        <div class="rounded-lg bg-orange-50 px-4 py-3 text-sm text-orange-900">
                                            <p class="font-semibold">{{ __('Student exam header') }}</p>
                                            <p class="mt-1">{{ __('This is the first information students will see.') }}</p>
                                        </div>
                                    </div>

                                    <div class="mt-5 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                                        <div class="rounded-lg border border-slate-200 bg-white p-4">
                                            <label for="course_id" class="block text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Course name') }}</label>
                                            <select id="course_id" name="course_id" required
                                                class="mt-2 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-orange-500 focus:ring-orange-500">
                                                @foreach ($courses as $course)
                                                    <option value="{{ $course->id }}" @selected(old('course_id', $exam->course_id) == $course->id)>
                                                        {{ $course->code }} - {{ $course->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            <x-input-error :messages="$errors->get('course_id')" class="mt-2" />
                                        </div>

                                        <div class="rounded-lg border border-slate-200 bg-white p-4">
                                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Program') }}</p>
                                            <p class="mt-2 text-sm font-semibold text-slate-900">{{ $programLabel }}</p>
                                            <p class="mt-1 text-xs text-slate-500">{{ __('Linked through course setup') }}</p>
                                        </div>

                                        <div class="rounded-lg border border-slate-200 bg-white p-4">
                                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Exam type') }}</p>
                                            <p class="mt-2 text-sm font-semibold text-slate-900">{{ __('Prepared exam') }}</p>
                                            <p class="mt-1 text-xs text-slate-500">{{ __('Configured in this builder') }}</p>
                                        </div>

                                        <div class="rounded-lg border border-slate-200 bg-white p-4">
                                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Academic period') }}</p>
                                            <p class="mt-2 text-sm font-semibold text-slate-900">{{ __('Set by assignment schedule') }}</p>
                                            <p class="mt-1 text-xs text-slate-500">{{ __('Adjust availability below') }}</p>
                                        </div>
                                    </div>

                                    <div class="mt-4 grid gap-4 md:grid-cols-2 xl:grid-cols-5">
                                        <div class="rounded-lg border border-slate-200 bg-white p-4">
                                            <label for="total_marks" class="block text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Total mark') }}</label>
                                            <input id="total_marks" type="number" step="0.01" min="1" name="total_marks" value="{{ old('total_marks', $exam->total_marks) }}" required
                                                class="mt-2 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-orange-500 focus:ring-orange-500">
                                            <x-input-error :messages="$errors->get('total_marks')" class="mt-2" />
                                        </div>

                                        <div class="rounded-lg border border-slate-200 bg-white p-4">
                                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Passing mark') }}</p>
                                            <p class="mt-2 text-sm font-semibold text-slate-900">{{ __('Set by academic policy') }}</p>
                                        </div>

                                        <div class="rounded-lg border border-slate-200 bg-white p-4">
                                            <label for="duration_minutes" class="block text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Duration') }}</label>
                                            <input id="duration_minutes" type="number" name="duration_minutes" value="{{ old('duration_minutes', $exam->duration_minutes) }}" min="5" max="600" required
                                                class="mt-2 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-orange-500 focus:ring-orange-500">
                                            <x-input-error :messages="$errors->get('duration_minutes')" class="mt-2" />
                                        </div>

                                        <div class="rounded-lg border border-slate-200 bg-white p-4">
                                            <label for="starts_at" class="block text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Start time') }}</label>
                                            <input id="starts_at" type="datetime-local" name="starts_at" value="{{ old('starts_at', $exam->starts_at?->format('Y-m-d\TH:i')) }}"
                                                class="mt-2 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-orange-500 focus:ring-orange-500">
                                            <x-input-error :messages="$errors->get('starts_at')" class="mt-2" />
                                        </div>

                                        <div class="rounded-lg border border-slate-200 bg-white p-4">
                                            <label for="ends_at" class="block text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('End time') }}</label>
                                            <input id="ends_at" type="datetime-local" name="ends_at" value="{{ old('ends_at', $exam->ends_at?->format('Y-m-d\TH:i')) }}"
                                                class="mt-2 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-orange-500 focus:ring-orange-500">
                                            <x-input-error :messages="$errors->get('ends_at')" class="mt-2" />
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </section>

                        <section id="exam-instructions" class="scroll-mt-24 rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
                            <div class="flex flex-col gap-2 border-b border-slate-100 pb-5 sm:flex-row sm:items-start sm:justify-between">
                                <div>
                                    <p class="text-sm font-semibold uppercase tracking-wide text-orange-600">{{ __('Step 2: Instructions') }}</p>
                                    <h3 class="mt-1 text-lg font-semibold text-slate-950">{{ __('What students read before answering') }}</h3>
                                    <p class="mt-2 text-sm leading-6 text-slate-600">{{ __('Write clear rules, time expectations, allowed resources, and submission notes.') }}</p>
                                </div>
                                <span class="w-fit rounded-full px-3 py-1 text-xs font-semibold {{ filled(old('description', $exam->description)) ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-800' }}">
                                    {{ filled(old('description', $exam->description)) ? __('Instructions ready') : __('Add instructions') }}
                                </span>
                            </div>

                            <div class="mt-6 rounded-xl border border-orange-200 bg-orange-50 p-5">
                                <label for="description" class="block text-sm font-semibold text-orange-950">{{ __('Exam Instructions') }}</label>
                                <textarea id="description" name="description" rows="7"
                                    class="mt-3 block w-full rounded-md border-orange-200 bg-white shadow-sm focus:border-orange-500 focus:ring-orange-500"
                                    placeholder="{{ __('Example: Read each question carefully. Save your answers before submitting. The exam closes when time ends.') }}">{{ old('description', $exam->description) }}</textarea>
                                <x-input-error :messages="$errors->get('description')" class="mt-2" />
                                <div class="mt-4 grid gap-3 text-sm text-orange-900 md:grid-cols-3">
                                    <div class="rounded-md bg-white/70 px-3 py-2">{{ __('Time rule') }}: {{ __(':count minutes', ['count' => old('duration_minutes', $exam->duration_minutes)]) }}</div>
                                    <div class="rounded-md bg-white/70 px-3 py-2">{{ __('Allowed attempts') }}: {{ __('Controlled by assignment') }}</div>
                                    <div class="rounded-md bg-white/70 px-3 py-2">{{ __('Late submission') }}: {{ __('Controlled by availability') }}</div>
                                </div>
                            </div>
                        </section>

                        <details id="advanced-options" class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
                            <summary class="cursor-pointer text-base font-semibold text-slate-950">{{ __('Advanced Options') }}</summary>
                            <div class="mt-5 border-t border-slate-100 pt-5">
                                <p class="text-sm leading-6 text-slate-600">{{ __('Choose how students move between questions. Most teachers can leave this unchanged.') }}</p>
                                <div class="mt-5">
                                    @include('instructor.exams.partials.format-selector', [
                                        'formats' => $displayFormats,
                                        'selected' => $exam->display_format ?? \App\Models\Exam\InstructorExam::FORMAT_ONE_QUESTION_AT_TIME,
                                    ])
                                    <x-input-error :messages="$errors->get('display_format')" class="mt-3" />
                                </div>
                            </div>
                        </details>

                        <div class="flex flex-col gap-3 rounded-lg border border-slate-200 bg-white p-5 shadow-sm sm:flex-row sm:items-center sm:justify-between">
                            <a href="{{ route('instructor.exams.create') }}"
                                class="inline-flex items-center justify-center rounded-md border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-800 shadow-sm hover:bg-slate-50">
                                {{ __('Back to Exams') }}
                            </a>
                            <button type="submit"
                                class="inline-flex items-center justify-center rounded-md bg-orange-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-orange-700 focus:outline-none focus:ring-2 focus:ring-orange-500 focus:ring-offset-2">
                                {{ __('Save Changes') }}
                            </button>
                        </div>
                    </form>

                    <section id="questions" class="scroll-mt-24 rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
                        <div class="flex flex-col gap-3 border-b border-slate-100 pb-5 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <p class="text-sm font-semibold uppercase tracking-wide text-orange-600">{{ __('Step 3: Questions') }}</p>
                                <h3 class="mt-1 text-lg font-semibold text-slate-950">{{ __('Arrange the exam body') }}</h3>
                                <p class="mt-2 text-sm leading-6 text-slate-600">{{ __('Question cards show what students answer and what teachers use for grading.') }}</p>
                            </div>
                            <a href="{{ route('instructor.exams.question-types.index', $exam) }}"
                                class="inline-flex items-center justify-center rounded-md bg-slate-950 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-orange-600">
                                {{ __('Add Question') }}
                            </a>
                        </div>

                        @unless ($marksMatch)
                            <div class="mt-5 rounded-md border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
                                {{ __('Question marks do not match the exam total yet. Adjust marks in the question cards or order screen.') }}
                            </div>
                        @endunless

                        <div class="mt-5 space-y-4">
                            @forelse ($questions as $question)
                                @include('instructor.exams.partials.builder-question-card', [
                                    'exam' => $exam,
                                    'question' => $question,
                                    'questionEditRoutes' => $questionEditRoutes,
                                ])
                            @empty
                                <div class="rounded-lg border border-dashed border-slate-300 bg-slate-50 p-10 text-center">
                                    <h4 class="text-lg font-semibold text-slate-950">{{ __('No questions yet') }}</h4>
                                    <p class="mt-2 text-sm text-slate-600">{{ __('Start with one question. You can duplicate, edit, and reorder later.') }}</p>
                                    <a href="{{ route('instructor.exams.question-types.index', $exam) }}"
                                        class="mt-5 inline-flex items-center justify-center rounded-md bg-orange-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-orange-700">
                                        {{ __('Add Question') }}
                                    </a>
                                </div>
                            @endforelse
                        </div>
                    </section>

                    @foreach ($questions as $question)
                        <form id="duplicate-question-{{ $question->id }}" method="POST" action="{{ route('instructor.exams.questions.duplicate', [$exam, $question]) }}">
                            @csrf
                        </form>
                        <form id="remove-question-{{ $question->id }}" method="POST" action="{{ route('instructor.exams.questions.destroy', [$exam, $question]) }}"
                            onsubmit="return confirm('{{ __('Remove this question from the exam?') }}')">
                            @csrf
                            @method('DELETE')
                        </form>
                    @endforeach
                </div>

                @include('instructor.exams.partials.exam-map', [
                    'exam' => $exam,
                    'questions' => $questions,
                    'totalQuestionMarks' => $totalQuestionMarks,
                    'questionEditRoutes' => $questionEditRoutes,
                ])
            </div>
        </div>
    </div>
</x-app-layout>
