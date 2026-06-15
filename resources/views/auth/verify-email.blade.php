<x-guest-layout>
    <div class="mb-6">
        <p class="text-sm font-semibold uppercase tracking-wide text-orange-600">{{ __('Email verification') }}</p>
        <h1 class="mt-1 text-2xl font-bold text-slate-950">{{ __('Check your inbox') }}</h1>
        <p class="mt-2 text-sm leading-6 text-slate-600">
            {{ __('Verify your email address using the link we sent. If you did not receive it, request a new one below.') }}
        </p>
    </div>

    @if (session('status') == 'verification-link-sent')
        <div class="mb-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800">
            {{ __('A new verification link has been sent to the email address you provided during registration.') }}
        </div>
    @endif

    <div class="mt-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <form method="POST" action="{{ route('verification.send') }}">
            @csrf

            <div>
                <x-primary-button>
                    {{ __('Resend Verification Email') }}
                </x-primary-button>
            </div>
        </form>

        <form method="POST" action="{{ route('logout') }}">
            @csrf

            <button type="submit" class="rounded-lg text-sm font-semibold text-slate-600 hover:text-orange-700 focus:outline-none focus:ring-4 focus:ring-orange-100">
                {{ __('Log Out') }}
            </button>
        </form>
    </div>
</x-guest-layout>
