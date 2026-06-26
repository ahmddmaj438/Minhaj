<x-app-layout>
    <script src="https://js.puter.com/v2/"></script>

    <x-slot name="header">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-sm font-medium text-orange-600">Instructor grading</p>
                <h2 class="text-2xl font-semibold leading-tight text-slate-950">{{ $session->assignment->exam->title }}</h2>
            </div>
            <div class="text-sm text-slate-500">{{ $session->student->user?->name }} - {{ $session->student->student_number }}</div>
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
                    <p class="font-semibold">Please review the grading form.</p>
                    <ul class="mt-2 list-disc space-y-1 ps-5">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_320px]">
                <div class="space-y-6">
                    @forelse ($answers as $answer)
                        @php
                            $question = $answer->question;
                            $payload = $answer->answer_payload ?? [];
                            $value = $payload['value'] ?? [];
                            $aiSuggestion = $payload['ai_grading_suggestion'] ?? null;
                            $isManual = $canManuallyGrade($answer);
                            $canAssist = $canAiAssist($answer);
                            $status = $payload['status'] ?? ($isManual ? 'manual_pending' : 'auto_graded');
                            $displayValue = match (true) {
                                isset($value['response']) => $value['response'],
                                isset($value['selected_options']) => 'Selected: '.implode(', ', $value['selected_options']),
                                isset($value['answer']) => 'Answer: '.$value['answer'].(! empty($value['correction']) ? "\nCorrection: ".$value['correction'] : ''),
                                isset($value['matches']) => json_encode($value['matches'], JSON_PRETTY_PRINT),
                                isset($value['blanks']) => json_encode($value['blanks'], JSON_PRETTY_PRINT),
                                default => json_encode($value, JSON_PRETTY_PRINT),
                            };
                        @endphp
                        <article class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
                            <div class="border-b border-slate-100 pb-4">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="rounded-full bg-slate-950 px-2.5 py-1 text-xs font-semibold text-white">Question {{ $question->position }}</span>
                                    <span class="rounded-full bg-orange-100 px-2.5 py-1 text-xs font-semibold text-orange-700">{{ str($question->type)->replace('_', ' ')->title() }}</span>
                                    <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-700">{{ $question->marks }} marks</span>
                                    <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $isManual ? 'bg-violet-100 text-violet-700' : 'bg-emerald-100 text-emerald-700' }}">
                                        {{ $isManual ? 'Manual grading' : 'Auto calculated' }}
                                    </span>
                                    <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-700">{{ str($status)->replace('_', ' ')->title() }}</span>
                                </div>
                                <h3 class="mt-3 text-lg font-semibold text-slate-950">{{ $question->prompt['question_text'] ?? $question->title }}</h3>
                            </div>

                            <div class="mt-5 rounded-md bg-slate-50 p-4">
                                <h4 class="text-sm font-semibold text-slate-900">Student answer</h4>
                                <pre class="mt-3 whitespace-pre-wrap text-sm leading-6 text-slate-700">{{ $displayValue ?: 'No answer saved.' }}</pre>
                            </div>

                            @if ($canAssist)
                                    <div class="mt-5 rounded-md border border-violet-100 bg-violet-50 p-4"
                                        x-data="{
                                            loading: false,
                                            suggestion: @js($aiSuggestion),
                                            backendError: null,
                                            evaluation: {
                                                instruction: 'Return only valid minified JSON. Grade this student answer from content correctness, rubric, expected answer, difficulty, and evidence. Do not grade from answer length.',
                                                question: {
                                                    type: @js($question->type),
                                                    category: @js($question->category),
                                                    title: @js($question->title),
                                                    text: @js($question->prompt['question_text'] ?? $question->title),
                                                    instructions: @js($question->prompt['instructions'] ?? null),
                                                    difficulty: @js($question->difficulty),
                                                    topic: @js($question->topic),
                                                    programming_language: @js($question->programming_language),
                                                    max_score: {{ (float) $question->marks }},
                                                },
                                                rubric_and_expected_answer: {
                                                    rubric: @js($question->settings['rubric'] ?? null),
                                                    expected_answer: @js($question->settings['expected_answer'] ?? null),
                                                    criteria: @js($question->settings['criteria'] ?? null),
                                                    expected_tasks: @js($question->settings['expected_tasks'] ?? null),
                                                },
                                                student_submission: {
                                                    answer: @js($displayValue ?: 'No answer saved.'),
                                                },
                                                required_response_shape: {
                                                    suggested_score: 'number between 0 and question.max_score',
                                                    confidence: 'number between 0 and 1',
                                                    feedback: 'short instructor-facing feedback',
                                                    rationale: 'why this score fits the rubric and answer evidence',
                                                    strengths: ['answer strengths'],
                                                    improvements: ['missing or weak requirements'],
                                                    rubric_assessment: [{
                                                        criterion: 'rubric item or inferred requirement',
                                                        score: 'number',
                                                        max_score: 'number',
                                                        evidence: 'student answer evidence',
                                                        notes: 'brief grading note',
                                                    }],
                                                },
                                            },
                                            async generate() {
                                                if (this.loading) return;
                                                this.loading = true;
                                                this.backendError = null;
                                                try {
                                                    const data = await this.generateFromBackend();
                                                    if (!data.suggestion) {
                                                        throw new Error('No grading suggestion was returned.');
                                                    }
                                                    if (data.suggestion.suggested_score === null || data.suggestion.suggested_score === undefined) {
                                                        this.backendError = data.suggestion.provider_note || 'The AI service did not generate a score.';
                                                        await this.generateFromPuter();
                                                    } else {
                                                        this.applySuggestion(data.suggestion);
                                                    }
                                                } catch (error) {
                                                    if (!this.backendError) this.backendError = error.message;
                                                    try {
                                                        await this.generateFromPuter();
                                                    } catch (puterError) {
                                                        alert('The AI service is currently unavailable. Please review this answer manually or try again later.');
                                                    }
                                                } finally {
                                                    this.loading = false;
                                                }
                                            },
                                            async generateFromBackend() {
                                                const response = await fetch(@js(route('instructor.grading.api.answers.assist-answer', [$session, $answer])), {
                                                    method: 'POST',
                                                    headers: {
                                                        'Accept': 'application/json',
                                                        'Content-Type': 'application/json',
                                                        'X-CSRF-TOKEN': @js(csrf_token()),
                                                    },
                                                });
                                                const raw = await response.text();
                                                let data = {};
                                                try {
                                                    data = raw ? JSON.parse(raw) : {};
                                                } catch (parseError) {
                                                    throw new Error('The AI service could not prepare a grading suggestion.');
                                                }
                                                if (!response.ok) {
                                                    throw new Error(data.message || 'Unable to generate a grading suggestion.');
                                                }

                                                return data;
                                            },
                                            async generateFromPuter() {
                                                if (!window.puter?.ai?.chat) {
                                                    throw new Error('Browser AI assistance is not available right now.');
                                                }

                                                const prompt = JSON.stringify({
                                                    ...this.evaluation,
                                                    hard_rules: [
                                                        'Return only JSON with no markdown.',
                                                        'Use the question max_score scale.',
                                                        'Never exceed max_score.',
                                                        'Do not grade by answer length or word count.',
                                                        'If the answer is blank or unrelated, suggest 0 or a very low score.',
                                                    ],
                                                });
                                                const response = await window.puter.ai.chat(prompt, { model: 'gpt-5-nano' });
                                                const text = typeof response === 'string'
                                                    ? response
                                                    : (response?.message?.content ?? response?.text ?? String(response ?? ''));
                                                const suggestion = this.parseSuggestion(text);
                                                const saveResponse = await fetch(@js(route('instructor.grading.api.answers.assist-browser', [$session, $answer])), {
                                                        method: 'POST',
                                                        headers: {
                                                            'Accept': 'application/json',
                                                            'Content-Type': 'application/json',
                                                            'X-CSRF-TOKEN': @js(csrf_token()),
                                                        },
                                                        body: JSON.stringify(suggestion),
                                                    });
                                                const raw = await saveResponse.text();
                                                let data = {};
                                                try {
                                                    data = raw ? JSON.parse(raw) : {};
                                                } catch (parseError) {
                                                    throw new Error('The grading suggestion could not be saved.');
                                                }
                                                if (!saveResponse.ok) {
                                                    throw new Error(data.message || 'The grading suggestion could not be saved.');
                                                }
                                                if (!data.suggestion) {
                                                    throw new Error('The saved grading suggestion was not available.');
                                                }
                                                this.applySuggestion(data.suggestion);
                                            },
                                            parseSuggestion(text) {
                                                const match = text.match(/\{[\s\S]*\}/);
                                                if (!match) {
                                                    throw new Error('The AI service returned an incomplete suggestion.');
                                                }
                                                let data = {};
                                                try {
                                                    data = JSON.parse(match[0]);
                                                } catch (parseError) {
                                                    throw new Error('The AI service returned a suggestion that could not be read.');
                                                }
                                                if (data.suggested_score === null || data.suggested_score === undefined || Number.isNaN(Number(data.suggested_score))) {
                                                    throw new Error('The AI service did not return a usable score.');
                                                }
                                                const maxScore = Number(this.evaluation.question.max_score || 0);

                                                return {
                                                    suggested_score: Math.min(Math.max(Number(data.suggested_score), 0), maxScore),
                                                    max_score: maxScore,
                                                    confidence: Math.min(Math.max(Number(data.confidence ?? 0.5), 0), 1),
                                                    feedback: String(data.feedback || 'Review the browser AI suggestion before saving.'),
                                                    rationale: String(data.rationale || 'Generated from the question, rubric, expected answer, and student answer.'),
                                                    strengths: Array.isArray(data.strengths) ? data.strengths.map(String).filter(Boolean) : [],
                                                    improvements: Array.isArray(data.improvements) ? data.improvements.map(String).filter(Boolean) : [],
                                                    rubric_assessment: Array.isArray(data.rubric_assessment) ? data.rubric_assessment : [],
                                                };
                                            },
                                            applySuggestion(suggestion) {
                                                this.suggestion = suggestion;
                                                const score = document.getElementById('score_{{ $answer->id }}');
                                                const feedback = document.getElementById('feedback_{{ $answer->id }}');
                                                if (score && !score.value && this.suggestion.suggested_score !== null && this.suggestion.suggested_score !== undefined) score.value = this.suggestion.suggested_score;
                                                if (feedback && !feedback.value) feedback.value = this.suggestion.feedback ?? '';
                                            }
                                        }">
                                        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                            <div>
                                                <p class="text-sm font-semibold text-violet-900">Written-answer AI assist</p>
                                                <p class="mt-1 text-xs leading-5 text-violet-700">Generates a suggested score and feedback from the question, difficulty, rubric, expected answer, and typed student response. If backend providers are unavailable, the browser AI fallback is used.</p>
                                            </div>
                                            <button type="button" x-on:click="generate()" x-bind:disabled="loading"
                                                class="inline-flex items-center rounded-md bg-violet-700 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-violet-800 disabled:cursor-not-allowed disabled:opacity-60">
                                                <span x-show="!loading">Generate suggestion</span>
                                                <span x-show="loading">Generating...</span>
                                            </button>
                                        </div>

                                        <template x-if="suggestion">
                                            <div class="mt-4 grid gap-4 rounded-md bg-white p-4 sm:grid-cols-[160px_minmax(0,1fr)]">
                                                <div>
                                                    <p class="text-xs font-semibold uppercase text-violet-700">Suggested score</p>
                                                    <p class="mt-1 text-lg font-semibold text-slate-950" x-show="suggestion.suggested_score !== null && suggestion.suggested_score !== undefined">
                                                        <span x-text="Number(suggestion.suggested_score || 0).toFixed(2)"></span> / <span x-text="Number(suggestion.max_score || {{ (float) $question->marks }}).toFixed(2)"></span>
                                                    </p>
                                                    <p class="mt-1 text-sm font-semibold text-slate-700" x-show="suggestion.suggested_score === null || suggestion.suggested_score === undefined">
                                                        No score generated
                                                    </p>
                                                    <p class="mt-1 text-xs text-slate-500">Confidence: <span x-text="Math.round(Number(suggestion.confidence || 0) * 100)"></span>%</p>
                                                </div>
                                                <div class="space-y-3 text-sm text-slate-700">
                                                    <p x-text="suggestion.feedback || 'Review the suggestion before saving.'"></p>
                                                    <template x-if="suggestion.rationale">
                                                        <div>
                                                            <p class="font-semibold text-slate-900">Why this suggestion</p>
                                                            <p class="mt-1" x-text="suggestion.rationale"></p>
                                                        </div>
                                                    </template>
                                                    <template x-if="suggestion.rubric_assessment && suggestion.rubric_assessment.length">
                                                        <div>
                                                            <p class="font-semibold text-slate-900">Criterion review</p>
                                                            <div class="mt-2 space-y-2">
                                                                <template x-for="criterion in suggestion.rubric_assessment">
                                                                    <div class="rounded-md border border-slate-200 bg-slate-50 px-3 py-2">
                                                                        <div class="flex flex-wrap items-center justify-between gap-2">
                                                                            <p class="font-semibold text-slate-900" x-text="criterion.criterion || 'Criterion'"></p>
                                                                            <p class="text-xs font-semibold text-slate-600">
                                                                                <span x-text="Number(criterion.score || 0).toFixed(2)"></span> / <span x-text="Number(criterion.max_score || 0).toFixed(2)"></span>
                                                                            </p>
                                                                        </div>
                                                                        <p class="mt-1 text-xs text-slate-600" x-text="criterion.evidence || 'No evidence provided.'"></p>
                                                                        <p class="mt-1 text-xs text-slate-500" x-show="criterion.notes" x-text="criterion.notes"></p>
                                                                    </div>
                                                                </template>
                                                            </div>
                                                        </div>
                                                    </template>
                                                    <template x-if="suggestion.strengths && suggestion.strengths.length">
                                                        <div>
                                                            <p class="font-semibold text-slate-900">Strengths</p>
                                                            <ul class="mt-1 list-disc ps-5">
                                                                <template x-for="strength in suggestion.strengths">
                                                                    <li x-text="strength"></li>
                                                                </template>
                                                            </ul>
                                                        </div>
                                                    </template>
                                                    <template x-if="suggestion.improvements && suggestion.improvements.length">
                                                        <div>
                                                            <p class="font-semibold text-slate-900">Improvements</p>
                                                            <ul class="mt-1 list-disc ps-5">
                                                                <template x-for="improvement in suggestion.improvements">
                                                                    <li x-text="improvement"></li>
                                                                </template>
                                                            </ul>
                                                        </div>
                                                    </template>
                                                    <template x-if="suggestion.provider_note">
                                                        <p class="rounded-md bg-violet-50 px-3 py-2 text-xs text-violet-800" x-text="suggestion.provider_note"></p>
                                                    </template>
                                                    <template x-if="suggestion.provider_error">
                                                        <p class="rounded-md bg-red-50 px-3 py-2 text-xs text-red-700">The AI service did not complete the request. Please try again later.</p>
                                                    </template>
                                                    <p class="text-xs text-slate-500">AI source: <span x-text="suggestion.provider === 'ai_provider_unavailable' ? 'Not available' : 'Configured assistance'"></span></p>
                                                </div>
                                            </div>
                                        </template>
                                    </div>
                            @endif

                            @if ($isManual)
                                <form method="POST" action="{{ route('instructor.grading.answers.update', [$session, $answer]) }}" class="mt-5 grid gap-4">
                                    @csrf
                                    @method('PUT')
                                    <div class="grid gap-4 sm:grid-cols-[180px_minmax(0,1fr)]">
                                        <div>
                                            <label for="score_{{ $answer->id }}" class="block text-sm font-medium text-slate-800">Score</label>
                                            <input id="score_{{ $answer->id }}" type="number" step="0.01" min="0" max="{{ $question->marks }}" name="score"
                                                value="{{ old('score', $answer->score ?? ($aiSuggestion['suggested_score'] ?? null)) }}"
                                                class="mt-2 block w-full rounded-md border-slate-300 shadow-sm focus:border-orange-500 focus:ring-orange-500">
                                            <p class="mt-1 text-xs text-slate-500">Maximum: {{ number_format((float) $question->marks, 2) }}</p>
                                        </div>
                                        <div>
                                            <label for="feedback_{{ $answer->id }}" class="block text-sm font-medium text-slate-800">Feedback</label>
                                            <textarea id="feedback_{{ $answer->id }}" name="feedback" rows="3"
                                                class="mt-2 block w-full rounded-md border-slate-300 shadow-sm focus:border-orange-500 focus:ring-orange-500">{{ old('feedback', $answer->feedback ?? ($aiSuggestion['feedback'] ?? null)) }}</textarea>
                                        </div>
                                    </div>
                                    <button class="inline-flex w-fit items-center rounded-md bg-orange-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-orange-700">
                                        Save score
                                    </button>
                                </form>
                            @else
                                <div class="mt-5 grid gap-4 rounded-md border border-emerald-100 bg-emerald-50 p-4 sm:grid-cols-2">
                                    <div>
                                        <p class="text-xs font-semibold uppercase text-emerald-700">Auto score</p>
                                        <p class="mt-1 text-lg font-semibold text-slate-950">{{ number_format((float) $answer->score, 2) }} / {{ number_format((float) $question->marks, 2) }}</p>
                                    </div>
                                    <div>
                                        <p class="text-xs font-semibold uppercase text-emerald-700">Feedback</p>
                                        <p class="mt-1 text-sm text-slate-700">{{ $answer->feedback ?: 'Automatically calculated.' }}</p>
                                    </div>
                                </div>
                            @endif
                        </article>
                    @empty
                        <section class="rounded-lg border border-dashed border-slate-300 bg-white p-8 text-center">
                            <h3 class="text-base font-semibold text-slate-950">No answers</h3>
                            <p class="mt-2 text-sm text-slate-600">This submission does not have answer rows yet.</p>
                        </section>
                    @endforelse
                </div>

                <aside class="space-y-6">
                    <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                        <h3 class="text-base font-semibold text-slate-950">Question-based grade</h3>
                        <dl class="mt-4 space-y-3 text-sm">
                            <div>
                                <dt class="text-slate-500">Question marks earned</dt>
                                <dd class="mt-1 font-semibold text-slate-900">{{ number_format((float) $session->score, 2) }} / {{ number_format((float) $session->max_score, 2) }}</dd>
                            </div>
                            <div>
                                <dt class="text-slate-500">Score out of 100</dt>
                                <dd class="mt-1 font-semibold text-slate-900">{{ $session->percentage !== null ? number_format((float) $session->percentage, 2).'%' : 'Pending manual grading' }}</dd>
                            </div>
                            <div>
                                <dt class="text-slate-500">Formula</dt>
                                <dd class="mt-1 text-slate-700">Earned question marks divided by total question marks, converted to 100.</dd>
                            </div>
                            <div>
                                <dt class="text-slate-500">Submitted</dt>
                                <dd class="mt-1 font-semibold text-slate-900">{{ $session->submitted_at?->format('M j, Y H:i') }}</dd>
                            </div>
                        </dl>
                    </section>

                    <a href="{{ route('instructor.grading.index') }}"
                        class="inline-flex w-full items-center justify-center rounded-md border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-800 shadow-sm hover:bg-slate-50">
                        Back to submissions
                    </a>
                </aside>
            </div>
        </div>
    </div>
</x-app-layout>
