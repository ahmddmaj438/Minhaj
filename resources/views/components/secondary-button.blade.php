<button {{ $attributes->merge(['type' => 'button', 'class' => 'btn-secondary inline-flex min-h-11 items-center justify-center rounded-xl border px-5 py-2.5 text-sm font-semibold shadow-sm hover:bg-white focus:outline-none focus-visible:ring-4 focus-visible:ring-orange-100 disabled:cursor-not-allowed disabled:opacity-50']) }}>
    {{ $slot }}
</button>
