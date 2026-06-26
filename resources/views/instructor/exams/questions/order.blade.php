<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-sm font-medium text-orange-600">Exam Builder</p>
                <h2 class="text-2xl font-semibold leading-tight text-slate-950">Question Ordering</h2>
            </div>
            <div class="text-sm text-slate-500">Step 4 of 5: Review</div>
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
                    <p class="font-semibold">Please review the ordering details.</p>
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
                'questionCount' => $questions->count(),
                'totalQuestionMarks' => $totalQuestionMarks,
            ])

            <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_360px]">
                <section class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
                    <div class="flex flex-col gap-4 border-b border-slate-100 pb-5 md:flex-row md:items-start md:justify-between">
                        <div>
                            <p class="text-sm font-semibold uppercase tracking-wide text-orange-600">{{ __('Step 4: Review') }}</p>
                            <h3 class="mt-1 text-xl font-semibold text-slate-950">{{ __('Arrange questions and marks') }}</h3>
                            <p class="mt-2 text-sm text-slate-600">{{ __('This list controls the order students will see and the marks each question carries.') }}</p>
                        </div>
                        <div class="flex flex-wrap gap-2">
                            <a href="{{ route('instructor.exams.question-types.index', $exam) }}"
                                class="inline-flex items-center justify-center rounded-md border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-800 shadow-sm hover:bg-slate-50">
                                {{ __('Add Question') }}
                            </a>
                            <a href="{{ route('instructor.exams.preview.show', $exam) }}"
                                class="inline-flex items-center justify-center rounded-md bg-slate-950 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-orange-600">
                                {{ __('Preview as Student') }}
                            </a>
                        </div>
                    </div>

                    @if ($questions->isEmpty())
                        <div class="mt-6 rounded-lg border border-dashed border-slate-300 bg-slate-50 p-8 text-center">
                            <h4 class="text-base font-semibold text-slate-950">No questions yet</h4>
                            <p class="mt-2 text-sm text-slate-600">Add a question type before ordering the exam.</p>
                        </div>
                    @else
                        <form id="order-form" method="POST" action="{{ route('instructor.exams.questions.order.update', $exam) }}" class="mt-6 space-y-4">
                            @csrf
                            @method('PATCH')

                            @foreach ($questions as $index => $question)
                                @php
                                    $typeLabel = str($question->type)->replace('_', ' ')->title();
                                    $questionText = $question->prompt['question_text'] ?? $question->prompt['statement'] ?? $question->title;
                                @endphp

                                <article class="rounded-lg border border-slate-200 bg-slate-50 p-4">
                                    <input type="hidden" name="questions[{{ $index }}][id]" value="{{ $question->id }}">

                                    <div class="grid gap-4 lg:grid-cols-[110px_120px_minmax(0,1fr)_auto] lg:items-start">
                                        <div>
                                            <label class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Position</label>
                                            <input type="number" min="1" max="500" name="questions[{{ $index }}][position]" value="{{ old("questions.$index.position", $question->position) }}"
                                                class="mt-1 block w-full rounded-md border-slate-300 bg-white shadow-sm focus:border-orange-500 focus:ring-orange-500">
                                        </div>

                                        <div>
                                            <label class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Marks</label>
                                            <input type="number" step="0.25" min="0.25" name="questions[{{ $index }}][marks]" value="{{ old("questions.$index.marks", $question->marks) }}"
                                                class="mt-1 block w-full rounded-md border-slate-300 bg-white shadow-sm focus:border-orange-500 focus:ring-orange-500">
                                        </div>

                                        <div>
                                            <div class="flex flex-wrap items-center gap-2">
                                                <span class="rounded-full bg-slate-900 px-2.5 py-1 text-xs font-semibold text-white">#{{ $question->position }}</span>
                                                <span class="rounded-full bg-orange-100 px-2.5 py-1 text-xs font-semibold text-orange-700">{{ $typeLabel }}</span>
                                                @if ($question->difficulty)
                                                    <span class="rounded-full bg-slate-200 px-2.5 py-1 text-xs font-medium text-slate-700">{{ ucfirst($question->difficulty) }}</span>
                                                @endif
                                            </div>
                                            <h4 class="mt-3 text-base font-semibold text-slate-950">{{ $question->title }}</h4>
                                            <p class="mt-2 text-sm leading-6 text-slate-600">{{ $questionText }}</p>
                                            @if ($question->topic)
                                                <p class="mt-2 text-xs font-medium uppercase tracking-wide text-slate-500">{{ $question->topic }}</p>
                                            @endif
                                        </div>

                                        <div class="flex flex-wrap gap-2 lg:flex-col">
                                            <a href="{{ $editRoutes[$question->id] }}"
                                                class="inline-flex items-center justify-center rounded-md border border-slate-300 bg-white px-3 py-2 text-sm font-semibold text-slate-700 shadow-sm hover:bg-slate-100">
                                                {{ __('Edit') }}
                                            </a>
                                            @can('button.instructor.exams.questions.order.duplicate')
                                                <button type="submit" form="duplicate-question-{{ $question->id }}"
                                                    class="inline-flex items-center justify-center rounded-md border border-slate-300 bg-white px-3 py-2 text-sm font-semibold text-slate-700 shadow-sm hover:bg-slate-100">
                                                    {{ __('Duplicate') }}
                                                </button>
                                            @endcan
                                            <button type="submit" form="delete-question-{{ $question->id }}"
                                                class="inline-flex items-center justify-center rounded-md border border-red-200 bg-white px-3 py-2 text-sm font-semibold text-red-700 shadow-sm hover:bg-red-50">
                                                {{ __('Remove') }}
                                            </button>
                                        </div>
                                    </div>
                                </article>
                            @endforeach
                        </form>

                        <div class="mt-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <p class="text-sm text-slate-600">{{ __('Positions are cleaned up after saving, so the student view stays numbered correctly.') }}</p>
                            <button type="submit" form="order-form"
                                class="inline-flex items-center justify-center rounded-md bg-orange-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-orange-700 focus:outline-none focus:ring-2 focus:ring-orange-500 focus:ring-offset-2">
                                {{ __('Save Changes') }}
                            </button>
                        </div>

                        @foreach ($questions as $question)
                            <form id="duplicate-question-{{ $question->id }}" method="POST" action="{{ route('instructor.exams.questions.duplicate', [$exam, $question]) }}">
                                @csrf
                            </form>
                            <form id="delete-question-{{ $question->id }}" method="POST" action="{{ route('instructor.exams.questions.destroy', [$exam, $question]) }}"
                                onsubmit="return confirm('{{ __('Remove this question from the exam?') }}')">
                                @csrf
                                @method('DELETE')
                            </form>
                        @endforeach
                    @endif
                </section>

                @include('instructor.exams.partials.exam-map', [
                    'exam' => $exam,
                    'questions' => $questions,
                    'totalQuestionMarks' => $totalQuestionMarks,
                    'editRoutes' => $editRoutes,
                ])
            </div>
        </div>
    </div>
</x-app-layout>
