<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-sm font-medium text-orange-600">Exam Builder</p>
                <h2 class="text-2xl font-semibold leading-tight text-slate-950">Multiple Choice Question</h2>
            </div>
            <div class="text-sm text-slate-500">Step 3 of 5: Question Management</div>
        </div>
    </x-slot>

    @php
        $storedPrompt = $question->prompt ?? [];
        $storedSettings = $question->settings ?? [];
        $oldOptions = old('options');
        $formOptions = $oldOptions
            ? collect($oldOptions)->map(fn ($option) => [
                'text' => $option['text'] ?? '',
                'feedback' => $option['feedback'] ?? '',
                'is_correct' => false,
            ])->values()->all()
            : $options;
        $correctIndexes = old('correct_options');
        if ($correctIndexes === null) {
            $correctIndexes = collect($formOptions)
                ->filter(fn ($option) => (bool) ($option['is_correct'] ?? false))
                ->keys()
                ->map(fn ($index) => (string) $index)
                ->values()
                ->all();
        }
        if (empty($correctIndexes)) {
            $correctIndexes = ['0'];
        }
        $allowMultiple = (bool) old('allow_multiple_correct', $storedSettings['allow_multiple_correct'] ?? false);
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
                    <p class="font-semibold">Please review the MCQ details.</p>
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
                <form method="POST" action="{{ route('instructor.exams.questions.mcq.update', [$exam, $question]) }}" class="space-y-6"
                    x-data="{
                        options: @js($formOptions),
                        allowMultiple: @js($allowMultiple),
                        correctSingle: @js((string) ($correctIndexes[0] ?? '0')),
                        correctMultiple: @js(array_values($correctIndexes)),
                        addOption() {
                            if (this.options.length < 8) {
                                this.options.push({ text: '', feedback: '', is_correct: false });
                            }
                        },
                        removeOption(index) {
                            if (this.options.length > 2) {
                                this.options.splice(index, 1);
                                this.correctMultiple = this.correctMultiple.filter((item) => Number(item) !== index).map((item) => {
                                    const value = Number(item);
                                    return value > index ? String(value - 1) : String(value);
                                });
                                if (Number(this.correctSingle) === index) {
                                    this.correctSingle = '0';
                                } else if (Number(this.correctSingle) > index) {
                                    this.correctSingle = String(Number(this.correctSingle) - 1);
                                }
                            }
                        },
                        syncCorrectMode() {
                            if (!this.allowMultiple) {
                                this.correctMultiple = [this.correctSingle || '0'];
                            }
                        }
                    }">
                    @csrf
                    @method('PUT')

                    <section class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
                        <div class="border-b border-slate-100 pb-5">
                            <p class="text-sm font-semibold uppercase tracking-wide text-orange-600">Question prompt</p>
                            <h3 class="mt-1 text-lg font-semibold text-slate-950">Write the question clearly</h3>
                            <p class="mt-2 text-sm leading-6 text-slate-600">
                                Use the instructions field for context such as diagrams, assumptions, or answer format.
                            </p>
                        </div>

                        <div class="mt-6 grid gap-5">
                            <div>
                                <label for="question_text" class="block text-sm font-medium text-slate-800">Question</label>
                                <textarea id="question_text" name="question_text" rows="5" required
                                    placeholder="Example: Which SQL clause is used to filter grouped records after aggregation?"
                                    class="mt-2 block w-full rounded-md border-slate-300 shadow-sm focus:border-orange-500 focus:ring-orange-500">{{ old('question_text', $storedPrompt['question_text'] ?? '') }}</textarea>
                                <x-input-error :messages="$errors->get('question_text')" class="mt-2" />
                            </div>

                            <div>
                                <label for="instructions" class="block text-sm font-medium text-slate-800">Optional instructions</label>
                                <textarea id="instructions" name="instructions" rows="3"
                                    placeholder="Example: Select the best answer. Assume standard SQL syntax."
                                    class="mt-2 block w-full rounded-md border-slate-300 shadow-sm focus:border-orange-500 focus:ring-orange-500">{{ old('instructions', $storedPrompt['instructions'] ?? '') }}</textarea>
                                <x-input-error :messages="$errors->get('instructions')" class="mt-2" />
                            </div>
                        </div>
                    </section>

                    <section class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
                        <div class="border-b border-slate-100 pb-5">
                            <p class="text-sm font-semibold uppercase tracking-wide text-orange-600">Answer options</p>
                            <h3 class="mt-1 text-lg font-semibold text-slate-950">Add choices and mark the correct answer</h3>
                            <p class="mt-2 text-sm leading-6 text-slate-600">
                                Add at least two options. Multiple correct answers can be enabled when the question requires it.
                            </p>
                        </div>

                        <div class="mt-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <label class="inline-flex items-center gap-2 text-sm font-medium text-slate-800">
                                <input type="checkbox" name="allow_multiple_correct" value="1" x-model="allowMultiple" @change="syncCorrectMode"
                                    class="rounded border-slate-300 text-orange-600 focus:ring-orange-500">
                                Allow multiple correct answers
                            </label>
                            <label class="inline-flex items-center gap-2 text-sm font-medium text-slate-800">
                                <input type="checkbox" name="shuffle_options" value="1" @checked(old('shuffle_options', $storedSettings['shuffle_options'] ?? true))
                                    class="rounded border-slate-300 text-orange-600 focus:ring-orange-500">
                                Shuffle options for students
                            </label>
                        </div>

                        <div class="mt-5 space-y-4">
                            <template x-for="(option, index) in options" :key="index">
                                <div class="rounded-lg border border-slate-200 bg-slate-50 p-4">
                                    <div class="flex flex-col gap-3 lg:flex-row lg:items-start">
                                        <div class="flex lg:w-36 lg:pt-2">
                                            <template x-if="!allowMultiple">
                                                <label class="inline-flex items-center gap-2 text-sm font-semibold text-slate-800">
                                                    <input type="radio" name="correct_options[]" :value="String(index)" x-model="correctSingle" @change="syncCorrectMode"
                                                        class="border-slate-300 text-orange-600 focus:ring-orange-500">
                                                    Correct
                                                </label>
                                            </template>
                                            <template x-if="allowMultiple">
                                                <label class="inline-flex items-center gap-2 text-sm font-semibold text-slate-800">
                                                    <input type="checkbox" name="correct_options[]" :value="String(index)" x-model="correctMultiple"
                                                        class="rounded border-slate-300 text-orange-600 focus:ring-orange-500">
                                                    Correct
                                                </label>
                                            </template>
                                        </div>

                                        <div class="grid flex-1 gap-3">
                                            <div>
                                                <label class="block text-xs font-semibold uppercase tracking-wide text-slate-500">
                                                    Option <span x-text="index + 1"></span>
                                                </label>
                                                <textarea rows="2" x-model="option.text" :name="`options[${index}][text]`"
                                                    placeholder="Write an answer option"
                                                    class="mt-1 block w-full rounded-md border-slate-300 bg-white shadow-sm focus:border-orange-500 focus:ring-orange-500"></textarea>
                                            </div>
                                            <div>
                                                <label class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Feedback note</label>
                                                <input x-model="option.feedback" :name="`options[${index}][feedback]`"
                                                    placeholder="Optional note for later review or feedback"
                                                    class="mt-1 block w-full rounded-md border-slate-300 bg-white shadow-sm focus:border-orange-500 focus:ring-orange-500">
                                            </div>
                                        </div>

                                        <button type="button" @click="removeOption(index)" x-show="options.length > 2"
                                            class="inline-flex items-center justify-center rounded-md border border-slate-300 bg-white px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-100">
                                            Remove
                                        </button>
                                    </div>
                                </div>
                            </template>
                        </div>

                        <div class="mt-5">
                            <button type="button" @click="addOption" x-bind:disabled="options.length >= 8"
                                class="inline-flex items-center justify-center rounded-md border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-800 shadow-sm hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-50">
                                Add option
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

                            @include('instructor.exams.questions.partials.display-override-selector', ['question' => $question])

                            <div class="md:col-span-2">
                                <label for="topic" class="block text-sm font-medium text-slate-800">Topic</label>
                                <input id="topic" name="topic" value="{{ old('topic', $question->topic) }}"
                                    placeholder="Example: SQL aggregation"
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
                                    Save and add another MCQ
                                </button>
                                <button type="submit" name="intent" value="save"
                                class="inline-flex items-center justify-center rounded-md bg-orange-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-orange-700 focus:outline-none focus:ring-2 focus:ring-orange-500 focus:ring-offset-2">
                                    Save MCQ question
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
                        <h3 class="text-base font-semibold text-slate-950">MCQ structure</h3>
                        <div class="mt-4 space-y-3 text-sm text-slate-600">
                            <p>Use concise options with one clearly best answer unless multiple correct answers is enabled.</p>
                            <p>The saved structure is ready for future grading, preview, and question bank reuse.</p>
                        </div>
                    </section>

                    <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                        <h3 class="text-base font-semibold text-slate-950">Next phases</h3>
                        <ol class="mt-4 space-y-3 text-sm text-slate-600">
                            <li>4. True/False builder</li>
                            <li>5. Matching builder</li>
                            <li>10. Ordering system</li>
                            <li>11. Preview page</li>
                        </ol>
                    </section>
                </aside>
            </div>
        </div>
    </div>
</x-app-layout>
