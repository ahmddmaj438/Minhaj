<x-guest-layout>
    <div class="mb-6">
        <p class="text-sm font-semibold uppercase tracking-wide text-orange-600">{{ __('Secure area') }}</p>
        <h1 class="mt-1 text-2xl font-bold text-slate-950">{{ __('Confirm your password') }}</h1>
        <p class="mt-2 text-sm leading-6 text-slate-600">
            {{ __('Please confirm your password before continuing.') }}
        </p>
    </div>

    <form method="POST" action="{{ route('password.confirm') }}">
        @csrf

        <!-- Password -->
        <div>
            <x-input-label for="password" :value="__('Password')" />

            <x-text-input id="password" class="block mt-1 w-full"
                            type="password"
                            name="password"
                            required autocomplete="current-password" />

            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <div class="flex justify-end mt-6">
            <x-primary-button>
                {{ __('Confirm') }}
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>
