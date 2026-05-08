<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-slate-900 leading-tight">Super User Management</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <div class="bg-orange-100 text-orange-800 p-3 rounded">{{ session('status') }}</div>
            @endif

            @if ($errors->any())
                <div class="bg-red-100 text-red-800 p-3 rounded">
                    {{ $errors->first() }}
                </div>
            @endif

            <div class="bg-white/95 shadow-sm rounded-xl border border-orange-100 p-6">
                <p class="text-sm text-slate-700">
                    Root super user (hardcoded): <span class="font-semibold">{{ $rootEmail }}</span>
                </p>
                <p class="text-sm text-slate-500 mt-1">
                    Only this email can grant or revoke super-user access.
                </p>
            </div>

            <div class="bg-white/95 shadow-sm rounded-xl border border-orange-100 p-6">
                <h3 class="text-lg font-semibold mb-3">Grant Super User</h3>
                <form method="POST" action="{{ route('admin.super-users.grant') }}" class="flex gap-3 items-end">
                    @csrf
                    <div class="flex-1">
                        <label class="block text-sm text-slate-700 mb-1">User</label>
                        <select name="user_id" class="block w-full border-slate-300 rounded-md shadow-sm focus:border-orange-500 focus:ring-orange-400" required>
                            <option value="">Select user</option>
                            @foreach ($users as $user)
                                <option value="{{ $user->id }}">{{ $user->name }} ({{ $user->email }})</option>
                            @endforeach
                        </select>
                    </div>
                    <x-primary-button>Grant</x-primary-button>
                </form>
            </div>

            <div class="bg-white/95 shadow-sm rounded-xl border border-orange-100 p-6">
                <h3 class="text-lg font-semibold mb-3">Current Users</h3>
                <div class="space-y-3">
                    @foreach ($users as $user)
                        <div class="border border-orange-100 rounded p-3 flex items-center justify-between">
                            <div>
                                <div class="font-medium">{{ $user->name }}</div>
                                <div class="text-sm text-slate-600">{{ $user->email }}</div>
                            </div>
                            <div class="flex items-center gap-2">
                                @if ($user->isRootSuperAdmin())
                                    <span class="px-2 py-1 bg-orange-100 text-orange-800 rounded text-xs">ROOT SUPER ADMIN</span>
                                @elseif ($user->isSuperAdmin())
                                    <span class="px-2 py-1 bg-slate-100 text-slate-800 rounded text-xs">SUPER ADMIN</span>
                                    <form method="POST" action="{{ route('admin.super-users.revoke', $user) }}">
                                        @csrf
                                        @method('DELETE')
                                        <button class="px-3 py-1 rounded bg-red-600 text-white text-sm">Revoke</button>
                                    </form>
                                @else
                                    <span class="px-2 py-1 bg-gray-100 text-gray-700 rounded text-xs">USER</span>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
