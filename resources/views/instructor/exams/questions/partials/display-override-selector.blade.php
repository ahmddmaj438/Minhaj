@php
    $displayOverrides = \App\Support\Exams\QuestionDisplayOverrideCatalog::all();
    $selectedDisplayOverride = old(
        'display_override',
        $question->display_override ?: \App\Support\Exams\QuestionDisplayOverrideCatalog::DEFAULT
    );
@endphp

<section class="md:col-span-2 rounded-xl border border-slate-200 bg-slate-50 p-5">
    <div class="max-w-2xl">
        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-orange-700">Student presentation</p>
        <h3 class="mt-1 text-base font-semibold text-slate-950">Does this question need a special layout?</h3>
        <p class="mt-1 text-sm leading-6 text-slate-600">
            Most questions should use the exam default. Choose another option only when this question needs extra space or emphasis.
        </p>
    </div>

    <div class="mt-4 grid gap-3 sm:grid-cols-2 xl:grid-cols-5">
        @foreach ($displayOverrides as $key => $option)
            @php $inputId = 'display_override_' . $key; @endphp
            <label for="{{ $inputId }}"
                class="cursor-pointer rounded-lg border bg-white p-4 transition hover:border-orange-300 has-[:checked]:border-orange-500 has-[:checked]:bg-orange-50 has-[:checked]:ring-2 has-[:checked]:ring-orange-100">
                <input id="{{ $inputId }}" type="radio" name="display_override" value="{{ $key }}"
                    @checked($selectedDisplayOverride === $key)
                    class="border-slate-300 text-orange-600 focus:ring-orange-500">
                <span class="mt-3 block text-sm font-semibold text-slate-950">{{ $option['label'] }}</span>
                <span class="mt-1 block text-xs leading-5 text-slate-600">{{ $option['description'] }}</span>
            </label>
        @endforeach
    </div>

    <x-input-error :messages="$errors->get('display_override')" class="mt-3" />
</section>
