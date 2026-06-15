@props([
    'disabled' => false,
    'invalid' => false,
])

<input
    @disabled($disabled)
    @if ($invalid) aria-invalid="true" @endif
    {{ $attributes->merge(['class' => 'min-h-11 w-full rounded-xl border border-slate-200 bg-white/90 text-slate-950 shadow-sm placeholder:text-slate-400 focus:border-orange-400 focus:ring-4 focus:ring-orange-100 disabled:cursor-not-allowed disabled:bg-slate-100 disabled:text-slate-500']) }}
>
