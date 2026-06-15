@props(['status'])

@if ($status)
    <div {{ $attributes->merge(['role' => 'status', 'aria-live' => 'polite', 'class' => 'rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800']) }}>
        {{ $status }}
    </div>
@endif
