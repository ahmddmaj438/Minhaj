@php
    $prompt = $question->prompt ?? [];
    $settings = $question->settings ?? [];
    $questionText = $prompt['question_text']
        ?? $prompt['statement']
        ?? $prompt['problem_statement']
        ?? $prompt['scenario']
        ?? $question->title;
    $typeLabel = str($question->type)->replace('_', ' ')->title();
    $isConfigured = ($prompt['status'] ?? null) === 'configured';
    $isAiGradable = ! in_array($question->type, ['mcq', 'true_false'], true);
    $editUrl = $questionEditRoutes[$question->id] ?? $editRoutes[$question->id] ?? route('instructor.exams.question-types.index', $exam);
    $correctOptions = collect($settings['options'] ?? [])->filter(fn ($option) => (bool) ($option['is_correct'] ?? false))->pluck('text')->values();
    $reviewStatus = $settings['review_guidance_status'] ?? null;
@endphp

<article id="question-{{ $question->id }}" class="rounded-lg border bg-white p-5 shadow-sm {{ $isConfigured ? 'border-slate-200' : 'border-amber-200 ring-1 ring-amber-100' }}">
    <div class="flex flex-col gap-4 border-b border-slate-100 pb-4 md:flex-row md:items-start md:justify-between">
        <div class="min-w-0">
            <div class="flex flex-wrap items-center gap-2">
                <span class="rounded-full bg-slate-950 px-2.5 py-1 text-xs font-semibold text-white">{{ __('Question :number', ['number' => $question->position]) }}</span>
                <span class="rounded-full bg-orange-100 px-2.5 py-1 text-xs font-semibold text-orange-700">{{ $typeLabel }}</span>
                <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-700">{{ number_format((float) $question->marks, 2) }} {{ __('marks') }}</span>
                <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $isConfigured ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-800' }}">
                    {{ $isConfigured ? __('Ready') : __('Needs details') }}
                </span>
            </div>
            <h4 class="mt-3 text-lg font-semibold text-slate-950">{{ $questionText }}</h4>
            @if (! empty($prompt['instructions']))
                <p class="mt-2 text-sm leading-6 text-slate-600">{{ $prompt['instructions'] }}</p>
            @endif
        </div>

        <div class="flex shrink-0 flex-wrap gap-2">
            <a href="{{ $editUrl }}"
                class="inline-flex items-center justify-center rounded-md border border-slate-300 bg-white px-3 py-2 text-sm font-semibold text-slate-700 shadow-sm hover:bg-slate-100"
                title="{{ __('Edit the question text, answer, marks, and guidance.') }}">
                {{ __('Edit Question') }}
            </a>
            @can('button.instructor.exams.questions.order.duplicate')
                <button type="submit" form="duplicate-question-{{ $question->id }}"
                    class="inline-flex items-center justify-center rounded-md border border-slate-300 bg-white px-3 py-2 text-sm font-semibold text-slate-700 shadow-sm hover:bg-slate-100"
                    title="{{ __('Make a copy of this question at the end of the exam.') }}">
                    {{ __('Duplicate') }}
                </button>
            @endcan
            @can('button.instructor.exams.questions.order.delete')
                <button type="submit" form="remove-question-{{ $question->id }}"
                    class="inline-flex items-center justify-center rounded-md border border-red-200 bg-white px-3 py-2 text-sm font-semibold text-red-700 shadow-sm hover:bg-red-50"
                    title="{{ __('Remove this question from the exam.') }}">
                    {{ __('Remove') }}
                </button>
            @endcan
        </div>
    </div>

    <div class="mt-5 grid gap-5 lg:grid-cols-[minmax(0,1fr)_280px]">
        <div class="rounded-lg border border-slate-200 bg-slate-50 p-4">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Student answer area') }}</p>

            @if ($question->type === 'mcq')
                <div class="mt-3 grid gap-3">
                    @forelse (($settings['options'] ?? []) as $option)
                        <div class="flex items-start gap-3 rounded-md border border-slate-200 bg-white px-4 py-3 text-sm text-slate-800">
                            <span class="mt-1 h-3 w-3 rounded-full border border-slate-400"></span>
                            <span>{{ $option['text'] ?? __('Empty option') }}</span>
                        </div>
                    @empty
                        <div class="rounded-md border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
                            {{ __('Answer options are missing. Open this question to add choices.') }}
                        </div>
                    @endforelse
                </div>
            @elseif (in_array($question->type, ['true_false', 'true_false_correct'], true))
                <div class="mt-3 grid gap-3 sm:grid-cols-2">
                    <div class="rounded-md border border-slate-200 bg-white px-4 py-3 text-sm font-semibold text-slate-800">{{ __('True') }}</div>
                    <div class="rounded-md border border-slate-200 bg-white px-4 py-3 text-sm font-semibold text-slate-800">{{ __('False') }}</div>
                </div>
                @if ($question->type === 'true_false_correct')
                    <div class="mt-3 rounded-md border border-slate-200 bg-white px-4 py-3 text-sm text-slate-500">{{ __('Student correction text area') }}</div>
                @endif
            @elseif ($question->type === 'matching')
                <div class="mt-3 grid gap-3">
                    @forelse (($settings['pairs'] ?? []) as $pair)
                        <div class="grid gap-2 rounded-md border border-slate-200 bg-white px-4 py-3 text-sm sm:grid-cols-2">
                            <span class="font-semibold text-slate-900">{{ $pair['left'] ?? __('Item') }}</span>
                            <span class="text-slate-500">{{ __('Student match field') }}</span>
                        </div>
                    @empty
                        <div class="rounded-md border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">{{ __('Matching pairs are missing.') }}</div>
                    @endforelse
                </div>
            @elseif ($question->type === 'fill_blank')
                <div class="mt-3 grid gap-3">
                    @forelse (($settings['blanks'] ?? []) as $blank)
                        <div class="rounded-md border border-slate-200 bg-white px-4 py-3 text-sm text-slate-700">
                            {{ $blank['label'] ?? __('Blank answer') }}
                        </div>
                    @empty
                        <div class="rounded-md border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">{{ __('Blank answer fields are missing.') }}</div>
                    @endforelse
                </div>
            @else
                <div class="mt-3 min-h-32 rounded-md border border-slate-200 bg-white px-4 py-3 text-sm text-slate-500">
                    {{ __('Written response area shown to students') }}
                </div>
            @endif
        </div>

        <div class="space-y-4">
            <section class="rounded-lg border border-slate-200 bg-white p-4">
                <p class="text-sm font-semibold text-slate-950">{{ __('Teacher answer key') }}</p>
                <div class="mt-3 text-sm leading-6 text-slate-600">
                    @if ($question->type === 'mcq')
                        <p class="font-medium text-slate-800">{{ __('Correct answer') }}</p>
                        <p>{{ $correctOptions->isNotEmpty() ? $correctOptions->implode(', ') : __('Not selected yet') }}</p>
                    @elseif (in_array($question->type, ['true_false', 'true_false_correct'], true))
                        <p class="font-medium text-slate-800">{{ __('Correct answer') }}</p>
                        <p>{{ isset($settings['correct_answer']) ? ucfirst((string) $settings['correct_answer']) : __('Not selected yet') }}</p>
                    @elseif (! empty($settings['expected_answer']))
                        <p>{{ str($settings['expected_answer'])->limit(240) }}</p>
                    @elseif (! empty($settings['expected_tasks']))
                        <p>{{ str($settings['expected_tasks'])->limit(240) }}</p>
                    @else
                        <p class="text-amber-800">{{ __('Open this question to add the expected answer or grading notes.') }}</p>
                    @endif
                </div>
            </section>

            @if ($isAiGradable)
                <section class="rounded-lg border border-violet-200 bg-violet-50 p-4">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="text-sm font-semibold text-violet-950">{{ __('Review Guidance') }}</p>
                            <p class="mt-1 text-xs leading-5 text-violet-800">{{ __('Teacher-approved guidance is used later for AI-assisted grading.') }}</p>
                        </div>
                        <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $reviewStatus === 'approved' ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-800' }}">
                            {{ $reviewStatus === 'approved' ? __('Approved') : __('Draft needed') }}
                        </span>
                    </div>

                    <dl class="mt-3 space-y-2 text-sm text-violet-900">
                        <div>
                            <dt class="font-semibold">{{ __('Model answer') }}</dt>
                            <dd class="mt-1">{{ filled($settings['expected_answer'] ?? null) ? str($settings['expected_answer'])->limit(120) : __('Not added yet') }}</dd>
                        </div>
                        <div>
                            <dt class="font-semibold">{{ __('Key points') }}</dt>
                            <dd class="mt-1">{{ filled($settings['key_points'] ?? null) ? str($settings['key_points'])->limit(120) : __('Not added yet') }}</dd>
                        </div>
                        <div>
                            <dt class="font-semibold">{{ __('Mark distribution') }}</dt>
                            <dd class="mt-1">{{ filled($settings['mark_distribution'] ?? null) ? str($settings['mark_distribution'])->limit(120) : __('Not added yet') }}</dd>
                        </div>
                    </dl>
                </section>
            @endif
        </div>
    </div>
</article>
