@props([
    'lines' => 3,
])

<div {{ $attributes->merge(['class' => 'space-y-3', 'aria-hidden' => 'true']) }}>
    @for ($line = 0; $line < $lines; $line++)
        <div class="skeleton skeleton-line {{ $line === $lines - 1 ? 'w-2/3' : 'w-full' }}"></div>
    @endfor
</div>
