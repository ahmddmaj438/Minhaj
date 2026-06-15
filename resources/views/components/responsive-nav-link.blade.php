@props(['active'])

@php
$classes = ($active ?? false)
            ? 'block min-h-11 w-full rounded-xl border border-orange-200 bg-orange-50 px-4 py-3 text-start text-base font-semibold text-brand-ink focus:outline-none focus-visible:ring-4 focus-visible:ring-orange-100'
            : 'block min-h-11 w-full rounded-xl border border-transparent px-4 py-3 text-start text-base font-semibold text-slate-700 hover:border-orange-100 hover:bg-white/80 hover:text-brand-ink focus:outline-none focus-visible:ring-4 focus-visible:ring-orange-100';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }} @if ($active ?? false) aria-current="page" @endif>
    {{ $slot }}
</a>
