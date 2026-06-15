@props(['messages'])

@if ($messages)
    <ul {{ $attributes->merge(['role' => 'alert', 'class' => 'space-y-1 rounded-xl border border-red-200 bg-red-50 px-3 py-2 text-sm font-medium text-red-700']) }}>
        @foreach ((array) $messages as $message)
            <li>{{ $message }}</li>
        @endforeach
    </ul>
@endif
