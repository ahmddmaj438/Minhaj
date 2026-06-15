<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex min-h-11 items-center justify-center rounded-xl border border-red-200 bg-red-700 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-red-600 focus:outline-none focus-visible:ring-4 focus-visible:ring-red-100 disabled:cursor-not-allowed disabled:opacity-50']) }}>
    {{ $slot }}
</button>
