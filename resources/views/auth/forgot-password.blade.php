<x-guest-layout>
    <div class="mb-6">
        <p class="text-sm font-semibold uppercase tracking-wide text-orange-600">{{ __('Password reset') }}</p>
        <h1 class="mt-1 text-2xl font-bold text-slate-950">{{ __('Recover your account') }}</h1>
        <p class="mt-2 text-sm leading-6 text-slate-600">
            {{ __('Enter your email address and we will send a secure reset link.') }}
        </p>
    </div>

    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('password.email') }}">
        @csrf

        <!-- Email Address -->
        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autofocus />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div class="flex items-center justify-end mt-6">
            <x-primary-button>
                {{ __('Email Password Reset Link') }}
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>
