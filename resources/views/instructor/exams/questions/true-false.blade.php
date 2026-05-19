<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-sm font-medium text-orange-600">Exam Builder</p>
                <h2 class="text-2xl font-semibold leading-tight text-slate-950">
                    {{ $requiresCorrection ? 'True / False + Correction' : 'True / False Question' }}
                </h2>
            </div>
            <div class="text-sm text-slate-500">Phase 4 of 4: True/False builder</div>
        </div>
    </x-slot>

    @php
        $storedPrompt = $question->prompt ?? [];
        $storedSettings = $question->settings ?? [];
        $oldWrongTerms = old('wrong_terms');
        $wrongTerms = $oldWrongTerms
            ? collect($oldWrongTerms)->map(fn ($term) => [
                'text' => $term['text'] ?? '',
                'correction' => $term['correction'] ?? '',
            ])->values()->all()
            : collect($storedSettings['wrong_terms'] ?? [])->map(fn ($term) => [
                'text' => $term['text'] ?? '',
                'correction' => $term['correction'] ?? '',
            ])->values()->all();

        if (empty($wrongTerms)) {
            $wrongTerms = [
                ['text' => '', 'correction' => ''],
            ];
        }
        $correctAnswer = old(
            'correct_answer',
            array_key_exists('correct_answer', $storedSettings)
                ? ($storedSettings['correct_answer'] ? 'true' : 'false')
                : 'true'
        );
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
                    <p class="font-semibold">Please review the True/False details.</p>
                    <ul class="mt-2 list-disc space-y-1 ps-5">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_330px]">
                <form method="POST" action="{{ route('instructor.exams.questions.true-false.update', [$exam, $question]) }}" class="space-y-6"
                    x-data="{
                        wrongTerms: @js($wrongTerms),
                        addWrongTerm() {
                            if (this.wrongTerms.length < 8) {
                                this.wrongTerms.push({ text: '', correction: '' });
                            }
                        },
                        removeWrongTerm(index) {
                            if (this.wrongTerms.length > 1) {
                                this.wrongTerms.splice(index, 1);
                            }
                        }
                    }">
                    @csrf
                    @method('PUT')

                    <section class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
                        <div class="border-b border-slate-100 pb-5">
                            <p class="text-sm font-semibold uppercase tracking-wide text-orange-600">Statement</p>
                            <h3 class="mt-1 text-lg font-semibold text-slate-950">Write a statement students can judge clearly</h3>
                            <p class="mt-2 text-sm leading-6 text-slate-600">
                                Keep the statement precise. Avoid combining two facts when only one answer is expected.
                            </p>
                        </div>

                        <div class="mt-6 grid gap-5">
                            <div>
                                <label for="statement" class="block text-sm font-medium text-slate-800">Question statement</label>
                                <textarea id="statement" name="statement" rows="5" required
                                    placeholder="Example: A primary key can contain duplicate values in a relational database table."
                                    class="mt-2 block w-full rounded-md border-slate-300 shadow-sm focus:border-orange-500 focus:ring-orange-500">{{ old('statement', $storedPrompt['statement'] ?? '') }}</textarea>
                                <x-input-error :messages="$errors->get('statement')" class="mt-2" />
                            </div>

                            <div>
                                <label for="instructions" class="block text-sm font-medium text-slate-800">Optional instructions</label>
                                <textarea id="instructions" name="instructions" rows="3"
                                    placeholder="{{ $requiresCorrection ? 'Example: If the statement is false, write the corrected version.' : 'Example: Select whether the statement is true or false.' }}"
                                    class="mt-2 block w-full rounded-md border-slate-300 shadow-sm focus:border-orange-500 focus:ring-orange-500">{{ old('instructions', $storedPrompt['instructions'] ?? '') }}</textarea>
                                <x-input-error :messages="$errors->get('instructions')" class="mt-2" />
                            </div>
                        </div>
                    </section>

                    <section class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
                        <div class="border-b border-slate-100 pb-5">
                            <p class="text-sm font-semibold uppercase tracking-wide text-orange-600">Answer model</p>
                            <h3 class="mt-1 text-lg font-semibold text-slate-950">Select the correct truth value</h3>
                        </div>

                        <div class="mt-6 grid gap-4 sm:grid-cols-2">
                            <label class="flex cursor-pointer items-start gap-3 rounded-lg border border-slate-200 bg-slate-50 p-4 hover:border-orange-300">
                                <input type="radio" name="correct_answer" value="true" @checked($correctAnswer === 'true')
                                    class="mt-1 border-slate-300 text-orange-600 focus:ring-orange-500">
                                <span>
                                    <span class="block font-semibold text-slate-950">True</span>
                                    <span class="mt-1 block text-sm text-slate-600">The statement is accurate as written.</span>
                                </span>
                            </label>

                            <label class="flex cursor-pointer items-start gap-3 rounded-lg border border-slate-200 bg-slate-50 p-4 hover:border-orange-300">
                                <input type="radio" name="correct_answer" value="false" @checked($correctAnswer === 'false')
                                    class="mt-1 border-slate-300 text-orange-600 focus:ring-orange-500">
                                <span>
                                    <span class="block font-semibold text-slate-950">False</span>
                                    <span class="mt-1 block text-sm text-slate-600">The statement contains an error.</span>
                                </span>
                            </label>
                        </div>
                        <x-input-error :messages="$errors->get('correct_answer')" class="mt-2" />

                        @if ($requiresCorrection)
                            <div class="mt-6 rounded-lg border border-orange-200 bg-orange-50 p-4">
                                <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                                    <div>
                                        <h4 class="text-sm font-semibold text-slate-900">Wrong word or phrase corrections</h4>
                                        <p class="mt-1 text-sm text-orange-800">Mark the wrong part of the statement and write what should replace it.</p>
                                    </div>
                                    <button type="button" @click="addWrongTerm" x-bind:disabled="wrongTerms.length >= 8"
                                        class="inline-flex items-center justify-center rounded-md border border-orange-300 bg-white px-3 py-2 text-sm font-semibold text-orange-700 hover:bg-orange-50 disabled:cursor-not-allowed disabled:opacity-50">
                                        Add correction
                                    </button>
                                </div>

                                <div class="mt-4 space-y-3">
                                    <template x-for="(term, index) in wrongTerms" :key="index">
                                        <div class="grid gap-3 rounded-md border border-orange-200 bg-white p-3 md:grid-cols-[1fr_1fr_auto]">
                                            <div>
                                                <label class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Wrong word or phrase</label>
                                                <input x-model="term.text" :name="`wrong_terms[${index}][text]`"
                                                    placeholder="Example: duplicate values"
                                                    class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-orange-500 focus:ring-orange-500">
                                            </div>
                                            <div>
                                                <label class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Correction</label>
                                                <input x-model="term.correction" :name="`wrong_terms[${index}][correction]`"
                                                    placeholder="Example: unique values"
                                                    class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-orange-500 focus:ring-orange-500">
                                            </div>
                                            <div class="flex items-end">
                                                <button type="button" @click="removeWrongTerm(index)" x-show="wrongTerms.length > 1"
                                                    class="inline-flex w-full items-center justify-center rounded-md border border-slate-300 bg-white px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-100">
                                                    Remove
                                                </button>
                                            </div>
                                        </div>
                                    </template>
                                </div>
                                <x-input-error :messages="$errors->get('wrong_terms')" class="mt-2" />

                                <label for="corrected_statement" class="mt-5 block text-sm font-semibold text-slate-900">Full corrected statement</label>
                                <textarea id="corrected_statement" name="corrected_statement" rows="4"
                                    placeholder="Example: A primary key must contain unique values in a relational database table."
                                    class="mt-2 block w-full rounded-md border-orange-200 bg-white shadow-sm focus:border-orange-500 focus:ring-orange-500">{{ old('corrected_statement', $storedSettings['corrected_statement'] ?? '') }}</textarea>
                                <p class="mt-2 text-sm text-orange-800">This optional full sentence helps preview and future correction review stay clear.</p>
                                <x-input-error :messages="$errors->get('corrected_statement')" class="mt-2" />
                            </div>
                        @endif

                        <div class="mt-6">
                            <label for="explanation" class="block text-sm font-medium text-slate-800">Explanation note</label>
                            <textarea id="explanation" name="explanation" rows="3"
                                placeholder="Optional note for review, feedback, or future preview."
                                class="mt-2 block w-full rounded-md border-slate-300 shadow-sm focus:border-orange-500 focus:ring-orange-500">{{ old('explanation', $storedSettings['explanation'] ?? '') }}</textarea>
                            <x-input-error :messages="$errors->get('explanation')" class="mt-2" />
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
                                    placeholder="Example: Database constraints"
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
                                    Save question
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
                        <h3 class="text-base font-semibold text-slate-950">Builder behavior</h3>
                        <div class="mt-4 space-y-3 text-sm text-slate-600">
                            <p>{{ $requiresCorrection ? 'Students will answer True or False and correct the wrong word or phrase.' : 'Students will answer True or False only.' }}</p>
                            <p>The saved structure is ready for future preview and grading without adding correction logic now.</p>
                        </div>
                    </section>

                    <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                        <h3 class="text-base font-semibold text-slate-950">Next phases</h3>
                        <ol class="mt-4 space-y-3 text-sm text-slate-600">
                            <li>5. Matching builder</li>
                            <li>6. Fill-in-the-blank builder</li>
                            <li>10. Ordering system</li>
                            <li>11. Preview page</li>
                        </ol>
                    </section>
                </aside>
            </div>
        </div>
    </div>
</x-app-layout>
