<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-slate-900 leading-tight">{{ __('User Accounts') }}</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8 space-y-4">
            @if (session('status'))
                <div role="status" aria-live="polite" class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800">{{ session('status') }}</div>
            @endif
            @if ($errors->any())
                <div role="alert" class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold text-red-800">
                    {{ $errors->first() }}
                </div>
            @endif

            <div class="bg-white/95 shadow-sm rounded-xl border border-orange-100 p-6">
                <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h3 class="text-lg font-semibold text-slate-950">{{ __('User Accounts') }}</h3>
                        <p class="mt-1 text-sm text-slate-600">{{ __('Review accounts, create users, and disable access without removing academic history.') }}</p>
                    </div>
                    @can('button.users.create.create_user')
                        <a href="{{ route('users.create') }}" class="inline-flex min-h-11 items-center justify-center rounded-xl bg-orange-600 px-4 py-2 text-sm font-semibold text-white hover:bg-orange-700">
                            {{ __('Add user account') }}
                        </a>
                    @endcan
                </div>

                <div class="mb-5 grid gap-3 sm:grid-cols-3">
                    <div class="rounded-lg border border-slate-200 bg-slate-50 px-4 py-3">
                        <p class="text-xs font-semibold uppercase text-slate-500">{{ __('Total accounts') }}</p>
                        <p class="mt-1 text-2xl font-bold text-slate-950">{{ $users->count() }}</p>
                    </div>
                    <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3">
                        <p class="text-xs font-semibold uppercase text-emerald-700">{{ __('Enabled') }}</p>
                        <p class="mt-1 text-2xl font-bold text-emerald-900">{{ $activeUserCount }}</p>
                    </div>
                    <div class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3">
                        <p class="text-xs font-semibold uppercase text-amber-700">{{ __('Disabled') }}</p>
                        <p class="mt-1 text-2xl font-bold text-amber-900">{{ $disabledUserCount }}</p>
                    </div>
                </div>

                <div class="table-comfort overflow-x-auto">
                    <table class="min-w-full text-sm text-left text-slate-700">
                        <thead class="text-xs uppercase bg-slate-100">
                            <tr>
                                <th class="px-4 py-2">{{ __('Name') }}</th>
                                <th class="px-4 py-2">{{ __('Email') }}</th>
                                <th class="px-4 py-2">{{ __('Status') }}</th>
                                <th class="px-4 py-2">{{ __('Access') }}</th>
                                <th class="px-4 py-2 text-right">{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($users as $user)
                                <tr class="border-b">
                                    <td class="px-4 py-2">{{ $user->name }}</td>
                                    <td class="px-4 py-2">{{ $user->email }}</td>
                                    <td class="px-4 py-2">
                                        <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold {{ $user->is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-800' }}">
                                            {{ $user->is_active ? __('Enabled') : __('Disabled') }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-2">
                                        {{ $user->hasAdministrativeAccess() ? __('Administrator') : ($user->studentProfile ? __('Student') : __('Staff') ) }}
                                    </td>
                                    <td class="px-4 py-2 text-right">
                                        @can('button.users.index.change_status')
                                            @can('db.users.update')
                                                <form method="POST" action="{{ route('users.status.update', $user) }}" class="inline-flex">
                                                    @csrf
                                                    @method('PATCH')
                                                    <input type="hidden" name="is_active" value="{{ $user->is_active ? 0 : 1 }}">
                                                    <button type="submit"
                                                        class="inline-flex min-h-10 items-center justify-center rounded-lg border px-3 py-2 text-xs font-semibold {{ $user->is_active ? 'border-amber-300 bg-white text-amber-800 hover:bg-amber-50' : 'border-emerald-300 bg-white text-emerald-800 hover:bg-emerald-50' }}"
                                                        @if ($user->is_active) onclick="return confirm('{{ __('Disable this account? The user will not be able to sign in.') }}')" @endif>
                                                        {{ $user->is_active ? __('Disable') : __('Enable') }}
                                                    </button>
                                                </form>
                                            @endcan
                                        @endcan
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-4 py-8">
                                        <div class="empty-state text-center">
                                            <strong class="block text-base">{{ __('No users yet') }}</strong>
                                            <span class="mt-1 block text-sm">{{ __('Created users will appear here.') }}</span>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
