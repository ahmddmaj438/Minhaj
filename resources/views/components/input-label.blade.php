@props([
    'value',
    'required' => false,
])

<label {{ $attributes->merge(['class' => 'block text-sm font-semibold text-slate-800']) }}>
    {{ $value ?? $slot }}
    @if ($required)
        <span class="required-indicator" aria-hidden="true">*</span>
        <span class="sr-only">{{ __('required') }}</span>
    @endif
</label>
