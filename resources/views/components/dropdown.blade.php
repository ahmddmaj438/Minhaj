@props(['align' => 'right', 'width' => '48', 'contentClasses' => 'p-2 bg-white/95 backdrop-blur-xl'])

@php
$alignmentClasses = match ($align) {
    'left' => 'ltr:origin-top-left rtl:origin-top-right start-0',
    'top' => 'origin-top',
    default => 'ltr:origin-top-right rtl:origin-top-left end-0',
};

$width = match ($width) {
    '48' => 'w-48',
    '64' => 'w-64',
    default => $width,
};
@endphp

<div class="relative" x-data="{ open: false }" @click.outside="open = false" @close.stop="open = false">
    <div @click="open = ! open">
        {{ $trigger }}
    </div>

    <div x-show="open"
            x-cloak
            x-transition:enter="transition ease-productive duration-200"
            x-transition:enter-start="opacity-0 translate-y-1 scale-[0.98]"
            x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="transition ease-premium duration-100"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 translate-y-1 scale-[0.98]"
            class="absolute z-50 mt-3 {{ $width }} rounded-2xl shadow-apple transform-gpu {{ $alignmentClasses }}"
            style="display: none;"
            @click="open = false">
        <div class="rounded-2xl border border-slate-200/80 ring-1 ring-white/50 {{ $contentClasses }}">
            {{ $content }}
        </div>
    </div>
</div>
