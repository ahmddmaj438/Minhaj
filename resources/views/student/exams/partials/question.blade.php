@php
    $prompt = $question->prompt ?? [];
    $settings = $question->settings ?? [];
    $answerPayload = $answer?->answer_payload ?? [];
    $saved = $answerPayload['value'] ?? [];
    $questionText = $prompt['question_text']
        ?? $prompt['statement']
        ?? $prompt['problem_statement']
        ?? $prompt['scenario']
        ?? $question->title;
    $typeLabel = str($question->type)->replace('_', ' ')->title();
@endphp

<article class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
    <div class="flex flex-col gap-3 border-b border-slate-100 pb-4 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <div class="flex flex-wrap items-center gap-2">
                <span class="rounded-full bg-slate-950 px-2.5 py-1 text-xs font-semibold text-white">Question {{ $question->position }}</span>
                <span class="rounded-full bg-orange-100 px-2.5 py-1 text-xs font-semibold text-orange-700">{{ $typeLabel }}</span>
                @if ($timing)
                    <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-700">{{ $timing['label'] }}</span>
                @endif
            </div>
            <h3 class="mt-3 text-lg font-semibold text-slate-950">{{ $questionText }}</h3>
            @if (! empty($prompt['instructions']))
                <p class="mt-2 text-sm leading-6 text-slate-600">{{ $prompt['instructions'] }}</p>
            @endif
        </div>
        <div class="rounded-md bg-slate-50 px-3 py-2 text-sm font-semibold text-slate-900">{{ $question->marks }} marks</div>
    </div>

    <div class="mt-5">
        @if ($question->type === 'mcq')
            @php
                $selectedOptions = collect($saved['selected_options'] ?? [])->map(fn ($value) => (string) $value)->all();
                $allowMultiple = (bool) ($settings['allow_multiple_correct'] ?? false);
            @endphp
            <div class="grid gap-3">
                @foreach (($settings['options'] ?? []) as $index => $option)
                    @php
                        $optionValue = (string) ($option['key'] ?? 'option_'.($index + 1));
                        $inputType = $allowMultiple ? 'checkbox' : 'radio';
                    @endphp
                    <label class="flex gap-3 rounded-md border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-800">
                        <input type="{{ $inputType }}" name="answers[{{ $question->id }}][selected_options][]" value="{{ $optionValue }}"
                            @checked(in_array($optionValue, $selectedOptions, true))
                            class="mt-1 border-slate-300 text-orange-600 focus:ring-orange-500">
                        <span>{{ $option['text'] ?? '' }}</span>
                    </label>
                @endforeach
            </div>
        @elseif (in_array($question->type, ['true_false', 'true_false_correct'], true))
            <div class="grid gap-3 sm:grid-cols-2">
                <label class="flex items-center gap-3 rounded-md border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-semibold text-slate-800">
                    <input type="radio" name="answers[{{ $question->id }}][answer]" value="true"
                        @checked(($saved['answer'] ?? null) === 'true')
                        class="border-slate-300 text-orange-600 focus:ring-orange-500">
                    True
                </label>
                <label class="flex items-center gap-3 rounded-md border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-semibold text-slate-800">
                    <input type="radio" name="answers[{{ $question->id }}][answer]" value="false"
                        @checked(($saved['answer'] ?? null) === 'false')
                        class="border-slate-300 text-orange-600 focus:ring-orange-500">
                    False
                </label>
            </div>
            @if ($question->type === 'true_false_correct')
                <div class="mt-4">
                    <label for="correction_{{ $question->id }}" class="block text-sm font-medium text-slate-800">Correction</label>
                    <textarea id="correction_{{ $question->id }}" name="answers[{{ $question->id }}][correction]" rows="3"
                        class="mt-2 block w-full rounded-md border-slate-300 shadow-sm focus:border-orange-500 focus:ring-orange-500">{{ $saved['correction'] ?? '' }}</textarea>
                </div>
            @endif
        @elseif ($question->type === 'matching')
            <div class="grid gap-3">
                @foreach (($settings['pairs'] ?? []) as $index => $pair)
                    <div class="grid gap-3 rounded-md border border-slate-200 bg-slate-50 p-4 sm:grid-cols-[minmax(0,1fr)_minmax(0,1fr)]">
                        <div class="text-sm font-semibold text-slate-900">{{ $pair['left'] ?? '' }}</div>
                        <input name="answers[{{ $question->id }}][matches][{{ $index }}]" value="{{ $saved['matches'][$index] ?? '' }}"
                            placeholder="Match"
                            class="block w-full rounded-md border-slate-300 shadow-sm focus:border-orange-500 focus:ring-orange-500">
                    </div>
                @endforeach
            </div>
        @elseif ($question->type === 'fill_blank')
            <div class="grid gap-3">
                @foreach (($settings['blanks'] ?? []) as $index => $blank)
                    <div>
                        <label for="blank_{{ $question->id }}_{{ $index }}" class="block text-sm font-medium text-slate-800">
                            {{ $blank['label'] ?? 'Blank '.($index + 1) }}
                        </label>
                        <input id="blank_{{ $question->id }}_{{ $index }}" name="answers[{{ $question->id }}][blanks][{{ $index }}]"
                            value="{{ $saved['blanks'][$index] ?? '' }}"
                            class="mt-2 block w-full rounded-md border-slate-300 shadow-sm focus:border-orange-500 focus:ring-orange-500">
                    </div>
                @endforeach
            </div>
        @elseif ($question->type === 'essay')
            <textarea name="answers[{{ $question->id }}][response]" rows="8"
                class="block w-full rounded-md border-slate-300 shadow-sm focus:border-orange-500 focus:ring-orange-500">{{ $saved['response'] ?? '' }}</textarea>
        @elseif ($question->category === 'coding')
            <div class="grid gap-4">
                @if (! empty($settings['starter_code']))
                    <pre class="overflow-x-auto whitespace-pre-wrap rounded-lg bg-slate-950 p-4 font-mono text-sm leading-6 text-slate-100">{{ $settings['starter_code'] }}</pre>
                @endif
                <textarea name="answers[{{ $question->id }}][response]" rows="10"
                    class="block w-full rounded-md border-slate-300 font-mono text-sm shadow-sm focus:border-orange-500 focus:ring-orange-500">{{ $saved['response'] ?? $settings['starter_code'] ?? '' }}</textarea>
            </div>
        @elseif ($question->type === 'packet_tracer')
            <div class="grid gap-4">
                @if (! empty($settings['expected_tasks']))
                    <div class="rounded-md bg-slate-50 px-4 py-3 text-sm leading-6 text-slate-700">{{ $settings['expected_tasks'] }}</div>
                @endif
                <textarea name="answers[{{ $question->id }}][response]" rows="6"
                    class="block w-full rounded-md border-slate-300 shadow-sm focus:border-orange-500 focus:ring-orange-500">{{ $saved['response'] ?? '' }}</textarea>
            </div>
        @else
            <textarea name="answers[{{ $question->id }}][response]" rows="5"
                class="block w-full rounded-md border-slate-300 shadow-sm focus:border-orange-500 focus:ring-orange-500">{{ $saved['response'] ?? '' }}</textarea>
        @endif
    </div>
</article>
