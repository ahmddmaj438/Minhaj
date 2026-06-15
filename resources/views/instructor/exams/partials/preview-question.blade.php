@php
    $prompt = $question->prompt ?? [];
    $settings = $question->settings ?? [];
    $questionText = $prompt['question_text']
        ?? $prompt['statement']
        ?? $prompt['problem_statement']
        ?? $prompt['scenario']
        ?? $question->title
        ?? __('Untitled question');
    $typeLabel = str($question->type)->replace('_', ' ')->title();
    $displayOverride = $question->display_override ?: \App\Support\Exams\QuestionDisplayOverrideCatalog::DEFAULT;
    $overrideOption = \App\Support\Exams\QuestionDisplayOverrideCatalog::all()[$displayOverride] ?? null;
    $options = collect($settings['options'] ?? []);
    $pairs = collect($settings['pairs'] ?? []);
    $blanks = collect($settings['blanks'] ?? []);
@endphp

<article class="exam-print-question rounded-xl border border-slate-200 bg-white p-5 shadow-sm" data-preview-question="{{ $question->id }}">
    <div class="flex flex-col gap-3 border-b border-slate-100 pb-4 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <div class="flex flex-wrap items-center gap-2">
                <span class="rounded-full bg-slate-950 px-2.5 py-1 text-xs font-semibold text-white">
                    {{ __('Question :number', ['number' => $question->position]) }}
                </span>
                <span class="rounded-full bg-orange-100 px-2.5 py-1 text-xs font-semibold text-orange-700">
                    {{ $typeLabel }}
                </span>
                @if ($question->topic)
                    <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-700">
                        {{ $question->topic }}
                    </span>
                @endif
                @if ($question->difficulty)
                    <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold capitalize text-slate-700">
                        {{ $question->difficulty }}
                    </span>
                @endif
                @if ($displayOverride !== 'standard' && $overrideOption)
                    <span class="rounded-full bg-blue-100 px-2.5 py-1 text-xs font-semibold text-blue-700">
                        {{ $overrideOption['label'] }}
                    </span>
                @endif
            </div>

            <h4 class="mt-3 text-lg font-semibold text-slate-950">{{ $questionText }}</h4>

            @if (! empty($prompt['instructions']))
                <p class="mt-2 text-sm leading-6 text-slate-600">{{ $prompt['instructions'] }}</p>
            @endif
        </div>

        <div class="rounded-lg bg-slate-50 px-3 py-2 text-sm font-semibold text-slate-900">
            {{ __(':marks marks', ['marks' => number_format((float) $question->marks, 2)]) }}
        </div>
    </div>

    <div class="mt-5">
        @if ($question->type === 'mcq')
            @if ($options->isNotEmpty())
                <div class="grid gap-3">
                    @foreach ($options as $index => $option)
                        <div class="flex gap-3 rounded-lg border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-800">
                            <span class="font-semibold text-slate-950">{{ chr(65 + $index) }}.</span>
                            <span>{{ $option['text'] ?? __('Option :number', ['number' => $index + 1]) }}</span>
                        </div>
                    @endforeach
                </div>
            @else
                <p class="rounded-lg border border-dashed border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                    {{ __('No answer options are configured for this MCQ yet.') }}
                </p>
            @endif
        @elseif (in_array($question->type, ['true_false', 'true_false_correct'], true))
            <div class="grid gap-3 sm:grid-cols-2">
                <div class="rounded-lg border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-semibold text-slate-800">{{ __('True') }}</div>
                <div class="rounded-lg border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-semibold text-slate-800">{{ __('False') }}</div>
            </div>
            @if ($question->type === 'true_false_correct')
                <div class="mt-4 rounded-lg border border-dashed border-orange-200 bg-orange-50 px-4 py-3 text-sm text-orange-800">
                    {{ __('Includes correction area when the statement is false.') }}
                </div>
            @endif
        @elseif ($question->type === 'matching')
            @if ($pairs->isNotEmpty())
                <div class="grid gap-4 md:grid-cols-2">
                    <div>
                        <h5 class="text-sm font-semibold text-slate-900">{{ __('Items') }}</h5>
                        <div class="mt-2 space-y-2">
                            @foreach ($pairs as $pair)
                                <div class="rounded-lg border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-800">{{ $pair['left'] ?? '' }}</div>
                            @endforeach
                        </div>
                    </div>
                    <div>
                        <h5 class="text-sm font-semibold text-slate-900">{{ __('Matches') }}</h5>
                        <div class="mt-2 space-y-2">
                            @foreach ($pairs as $pair)
                                <div class="rounded-lg border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-800">{{ $pair['right'] ?? '' }}</div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @else
                <p class="rounded-lg border border-dashed border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                    {{ __('No matching pairs are configured yet.') }}
                </p>
            @endif
        @elseif ($question->type === 'fill_blank')
            @if ($blanks->isNotEmpty())
                <div class="grid gap-3">
                    @foreach ($blanks as $index => $blank)
                        <div class="rounded-lg border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-800">
                            {{ $blank['label'] ?? __('Blank :number', ['number' => $index + 1]) }}: ______________________________
                        </div>
                    @endforeach
                </div>
            @else
                <p class="rounded-lg border border-dashed border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                    {{ __('No fill-in blanks are configured yet.') }}
                </p>
            @endif
        @elseif ($question->type === 'essay')
            <div class="rounded-lg border border-dashed border-slate-300 bg-slate-50 p-4">
                <p class="text-sm font-semibold text-slate-900">{{ __('Student answer area') }}</p>
                <div class="mt-4 space-y-4">
                    @for ($line = 0; $line < ($displayOverride === 'expanded_essay' ? 8 : 5); $line++)
                        <div class="h-px bg-slate-300"></div>
                    @endfor
                </div>
            </div>
        @elseif ($question->category === 'coding')
            <div class="rounded-xl border border-slate-800 bg-slate-950 p-4">
                <div class="mb-3 flex items-center justify-between text-xs font-semibold text-slate-400">
                    <span>{{ $question->programming_language ?: __('Code') }}</span>
                    <span>{{ __('Starter code') }}</span>
                </div>
                <pre class="overflow-x-auto whitespace-pre-wrap font-mono text-sm leading-6 text-slate-100">{{ $settings['starter_code'] ?? '// No starter code provided.' }}</pre>
            </div>
            @if (! empty($settings['sample_input']) || ! empty($settings['sample_output']))
                <div class="mt-4 grid gap-3 md:grid-cols-2">
                    <pre class="rounded-lg bg-slate-50 p-3 text-sm text-slate-800">{{ $settings['sample_input'] ?? __('No sample input.') }}</pre>
                    <pre class="rounded-lg bg-slate-50 p-3 text-sm text-slate-800">{{ $settings['sample_output'] ?? __('No sample output.') }}</pre>
                </div>
            @endif
        @elseif ($question->type === 'packet_tracer')
            <div class="grid gap-3 md:grid-cols-2">
                <div class="rounded-lg border border-dashed border-slate-300 bg-slate-50 px-4 py-3 text-sm text-slate-700">
                    {{ __('Packet Tracer file') }}: {{ $settings['pkt_file']['original_name'] ?? __('Not uploaded') }}
                </div>
                <div class="rounded-lg border border-dashed border-slate-300 bg-slate-50 px-4 py-3 text-sm text-slate-700">
                    {{ __('Topology screenshot') }}: {{ $settings['topology_screenshot']['original_name'] ?? __('Not uploaded') }}
                </div>
            </div>
            @if (! empty($settings['expected_tasks']))
                <div class="mt-4 rounded-lg bg-slate-50 px-4 py-3 text-sm leading-6 text-slate-700">{{ $settings['expected_tasks'] }}</div>
            @endif
        @else
            <div class="rounded-lg border border-dashed border-slate-300 bg-slate-50 p-4">
                <p class="text-sm font-semibold text-slate-900">{{ __('Response area') }}</p>
                <div class="mt-4 space-y-4">
                    @for ($line = 0; $line < 4; $line++)
                        <div class="h-px bg-slate-300"></div>
                    @endfor
                </div>
            </div>
        @endif
    </div>
</article>
