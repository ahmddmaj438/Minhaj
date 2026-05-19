<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-sm font-medium text-orange-600">Exam Builder</p>
                <h2 class="text-2xl font-semibold leading-tight text-slate-950">Fill in the Blank Question</h2>
            </div>
            <div class="text-sm text-slate-500">Phase 6: Fill-in-the-blank builder</div>
        </div>
    </x-slot>

    @php
        $storedPrompt = $question->prompt ?? [];
        $storedSettings = $question->settings ?? [];
        $oldBlanks = old('blanks');
        $formBlanks = $oldBlanks
            ? collect($oldBlanks)->map(fn ($blank) => [
                'label' => $blank['label'] ?? '',
                'answers' => $blank['answers'] ?? '',
                'hint' => $blank['hint'] ?? '',
            ])->values()->all()
            : $blanks;
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
                    <p class="font-semibold">Please review the fill-in-the-blank details.</p>
                    <ul class="mt-2 list-disc space-y-1 ps-5">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_330px]">
                <form method="POST" action="{{ route('instructor.exams.questions.fill-blank.update', [$exam, $question]) }}" class="space-y-6"
                    x-data="{
                        blanks: @js($formBlanks),
                        addBlank() {
                            if (this.blanks.length < 12) {
                                this.blanks.push({ label: `Blank ${this.blanks.length + 1}`, answers: '', hint: '' });
                            }
                        },
                        removeBlank(index) {
                            if (this.blanks.length > 1) {
                                this.blanks.splice(index, 1);
                            }
                        }
                    }">
                    @csrf
                    @method('PUT')

                    <section class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
                        <div class="border-b border-slate-100 pb-5">
                            <p class="text-sm font-semibold uppercase tracking-wide text-orange-600">Question passage</p>
                            <h3 class="mt-1 text-lg font-semibold text-slate-950">Write the sentence or paragraph</h3>
                            <p class="mt-2 text-sm leading-6 text-slate-600">
                                Use placeholders such as <span class="font-mono text-slate-900">[blank 1]</span> and define the answer key below.
                            </p>
                        </div>

                        <div class="mt-6 grid gap-5">
                            <div>
                                <label for="question_text" class="block text-sm font-medium text-slate-800">Question passage</label>
                                <textarea id="question_text" name="question_text" rows="6" required
                                    placeholder="Example: In SQL, the [blank 1] clause filters grouped records after aggregation."
                                    class="mt-2 block w-full rounded-md border-slate-300 shadow-sm focus:border-orange-500 focus:ring-orange-500">{{ old('question_text', $storedPrompt['question_text'] ?? '') }}</textarea>
                                <x-input-error :messages="$errors->get('question_text')" class="mt-2" />
                            </div>

                            <div>
                                <label for="instructions" class="block text-sm font-medium text-slate-800">Optional instructions</label>
                                <textarea id="instructions" name="instructions" rows="3"
                                    placeholder="Example: Fill each blank with the most accurate technical term."
                                    class="mt-2 block w-full rounded-md border-slate-300 shadow-sm focus:border-orange-500 focus:ring-orange-500">{{ old('instructions', $storedPrompt['instructions'] ?? '') }}</textarea>
                                <x-input-error :messages="$errors->get('instructions')" class="mt-2" />
                            </div>
                        </div>
                    </section>

                    <section class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
                        <div class="border-b border-slate-100 pb-5">
                            <p class="text-sm font-semibold uppercase tracking-wide text-orange-600">Answer key</p>
                            <h3 class="mt-1 text-lg font-semibold text-slate-950">Define each blank and accepted answers</h3>
                            <p class="mt-2 text-sm leading-6 text-slate-600">
                                Separate alternative accepted answers with <span class="font-mono text-slate-900">|</span>, for example <span class="font-mono text-slate-900">HAVING | having</span>.
                            </p>
                        </div>

                        <div class="mt-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <label class="inline-flex items-center gap-2 text-sm font-medium text-slate-800">
                                <input type="checkbox" name="case_sensitive" value="1" @checked(old('case_sensitive', $storedSettings['case_sensitive'] ?? false))
                                    class="rounded border-slate-300 text-orange-600 focus:ring-orange-500">
                                Case sensitive answers
                            </label>
                            <label class="inline-flex items-center gap-2 text-sm font-medium text-slate-800">
                                <input type="checkbox" name="trim_whitespace" value="1" @checked(old('trim_whitespace', $storedSettings['trim_whitespace'] ?? true))
                                    class="rounded border-slate-300 text-orange-600 focus:ring-orange-500">
                                Ignore extra spaces
                            </label>
                        </div>

                        <div class="mt-5 space-y-4">
                            <template x-for="(blank, index) in blanks" :key="index">
                                <div class="rounded-lg border border-slate-200 bg-slate-50 p-4">
                                    <div class="grid gap-4 xl:grid-cols-[220px_1fr_auto]">
                                        <div>
                                            <label class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Blank label</label>
                                            <input x-model="blank.label" :name="`blanks[${index}][label]`"
                                                placeholder="Blank 1"
                                                class="mt-1 block w-full rounded-md border-slate-300 bg-white shadow-sm focus:border-orange-500 focus:ring-orange-500">
                                        </div>
                                        <div>
                                            <label class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Accepted answers</label>
                                            <input x-model="blank.answers" :name="`blanks[${index}][answers]`"
                                                placeholder="HAVING | having"
                                                class="mt-1 block w-full rounded-md border-slate-300 bg-white shadow-sm focus:border-orange-500 focus:ring-orange-500">
                                        </div>
                                        <div class="flex items-start xl:pt-6">
                                            <button type="button" @click="removeBlank(index)" x-show="blanks.length > 1"
                                                class="inline-flex w-full items-center justify-center rounded-md border border-slate-300 bg-white px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-100">
                                                Remove
                                            </button>
                                        </div>
                                    </div>

                                    <div class="mt-3">
                                        <label class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Hint</label>
                                        <input x-model="blank.hint" :name="`blanks[${index}][hint]`"
                                            placeholder="Optional hint shown later in preview or review"
                                            class="mt-1 block w-full rounded-md border-slate-300 bg-white shadow-sm focus:border-orange-500 focus:ring-orange-500">
                                    </div>
                                </div>
                            </template>
                        </div>

                        <div class="mt-5">
                            <button type="button" @click="addBlank" x-bind:disabled="blanks.length >= 12"
                                class="inline-flex items-center justify-center rounded-md border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-800 shadow-sm hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-50">
                                Add blank
                            </button>
                        </div>
                    </section>

                    <section class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
                        <div class="border-b border-slate-100 pb-5">
                            <p class="text-sm font-semibold uppercase tracking-wide text-orange-600">Metadata</p>
                            <h3 class="mt-1 text-lg font-semibold text-slate-950">Classify the question</h3>
                        </div>

                        <div class="mt-6 grid gap-5 md:grid-cols-2">
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

                            <div class="md:col-span-2">
                                <label for="topic" class="block text-sm font-medium text-slate-800">Topic</label>
                                <input id="topic" name="topic" value="{{ old('topic', $question->topic) }}"
                                    placeholder="Example: SQL clauses"
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
                                    Save blank question
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
                        <h3 class="text-base font-semibold text-slate-950">Blank structure</h3>
                        <div class="mt-4 space-y-3 text-sm text-slate-600">
                            <p>Use visible placeholders in the passage and define accepted answers separately.</p>
                            <p>The saved answer key is ready for future preview and auto-grading settings.</p>
                        </div>
                    </section>

                    <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                        <h3 class="text-base font-semibold text-slate-950">Next phases</h3>
                        <ol class="mt-4 space-y-3 text-sm text-slate-600">
                            <li>7. Essay/direct question builder</li>
                            <li>8. Coding question builder</li>
                            <li>10. Ordering system</li>
                            <li>11. Preview page</li>
                        </ol>
                    </section>
                </aside>
            </div>
        </div>
    </div>
</x-app-layout>
