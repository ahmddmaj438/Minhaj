@props([
    'formats',
    'selected' => 'one_question_at_time',
])

<div class="grid gap-4 lg:grid-cols-3">
    @foreach ($formats as $key => $format)
        @php
            $inputId = 'display_format_' . $key;
            $isSelected = old('display_format', $selected) === $key;
        @endphp

        <label for="{{ $inputId }}"
            class="group flex h-full cursor-pointer flex-col rounded-lg border bg-white p-4 shadow-sm transition focus-within:ring-2 focus-within:ring-orange-500 focus-within:ring-offset-2 {{ $isSelected ? 'border-orange-400 ring-1 ring-orange-200' : 'border-slate-200 hover:border-orange-300' }}">
            <input id="{{ $inputId }}" type="radio" name="display_format" value="{{ $key }}"
                class="sr-only" @checked($isSelected)>

            <div class="rounded-md border border-slate-200 bg-slate-50 p-3">
                @if (($format['preview'] ?? '') === 'single')
                    <div class="space-y-3">
                        <div class="flex items-center justify-between">
                            <div class="h-2 w-20 rounded bg-orange-500"></div>
                            <div class="h-2 w-12 rounded bg-slate-300"></div>
                        </div>
                        <div class="rounded-md border border-slate-200 bg-white p-3">
                            <div class="mb-3 h-2 w-2/3 rounded bg-slate-700"></div>
                            <div class="space-y-2">
                                <div class="h-2 rounded bg-slate-200"></div>
                                <div class="h-2 w-5/6 rounded bg-slate-200"></div>
                                <div class="h-2 w-3/4 rounded bg-slate-200"></div>
                            </div>
                        </div>
                        <div class="flex justify-between">
                            <div class="h-6 w-16 rounded bg-slate-300"></div>
                            <div class="h-6 w-16 rounded bg-orange-500"></div>
                        </div>
                    </div>
                @elseif (($format['preview'] ?? '') === 'all')
                    <div class="space-y-2">
                        @for ($i = 0; $i < 4; $i++)
                            <div class="rounded-md border border-slate-200 bg-white p-2">
                                <div class="mb-2 h-2 w-1/2 rounded bg-slate-700"></div>
                                <div class="h-2 rounded bg-slate-200"></div>
                            </div>
                        @endfor
                    </div>
                @else
                    <div class="space-y-3">
                        <div class="rounded-md bg-orange-500 p-2">
                            <div class="h-2 w-20 rounded bg-white"></div>
                        </div>
                        <div class="space-y-2">
                            <div class="rounded-md border border-slate-200 bg-white p-2">
                                <div class="mb-2 h-2 w-2/3 rounded bg-slate-700"></div>
                                <div class="h-2 w-5/6 rounded bg-slate-200"></div>
                            </div>
                            <div class="rounded-md border border-slate-200 bg-white p-2">
                                <div class="mb-2 h-2 w-1/2 rounded bg-slate-700"></div>
                                <div class="h-2 rounded bg-slate-200"></div>
                            </div>
                        </div>
                    </div>
                @endif
            </div>

            <div class="mt-4 flex flex-1 flex-col">
                <div class="flex items-start justify-between gap-3">
                    <h4 class="text-base font-semibold text-slate-950">{{ $format['title'] }}</h4>
                    <span class="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-full border {{ $isSelected ? 'border-orange-500 bg-orange-500' : 'border-slate-300 bg-white group-hover:border-orange-400' }}">
                        <span class="h-2 w-2 rounded-full bg-white"></span>
                    </span>
                </div>
                <p class="mt-2 text-sm leading-6 text-slate-600">{{ $format['summary'] }}</p>
                <p class="mt-3 text-xs font-semibold uppercase tracking-wide text-slate-500">{{ $format['best_for'] }}</p>

                <div class="mt-4 flex flex-wrap gap-2">
                    @foreach ($format['features'] as $feature)
                        <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-700">{{ $feature }}</span>
                    @endforeach
                </div>

                <div class="mt-5 inline-flex items-center justify-center rounded-md border px-3 py-2 text-sm font-semibold transition {{ $isSelected ? 'border-orange-500 bg-orange-50 text-orange-700' : 'border-slate-300 text-slate-700 group-hover:border-orange-300 group-hover:text-orange-700' }}">
                    {{ $isSelected ? 'Selected format' : 'Choose this format' }}
                </div>
            </div>
        </label>
    @endforeach
</div>
