@props(['active'])

@php
$classes = ($active ?? false)
            ? 'inline-flex min-h-11 items-center rounded-full bg-orange-100 px-3.5 py-2 text-sm font-semibold leading-5 text-brand-ink shadow-inner-soft focus:outline-none focus-visible:ring-4 focus-visible:ring-orange-100'
            : 'inline-flex min-h-11 items-center rounded-full px-3.5 py-2 text-sm font-semibold leading-5 text-slate-700 hover:bg-white/80 hover:text-brand-ink focus:outline-none focus-visible:ring-4 focus-visible:ring-orange-100';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }} @if ($active ?? false) aria-current="page" @endif>
    {{ $slot }}
</a>
