<button {{ $attributes->merge(['type' => 'submit', 'class' => 'btn-primary inline-flex min-h-11 items-center justify-center rounded-xl border px-5 py-2.5 text-sm font-semibold focus:outline-none focus-visible:ring-4 focus-visible:ring-orange-200 disabled:cursor-not-allowed disabled:opacity-50']) }}>
    {{ $slot }}
</button>
