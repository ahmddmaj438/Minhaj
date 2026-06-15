<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-sm font-medium text-orange-600">Exam Builder</p>
                <h2 class="text-2xl font-semibold leading-tight text-slate-950">{{ $exam->title }}</h2>
            </div>
            <div class="text-sm text-slate-500">Exam workspace</div>
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
                    <p class="font-semibold">Please review the highlighted fields.</p>
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

            <div class="mb-6 grid gap-4 md:grid-cols-4">
                <a href="{{ route('instructor.exams.question-types.index', $exam) }}"
                    class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm transition hover:border-orange-300 hover:shadow-md">
                    <p class="text-sm font-semibold text-orange-600">Questions</p>
                    <p class="mt-2 text-2xl font-semibold text-slate-950">{{ $questions->count() }}</p>
                    <p class="mt-1 text-sm text-slate-600">Add question types</p>
                </a>
                <a href="{{ route('instructor.exams.questions.order.index', $exam) }}"
                    class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm transition hover:border-orange-300 hover:shadow-md">
                    <p class="text-sm font-semibold text-orange-600">Ordering</p>
                    <p class="mt-2 text-2xl font-semibold text-slate-950">{{ number_format($totalQuestionMarks, 2) }}</p>
                    <p class="mt-1 text-sm text-slate-600">Question marks total</p>
                </a>
                <a href="{{ route('instructor.exams.preview.show', $exam) }}"
                    class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm transition hover:border-orange-300 hover:shadow-md">
                    <p class="text-sm font-semibold text-orange-600">Preview</p>
                    <p class="mt-2 text-2xl font-semibold text-slate-950">{{ $exam->duration_minutes }}</p>
                    <p class="mt-1 text-sm text-slate-600">Minutes duration</p>
                </a>
                <div class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
                    <p class="text-sm font-semibold text-orange-600">Status</p>
                    <p class="mt-2 text-2xl font-semibold capitalize text-slate-950">{{ $exam->status }}</p>
                    <p class="mt-1 text-sm text-slate-600">{{ $exam->course->code }} - {{ $exam->course->name }}</p>
                </div>
            </div>

            <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_360px]">
                <div class="space-y-6">
                    <form method="POST" action="{{ route('instructor.exams.update', $exam) }}" class="space-y-6">
                        @csrf
                        @method('PUT')

                        <section id="exam-information" class="scroll-mt-24 rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
                            <div class="border-b border-slate-100 pb-5">
                                <p class="text-sm font-semibold uppercase tracking-wide text-orange-600">Exam settings</p>
                                <h3 class="mt-1 text-lg font-semibold text-slate-950">Edit the exam shell</h3>
                            </div>

                            <div class="mt-6 grid gap-5">
                                <div>
                                    <label for="title" class="block text-sm font-medium text-slate-800">Exam title</label>
                                    <input id="title" name="title" value="{{ old('title', $exam->title) }}" required
                                        class="mt-2 block w-full rounded-md border-slate-300 shadow-sm focus:border-orange-500 focus:ring-orange-500">
                                    <x-input-error :messages="$errors->get('title')" class="mt-2" />
                                </div>

                                <div>
                                    <label for="course_id" class="block text-sm font-medium text-slate-800">Course</label>
                                    <select id="course_id" name="course_id" required
                                        class="mt-2 block w-full rounded-md border-slate-300 shadow-sm focus:border-orange-500 focus:ring-orange-500">
                                        @foreach ($courses as $course)
                                            <option value="{{ $course->id }}" @selected(old('course_id', $exam->course_id) == $course->id)>
                                                {{ $course->code }} - {{ $course->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <x-input-error :messages="$errors->get('course_id')" class="mt-2" />
                                </div>

                                <div>
                                    <label for="description" class="block text-sm font-medium text-slate-800">Description</label>
                                    <textarea id="description" name="description" rows="5"
                                        class="mt-2 block w-full rounded-md border-slate-300 shadow-sm focus:border-orange-500 focus:ring-orange-500">{{ old('description', $exam->description) }}</textarea>
                                    <x-input-error :messages="$errors->get('description')" class="mt-2" />
                                </div>
                            </div>
                        </section>

                        <section class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
                            <div class="border-b border-slate-100 pb-5">
                                <p class="text-sm font-semibold uppercase tracking-wide text-orange-600">Timing and marks</p>
                                <h3 class="mt-1 text-lg font-semibold text-slate-950">Adjust delivery settings</h3>
                            </div>

                            <div class="mt-6 grid gap-5 md:grid-cols-2">
                                <div>
                                    <label for="duration_minutes" class="block text-sm font-medium text-slate-800">Duration</label>
                                    <div class="mt-2 flex rounded-md shadow-sm">
                                        <input id="duration_minutes" type="number" name="duration_minutes" value="{{ old('duration_minutes', $exam->duration_minutes) }}" min="5" max="600" required
                                            class="block w-full ltr:rounded-l-md rtl:rounded-r-md border-slate-300 focus:border-orange-500 focus:ring-orange-500">
                                        <span class="inline-flex items-center border border-slate-300 bg-slate-50 px-3 text-sm text-slate-600 ltr:rounded-r-md ltr:border-l-0 rtl:rounded-l-md rtl:border-r-0">minutes</span>
                                    </div>
                                    <x-input-error :messages="$errors->get('duration_minutes')" class="mt-2" />
                                </div>

                                <div>
                                    <label for="total_marks" class="block text-sm font-medium text-slate-800">Total marks</label>
                                    <input id="total_marks" type="number" step="0.01" min="1" name="total_marks" value="{{ old('total_marks', $exam->total_marks) }}" required
                                        class="mt-2 block w-full rounded-md border-slate-300 shadow-sm focus:border-orange-500 focus:ring-orange-500">
                                    <x-input-error :messages="$errors->get('total_marks')" class="mt-2" />
                                </div>

                                <div>
                                    <label for="starts_at" class="block text-sm font-medium text-slate-800">Start date and time</label>
                                    <input id="starts_at" type="datetime-local" name="starts_at" value="{{ old('starts_at', $exam->starts_at?->format('Y-m-d\TH:i')) }}"
                                        class="mt-2 block w-full rounded-md border-slate-300 shadow-sm focus:border-orange-500 focus:ring-orange-500">
                                    <x-input-error :messages="$errors->get('starts_at')" class="mt-2" />
                                </div>

                                <div>
                                    <label for="ends_at" class="block text-sm font-medium text-slate-800">End date and time</label>
                                    <input id="ends_at" type="datetime-local" name="ends_at" value="{{ old('ends_at', $exam->ends_at?->format('Y-m-d\TH:i')) }}"
                                        class="mt-2 block w-full rounded-md border-slate-300 shadow-sm focus:border-orange-500 focus:ring-orange-500">
                                    <x-input-error :messages="$errors->get('ends_at')" class="mt-2" />
                                </div>
                            </div>
                        </section>

                        <section id="exam-format" class="scroll-mt-24 rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
                            <div class="border-b border-slate-100 pb-5">
                                <p class="text-sm font-semibold uppercase tracking-wide text-orange-600">Exam Format</p>
                                <h3 class="mt-1 text-lg font-semibold text-slate-950">How students will move through the exam</h3>
                                <p class="mt-2 text-sm leading-6 text-slate-600">
                                    Pick the layout that best fits this exam. The preview and student exam page will use this choice.
                                </p>
                            </div>

                            <div class="mt-6">
                                @include('instructor.exams.partials.format-selector', [
                                    'formats' => $displayFormats,
                                    'selected' => $exam->display_format ?? \App\Models\Exam\InstructorExam::FORMAT_ONE_QUESTION_AT_TIME,
                                ])
                                <x-input-error :messages="$errors->get('display_format')" class="mt-3" />
                            </div>
                        </section>

                        <section class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
                            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                <a href="{{ route('instructor.exams.create') }}"
                                    class="inline-flex items-center justify-center rounded-md border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-800 shadow-sm hover:bg-slate-50">
                                    Back to exams
                                </a>
                                <button type="submit"
                                    class="inline-flex items-center justify-center rounded-md bg-orange-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-orange-700 focus:outline-none focus:ring-2 focus:ring-orange-500 focus:ring-offset-2">
                                    Save exam settings
                                </button>
                            </div>
                        </section>
                    </form>

                    <section class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
                        <div class="flex flex-col gap-3 border-b border-slate-100 pb-5 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <p class="text-sm font-semibold uppercase tracking-wide text-orange-600">Question details</p>
                                <h3 class="mt-1 text-lg font-semibold text-slate-950">Edit questions in this exam</h3>
                            </div>
                            <a href="{{ route('instructor.exams.question-types.index', $exam) }}"
                                class="inline-flex items-center justify-center rounded-md bg-slate-950 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-orange-600">
                                Add question
                            </a>
                        </div>

                        @forelse ($questions as $question)
                            @php
                                $questionText = $question->prompt['question_text'] ?? $question->prompt['statement'] ?? $question->title;
                                $typeLabel = str($question->type)->replace('_', ' ')->title();
                            @endphp
                            <article class="mt-4 rounded-lg border border-slate-200 bg-slate-50 p-4">
                                <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
                                    <div>
                                        <div class="flex flex-wrap items-center gap-2">
                                            <span class="rounded-full bg-slate-900 px-2.5 py-1 text-xs font-semibold text-white">#{{ $question->position }}</span>
                                            <span class="rounded-full bg-orange-100 px-2.5 py-1 text-xs font-semibold text-orange-700">{{ $typeLabel }}</span>
                                            <span class="rounded-full bg-slate-200 px-2.5 py-1 text-xs font-semibold text-slate-700">{{ $question->marks }} marks</span>
                                        </div>
                                        <h4 class="mt-3 text-base font-semibold text-slate-950">{{ $question->title }}</h4>
                                        <p class="mt-2 text-sm leading-6 text-slate-600">{{ $questionText }}</p>
                                    </div>
                                    <a href="{{ $questionEditRoutes[$question->id] }}"
                                        class="inline-flex shrink-0 items-center justify-center rounded-md border border-slate-300 bg-white px-3 py-2 text-sm font-semibold text-slate-700 shadow-sm hover:bg-slate-100">
                                        Edit question
                                    </a>
                                </div>
                            </article>
                        @empty
                            <div class="mt-5 rounded-lg border border-dashed border-slate-300 bg-slate-50 p-8 text-center">
                                <h4 class="text-base font-semibold text-slate-950">No questions yet</h4>
                                <p class="mt-2 text-sm text-slate-600">Add a question type to begin building the exam body.</p>
                            </div>
                        @endforelse
                    </section>
                </div>

                <aside class="space-y-6">
                    <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                        <h3 class="text-base font-semibold text-slate-950">Workspace actions</h3>
                        <div class="mt-4 grid gap-3">
                            <a href="{{ route('instructor.exams.question-types.index', $exam) }}"
                                class="inline-flex items-center justify-center rounded-md bg-orange-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-orange-700">
                                Add questions
                            </a>
                            <a href="{{ route('instructor.exams.questions.order.index', $exam) }}"
                                class="inline-flex items-center justify-center rounded-md border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-800 shadow-sm hover:bg-slate-50">
                                Manage order and marks
                            </a>
                            <a href="{{ route('instructor.exams.preview.show', $exam) }}"
                                class="inline-flex items-center justify-center rounded-md border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-800 shadow-sm hover:bg-slate-50">
                                Preview exam
                            </a>
                        </div>
                    </section>

                    <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                        <h3 class="text-base font-semibold text-slate-950">Exam totals</h3>
                        <dl class="mt-4 space-y-3 text-sm">
                            <div>
                                <dt class="text-slate-500">Configured exam marks</dt>
                                <dd class="mt-1 font-semibold text-slate-900">{{ $exam->total_marks }}</dd>
                            </div>
                            <div>
                                <dt class="text-slate-500">Question marks total</dt>
                                <dd class="mt-1 font-semibold text-slate-900">{{ number_format($totalQuestionMarks, 2) }}</dd>
                            </div>
                            <div>
                                <dt class="text-slate-500">Questions</dt>
                                <dd class="mt-1 font-semibold text-slate-900">{{ $questions->count() }}</dd>
                            </div>
                        </dl>
                    </section>

                    <section class="rounded-lg border border-red-200 bg-white p-5 shadow-sm">
                        <h3 class="text-base font-semibold text-red-700">Delete exam</h3>
                        <p class="mt-3 text-sm leading-6 text-slate-600">Deleting the exam also removes its questions.</p>
                        <form method="POST" action="{{ route('instructor.exams.destroy', $exam) }}" class="mt-4"
                            onsubmit="return confirm('Delete this exam and its questions?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit"
                                class="inline-flex w-full items-center justify-center rounded-md border border-red-200 bg-white px-4 py-2 text-sm font-semibold text-red-700 shadow-sm hover:bg-red-50">
                                Delete exam
                            </button>
                        </form>
                    </section>
                </aside>
            </div>
        </div>
    </div>
</x-app-layout>
