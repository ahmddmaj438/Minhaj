@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'border-slate-300 bg-white/90 focus:border-orange-500 focus:ring-orange-400 rounded-md shadow-sm text-slate-900 placeholder:text-slate-400']) }}>
