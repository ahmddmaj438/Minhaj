<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-sm font-medium text-orange-600">Exam Builder</p>
                <h2 class="text-2xl font-semibold leading-tight text-slate-950">Essay / Short Answer Question</h2>
            </div>
            <div class="text-sm text-slate-500">Step 3 of 5: Question Management</div>
        </div>
    </x-slot>

    @php
        $storedPrompt = $question->prompt ?? [];
        $storedSettings = $question->settings ?? [];
    @endphp

    <div class="py-8">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="mb-6 rounded-md border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800">
                    {{ session('status') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="mb-6 rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                    <p class="font-semibold">Please review the essay question details.</p>
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
            ])

            <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_330px]">
                <form method="POST" action="{{ route('instructor.exams.questions.essay.update', [$exam, $question]) }}" class="space-y-6">
                    @csrf
                    @method('PUT')

                    <section class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
                        <div class="border-b border-slate-100 pb-5">
                            <p class="text-sm font-semibold uppercase tracking-wide text-orange-600">Prompt</p>
                            <h3 class="mt-1 text-lg font-semibold text-slate-950">Ask a clear direct question</h3>
                            <p class="mt-2 text-sm leading-6 text-slate-600">
                                This builder is for short answers, direct questions, explanation prompts, and essay responses.
                            </p>
                        </div>

                        <div class="mt-6 grid gap-5">
                            <div>
                                <label for="question_text" class="block text-sm font-medium text-slate-800">Question</label>
                                <textarea id="question_text" name="question_text" rows="6" required
                                    placeholder="Example: Explain the difference between primary keys and foreign keys, and give one example of each."
                                    class="mt-2 block w-full rounded-md border-slate-300 shadow-sm focus:border-orange-500 focus:ring-orange-500">{{ old('question_text', $storedPrompt['question_text'] ?? '') }}</textarea>
                                <x-input-error :messages="$errors->get('question_text')" class="mt-2" />
                            </div>

                            <div>
                                <label for="instructions" class="block text-sm font-medium text-slate-800">Optional student instructions</label>
                                <textarea id="instructions" name="instructions" rows="3"
                                    placeholder="Example: Answer in 3-5 sentences and include a database example."
                                    class="mt-2 block w-full rounded-md border-slate-300 shadow-sm focus:border-orange-500 focus:ring-orange-500">{{ old('instructions', $storedPrompt['instructions'] ?? '') }}</textarea>
                                <x-input-error :messages="$errors->get('instructions')" class="mt-2" />
                            </div>
                        </div>
                    </section>

                    <section class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
                        <div class="border-b border-slate-100 pb-5">
                            <p class="text-sm font-semibold uppercase tracking-wide text-orange-600">Review guidance</p>
                            <h3 class="mt-1 text-lg font-semibold text-slate-950">Add model answer and rubric notes</h3>
                            <p class="mt-2 text-sm leading-6 text-slate-600">
                                These fields guide future manual, AI, or rubric-based review. They do not grade submissions yet.
                            </p>
                        </div>

                        <div class="mt-6 grid gap-5">
                            <div>
                                <label for="expected_answer" class="block text-sm font-medium text-slate-800">Model answer</label>
                                <textarea id="expected_answer" name="expected_answer" rows="5"
                                    placeholder="Example: A primary key uniquely identifies each row in its own table. A foreign key references a primary key in another table..."
                                    class="mt-2 block w-full rounded-md border-slate-300 shadow-sm focus:border-orange-500 focus:ring-orange-500">{{ old('expected_answer', $storedSettings['expected_answer'] ?? '') }}</textarea>
                                <x-input-error :messages="$errors->get('expected_answer')" class="mt-2" />
                            </div>

                            <div>
                                <label for="rubric" class="block text-sm font-medium text-slate-800">Rubric / marking notes</label>
                                <textarea id="rubric" name="rubric" rows="5"
                                    placeholder="Example: 2 marks for defining primary key, 2 marks for defining foreign key, 1 mark for examples."
                                    class="mt-2 block w-full rounded-md border-slate-300 shadow-sm focus:border-orange-500 focus:ring-orange-500">{{ old('rubric', $storedSettings['rubric'] ?? '') }}</textarea>
                                <x-input-error :messages="$errors->get('rubric')" class="mt-2" />
                            </div>
                        </div>
                    </section>

                    <section class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
                        <div class="border-b border-slate-100 pb-5">
                            <p class="text-sm font-semibold uppercase tracking-wide text-orange-600">Limits and metadata</p>
                            <h3 class="mt-1 text-lg font-semibold text-slate-950">Classify the response</h3>
                        </div>

                        <div class="mt-6 grid gap-5 md:grid-cols-2">
                            <div>
                                <label for="min_words" class="block text-sm font-medium text-slate-800">Minimum words</label>
                                <input id="min_words" type="number" name="min_words" min="0" value="{{ old('min_words', $storedSettings['min_words'] ?? '') }}"
                                    placeholder="Optional"
                                    class="mt-2 block w-full rounded-md border-slate-300 shadow-sm focus:border-orange-500 focus:ring-orange-500">
                                <x-input-error :messages="$errors->get('min_words')" class="mt-2" />
                            </div>

                            <div>
                                <label for="max_words" class="block text-sm font-medium text-slate-800">Maximum words</label>
                                <input id="max_words" type="number" name="max_words" min="1" value="{{ old('max_words', $storedSettings['max_words'] ?? '') }}"
                                    placeholder="Optional"
                                    class="mt-2 block w-full rounded-md border-slate-300 shadow-sm focus:border-orange-500 focus:ring-orange-500">
                                <x-input-error :messages="$errors->get('max_words')" class="mt-2" />
                            </div>

                            <div>
                                <label for="marks" class="block text-sm font-medium text-slate-800">Marks</label>
                                <input id="marks" type="number" name="marks" step="0.25" min="0.25" value="{{ old('marks', $question->marks) }}" required
                                    class="mt-2 block w-full rounded-md border-slate-300 shadow-sm focus:border-orange-500 focus:ring-orange-500">
                                <x-input-error :messages="$errors->get('marks')" class="mt-2" />
                            </div>

                            <div>
                                <label for="difficulty" class="block text-sm font-medium text-slate-800">Difficulty</label>
                                <select id="difficulty" name="difficulty"
                                    class="mt-2 block w-full rounded-md border-slate-300 shadow-sm focus:border-orange-500 focus:ring-orange-500">
                                    <option value="">Not specified</option>
                                    @foreach (['easy' => 'Easy', 'medium' => 'Medium', 'hard' => 'Hard', 'advanced' => 'Advanced'] as $value => $label)
                                        <option value="{{ $value }}" @selected(old('difficulty', $question->difficulty) === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                                <x-input-error :messages="$errors->get('difficulty')" class="mt-2" />
                            </div>

                            @include('instructor.exams.questions.partials.display-override-selector', ['question' => $question])

                            <div class="md:col-span-2">
                                <label for="topic" class="block text-sm font-medium text-slate-800">Topic</label>
                                <input id="topic" name="topic" value="{{ old('topic', $question->topic) }}"
                                    placeholder="Example: Database relationships"
                                    class="mt-2 block w-full rounded-md border-slate-300 shadow-sm focus:border-orange-500 focus:ring-orange-500">
                                <x-input-error :messages="$errors->get('topic')" class="mt-2" />
                            </div>

                            <div class="md:col-span-2">
                                <label class="inline-flex items-center gap-2 text-sm font-medium text-slate-800">
                                    <input type="checkbox" name="save_to_bank" value="1" @checked(old('save_to_bank', $question->save_to_bank))
                                        class="rounded border-slate-300 text-orange-600 focus:ring-orange-500">
                                    Save this question to the question bank
                                </label>
                            </div>
                        </div>
                    </section>

                    <section class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
                        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                            <a href="{{ route('instructor.exams.question-types.index', $exam) }}"
                                class="inline-flex items-center justify-center rounded-md border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-800 shadow-sm hover:bg-slate-50">
                                Back to question types
                            </a>
                            <div class="flex flex-col gap-3 sm:flex-row">
                                <button type="submit" name="intent" value="save_add_another"
                                    class="inline-flex items-center justify-center rounded-md border border-orange-300 bg-white px-5 py-2.5 text-sm font-semibold text-orange-700 shadow-sm hover:bg-orange-50 focus:outline-none focus:ring-2 focus:ring-orange-500 focus:ring-offset-2">
                                    Save and add another
                                </button>
                                <button type="submit" name="intent" value="save"
                                    class="inline-flex items-center justify-center rounded-md bg-orange-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-orange-700 focus:outline-none focus:ring-2 focus:ring-orange-500 focus:ring-offset-2">
                                    Save essay question
                                </button>
                            </div>
                        </div>
                    </section>
                </form>

                <aside class="space-y-6">
                    <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                        <h3 class="text-base font-semibold text-slate-950">Exam context</h3>
                        <dl class="mt-4 space-y-3 text-sm">
                            <div>
                                <dt class="text-slate-500">Exam</dt>
                                <dd class="mt-1 font-semibold text-slate-900">{{ $exam->title }}</dd>
                            </div>
                            <div>
                                <dt class="text-slate-500">Course</dt>
                                <dd class="mt-1 font-semibold text-slate-900">{{ $exam->course->code }} - {{ $exam->course->name }}</dd>
                            </div>
                            <div>
                                <dt class="text-slate-500">Question position</dt>
                                <dd class="mt-1 font-semibold text-slate-900">#{{ $question->position }}</dd>
                            </div>
                        </dl>
                    </section>

                    <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                        <h3 class="text-base font-semibold text-slate-950">Essay structure</h3>
                        <div class="mt-4 space-y-3 text-sm text-slate-600">
                            <p>The model answer and rubric are stored for review and future AI/manual grading support.</p>
                            <p>No grading or correction is performed in this phase.</p>
                        </div>
                    </section>

                    <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                        <h3 class="text-base font-semibold text-slate-950">Next phases</h3>
                        <ol class="mt-4 space-y-3 text-sm text-slate-600">
                            <li>8. Coding question builder</li>
                            <li>9. Networking / Packet Tracer builder</li>
                            <li>10. Ordering system</li>
                            <li>11. Preview page</li>
                        </ol>
                    </section>
                </aside>
            </div>
        </div>
    </div>
</x-app-layout>
