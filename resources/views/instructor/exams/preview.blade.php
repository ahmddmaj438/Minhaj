<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-sm font-medium text-orange-600">Exam Builder</p>
                <h2 class="text-2xl font-semibold leading-tight text-slate-950">Preview Exam</h2>
            </div>
            <div class="text-sm text-slate-500">Phase 11: Structure preview</div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            @include('instructor.exams.partials.workspace-nav', [
                'exam' => $exam,
                'active' => 'preview',
                'questionCount' => $questions->count(),
                'totalQuestionMarks' => $totalQuestionMarks,
            ])

            <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_330px]">
                <div class="space-y-6">
                    <section class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
                        <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
                            <div>
                                <p class="text-sm font-semibold uppercase tracking-wide text-orange-600">{{ $exam->course->code }} - {{ $exam->course->name }}</p>
                                <h3 class="mt-2 text-2xl font-semibold text-slate-950">{{ $exam->title }}</h3>
                                <p class="mt-3 max-w-3xl text-sm leading-6 text-slate-600">{{ $exam->description ?: 'No description provided.' }}</p>
                            </div>
                            <dl class="grid min-w-64 grid-cols-2 gap-3 text-sm">
                                <div class="rounded-md bg-slate-50 p-3">
                                    <dt class="text-slate-500">Duration</dt>
                                    <dd class="mt-1 font-semibold text-slate-900">{{ $exam->duration_minutes }} minutes</dd>
                                </div>
                                <div class="rounded-md bg-slate-50 p-3">
                                    <dt class="text-slate-500">Total marks</dt>
                                    <dd class="mt-1 font-semibold text-slate-900">{{ $exam->total_marks }}</dd>
                                </div>
                                <div class="rounded-md bg-slate-50 p-3">
                                    <dt class="text-slate-500">Starts</dt>
                                    <dd class="mt-1 font-semibold text-slate-900">{{ $exam->starts_at?->format('Y-m-d H:i') ?? 'Not set' }}</dd>
                                </div>
                                <div class="rounded-md bg-slate-50 p-3">
                                    <dt class="text-slate-500">Ends</dt>
                                    <dd class="mt-1 font-semibold text-slate-900">{{ $exam->ends_at?->format('Y-m-d H:i') ?? 'Not set' }}</dd>
                                </div>
                            </dl>
                        </div>
                    </section>

                    @forelse ($questions as $question)
                        @php
                            $prompt = $question->prompt ?? [];
                            $settings = $question->settings ?? [];
                            $questionText = $prompt['question_text'] ?? $prompt['statement'] ?? $question->title;
                            $typeLabel = str($question->type)->replace('_', ' ')->title();
                        @endphp

                        <article class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
                            <div class="flex flex-col gap-3 border-b border-slate-100 pb-4 sm:flex-row sm:items-start sm:justify-between">
                                <div>
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span class="rounded-full bg-slate-950 px-2.5 py-1 text-xs font-semibold text-white">Question {{ $question->position }}</span>
                                        <span class="rounded-full bg-orange-100 px-2.5 py-1 text-xs font-semibold text-orange-700">{{ $typeLabel }}</span>
                                    </div>
                                    <h4 class="mt-3 text-lg font-semibold text-slate-950">{{ $questionText }}</h4>
                                    @if (! empty($prompt['instructions']))
                                        <p class="mt-2 text-sm leading-6 text-slate-600">{{ $prompt['instructions'] }}</p>
                                    @endif
                                </div>
                                <div class="rounded-md bg-slate-50 px-3 py-2 text-sm font-semibold text-slate-900">{{ $question->marks }} marks</div>
                            </div>

                            <div class="mt-5">
                                @if ($question->type === 'mcq')
                                    <div class="grid gap-3">
                                        @foreach (($settings['options'] ?? []) as $option)
                                            <div class="rounded-md border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-800">
                                                {{ $option['text'] ?? '' }}
                                            </div>
                                        @endforeach
                                    </div>
                                @elseif (in_array($question->type, ['true_false', 'true_false_correct'], true))
                                    <div class="grid gap-3 sm:grid-cols-2">
                                        <div class="rounded-md border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-semibold text-slate-800">True</div>
                                        <div class="rounded-md border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-semibold text-slate-800">False</div>
                                    </div>
                                    @if ($question->type === 'true_false_correct')
                                        <div class="mt-4 rounded-md border border-dashed border-orange-200 bg-orange-50 px-4 py-3 text-sm text-orange-800">
                                            Student correction area for wrong word or phrase.
                                        </div>
                                    @endif
                                @elseif ($question->type === 'matching')
                                    <div class="grid gap-4 md:grid-cols-2">
                                        <div>
                                            <h5 class="text-sm font-semibold text-slate-900">Items</h5>
                                            <div class="mt-2 space-y-2">
                                                @foreach (($settings['pairs'] ?? []) as $pair)
                                                    <div class="rounded-md border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-800">{{ $pair['left'] ?? '' }}</div>
                                                @endforeach
                                            </div>
                                        </div>
                                        <div>
                                            <h5 class="text-sm font-semibold text-slate-900">Matches</h5>
                                            <div class="mt-2 space-y-2">
                                                @foreach (($settings['pairs'] ?? []) as $pair)
                                                    <div class="rounded-md border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-800">{{ $pair['right'] ?? '' }}</div>
                                                @endforeach
                                            </div>
                                        </div>
                                    </div>
                                @elseif ($question->type === 'fill_blank')
                                    <div class="space-y-3">
                                        @foreach (($settings['blanks'] ?? []) as $blank)
                                            <div class="rounded-md border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-800">
                                                {{ $blank['label'] ?? 'Blank' }}: ____________________
                                            </div>
                                        @endforeach
                                    </div>
                                @elseif ($question->type === 'essay')
                                    <textarea rows="6" disabled placeholder="Student answer area"
                                        class="block w-full rounded-md border-slate-300 bg-slate-50 text-sm shadow-sm"></textarea>
                                @elseif ($question->category === 'coding')
                                    <div class="rounded-lg border border-slate-800 bg-slate-950 p-4">
                                        <div class="mb-3 flex items-center justify-between text-xs font-semibold text-slate-400">
                                            <span>{{ $question->programming_language ?: 'Code' }}</span>
                                            <span>starter code</span>
                                        </div>
                                        <pre class="overflow-x-auto whitespace-pre-wrap font-mono text-sm leading-6 text-slate-100">{{ $settings['starter_code'] ?? '// No starter code provided.' }}</pre>
                                    </div>
                                    @if (! empty($settings['sample_input']) || ! empty($settings['sample_output']))
                                        <div class="mt-4 grid gap-3 md:grid-cols-2">
                                            <pre class="rounded-md bg-slate-50 p-3 text-sm text-slate-800">{{ $settings['sample_input'] ?? 'No sample input.' }}</pre>
                                            <pre class="rounded-md bg-slate-50 p-3 text-sm text-slate-800">{{ $settings['sample_output'] ?? 'No sample output.' }}</pre>
                                        </div>
                                    @endif
                                @elseif ($question->type === 'packet_tracer')
                                    <div class="grid gap-3 md:grid-cols-2">
                                        <div class="rounded-md border border-dashed border-slate-300 bg-slate-50 px-4 py-3 text-sm text-slate-700">
                                            Packet Tracer file: {{ $settings['pkt_file']['original_name'] ?? 'Not uploaded' }}
                                        </div>
                                        <div class="rounded-md border border-dashed border-slate-300 bg-slate-50 px-4 py-3 text-sm text-slate-700">
                                            Topology screenshot: {{ $settings['topology_screenshot']['original_name'] ?? 'Not uploaded' }}
                                        </div>
                                    </div>
                                    @if (! empty($settings['expected_tasks']))
                                        <div class="mt-4 rounded-md bg-slate-50 px-4 py-3 text-sm leading-6 text-slate-700">{{ $settings['expected_tasks'] }}</div>
                                    @endif
                                @else
                                    <p class="text-sm text-slate-600">This question type will be shown in detail after its builder is completed.</p>
                                @endif
                            </div>
                        </article>
                    @empty
                        <section class="rounded-lg border border-dashed border-slate-300 bg-white p-8 text-center">
                            <h3 class="text-base font-semibold text-slate-950">No questions to preview</h3>
                            <p class="mt-2 text-sm text-slate-600">Add questions before reviewing the exam structure.</p>
                        </section>
                    @endforelse
                </div>

                <aside class="space-y-6">
                    <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                        <h3 class="text-base font-semibold text-slate-950">Preview summary</h3>
                        <dl class="mt-4 space-y-3 text-sm">
                            <div>
                                <dt class="text-slate-500">Questions</dt>
                                <dd class="mt-1 font-semibold text-slate-900">{{ $questions->count() }}</dd>
                            </div>
                            <div>
                                <dt class="text-slate-500">Question marks total</dt>
                                <dd class="mt-1 font-semibold text-slate-900">{{ number_format($totalQuestionMarks, 2) }}</dd>
                            </div>
                            <div>
                                <dt class="text-slate-500">Exam status</dt>
                                <dd class="mt-1 font-semibold capitalize text-slate-900">{{ $exam->status }}</dd>
                            </div>
                        </dl>
                    </section>

                    <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                        <h3 class="text-base font-semibold text-slate-950">Actions</h3>
                        <div class="mt-4 grid gap-3">
                            <a href="{{ route('instructor.exams.questions.order.index', $exam) }}"
                                class="inline-flex items-center justify-center rounded-md border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-800 shadow-sm hover:bg-slate-50">
                                Manage order
                            </a>
                            <a href="{{ route('instructor.exams.question-types.index', $exam) }}"
                                class="inline-flex items-center justify-center rounded-md bg-orange-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-orange-700">
                                Add questions
                            </a>
                        </div>
                    </section>

                    <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                        <h3 class="text-base font-semibold text-slate-950">Preview only</h3>
                        <p class="mt-3 text-sm leading-6 text-slate-600">
                            This page reviews structure only. It does not publish, accept submissions, grade answers, run code, or evaluate Packet Tracer files.
                        </p>
                    </section>
                </aside>
            </div>
        </div>
    </div>
</x-app-layout>
